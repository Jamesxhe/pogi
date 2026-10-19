<?php
require_once "db.php";
require_once "helpers.php";
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    redirect("notifications.php");
}
$stmt = $conn->prepare("SELECT notification_id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$_SESSION['user_id']]); $notifications = $stmt->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Notifications | PUPick AI</title><link rel="stylesheet" href="style.css"><script src="theme.js" defer></script></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand sidebar-brand" href="dashboard.php"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a><p class="side-label">MENU</p><a class="nav-link" href="dashboard.php">⌂ &nbsp; Dashboard</a><a class="nav-link" href="report_lost.php">＋ &nbsp; Report lost item</a><a class="nav-link" href="report_found.php">＋ &nbsp; Report found item</a><a class="nav-link" href="items.php">▤ &nbsp; Browse items</a><a class="nav-link" href="my_reports.php">▣ &nbsp; My reports</a><a class="nav-link active" href="notifications.php">♧ &nbsp; Notifications</a><a class="nav-link" href="chatbot.php">💬 &nbsp; Help chat</a></aside>
<main class="main-content"><header class="topbar"><div><p class="eyebrow">UPDATES</p><h1>Notifications</h1></div><form method="post"><button class="btn secondary" name="mark_read" value="1">Mark all as read</button></form></header>
<div class="notification-list"><?php if (!$notifications): ?><div class="empty-card">You don't have any notifications yet.</div><?php endif; ?><?php foreach($notifications as $n): ?><article class="notification <?= (int)$n['is_read']===0?'unread':'' ?>"><span class="notification-symbol">♧</span><div class="notification-body"><div class="notification-heading"><h2><?= e($n['title']) ?></h2><?php if (!(int)$n['is_read']): ?><span class="unread-dot"></span><?php endif; ?></div><p><?= e($n['message']) ?></p><small><?= e($n['created_at']) ?> · <?= e($n['type']) ?></small></div></article><?php endforeach; ?></div></main></div></body></html>
