<?php
require_once __DIR__ . '/helpers.php';
require_login();
$isAdmin = is_admin();
$chatbotConfig = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$chatbotApiKey = getenv('OPENROUTER_API_KEY');
if (!is_string($chatbotApiKey) || trim($chatbotApiKey) === '') {
    $chatbotApiKey = getenv('OPENAI_API_KEY');
}
if (!is_string($chatbotApiKey) || trim($chatbotApiKey) === '') {
    $chatbotApiKey = is_array($chatbotConfig) && is_string($chatbotConfig['openrouter_api_key'] ?? null)
        ? trim($chatbotConfig['openrouter_api_key'])
        : (is_array($chatbotConfig) && is_string($chatbotConfig['openai_api_key'] ?? null)
            ? trim($chatbotConfig['openai_api_key'])
            : '');
}
$chatbotAiConfigured = $chatbotApiKey !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUPick Help Chat | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js?v=16" defer></script>
    <style>
        .chat-intro{max-width:760px;line-height:1.6;margin-bottom:18px}
        .chat-card{max-width:850px;background:#fff;border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 12px 36px #25131b0b}
        .chat-heading{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 19px;border-bottom:1px solid var(--line);background:#fff}
        .chat-assistant{display:flex;align-items:center;gap:11px;min-width:0}
        .chat-avatar{display:grid;place-items:center;width:40px;height:40px;flex:none;border-radius:13px;background:#fff4cc;color:#800020;font-size:20px}
        .chat-assistant strong{display:block;font-size:13px}
        .chat-status{display:flex;align-items:center;gap:6px;margin-top:4px;color:var(--muted);font-size:11px}
        .chat-status::before{content:"";width:7px;height:7px;border-radius:50%;background:#24a15a}
        .chat-status.offline::before{background:#d38b21}
        .chat-clear{padding:8px 10px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--muted);font:inherit;font-size:11px;font-weight:700;cursor:pointer}
        .chat-clear:hover{border-color:#d9bf69;background:#fff9e7;color:#5a0012}
        .chat-log{height:min(55vh,480px);min-height:300px;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:12px;scroll-behavior:smooth}
        .chat-message{max-width:min(82%,620px);padding:12px 15px;border-radius:14px;line-height:1.6;white-space:pre-wrap;overflow-wrap:anywhere}
        .chat-message.bot{align-self:flex-start;background:#f5f2f3;color:var(--ink);border-bottom-left-radius:5px}
        .chat-message.user{align-self:flex-end;background:#800020;color:#fff;border-bottom-right-radius:5px}
        .chat-reply-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}
        .chat-reply-actions a,.chat-reply-actions button{display:inline-flex;align-items:center;min-height:34px;padding:7px 10px;border:1px solid #d9bf69;border-radius:8px;background:#fff;color:#5a0012;font:inherit;font-size:11px;font-weight:700;text-decoration:none;cursor:pointer}
        .chat-reply-actions a:hover,.chat-reply-actions button:hover{background:#fff9e7}
        .chat-reply-actions a:focus-visible,.chat-reply-actions button:focus-visible{outline:3px solid #ffc72c;outline-offset:2px}
        .chat-suggest-title{padding:0 20px 9px;color:var(--muted);font-size:11px;font-weight:700}
        .chat-suggestions{display:flex;flex-wrap:wrap;gap:8px;padding:0 20px 16px}
        .chat-suggestions button{border:1px solid var(--line);border-radius:20px;padding:8px 12px;background:#fff;color:var(--ink);font:inherit;font-size:11px;font-weight:600;cursor:pointer;transition:background .15s,border-color .15s}
        .chat-suggestions button:hover{background:#fff4cc;border-color:#e6cf7d}
        .chat-suggestions button:focus-visible,.chat-clear:focus-visible{outline:3px solid #ffc72c;outline-offset:2px}
        .chat-input{display:flex;align-items:center;gap:10px;padding:14px 18px;border-top:1px solid var(--line);background:#fff}
        .chat-input input[type="text"]{flex:1;min-width:0}
        .chat-input .btn{min-width:88px}
        .chat-image-picker{display:inline-flex;min-height:43px;align-items:center;justify-content:center;padding:0 13px;border:1px solid var(--line);border-radius:10px;background:var(--white);color:var(--ink);font-size:12px;font-weight:700;cursor:pointer}
        .chat-image-picker:hover{border-color:#d9bf69;background:#fff9e7}
        .chat-image-file{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
        .chat-image-file:focus-visible+.chat-image-picker{outline:3px solid #ffc72c;outline-offset:2px}
        .chat-image-preview{display:block;width:min(100%,260px);max-height:180px;margin-top:9px;border-radius:10px;object-fit:cover}
        .chat-image-status{max-width:850px;margin:10px 2px 0;color:var(--muted);font-size:12px;line-height:1.5}
        .chat-image-results{display:grid;gap:9px;margin-top:12px}
        .chat-image-result{display:grid;grid-template-columns:74px minmax(0,1fr);gap:11px;align-items:center;padding:9px;border:1px solid var(--line);border-radius:11px;background:var(--white);color:var(--ink);text-decoration:none}
        .chat-image-result:hover{text-decoration:none;border-color:#d9bf69}
        .chat-image-result img{width:74px;height:64px;border-radius:8px;object-fit:cover}
        .chat-image-result strong,.chat-image-result small{display:block}
        .chat-image-result small{margin-top:4px;color:var(--muted);font-size:11px}
        .chat-image-disclaimer{margin-top:11px;color:var(--muted);font-size:11px}
        .chat-footnote{max-width:850px;margin:11px 2px 0;color:var(--muted);font-size:11px;line-height:1.5}
        @media(max-width:520px){.chat-log{height:52vh;min-height:260px;padding:15px}.chat-heading{padding:13px}.chat-suggestions{padding-right:14px;padding-left:14px}.chat-suggest-title{padding-left:14px}.chat-message{max-width:92%}.chat-input{flex-wrap:wrap;padding:12px}.chat-input input[type="text"]{flex-basis:100%;order:0}.chat-image-picker{order:1;flex:1}.chat-input .btn{order:2;flex:1;min-width:74px}}
    </style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand sidebar-brand" href="<?= $isAdmin ? 'admin.php' : 'dashboard.php' ?>"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a>
        <p class="side-label">HELP</p>
        <a class="nav-link" href="<?= $isAdmin ? 'admin.php' : 'dashboard.php' ?>">⌂ &nbsp; Dashboard</a>
        <?php if (!$isAdmin): ?>
            <a class="nav-link" href="report_lost.php">＋ &nbsp; Report lost item</a>
            <a class="nav-link" href="report_found.php">＋ &nbsp; Report found item</a>
            <a class="nav-link" href="items.php">▤ &nbsp; Browse items</a>
            <a class="nav-link" href="my_reports.php">▣ &nbsp; My reports</a>
            <a class="nav-link" href="notifications.php">♧ &nbsp; Notifications</a>
        <?php else: ?>
            <a class="nav-link" href="admin_items.php">▤ &nbsp; Manage items</a>
            <a class="nav-link" href="admin.php#claims">✓ &nbsp; Manage claims</a>
            <a class="nav-link" href="admin.php">▦ &nbsp; Overview</a>
        <?php endif; ?>
        <a class="nav-link active" href="chatbot.php">💬 &nbsp; Help chat</a>
        <div class="sidebar-bottom"><div class="profile-mini"><strong><?= e($_SESSION['full_name'] ?? 'User') ?></strong><span><?= $isAdmin ? 'Administrator' : 'Student' ?></span></div><a class="logout-link" href="logout.php" title="Log out">↗</a></div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div><p class="eyebrow">PUPICK HELP</p><h1>Chat with PUPick</h1></div>
        </header>
        <p class="muted chat-intro">Ask about PUPick or any other topic in English, Filipino/Taglish, Spanish, French, German, Portuguese, Indonesian, Chinese, or Japanese. When AI is offline, the suggested PUPick questions still work. You can also attach a photo to compare it with photos on reported items.</p>
        <section class="chat-card" id="pupickChat" data-admin="<?= $isAdmin ? 'true' : 'false' ?>" data-ai-configured="<?= $chatbotAiConfigured ? 'true' : 'false' ?>" aria-label="PUPick help chatbot">
            <div class="chat-heading">
                <div class="chat-assistant">
                    <span class="chat-avatar" aria-hidden="true">✦</span>
                    <div><strong>PUPick Help Assistant</strong><span class="chat-status<?= $chatbotAiConfigured ? '' : ' offline' ?>" id="chatStatus"><?= $chatbotAiConfigured ? 'AI assistant · Configured' : 'FAQ assistant · Offline' ?></span></div>
                </div>
                <button type="button" class="chat-clear" id="clearChat">Clear chat</button>
            </div>
            <div class="chat-log" id="chatLog" role="log" aria-live="polite" aria-relevant="additions">
                <div class="chat-message bot">Hi! I can guide you through PUPick. Pumili ng tanong sa ibaba o i-type ang kailangan mong malaman.</div>
            </div>
            <p class="chat-suggest-title">Try asking</p>
            <div class="chat-suggestions" aria-label="Suggested questions">
                <button type="button">Paano mag-report ng nawawalang item?</button>
                <button type="button">Find a reported item</button>
                <button type="button">How do I search for an item?</button>
                <button type="button">Was my wallet reported?</button>
                <button type="button">What if there are no results?</button>
                <button type="button">Paano mag-claim ng found item?</button>
                <button type="button">Saan ko makikita ang claim status ko?</button>
                <?php if ($isAdmin): ?><button type="button">How do I review a claim?</button><button type="button">How do I export a report?</button><?php endif; ?>
            </div>
            <form class="chat-input" id="chatForm">
                <label class="sr-only" for="chatQuestion">Your question</label>
                <input id="chatQuestion" type="text" maxlength="300" placeholder="Ask about PUPick or anything else..." autocomplete="off" aria-describedby="chatFootnote" required>
                <input class="chat-image-file" id="chatImage" type="file" accept="image/jpeg,image/png,image/webp" aria-label="Attach a photo to check reported items">
                <label class="chat-image-picker" for="chatImage">Attach a photo</label>
                <button class="btn primary" type="submit">Send</button>
            </form>
        </section>
        <p class="chat-image-status" id="chatImageStatus" role="status" aria-live="polite">Checks up to the latest 50 lost and 50 found reports that include photos.</p>
        <p class="chat-footnote" id="chatFootnote">When AI is online, your question and recent chat messages are sent to OpenAI to generate a reply. Offline FAQ answers stay on this site. Do not include passwords or sensitive information.</p>
        <p class="chat-footnote">Photo matching compares visual appearance with existing report photos only. It is approximate, may miss the same item photographed differently, and does not check reports without photos. The selected photo is processed in your browser and is not saved or sent to an external service.</p>
    </main>
</div>
<script src="chatbot.js?v=14" defer></script>
</body>
</html>
