<?php
require_once "db.php";
require_once "helpers.php";
require_login();

$userId = (int)$_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Get the student's lost-item reports
|--------------------------------------------------------------------------
| The actual database ID is retained internally.
| The display ID is generated separately below.
*/
$stmt = $conn->prepare("
    SELECT
        lost_item_id AS item_id,
        category,
        lost_location AS location,
        date_lost AS item_date,
        status,
        created_at,
        'Lost' AS kind
    FROM lost_items
    WHERE user_id = ?
    ORDER BY created_at DESC, lost_item_id DESC
");
$stmt->execute([$userId]);
$lostItems = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get the student's found-item reports
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        found_item_id AS item_id,
        category,
        found_location AS location,
        date_found AS item_date,
        status,
        created_at,
        'Found' AS kind
    FROM found_items
    WHERE user_id = ?
    ORDER BY created_at DESC, found_item_id DESC
");
$stmt->execute([$userId]);
$foundItems = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get the student's claims
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        c.claim_id,
        c.claim_message,
        c.claim_image_path,
        c.status,
        c.created_at,
        fi.category
    FROM claims c
    LEFT JOIN found_items fi
        ON c.found_item_id = fi.found_item_id
    WHERE c.claimant_user_id = ?
    ORDER BY c.created_at DESC, c.claim_id DESC
");
$stmt->execute([$userId]);
$claims = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Generate display IDs
|--------------------------------------------------------------------------
| These numbers are only for display.
| They do NOT replace the real database primary keys.
| Each report section starts at 001.
*/
$lostDisplayIds = [];
foreach ($lostItems as $index => $item) {
    $lostDisplayIds[(int)$item['item_id']] =
        str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
}

$foundDisplayIds = [];
foreach ($foundItems as $index => $item) {
    $foundDisplayIds[(int)$item['item_id']] =
        str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My reports | PUPick AI</title>
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
        <a class="nav-link" href="items.php">▤ &nbsp; Browse items</a>
        <a class="nav-link active" href="my_reports.php">▣ &nbsp; My reports</a>
        <a class="nav-link" href="notifications.php">♧ &nbsp; Notifications</a>
        <a class="nav-link" href="chatbot.php">💬 &nbsp; Help chat</a>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="eyebrow">YOUR ACTIVITY</p>
                <h1>My reports and claims</h1>
            </div>
        </header>

        <?php if (isset($_GET['submitted'])): ?>
            <div class="alert success">
                Your <?= e($_GET['submitted']) ?> submission was saved.
            </div>
        <?php endif; ?>

        <!-- LOST-ITEM REPORTS -->
        <section class="section-heading">
            <div>
                <h2>My lost-item reports</h2>
                <p class="muted">Items you reported missing</p>
            </div>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$lostItems): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                No lost reports yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($lostItems as $item): ?>
                        <?php
                            $realId = (int)$item['item_id'];
                            $displayId = $lostDisplayIds[$realId];
                        ?>

                        <tr>
                            <td><?= e($displayId) ?></td>
                            <td><?= e($item['category']) ?></td>
                            <td><?= e($item['location']) ?></td>
                            <td><?= e($item['item_date']) ?></td>
                            <td>
                                <span class="pill">
                                    <?= e(trim($item['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- FOUND-ITEM REPORTS -->
        <section class="section-heading">
            <div>
                <h2>My found-item reports</h2>
                <p class="muted">Items you reported finding</p>
            </div>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$foundItems): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                No found reports yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($foundItems as $item): ?>
                        <?php
                            $realId = (int)$item['item_id'];
                            $displayId = $foundDisplayIds[$realId];
                        ?>

                        <tr>
                            <td><?= e($displayId) ?></td>
                            <td><?= e($item['category']) ?></td>
                            <td><?= e($item['location']) ?></td>
                            <td><?= e($item['item_date']) ?></td>
                            <td>
                                <span class="pill">
                                    <?= e(trim($item['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- CLAIMS -->
        <section class="section-heading">
            <div>
                <h2>My claims</h2>
                <p class="muted">Claims you submitted for found items</p>
            </div>
        </section>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Message</th>
                        <th>Photo</th>
                        <th>Status</th>
                        <th>Submitted</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!$claims): ?>
                        <tr>
                            <td colspan="5" class="empty">
                                No claims submitted yet.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td>
                                <?= e($claim['category'] ?? 'Item unavailable') ?>
                            </td>

                            <td>
                                <?= e($claim['claim_message']) ?>
                            </td>

                            <td>
                                <?php if (preg_match('#\Aclaims/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', (string)($claim['claim_image_path'] ?? ''))): ?>
                                    <a href="claim_image.php?id=<?= (int)$claim['claim_id'] ?>" target="_blank" rel="noopener">View photo</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="pill">
                                    <?= e($claim['status']) ?>
                                </span>
                            </td>

                            <td>
                                <?= e($claim['created_at']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>
</div>
</body>
</html>