<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit("Access denied. Admin account required.");
    }
}

function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

function create_notification(PDO $conn, int $userId, string $title, string $message, string $type = 'system'): void {
    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
         VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP)"
    );
    $stmt->execute([$userId, $title, $message, $type]);
}

function item_category_suggestions(): array {
    return [
        'Electronics',
        'Wallet / ID',
        'Bag',
        'Clothing',
        'Books / School supplies',
        'Keys',
        'Accessories',
        'Documents',
        'Other',
    ];
}

function save_uploaded_image(array $upload, string $kind): ?string {
    if (!in_array($kind, ['reports', 'claims'], true)) {
        throw new InvalidArgumentException('Invalid image upload destination.');
    }

    $errorCode = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The photo could not be uploaded. Please try again.');
    }

    $temporaryPath = $upload['tmp_name'] ?? '';
    $fileSize = (int)($upload['size'] ?? 0);
    if (!is_uploaded_file($temporaryPath) || $fileSize < 1 || $fileSize > 5 * 1024 * 1024) {
        throw new RuntimeException('Choose a photo smaller than 5 MB.');
    }
    if (!class_exists('finfo')) {
        throw new RuntimeException('Photo uploads are unavailable because file inspection is not enabled.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($temporaryPath);
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!is_string($mimeType) || !isset($allowedTypes[$mimeType])) {
        throw new RuntimeException('Use a JPG, PNG, or WEBP photo.');
    }
    $imageInfo = @getimagesize($temporaryPath);
    if (!$imageInfo || ($imageInfo['mime'] ?? '') !== $mimeType) {
        throw new RuntimeException('The uploaded file is not a valid image.');
    }
    if (
        ($imageInfo[0] ?? 0) < 1
        || ($imageInfo[1] ?? 0) < 1
        || $imageInfo[0] > 8000
        || $imageInfo[1] > 8000
        || $imageInfo[0] * $imageInfo[1] > 40000000
    ) {
        throw new RuntimeException('Choose a photo with dimensions no larger than 8,000 × 8,000 pixels.');
    }

    if ($kind === 'reports') {
        $directory = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'reports';
        $storedPath = 'uploads/reports/';
    } else {
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'pupick-private'
            . DIRECTORY_SEPARATOR . 'claims';
        $storedPath = 'claims/';
    }

    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('The server could not prepare photo storage. Please try again later.');
    }

    $filename = bin2hex(random_bytes(24)) . '.' . $allowedTypes[$mimeType];
    if (!move_uploaded_file($temporaryPath, $directory . DIRECTORY_SEPARATOR . $filename)) {
        throw new RuntimeException('The server could not save your photo. Please try again.');
    }

    return $storedPath . $filename;
}

function delete_report_image(?string $storedPath): void {
    if (!$storedPath || !preg_match('#\Auploads/reports/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', $storedPath)) {
        return;
    }

    $filePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
    if (is_file($filePath) && !unlink($filePath)) {
        error_log('PUPick could not delete report image: ' . $storedPath);
    }
}

function delete_claim_image(?string $storedPath): void {
    if (!$storedPath || !preg_match('#\Aclaims/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', $storedPath)) {
        return;
    }

    $filePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'pupick-private'
        . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPath);
    if (is_file($filePath) && !unlink($filePath)) {
        error_log('PUPick could not delete claim image.');
    }
}


/** Estimate report similarity from descriptive text. This is a rule-based prototype, not a trained AI model. */
function item_similarity_score(array $a, array $b): int {
    $tokens = static function ($text): array {
        $text = mb_strtolower((string)$text, 'UTF-8');
        preg_match_all('/[\p{L}\p{N}]{2,}/u', $text, $matches);
        $stop = ['the','and','with','for','from','that','this','was','were','has','have','had','item','found','lost','near','very','one','its'];
        return array_values(array_unique(array_diff($matches[0], $stop)));
    };
    $textSimilarity = static function ($left, $right) use ($tokens): float {
        $x = $tokens($left); $y = $tokens($right);
        if (!$x || !$y) return 0.0;
        $intersection = count(array_intersect($x, $y));
        return (2 * $intersection) / (count($x) + count($y));
    };
    $category = (mb_strtolower(trim((string)($a['category'] ?? '')), 'UTF-8') !== '' && mb_strtolower(trim((string)($a['category'] ?? '')), 'UTF-8') === mb_strtolower(trim((string)($b['category'] ?? '')), 'UTF-8')) ? 1.0 : 0.0;
    $description = $textSimilarity($a['description'] ?? '', $b['description'] ?? '');
    $colorA = mb_strtolower(trim((string)($a['color'] ?? '')), 'UTF-8');
    $colorB = mb_strtolower(trim((string)($b['color'] ?? '')), 'UTF-8');
    $color = ($colorA !== '' && $colorB !== '' && $colorA === $colorB) ? 1.0 : 0.0;
    return (int)round(100 * (0.20 * $category + 0.75 * $description + 0.05 * $color));
}
