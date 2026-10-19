<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_login();

// Refresh the role from the database so a role change in phpMyAdmin is picked up
// after the user logs in again (or opens this page with an older session).
$roleStmt = $conn->prepare('SELECT role, full_name, email FROM users WHERE user_id = ? LIMIT 1');
$roleStmt->execute([(int)$_SESSION['user_id']]);
$currentUser = $roleStmt->fetch();
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['role'] = $currentUser['role'];
$_SESSION['full_name'] = $currentUser['full_name'];
if (!is_admin()) {
    http_response_code(403);
    exit('Access denied. Admin account required. Please update your account role to admin, log out, and log in again.');
}

$message = '';
$error = '';
$csrfToken = $_SESSION['admin_action_token'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_action_token'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_id'], $_POST['decision'])) {
    $claimId = filter_var($_POST['claim_id'], FILTER_VALIDATE_INT);
    $decision = (string)$_POST['decision'];
    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$claimId || !in_array($decision, ['approved', 'rejected', 'completed'], true)) {
        $error = 'Please choose a valid claim action.';
    } else {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare(
                'SELECT claimant_user_id, found_item_id, lost_item_id, status
                 FROM claims WHERE claim_id = ? FOR UPDATE'
            );
            $stmt->execute([$claimId]);
            $claim = $stmt->fetch();

            if (!$claim) {
                throw new RuntimeException('Claim not found.');
            }

            $currentStatus = strtolower(trim((string)$claim['status']));
            $validTransition = (
                $currentStatus === 'pending'
                && in_array($decision, ['approved', 'rejected'], true)
            ) || ($currentStatus === 'approved' && $decision === 'completed');

            if (!$validTransition) {
                throw new RuntimeException(
                    'This claim has already been reviewed or cannot move to that status.'
                );
            }

            $stmt = $conn->prepare(
                'SELECT status FROM found_items WHERE found_item_id = ? FOR UPDATE'
            );
            $stmt->execute([(int)$claim['found_item_id']]);
            $foundItemStatus = $stmt->fetchColumn();

            if ($foundItemStatus === false) {
                throw new RuntimeException('The found item for this claim no longer exists.');
            }

            if ($decision === 'approved' && strtolower(trim((string)$foundItemStatus)) !== 'open') {
                throw new RuntimeException('This item is no longer available to approve.');
            }

            if ($decision === 'approved' || $decision === 'completed') {
                $itemStatus = $decision === 'approved' ? 'matched' : 'returned';
                $stmt = $conn->prepare(
                    'UPDATE found_items SET status = ? WHERE found_item_id = ?'
                );
                $stmt->execute([$itemStatus, (int)$claim['found_item_id']]);

                if ($claim['lost_item_id'] !== null) {
                    $stmt = $conn->prepare(
                        'UPDATE lost_items SET status = ? WHERE lost_item_id = ?'
                    );
                    $stmt->execute([$itemStatus, (int)$claim['lost_item_id']]);
                }
            }

            $stmt = $conn->prepare(
                'UPDATE claims SET status = ?, reviewed_by = ? WHERE claim_id = ?'
            );
            $stmt->execute([$decision, (int)$_SESSION['user_id'], $claimId]);

            create_notification(
                $conn,
                (int)$claim['claimant_user_id'],
                'Claim updated',
                'Your claim #' . $claimId . ' has been marked ' . $decision . '.',
                'claim'
            );
            $conn->commit();
            $message = 'Claim #' . $claimId . ' updated to ' . $decision . '.';
        } catch (Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('PUPick AI admin claim update error: ' . $ex->getMessage());
            $error = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Could not update the claim. Please try again.';
        }
    }
}

function safeCount(PDO $conn, string $sql): int {
    try { return (int)$conn->query($sql)->fetchColumn(); }
    catch (Throwable $e) { error_log('PUPick AI admin count error: ' . $e->getMessage()); return 0; }
}
function safeRows(PDO $conn, string $sql): array {
    try { return $conn->query($sql)->fetchAll(); }
    catch (Throwable $e) { error_log('PUPick AI admin query error: ' . $e->getMessage()); return []; }
}

$totalUsers = safeCount($conn, 'SELECT COUNT(*) FROM users');
$totalLost = safeCount($conn, 'SELECT COUNT(*) FROM lost_items');
$totalFound = safeCount($conn, 'SELECT COUNT(*) FROM found_items');
$totalClaims = safeCount($conn, 'SELECT COUNT(*) FROM claims');
$pendingClaims = safeCount($conn, "SELECT COUNT(*) FROM claims WHERE status = 'pending'");
$totalMatches = safeCount($conn, 'SELECT COUNT(*) FROM match_result');
$recentLost = safeRows($conn, "SELECT lost_item_id AS item_id, category, lost_location AS location, status, created_at, 'Lost' AS item_type FROM lost_items ORDER BY created_at DESC LIMIT 6");
$recentFound = safeRows($conn, "SELECT found_item_id AS item_id, category, found_location AS location, status, created_at, 'Found' AS item_type FROM found_items ORDER BY created_at DESC LIMIT 6");
$recentItems = array_merge($recentLost, $recentFound);
usort($recentItems, function ($a, $b) { return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')); });
$recentItems = array_slice($recentItems, 0, 8);
$claims = safeRows(
    $conn,
    "SELECT c.claim_id, c.claim_message, c.claim_image_path, c.status, c.created_at,
            u.full_name AS claimant, fi.category
     FROM claims c
     LEFT JOIN users u ON c.claimant_user_id = u.user_id
     LEFT JOIN found_items fi ON c.found_item_id = fi.found_item_id
     ORDER BY (c.status = 'pending') DESC, (c.status = 'approved') DESC, c.created_at DESC
     LIMIT 20"
);
$matches = safeRows($conn, "SELECT mr.match_id, mr.match_score, mr.match_status, mr.created_at, li.category AS lost_name, fi.category AS found_name FROM match_result mr LEFT JOIN lost_items li ON mr.lost_item_id = li.lost_item_id LEFT JOIN found_items fi ON mr.found_item_id = fi.found_item_id ORDER BY mr.created_at DESC LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | PUPick AI</title><link rel="stylesheet" href="style.css"><script src="theme.js" defer></script>
<style>
:root{--admin-maroon:#790019;--admin-gold:#f3c64e;--admin-ink:#28212a;--admin-muted:#77717a;--admin-line:#eee7e9;--admin-bg:#faf7f8}
body{background:var(--admin-bg)}.admin-shell{display:flex;min-height:100vh}.admin-side{width:245px;flex-shrink:0;background:#560013;color:#fff;padding:25px 16px;display:flex;flex-direction:column;gap:8px;position:fixed;left:0;top:0;height:100vh;box-sizing:border-box;overflow-y:auto;z-index:1000}.admin-brand{display:flex;align-items:center;gap:10px;padding:5px 10px 26px;color:#fff;text-decoration:none;font-size:20px;font-weight:800}.admin-mark{display:grid;place-items:center;width:39px;height:39px;object-fit:contain;background:transparent;border-radius:50%;box-shadow:none}.admin-label{font-size:11px;letter-spacing:1.8px;color:#e3bdc5;margin:10px 12px}.admin-nav{display:block;color:#f7e9ec;text-decoration:none;padding:12px 13px;border-radius:10px;font-size:14px}.admin-nav:hover,.admin-nav.active{background:rgba(255,255,255,.13);color:#fff}.admin-nav.active{border-left:3px solid var(--admin-gold)}.admin-side-bottom{margin-top:auto}.admin-main{flex:1;min-width:0;width:calc(100% - 245px);margin-left:245px;padding:30px clamp(16px,3vw,38px);max-width:none;box-sizing:border-box}.admin-top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:25px}.admin-top h1{margin:3px 0 0;font-size:clamp(25px,3vw,34px);color:var(--admin-ink)}.admin-eyebrow{margin:0;color:var(--admin-maroon);font-size:11px;font-weight:800;letter-spacing:1.7px}.admin-greeting{color:var(--admin-muted);font-size:13px;text-align:right}.admin-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:25px}.admin-stat{background:#fff;border:1px solid var(--admin-line);border-radius:15px;padding:19px;box-shadow:0 5px 18px rgba(60,0,15,.035)}.admin-stat-top{display:flex;justify-content:space-between;gap:8px;color:var(--admin-muted);font-size:13px}.admin-stat-icon{width:35px;height:35px;display:grid;place-items:center;background:#fbf0f2;border-radius:10px;color:var(--admin-maroon);font-size:18px}.admin-stat strong{display:block;font-size:30px;color:var(--admin-ink);margin-top:10px}.admin-stat small{color:var(--admin-muted)}.admin-section{background:#fff;border:1px solid var(--admin-line);border-radius:15px;padding:20px;margin-bottom:22px;box-shadow:0 5px 18px rgba(60,0,15,.03);min-width:0}.admin-section-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.admin-section h2{font-size:18px;margin:0;color:var(--admin-ink)}.admin-section p.sub{font-size:12px;color:var(--admin-muted);margin:5px 0 0}.admin-table-wrap{width:100%;overflow-x:auto}table.admin-table{width:100%;border-collapse:collapse;font-size:13px;min-width:600px} .admin-table th{text-align:left;color:#7c7075;font-size:11px;text-transform:uppercase;letter-spacing:.6px;padding:12px;border-bottom:1px solid var(--admin-line);white-space:nowrap}.admin-table td{padding:13px 12px;border-bottom:1px solid #f3edef;color:#413a3e;vertical-align:top}.admin-table tr:last-child td{border-bottom:0}.admin-pill{display:inline-block;padding:5px 9px;border-radius:20px;font-size:11px;font-weight:700;background:#f2edf0;color:#66525a}.admin-pill.lost{background:#fff0e6;color:#98501c}.admin-pill.found{background:#eaf7ef;color:#267044}.admin-pill.pending{background:#fff5d9;color:#87630c}.admin-pill.approved,.admin-pill.completed,.admin-pill.returned{background:#eaf7ef;color:#267044}.admin-pill.rejected{background:#ffebed;color:#a32234}.admin-action{display:flex;gap:7px;align-items:center;flex-wrap:wrap}.admin-action select{border:1px solid #e4d8dc;border-radius:8px;padding:8px;max-width:120px;background:#fff}.admin-btn{border:0;border-radius:8px;padding:9px 12px;background:var(--admin-maroon);color:#fff;font-weight:700;font-size:12px;cursor:pointer}.admin-btn:hover{background:#5b0013}.admin-alert{padding:12px 14px;border-radius:10px;margin-bottom:18px;font-size:13px}.admin-alert.success{background:#eaf7ef;color:#246b40}.admin-alert.error{background:#ffebed;color:#9e2030}.admin-empty{color:var(--admin-muted);padding:22px!important;text-align:center}.admin-two-col{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:22px}.admin-mobile-menu{display:none}
@media(max-width:900px){.admin-side{width:205px}.admin-main{width:calc(100% - 205px);margin-left:205px}.admin-cards{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-two-col{grid-template-columns:1fr}}
@media(max-width:620px){.admin-shell{display:block}.admin-side{position:relative;left:auto;top:auto;height:auto;width:auto;padding:12px;display:flex;flex-direction:row;align-items:center;flex-wrap:wrap;overflow:visible}.admin-main{width:auto;margin-left:0}.admin-brand{padding:4px 8px;margin-right:auto}.admin-label{display:none}.admin-nav{padding:9px 10px;font-size:12px}.admin-side-bottom{margin:0}.admin-main{padding:20px 12px}.admin-top{align-items:flex-start;flex-direction:column}.admin-greeting{text-align:left}.admin-cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.admin-stat{padding:14px}.admin-stat strong{font-size:25px}.admin-section{padding:14px}.admin-section-head{align-items:flex-start;flex-direction:column}}
</style></head>
<body>
<div class="admin-shell">
<aside class="admin-side">
<a class="admin-brand" href="admin.php"><img class="admin-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a>
<p class="admin-label">ADMINISTRATION</p>
<a class="admin-nav active" href="admin.php">▦ &nbsp; Overview</a>
<a class="admin-nav" href="admin_items.php">▤ &nbsp; Manage Items</a>
<a class="admin-nav" href="#claims">✓ &nbsp; Manage Claims</a>
<a class="admin-nav" href="#matches">✧ &nbsp; AI Matches</a>
<a class="admin-nav" href="chatbot.php">💬 &nbsp; Help Chat</a>
<div class="admin-side-bottom"><a class="admin-nav" href="logout.php">↪ &nbsp; Log Out</a></div>
</aside>
<main class="admin-main">
<header class="admin-top"><div><p class="admin-eyebrow">PUPICK AI · CONTROL CENTER</p><h1>Admin Dashboard</h1><p class="sub" style="color:#77717a;margin:8px 0 0">Monitor reports, review claims, and track possible item matches.</p><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px"><a class="admin-btn" href="admin_export.php?format=xlsx">Download Excel workbook (.xlsx)</a><a class="admin-btn" href="admin_export.php?format=pdf">Download PDF</a><a class="admin-btn" href="chatbot.php">Help Chat</a></div></div><div class="admin-greeting">Signed in as<br><strong><?= e($currentUser['full_name'] ?? 'Administrator') ?></strong><br><?= e($currentUser['email'] ?? '') ?></div></header>

<?php if ($error): ?><div class="admin-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="admin-alert success" role="status"><?= e($message) ?></div><?php endif; ?>
<section class="admin-cards">
<div class="admin-stat"><div class="admin-stat-top"><span>Registered Users</span><span class="admin-stat-icon">♙</span></div><strong><?= $totalUsers ?></strong><small>Accounts in the system</small></div>
<div class="admin-stat"><div class="admin-stat-top"><span>Lost Reports</span><span class="admin-stat-icon">⌕</span></div><strong><?= $totalLost ?></strong><small>Items reported lost</small></div>
<div class="admin-stat"><div class="admin-stat-top"><span>Found Reports</span><span class="admin-stat-icon">✓</span></div><strong><?= $totalFound ?></strong><small>Items reported found</small></div>
<div class="admin-stat"><div class="admin-stat-top"><span>Total Claims</span><span class="admin-stat-icon">▣</span></div><strong><?= $totalClaims ?></strong><small><?= $pendingClaims ?> pending review</small></div>
<div class="admin-stat"><div class="admin-stat-top"><span>AI Match Records</span><span class="admin-stat-icon">✧</span></div><strong><?= $totalMatches ?></strong><small>Potential lost/found matches</small></div>
<div class="admin-stat"><div class="admin-stat-top"><span>Quick Actions</span><span class="admin-stat-icon">＋</span></div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px"><a class="admin-btn" href="admin_items.php">Manage Items</a><a class="admin-btn" href="#claims" style="background:#f3c64e;color:#560013">Review Claims</a></div></div>
</section>
<div class="admin-two-col">
<section class="admin-section"><div class="admin-section-head"><div><h2>Recent Item Reports</h2><p class="sub">Latest lost and found submissions</p></div><a class="admin-btn" href="admin_items.php">Manage Items</a></div><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Report</th><th>Type</th><th>Category</th><th>Location</th><th>Status</th></tr></thead><tbody>
<?php if (!$recentItems): ?><tr><td colspan="5" class="admin-empty">No item reports found yet.</td></tr><?php else: foreach ($recentItems as $item): ?><tr><td><?= strtoupper(e($item['item_type'])) ?>-<?= str_pad((string)(int)$item['item_id'], 4, '0', STR_PAD_LEFT) ?></td><td><span class="admin-pill <?= strtolower(e($item['item_type'])) ?>"><?= e($item['item_type']) ?></span></td><td><strong><?= e($item['category'] ?? '—') ?></strong></td><td><?= e($item['location'] ?? '—') ?></td><td><span class="admin-pill <?= strtolower(e($item['status'] ?? 'pending')) ?>"><?= e($item['status'] ?? 'pending') ?></span></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section>
<section class="admin-section" id="matches"><div class="admin-section-head"><div><h2>Latest AI Matches</h2><p class="sub">Potential connections between lost and found reports</p></div></div><div class="admin-table-wrap"><table class="admin-table" style="min-width:420px"><thead><tr><th>Lost Item</th><th>Found Item</th><th>Score</th><th>Status</th></tr></thead><tbody>
<?php if (!$matches): ?><tr><td colspan="4" class="admin-empty">No matches recorded yet.</td></tr><?php else: foreach ($matches as $match): ?><tr><td><?= e($match['lost_name'] ?? 'Deleted/unavailable') ?></td><td><?= e($match['found_name'] ?? 'Deleted/unavailable') ?></td><td><strong><?= (int)$match['match_score'] ?>%</strong></td><td><span class="admin-pill <?= strtolower(e($match['match_status'] ?? 'pending')) ?>"><?= e($match['match_status'] ?? 'pending') ?></span></td></tr><?php endforeach; endif; ?>
</tbody></table></div></section>
</div>
<section class="admin-section claims-section" id="claims">
    <div class="admin-section-head">
        <div>
            <h2>Manage Claims</h2>
            <p class="sub">Review each claimant’s message and photo, if provided. Approve to reserve, reject if details do not match, and mark returned only after handoff.</p>
        </div>
        <span class="admin-pill pending"><?= $pendingClaims ?> pending</span>
    </div>

    <?php if (!$claims): ?>
        <div class="admin-empty">No claims submitted yet.</div>
    <?php else: ?>
        <div class="claim-review-list">
            <?php foreach ($claims as $claim): ?>
                <?php $claimStatus = strtolower(trim((string)($claim['status'] ?? 'pending'))); ?>
                <article class="claim-review-card">
                    <div class="claim-review-main">
                        <div class="claim-review-heading">
                            <div>
                                <p class="claim-review-kicker">Claim #<?= (int)$claim['claim_id'] ?></p>
                                <h3><?= e($claim['category'] ?? 'Item unavailable') ?></h3>
                            </div>
                            <span class="admin-pill <?= e($claimStatus) ?>"><?= e(ucfirst($claimStatus)) ?></span>
                        </div>
                        <div class="claim-review-meta">
                            <span><strong>Claimant</strong><?= e($claim['claimant'] ?? 'Unknown user') ?></span>
                            <span><strong>Submitted</strong><?= e($claim['created_at'] ?? '') ?></span>
                        </div>
                        <div class="claim-message">
                            <strong>Claim message</strong>
                            <p><?= e($claim['claim_message'] ?? '') ?></p>
                        </div>
                        <?php if (preg_match('#\Aclaims/[a-f0-9]{48}\.(?:jpg|png|webp)\z#', (string)($claim['claim_image_path'] ?? ''))): ?>
                            <a class="claim-photo-link" href="claim_image.php?id=<?= (int)$claim['claim_id'] ?>" target="_blank" rel="noopener">View supporting photo</a>
                        <?php endif; ?>
                    </div>
                    <div class="claim-review-action">
                        <?php if ($claimStatus === 'pending'): ?>
                            <form method="post" class="claim-decision-form">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="claim_id" value="<?= (int)$claim['claim_id'] ?>">
                                <label for="claim-decision-<?= (int)$claim['claim_id'] ?>">Review decision</label>
                                <select id="claim-decision-<?= (int)$claim['claim_id'] ?>" name="decision" aria-label="Review claim #<?= (int)$claim['claim_id'] ?>">
                                    <option value="approved">Approve &amp; reserve</option>
                                    <option value="rejected">Reject</option>
                                </select>
                                <button class="admin-btn" type="submit">Save decision</button>
                            </form>
                        <?php elseif ($claimStatus === 'approved'): ?>
                            <form method="post" class="claim-decision-form">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="claim_id" value="<?= (int)$claim['claim_id'] ?>">
                                <input type="hidden" name="decision" value="completed">
                                <button class="admin-btn" type="submit">Confirm item returned</button>
                            </form>
                        <?php else: ?>
                            <span class="claim-no-action">No further action</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<footer style="text-align:center;color:#8a7d82;font-size:12px;padding:4px 0 20px">PUPick AI · Polytechnic University of the Philippines</footer>
</main></div>
</body></html>
