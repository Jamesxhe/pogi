<?php
require_once 'db.php';
require_once 'helpers.php';
require_login();

$kind = $_GET['kind'] ?? 'all';
if (!is_string($kind) || !in_array($kind, ['all', 'lost', 'found'], true)) {
    $kind = 'all';
}

$search = $_GET['q'] ?? '';
$search = is_string($search) ? trim($search) : '';

$status = $_GET['status'] ?? 'open';
if (!is_string($status) || !in_array($status, ['open', 'matched', 'returned', 'all'], true)) {
    $status = 'open';
}

$dateFrom = $_GET['date_from'] ?? '';
$dateFrom = is_string($dateFrom) ? $dateFrom : '';
$dateTo = $_GET['date_to'] ?? '';
$dateTo = is_string($dateTo) ? $dateTo : '';
$dateError = '';

$isValidDate = static function (string $date): bool {
    if ($date === '') {
        return true;
    }

    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $parsedDate !== false && $parsedDate->format('Y-m-d') === $date;
};

if (!$isValidDate($dateFrom) || !$isValidDate($dateTo)) {
    $dateError = 'Enter a valid date to filter reports.';
} elseif ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $dateError = 'The start date must be on or before the end date.';
}

$lostItems = [];
$foundItems = [];
$dateFilterEnabled = $dateError === '';

if ($kind !== 'found') {
    $sql = "SELECT li.lost_item_id AS item_id, li.category, li.description, li.color, li.image_path,
                   li.lost_location AS location, li.date_lost AS item_date, li.status, li.created_at,
                   li.user_id AS reporter_id, u.full_name, 'Lost' AS kind
            FROM lost_items li
            LEFT JOIN users u ON li.user_id = u.user_id";
    $conditions = [];
    $params = [];

    if ($status !== 'all') {
        $conditions[] = 'li.status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $conditions[] = '(li.category LIKE ? OR li.description LIKE ? OR li.color LIKE ? OR li.lost_location LIKE ?)';
        $term = "%$search%";
        array_push($params, $term, $term, $term, $term);
    }
    if ($dateFilterEnabled && $dateFrom !== '') {
        $conditions[] = 'li.date_lost >= ?';
        $params[] = $dateFrom;
    }
    if ($dateFilterEnabled && $dateTo !== '') {
        $conditions[] = 'li.date_lost <= ?';
        $params[] = $dateTo;
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY li.date_lost DESC, li.created_at DESC LIMIT 100';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $lostItems = $stmt->fetchAll();
}

if ($kind !== 'lost') {
    $sql = "SELECT fi.found_item_id AS item_id, fi.category, fi.description, fi.color, fi.image_path,
                   fi.found_location AS location, fi.date_found AS item_date, fi.status, fi.created_at,
                   fi.user_id AS reporter_id, u.full_name, 'Found' AS kind
            FROM found_items fi
            LEFT JOIN users u ON fi.user_id = u.user_id";
    $conditions = [];
    $params = [];

    if ($status !== 'all') {
        $conditions[] = 'fi.status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $conditions[] = '(fi.category LIKE ? OR fi.description LIKE ? OR fi.color LIKE ? OR fi.found_location LIKE ?)';
        $term = "%$search%";
        array_push($params, $term, $term, $term, $term);
    }
    if ($dateFilterEnabled && $dateFrom !== '') {
        $conditions[] = 'fi.date_found >= ?';
        $params[] = $dateFrom;
    }
    if ($dateFilterEnabled && $dateTo !== '') {
        $conditions[] = 'fi.date_found <= ?';
        $params[] = $dateTo;
    }

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY fi.date_found DESC, fi.created_at DESC LIMIT 100';

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $foundItems = $stmt->fetchAll();
}

$sections = [];
if ($kind !== 'found') {
    $sections['Lost items'] = $lostItems;
}
if ($kind !== 'lost') {
    $sections['Found items'] = $foundItems;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse items | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand sidebar-brand" href="dashboard.php">
            <img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt="">
            <span>PUPick <b>AI</b></span>
        </a>
        <p class="side-label">MENU</p>
        <a class="nav-link" href="dashboard.php">⌂ &nbsp; Dashboard</a>
        <a class="nav-link" href="report_lost.php">＋ &nbsp; Report lost item</a>
        <a class="nav-link" href="report_found.php">＋ &nbsp; Report found item</a>
        <a class="nav-link active" href="items.php">▤ &nbsp; Browse items</a>
        <a class="nav-link" href="my_reports.php">▣ &nbsp; My reports</a>
        <a class="nav-link" href="notifications.php">♧ &nbsp; Notifications</a>
        <a class="nav-link" href="chatbot.php">💬 &nbsp; Help chat</a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">COMMUNITY ITEMS</p>
                <h1>Browse items</h1>
                <p class="browse-intro">Search by keyword, report type, status, or the date the item was lost or found.</p>
            </div>
        </header>

        <form class="search-bar browse-filters" method="get">
            <div class="filter-field filter-keyword">
                <label for="item-search">Search</label>
                <input
                    id="item-search"
                    type="search"
                    name="q"
                    value="<?= e($search) ?>"
                    placeholder="Category, details, color, or location"
                >
            </div>
            <div class="filter-field">
                <label for="item-kind">Report type</label>
                <select id="item-kind" name="kind">
                    <option value="all" <?= $kind === 'all' ? 'selected' : '' ?>>All items</option>
                    <option value="lost" <?= $kind === 'lost' ? 'selected' : '' ?>>Lost items</option>
                    <option value="found" <?= $kind === 'found' ? 'selected' : '' ?>>Found items</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="item-status">Status</label>
                <select id="item-status" name="status">
                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Available only</option>
                    <option value="matched" <?= $status === 'matched' ? 'selected' : '' ?>>Matched</option>
                    <option value="returned" <?= $status === 'returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All statuses</option>
                </select>
            </div>
            <div class="filter-field">
                <label for="date-from">Date from</label>
                <input id="date-from" type="date" name="date_from" value="<?= e($dateFrom) ?>">
            </div>
            <div class="filter-field">
                <label for="date-to">Date to</label>
                <input id="date-to" type="date" name="date_to" value="<?= e($dateTo) ?>">
            </div>
            <div class="filter-actions">
                <button class="btn primary" type="submit">Apply filters</button>
                <a class="btn secondary" href="items.php">Reset filters</a>
            </div>
        </form>

        <?php if ($dateError !== ''): ?>
            <p class="alert error" role="alert"><?= e($dateError) ?></p>
        <?php endif; ?>

        <?php foreach ($sections as $sectionTitle => $sectionItems): ?>
            <section class="item-section" aria-labelledby="<?= $sectionTitle === 'Lost items' ? 'lost-items-heading' : 'found-items-heading' ?>">
                <div class="section-heading browse-section-heading">
                    <div>
                        <h2 id="<?= $sectionTitle === 'Lost items' ? 'lost-items-heading' : 'found-items-heading' ?>"><?= e($sectionTitle) ?></h2>
                        <p><strong><?= count($sectionItems) ?></strong> <span>reports</span></p>
                    </div>
                </div>
                <div class="item-grid">
                    <?php if (!$sectionItems): ?>
                        <div class="empty-card">
                            <?= $sectionTitle === 'Lost items'
                                ? 'No lost items match these filters.'
                                : 'No found items match these filters.' ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($sectionItems as $item): ?>
                        <article class="item-card">
                            <?php if (preg_match('#\Auploads/reports/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', (string)($item['image_path'] ?? ''))): ?>
                                <img
                                    class="item-photo"
                                    src="<?= e($item['image_path']) ?>"
                                    alt="<?= e($item['category']) ?> photo"
                                    loading="lazy"
                                >
                            <?php endif; ?>
                            <div class="item-card-top">
                                <span class="kind-tag <?= $item['kind'] === 'Lost' ? 'lost-tag' : 'found-tag' ?>"><?= e($item['kind']) ?></span>
                                <span class="pill"><?= e(trim($item['status'])) ?></span>
                            </div>
                            <h3><?= e($item['category']) ?></h3>
                            <?php if (!empty($item['color'])): ?>
                                <p class="item-category"><?= e($item['color']) ?></p>
                            <?php endif; ?>
                            <p><?= e($item['description']) ?></p>
                            <div class="item-meta">
                                <span>⌖ <?= e($item['location']) ?></span>
                                <span>◷ <?= e($item['item_date']) ?></span>
                            </div>
                            <small class="muted">Reported by <?= e($item['full_name'] ?? 'User') ?></small>
                            <?php if (
                                $item['kind'] === 'Found'
                                && $item['status'] === 'open'
                                && (int)$item['reporter_id'] !== (int)$_SESSION['user_id']
                            ): ?>
                                <a class="btn secondary full small-btn" href="claim.php?found_item_id=<?= (int)$item['item_id'] ?>">Claim this item</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
</div>
</body>
</html>
