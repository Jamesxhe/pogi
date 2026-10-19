<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Your session expired. Sign in and try again.']);
    exit;
}

try {
    $reports = [];
    $queries = [
        "SELECT 'lost' AS type, lost_item_id AS id, category, description, color,
                lost_location AS location, date_lost AS item_date, status, image_path
         FROM lost_items
         WHERE image_path IS NOT NULL
         ORDER BY created_at DESC
         LIMIT 50",
        "SELECT 'found' AS type, found_item_id AS id, category, description, color,
                found_location AS location, date_found AS item_date, status, image_path
         FROM found_items
         WHERE image_path IS NOT NULL
         ORDER BY created_at DESC
         LIMIT 50",
    ];

    foreach ($queries as $sql) {
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $imagePath = (string)($row['image_path'] ?? '');
            if (!preg_match('#\Auploads/reports/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', $imagePath)) {
                continue;
            }

            $reports[] = [
                'id' => (int)$row['id'],
                'type' => (string)$row['type'],
                'category' => (string)($row['category'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'color' => (string)($row['color'] ?? ''),
                'location' => (string)($row['location'] ?? ''),
                'date' => (string)($row['item_date'] ?? ''),
                'status' => trim((string)($row['status'] ?? '')),
                'image' => $imagePath,
            ];
        }
    }

    echo json_encode(['reports' => $reports], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('PUPick chatbot image lookup error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not load report photos. Please try again later.']);
}
