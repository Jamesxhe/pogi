<?php
require_once __DIR__ . '/helpers.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function chatbotAiJson(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$config = [];
$configPath = __DIR__ . '/config.local.php';
if (is_file($configPath)) {
    $loadedConfig = require $configPath;
    if (is_array($loadedConfig)) {
        $config = $loadedConfig;
    }
}

$openrouterApiKey = getenv('OPENROUTER_API_KEY');
if (!is_string($openrouterApiKey) || trim($openrouterApiKey) === '') {
    $openrouterApiKey = is_string($config['openrouter_api_key'] ?? null)
        ? trim($config['openrouter_api_key'])
        : '';
}

$openaiApiKey = getenv('OPENAI_API_KEY');
if (!is_string($openaiApiKey) || trim($openaiApiKey) === '') {
    $openaiApiKey = is_string($config['openai_api_key'] ?? null)
        ? trim($config['openai_api_key'])
        : '';
}

$explicitProvider = strtolower(trim((string)($config['ai_provider'] ?? '')));
if ($explicitProvider === 'openrouter') {
    $provider = 'openrouter';
    $apiKey = $openrouterApiKey !== '' ? $openrouterApiKey : $openaiApiKey;
} elseif ($explicitProvider === 'openai') {
    $provider = 'openai';
    $apiKey = $openaiApiKey !== '' ? $openaiApiKey : $openrouterApiKey;
} elseif ($openrouterApiKey !== '') {
    $provider = 'openrouter';
    $apiKey = $openrouterApiKey;
} elseif ($openaiApiKey !== '') {
    if (str_starts_with($openaiApiKey, 'sk-or-')) {
        $provider = 'openrouter';
    } else {
        $provider = 'openai';
    }
    $apiKey = $openaiApiKey;
} else {
    $provider = 'openrouter';
    $apiKey = '';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET' && ($_GET['status'] ?? '') === '1') {
    chatbotAiJson(200, ['configured' => $apiKey !== '', 'provider' => $provider]);
}
if ($method !== 'POST') {
    header('Allow: GET, POST');
    chatbotAiJson(405, ['error' => 'Method not allowed.']);
}
if ($apiKey === '' || !function_exists('curl_init')) {
    chatbotAiJson(503, ['available' => false]);
}

$body = json_decode(file_get_contents('php://input') ?: '', true);
$message = is_array($body) && is_string($body['message'] ?? null)
    ? trim($body['message'])
    : '';
if ($message === '' || mb_strlen($message, 'UTF-8') > 300) {
    chatbotAiJson(400, ['error' => 'Enter a question up to 300 characters.']);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 14000) {
    chatbotAiJson(413, ['error' => 'The chat request is too large.']);
}

// Limit each signed-in session to 12 AI requests per minute.
$now = time();
$recentRequests = array_values(array_filter(
    $_SESSION['chatbot_ai_requests'] ?? [],
    static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 60
));
if (count($recentRequests) >= 12) {
    $_SESSION['chatbot_ai_requests'] = $recentRequests;
    chatbotAiJson(429, ['available' => false]);
}
$recentRequests[] = $now;
$_SESSION['chatbot_ai_requests'] = $recentRequests;

$history = [];
if (is_array($body['history'] ?? null)) {
    foreach (array_slice($body['history'], -8) as $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $role = $entry['role'] ?? '';
        $content = $entry['content'] ?? '';
        if (
            in_array($role, ['user', 'assistant'], true)
            && is_string($content)
            && trim($content) !== ''
            && mb_strlen($content, 'UTF-8') <= 1200
        ) {
            $history[] = ['role' => $role, 'content' => trim($content)];
        }
    }
}
$history[] = ['role' => 'user', 'content' => $message];

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$instructions = 'You are PUPick AI, a helpful general-purpose assistant. Answer the user’s question even when it is unrelated to the PUPick website, and answer in the same language as the user. '
    . 'For PUPick-specific questions, explain how to report lost or found items, browse reports, submit claims, check report status, use notifications, view possible matches, access accounts, and compare photos. '
    . 'Do not claim that you searched reports, checked someone’s account, verified ownership, or changed data; you have no access to the database or private records. '
    . 'Never ask for or repeat passwords, student IDs, or sensitive personal details. Remind users to verify ownership privately and arrange safe handoffs when relevant. '
    . 'You do not have live web access; say so if a question depends on current events or real-time information. If you are unsure about a PUPick-specific detail, say so and direct the user to the administrator. Keep replies brief and practical. '
    . ($isAdmin
        ? 'The current user is an administrator; you may explain claim review and report export tools.'
        : 'The current user is a student; do not provide administrator-only instructions.');
session_write_close();

$messages = [
    ['role' => 'system', 'content' => $instructions]
];
foreach ($history as $entry) {
    $messages[] = [
        'role' => $entry['role'],
        'content' => $entry['content'],
    ];
}

if ($provider === 'openrouter') {
    $configuredModel = is_string($config['openrouter_model'] ?? null) && trim($config['openrouter_model']) !== ''
        ? trim($config['openrouter_model'])
        : (is_string($config['openai_model'] ?? null) && trim($config['openai_model']) !== ''
            ? trim($config['openai_model'])
            : 'google/gemma-4-26b-a4b-it:free');

    // Free model fallbacks: if the primary is busy, OpenRouter will automatically try the next free model:
    $fallbackModels = [
        'google/gemma-4-26b-a4b-it:free',
        'google/gemma-4-31b-it:free',
        'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
        'nvidia/nemotron-3-ultra-550b-a55b:free',
    ];
    if (!in_array($configuredModel, $fallbackModels, true)) {
        array_unshift($fallbackModels, $configuredModel);
    }

    $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';
    $siteUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
        . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $httpHeaders = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
        'HTTP-Referer: ' . $siteUrl,
        'X-Title: PUPick AI Chatbot',
    ];
    $request = [
        'model' => $configuredModel,
        'models' => $fallbackModels,
        'messages' => $messages,
        'max_tokens' => 800,
    ];
} else {
    $model = is_string($config['openai_model'] ?? null) && trim($config['openai_model']) !== ''
        ? trim($config['openai_model'])
        : 'gpt-4o-mini';
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    $httpHeaders = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];
    $request = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => 500,
    ];
}

$curl = curl_init($apiUrl);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER => $httpHeaders,
    CURLOPT_POSTFIELDS => json_encode($request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
]);
$responseBody = curl_exec($curl);
$httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curlError = curl_error($curl);
curl_close($curl);

if (!is_string($responseBody) || $httpStatus < 200 || $httpStatus >= 300) {
    $providerError = is_string($responseBody) ? json_decode($responseBody, true) : null;
    $providerCode = '';
    $errorMessage = '';
    if (is_array($providerError)) {
        if (isset($providerError['error'])) {
            if (is_array($providerError['error'])) {
                $providerCode = (string)($providerError['error']['code'] ?? '');
                $errorMessage = (string)($providerError['error']['message'] ?? '');
            } elseif (is_string($providerError['error'])) {
                $errorMessage = $providerError['error'];
            }
        }
    }
    $safeCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $providerCode);
    $reason = 'offline';
    if ($httpStatus === 401 || $providerCode === 'invalid_api_key' || stripos($errorMessage, 'api key') !== false) {
        $reason = 'key';
    } elseif ($httpStatus === 402 || in_array($providerCode, ['insufficient_quota', 'insufficient_credits', 'billing_hard_limit_reached', 'credit_balance_exhausted'], true) || stripos($errorMessage, 'credit') !== false || stripos($errorMessage, 'quota') !== false) {
        $reason = 'quota';
    } elseif ($httpStatus === 429 || $providerCode === 'rate_limit_exceeded') {
        $reason = 'busy';
    }
    error_log("PUPick chatbot AI request to {$provider} failed; HTTP {$httpStatus}"
        . ($safeCode !== '' ? "; code {$safeCode}" : '')
        . ($errorMessage !== '' ? "; message {$errorMessage}" : '')
        . ($curlError !== '' ? '; network error: ' . $curlError : ''));
    chatbotAiJson(503, ['available' => false, 'reason' => $reason]);
}

$response = json_decode($responseBody, true);
$answer = '';
if (is_array($response)) {
    if (!empty($response['choices'][0]['message']['content'])) {
        $answer = (string)$response['choices'][0]['message']['content'];
    } elseif (!empty($response['choices'][0]['message']['reasoning'])) {
        $answer = (string)$response['choices'][0]['message']['reasoning'];
    } elseif (!empty($response['output'])) {
        foreach (($response['output'] ?? []) as $outputItem) {
            if (!is_array($outputItem) || ($outputItem['type'] ?? '') !== 'message') {
                continue;
            }
            foreach (($outputItem['content'] ?? []) as $contentItem) {
                if (is_array($contentItem) && ($contentItem['type'] ?? '') === 'output_text') {
                    $answer .= (string)($contentItem['text'] ?? '');
                }
            }
        }
    }
}
$answer = trim($answer);
if ($answer === '') {
    error_log('PUPick chatbot AI returned no text output.');
    chatbotAiJson(503, ['available' => false]);
}

chatbotAiJson(200, ['available' => true, 'answer' => mb_substr($answer, 0, 4000, 'UTF-8')]);

