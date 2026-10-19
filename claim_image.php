<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_login();

$claimId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$claimId || $claimId < 1) {
    http_response_code(400);
    exit('Invalid claim.');
}

$stmt = $conn->prepare(
    'SELECT c.claim_image_path, c.claimant_user_id, fi.user_id AS finder_user_id
     FROM claims c
     LEFT JOIN found_items fi ON fi.found_item_id = c.found_item_id
     WHERE c.claim_id = ?'
);
$stmt->execute([$claimId]);
$claim = $stmt->fetch();
if (!$claim || !$claim['claim_image_path']) {
    http_response_code(404);
    exit('Claim photo not found.');
}

$roleStmt = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
$roleStmt->execute([(int)$_SESSION['user_id']]);
$role = $roleStmt->fetchColumn();
$userId = (int)$_SESSION['user_id'];
if (
    $role !== 'admin'
    && (int)$claim['claimant_user_id'] !== $userId
    && (int)$claim['finder_user_id'] !== $userId
) {
    http_response_code(403);
    exit('Access denied.');
}

$storedPath = (string)$claim['claim_image_path'];
if (!preg_match('#\Aclaims/[a-f0-9]{48}\.(jpg|png|webp)\z#', $storedPath)) {
    error_log('PUPick claim image path is invalid for claim #' . $claimId);
    http_response_code(404);
    exit('Claim photo not found.');
}

$privateDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'pupick-private';
$privatePath = realpath($privateDirectory);
$imagePath = realpath($privateDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $storedPath));
if (
    $privatePath === false
    || $imagePath === false
    || !str_starts_with($imagePath, $privatePath . DIRECTORY_SEPARATOR)
    || !is_file($imagePath)
) {
    http_response_code(404);
    exit('Claim photo not found.');
}

$mimeTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
$fileInfo = new finfo(FILEINFO_MIME_TYPE);
if (!isset($mimeTypes[$extension]) || $fileInfo->file($imagePath) !== $mimeTypes[$extension]) {
    error_log('PUPick claim image failed MIME validation for claim #' . $claimId);
    http_response_code(404);
    exit('Claim photo not found.');
}

$handle = fopen($imagePath, 'rb');
if ($handle === false) {
    error_log('PUPick could not open claim photo for claim #' . $claimId);
    http_response_code(500);
    exit('Claim photo is temporarily unavailable.');
}

session_write_close();
header('Content-Type: ' . $mimeTypes[$extension]);
header('Content-Length: ' . filesize($imagePath));
header('Content-Disposition: inline; filename="claim-evidence.' . $extension . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
fpassthru($handle);
fclose($handle);
