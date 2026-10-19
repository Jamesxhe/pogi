<?php
require_once "db.php";
require_once "helpers.php";
require_login();

// Keep administrator accounts in the administration area.
if (($_SESSION['role'] ?? 'student') === 'admin') {
    redirect('admin.php');
}

$counts = ['lost' => 0, 'found' => 0, 'mine' => 0, 'claims' => 0, 'unread' => 0];
$counts['lost'] = (int)$conn->query("SELECT COUNT(*) FROM lost_items")->fetchColumn();
$counts['found'] = (int)$conn->query("SELECT COUNT(*) FROM found_items")->fetchColumn();

$stmt = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM lost_items WHERE user_id = ?) +
        (SELECT COUNT(*) FROM found_items WHERE user_id = ?)"
);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$counts['mine'] = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM claims WHERE claimant_user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$counts['claims'] = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$_SESSION['user_id']]);
$counts['unread'] = (int)$stmt->fetchColumn();

$stmt = $conn->query(
    "SELECT li.lost_item_id, li.category, li.lost_location, li.date_lost,
            li.status, u.full_name
     FROM lost_items li LEFT JOIN users u ON li.user_id = u.user_id
     ORDER BY li.created_at DESC LIMIT 5"
);
$recentLost = $stmt->fetchAll();

$stmt = $conn->query(
    "SELECT fi.found_item_id, fi.category, fi.found_location, fi.date_found,
            fi.status, u.full_name
     FROM found_items fi LEFT JOIN users u ON fi.user_id = u.user_id
     ORDER BY fi.created_at DESC LIMIT 5"
);
$recentFound = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand sidebar-brand" href="dashboard.php"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a>
        <p class="side-label">MENU</p>
        <a class="nav-link active" href="dashboard.php">⌂ &nbsp; Dashboard</a>
        <a class="nav-link" href="report_lost.php">＋ &nbsp; Report lost item</a>
        <a class="nav-link" href="report_found.php">＋ &nbsp; Report found item</a>
        <a class="nav-link" href="items.php">▤ &nbsp; Browse items</a>
        <a class="nav-link" href="my_reports.php">▣ &nbsp; My reports</a>
        <a class="nav-link" href="notifications.php">♧ &nbsp; Notifications <span class="nav-count"><?= $counts['unread'] ?></span></a>
        <a class="nav-link" href="chatbot.php">💬 &nbsp; Help chat</a>
        <?php if (is_admin()): ?><a class="nav-link" href="admin.php">⚙ &nbsp; Admin panel</a><?php endif; ?>
        <div class="sidebar-bottom">
            <div class="avatar"><?= e(strtoupper(substr($_SESSION['full_name'], 0, 1))) ?></div>
            <div class="profile-mini"><strong><?= e($_SESSION['full_name']) ?></strong><span><?= e(ucfirst($_SESSION['role'])) ?></span></div>
            <a class="logout-link" href="logout.php" title="Log out">↗</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div><p class="eyebrow">PUPICK AI / OVERVIEW</p><h1>Dashboard</h1></div>
            <div class="topbar-right"><span class="status-dot"></span> Signed in as <?= e($_SESSION['full_name']) ?></div>
        </header>

        <section class="welcome-banner">
            <div><p class="eyebrow light">WELCOME TO PUPICK AI</p><h2>Lost something? Found something?</h2><p>Help reconnect PUP students with their belongings.</p>
                <div class="banner-actions"><a class="btn white" href="report_lost.php">Report lost item</a><a class="btn outline-white" href="report_found.php">Report found item</a><a class="btn outline-white" href="ai_matches.php">View possible matches</a></div>
            </div>
            <div class="banner-icon">⌕</div>
        </section>

        <section class="stats-grid">
            <article class="stat-card"><span class="stat-icon red">⌕</span><p>Community lost reports</p><strong><?= $counts['lost'] ?></strong><small>Open and closed reports</small></article>
            <article class="stat-card"><span class="stat-icon green">✓</span><p>Community found reports</p><strong><?= $counts['found'] ?></strong><small>Items reported found</small></article>
            <article class="stat-card"><span class="stat-icon gold">▤</span><p>My reports</p><strong><?= $counts['mine'] ?></strong><small><a href="my_reports.php">View your reports</a></small></article>
            <article class="stat-card"><span class="stat-icon blue">▣</span><p>My claims</p><strong><?= $counts['claims'] ?></strong><small>Claims submitted by you</small></article>
            <article class="stat-card"><span class="stat-icon gold">♧</span><p>Unread notifications</p><strong><?= $counts['unread'] ?></strong><small>Updates for your account</small></article>
        </section>

        <section class="section-heading"><div><h2>Recently reported lost items</h2><p class="muted">Latest reports from the community</p></div><a href="items.php?kind=lost">View all →</a></section>
        <div class="table-wrap">
            <table><thead><tr><th>Category</th><th>Location</th><th>Date lost</th><th>Status</th></tr></thead><tbody>
            <?php if (!$recentLost): ?><tr><td colspan="4" class="empty">No lost items reported yet.</td></tr><?php endif; ?>
            <?php foreach ($recentLost as $item): ?><tr><td><strong><?= e($item['category']) ?></strong></td><td><?= e($item['lost_location']) ?></td><td><?= e($item['date_lost']) ?></td><td><span class="pill"><?= e(trim($item['status'])) ?></span></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>

        <section class="section-heading"><div><h2>Recently reported found items</h2><p class="muted">Items that may belong to someone</p></div><a href="items.php?kind=found">View all →</a></section>
        <div class="table-wrap">
            <table><thead><tr><th>Category</th><th>Location</th><th>Date found</th><th>Status</th></tr></thead><tbody>
            <?php if (!$recentFound): ?><tr><td colspan="4" class="empty">No found items reported yet.</td></tr><?php endif; ?>
            <?php foreach ($recentFound as $item): ?><tr><td><strong><?= e($item['category']) ?></strong></td><td><?= e($item['found_location']) ?></td><td><?= e($item['date_found']) ?></td><td><span class="pill"><?= e(trim($item['status'])) ?></span></td></tr><?php endforeach; ?>
            </tbody></table>
        </div>
        <footer class="footer">PUPick AI · Helping belongings find their way home.</footer>
    </main>
</div>
</body>
</html>
