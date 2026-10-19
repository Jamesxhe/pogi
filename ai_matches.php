<?php
require_once 'db.php';
require_once 'helpers.php';

require_login();

$userId = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? 'student';

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| Submit a claim
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_claim'])) {

    $lostId = (int)($_POST['lost_item_id'] ?? 0);
    $foundId = (int)($_POST['found_item_id'] ?? 0);
    $claimMessage = trim($_POST['claim_message'] ?? '');

    if ($lostId <= 0 || $foundId <= 0) {
        $error = 'Invalid item selection.';
    } elseif ($claimMessage === '') {
        $error = 'Please explain why you believe this item belongs to you.';
    } else {
        // The lost item must belong to the logged-in student
        // and must still be open.
        $check = $conn->prepare(
            "SELECT lost_item_id
             FROM lost_items
             WHERE lost_item_id = ?
               AND user_id = ?
               AND LOWER(TRIM(status)) = 'open'"
        );

        $check->execute([$lostId, $userId]);
        $ownedLostItem = $check->fetch();

        // The found item must still be available.
        $check = $conn->prepare(
            "SELECT found_item_id
             FROM found_items
             WHERE found_item_id = ?
               AND LOWER(TRIM(status)) = 'open'"
        );

        $check->execute([$foundId]);
        $availableFoundItem = $check->fetch();

        if (!$ownedLostItem || !$availableFoundItem) {
            $error = 'The selected items are no longer available for a claim.';
        } else {
            // Prevent duplicate active claims.
            $check = $conn->prepare(
                "SELECT claim_id
                 FROM claims
                 WHERE lost_item_id = ?
                   AND found_item_id = ?
                   AND claimant_user_id = ?
                   AND status IN ('pending', 'approved')
                 LIMIT 1"
            );

            $check->execute([$lostId, $foundId, $userId]);

            if ($check->fetch()) {
                $error = 'You already have an active claim for this item.';
            } else {
                $insert = $conn->prepare(
                    "INSERT INTO claims
                     (lost_item_id, found_item_id, claimant_user_id,
                      claim_message, status, reviewed_by)
                     VALUES (?, ?, ?, ?, 'pending', NULL)"
                );

                $insert->execute([
                    $lostId,
                    $foundId,
                    $userId,
                    $claimMessage
                ]);

                $message = 'Your claim has been submitted for admin review.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Retrieve possible lost-and-found pairs
|--------------------------------------------------------------------------
| Students see matches for their own lost reports.
| Administrators can review matches across all users.
*/
$sql = "
    SELECT
        l.lost_item_id,
        l.user_id AS lost_owner_id,
        l.category AS lost_category,
        l.description AS lost_description,
        l.color AS lost_color,
        l.lost_location,
        l.date_lost,
        l.image_path AS lost_image,
        l.created_at AS lost_created_at,

        f.found_item_id,
        f.user_id AS finder_user_id,
        f.category AS found_category,
        f.description AS found_description,
        f.color AS found_color,
        f.found_location,
        f.date_found,
        f.image_path AS found_image,
        f.created_at AS found_created_at

    FROM lost_items l
    CROSS JOIN found_items f

    WHERE LOWER(TRIM(l.status)) = 'open'
      AND LOWER(TRIM(f.status)) = 'open'
";

$params = [];

if ($role !== 'admin') {
    $sql .= " AND l.user_id = ?";
    $params[] = $userId;
}

$sql .= "
    ORDER BY l.created_at DESC,
             f.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Calculate similarity percentages
|--------------------------------------------------------------------------
*/
$matches = [];

foreach ($records as $record) {

    $lost = [
        'category' => $record['lost_category'],
        'description' => $record['lost_description'],
        'color' => $record['lost_color'],
        'lost_location' => $record['lost_location']
    ];

    $found = [
        'category' => $record['found_category'],
        'description' => $record['found_description'],
        'color' => $record['found_color'],
        'found_location' => $record['found_location']
    ];

    // Use the shared scoring function in helpers.php.
    $score = item_similarity_score($lost, $found);

    /*
     * Avoid displaying every unrelated combination.
     * Scores of 20% and above are displayed.
     */
    if ($score >= 20) {
        $record['score'] = $score;

        if ($score >= 80) {
            $record['match_level'] = 'High Similarity';
            $record['match_class'] = 'high';
        } elseif ($score >= 60) {
            $record['match_level'] = 'Moderate Similarity';
            $record['match_class'] = 'moderate';
        } else {
            $record['match_level'] = 'Low Similarity';
            $record['match_class'] = 'low';
        }

        $matches[] = $record;
    }
}

/*
|--------------------------------------------------------------------------
| Sort highest percentage first
|--------------------------------------------------------------------------
*/
usort($matches, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

/*
|--------------------------------------------------------------------------
| Escape output
|--------------------------------------------------------------------------
*/
function h($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>AI Matches | PUPick</title>
<link rel="stylesheet" href="theme.css?v=2">
<script src="theme.js" defer></script>

<style>
*{box-sizing:border-box}
body{margin:0;background:#f7f7fb;color:#24212a;font-family:'DM Sans',Arial,sans-serif}
.main{width:min(1120px,100%);margin:0 auto;padding:34px 28px 56px}
.page-header{display:flex;justify-content:space-between;align-items:flex-start;gap:20px;margin-bottom:24px}
.eyebrow{margin:0 0 8px;color:#806000;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase}
h1{margin:0;color:#3b1722;font-family:Manrope,'DM Sans',Arial,sans-serif;font-size:clamp(28px,4vw,38px);letter-spacing:-.04em}
.intro{max-width:650px;margin:10px 0 0;color:#6d6872;font-size:15px;line-height:1.65}
.back-link{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;min-height:42px;padding:0 15px;border:1px solid #e5dfe2;border-radius:10px;background:#fff;color:#5b1026;font-size:13px;font-weight:700;text-decoration:none}
.back-link:hover{background:#fff8e5;border-color:#e8c96a}
.notice{padding:14px 16px;border:1px solid #e8e3e8;border-radius:12px;margin:14px 0;background:#fff;color:#49434c;line-height:1.55}
.notice.success{border-color:#b7e3c5;background:#effaf2;color:#22623a}
.notice.error{border-color:#f0c3c3;background:#fff3f2;color:#922d32}
.legend{display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin:24px 0 18px}
.legend-title{margin-right:4px;color:#77717d;font-size:12px;font-weight:700}
.match-label{display:inline-flex;align-items:center;padding:7px 11px;border-radius:999px;font-size:12px;font-weight:800}
.high{color:#17623a;background:#e8f7ed}
.moderate{color:#805514;background:#fff4d6}
.low{color:#982e39;background:#ffeded}
.match{margin:16px 0;padding:20px;background:#fff;border:1px solid #ece7eb;border-radius:16px;box-shadow:0 8px 24px rgba(53,24,37,.055)}
.match-top{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px}
.score{display:flex;align-items:center;gap:7px;margin:0;color:#6b142d;font-size:14px;font-weight:800}
.score strong{font-size:24px;letter-spacing:-.03em}
.pair{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.item{min-width:0;padding:17px;border:1px solid #eee8ec;border-radius:12px;background:#fcfbfc;overflow-wrap:anywhere}
.item.lost-item{border-top:3px solid #8b2941}
.item.found-item{border-top:3px solid #328151}
.item h3{margin:0 0 9px;color:#756a74;font-size:11px;letter-spacing:.1em;text-transform:uppercase}
.item strong{display:block;margin-bottom:12px;color:#28232a;font-size:16px}
.item-detail{margin:7px 0;color:#5e5862;font-size:13px;line-height:1.55}
.item-detail b{color:#39323c;font-weight:700}
.claim-form{margin-top:17px;padding-top:16px;border-top:1px solid #eee8ec}
.claim-form label{display:block;margin-bottom:8px;color:#39323c;font-size:13px;font-weight:700}
.claim-form textarea{display:block;width:100%;min-height:80px;padding:11px 12px;border:1px solid #ddd5dc;border-radius:9px;background:#fff;color:#28232a;font:inherit;resize:vertical}
.claim-form textarea:focus{outline:3px solid #ffc72c55;border-color:#bd8c16}
.claim-form button{margin-top:10px;padding:10px 15px;border:0;border-radius:9px;background:#800020;color:white;font:inherit;font-weight:700;cursor:pointer}
.claim-form button:hover{background:#5a0012}
.disclaimer{margin:24px 0 0;padding:13px 15px;border-left:3px solid #d7b54a;border-radius:0 9px 9px 0;background:#fff9e9;color:#625638;font-size:12px;line-height:1.6}
@media(max-width:680px){.main{padding:24px 16px 44px}.page-header{flex-direction:column}.back-link{align-self:flex-start}.pair{grid-template-columns:1fr}.match{padding:15px}.match-top{align-items:flex-start;flex-direction:column}.legend{margin-top:18px}}
</style>
</head>

<body>
<main class="main ai-matches">
    <header class="page-header">
        <div>
            <p class="eyebrow">PUPICK AI · ITEM RECOVERY</p>
            <h1>Possible matches</h1>
            <p class="intro">
                Compare lost and found reports with similar details. Review the information
                carefully before contacting a finder or submitting a claim.
            </p>
        </div>
        <a class="back-link" href="<?= $role === 'admin' ? 'admin.php' : 'dashboard.php' ?>">← Back to dashboard</a>
    </header>

    <?php if ($message): ?>
        <div class="notice success">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <div class="legend" aria-label="Similarity score legend">
        <span class="legend-title">Similarity guide</span>
        <span class="match-label high">High · 80–100%</span>
        <span class="match-label moderate">Moderate · 60–79%</span>
        <span class="match-label low">Low · 20–59%</span>
    </div>

    <?php if (!$matches): ?>
        <div class="notice">
            <strong>No possible matches yet.</strong><br>
            Add or update an open lost or found report with clear item details, then check again.
        </div>
    <?php endif; ?>

    <?php foreach ($matches as $m): ?>

        <section class="match">
            <div class="match-top">
                <span class="match-label <?= h($m['match_class']) ?>"><?= h($m['match_level']) ?></span>
                <p class="score">Similarity estimate <strong><?= (int)$m['score'] ?>%</strong></p>
            </div>

            <div class="pair">

                <div class="item lost-item">
                    <h3>Lost report · #<?= (int)$m['lost_item_id'] ?></h3>
                    <strong><?= h($m['lost_category']) ?></strong>
                    <p class="item-detail"><b>Color:</b> <?= h($m['lost_color']) ?></p>
                    <p class="item-detail"><b>Last seen:</b> <?= h($m['lost_location']) ?></p>
                    <p class="item-detail"><b>Description:</b> <?= h($m['lost_description']) ?></p>
                    <p class="item-detail"><b>Date lost:</b> <?= h($m['date_lost']) ?></p>
                </div>

                <div class="item found-item">
                    <h3>Found report · #<?= (int)$m['found_item_id'] ?></h3>
                    <strong><?= h($m['found_category']) ?></strong>
                    <p class="item-detail"><b>Color:</b> <?= h($m['found_color']) ?></p>
                    <p class="item-detail"><b>Found at:</b> <?= h($m['found_location']) ?></p>
                    <p class="item-detail"><b>Description:</b> <?= h($m['found_description']) ?></p>
                    <p class="item-detail"><b>Date found:</b> <?= h($m['date_found']) ?></p>
                </div>
                

            </div>

            <?php if (
                $role !== 'admin'
                && (int)$m['lost_owner_id'] === $userId
            ): ?>

                <form method="post" class="claim-form">

                    <input
                        type="hidden"
                        name="lost_item_id"
                        value="<?= (int)$m['lost_item_id'] ?>"
                    >

                    <input
                        type="hidden"
                        name="found_item_id"
                        value="<?= (int)$m['found_item_id'] ?>"
                    >

                    <label for="claim<?= (int)$m['lost_item_id'] ?>_<?= (int)$m['found_item_id'] ?>">
                        Explain why you believe this found item is yours
                    </label>

                    <textarea
                        id="claim<?= (int)$m['lost_item_id'] ?>_<?= (int)$m['found_item_id'] ?>"
                        name="claim_message"
                        rows="3"
                        required
                    ></textarea>

                    <button type="submit" name="submit_claim">
                        Submit Claim
                    </button>

                </form>

            <?php endif; ?>

        </section>

    <?php endforeach; ?>

    <p class="disclaimer">
        Similarity scores are estimates based on report text, category, and color. They do not prove
        that two reports refer to the same item. Verify ownership before returning any item.
    </p>

</main>
</body>
</html>