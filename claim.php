<?php
require_once "db.php";
require_once "helpers.php";
require_login();

$userId = (int)$_SESSION['user_id'];
$foundId = filter_input(INPUT_GET, 'found_item_id', FILTER_VALIDATE_INT);

$error = '';
$item = null;
$canClaim = false;
$csrfToken = $_SESSION['claim_action_token'] ?? bin2hex(random_bytes(32));
$_SESSION['claim_action_token'] = $csrfToken;

if (!$foundId || $foundId < 1) {
    http_response_code(400);
    exit("Invalid found item.");
}

// Load the found item.
$stmt = $conn->prepare(
    "SELECT found_item_id, user_id, category, status
     FROM found_items
     WHERE found_item_id = ?"
);
$stmt->execute([$foundId]);
$item = $stmt->fetch();

if (!$item) {
    http_response_code(404);
    exit("Found item not found.");
}

$status = strtolower(trim((string)$item['status']));

// Do not allow a user to claim an item they reported finding.
if ((int)$item['user_id'] === $userId) {
    $error = "You cannot claim an item you reported finding.";
} elseif ($status === 'returned') {
    $error = "This item has already been returned to its owner.";
} elseif ($status === 'matched') {
    $error = "This item already has a confirmed match and is unavailable for new claims.";
} elseif ($status !== 'open') {
    $error = "This item is no longer available for claims.";
} else {
    // Check for an existing active claim.
    $stmt = $conn->prepare(
        "SELECT claim_id, status
         FROM claims
         WHERE found_item_id = ?
           AND claimant_user_id = ?
           AND LOWER(TRIM(status)) IN ('pending', 'approved')
         LIMIT 1"
    );
    $stmt->execute([$foundId, $userId]);
    $existingClaim = $stmt->fetch();

    if ($existingClaim) {
        if (strtolower(trim($existingClaim['status'])) === 'pending') {
            $error = "You have already submitted a claim for this item. It is awaiting admin review.";
        } else {
            $error = "Your claim for this item has already been approved. Please check your notifications for updates.";
        }
    } else {
        $canClaim = true;
    }
}

// Process claim submission.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['submit_claim'])
    && $canClaim
    && $error === ''
) {
    $message = trim($_POST['claim_message'] ?? '');

    if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
        $error = "Your session expired. Refresh the page and try again.";
    } elseif ($message === '') {
        $error = "Please explain why you believe this item belongs to you.";
    } elseif (mb_strlen($message, 'UTF-8') > 2000) {
        $error = "Please keep your message to 2,000 characters or fewer.";
    } else {
        $claimImagePath = null;
        $claimSaved = false;
        try {
            $claimImagePath = save_uploaded_image($_FILES['claim_photo'] ?? [], 'claims');
            $conn->beginTransaction();

            // Recheck availability to avoid claiming an item that
            // became unavailable after the page was opened.
            $stmt = $conn->prepare(
                "SELECT found_item_id, user_id, category, status
                 FROM found_items
                 WHERE found_item_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$foundId]);
            $currentItem = $stmt->fetch();

            if (
                !$currentItem
                || (int)$currentItem['user_id'] === $userId
                || strtolower(trim((string)$currentItem['status'])) !== 'open'
            ) {
                throw new RuntimeException(
                    "This item is no longer available for claims."
                );
            }

            $stmt = $conn->prepare(
                "SELECT claim_id
                 FROM claims
                 WHERE found_item_id = ?
                   AND claimant_user_id = ?
                   AND LOWER(TRIM(status)) IN ('pending', 'approved')
                 LIMIT 1"
            );
            $stmt->execute([$foundId, $userId]);

            if ($stmt->fetch()) {
                throw new RuntimeException(
                    "You already have an active claim for this item."
                );
            }

            // This assumes claims.lost_item_id is allowed to be NULL.
            $stmt = $conn->prepare(
                "INSERT INTO claims
                 (lost_item_id, found_item_id, claimant_user_id,
                  claim_message, claim_image_path, status, reviewed_by)
                 VALUES (NULL, ?, ?, ?, ?, 'pending', NULL)"
            );
            $stmt->execute([$foundId, $userId, $message, $claimImagePath]);

            $conn->commit();
            $claimSaved = true;

            // Notify the finder and claimant.
            create_notification(
                $conn,
                (int)$currentItem['user_id'],
                "New item claim",
                ($_SESSION['full_name'] ?? 'A student')
                    . " submitted a claim for "
                    . $currentItem['category']
                    . ".",
                "claim"
            );

            create_notification(
                $conn,
                $userId,
                "Claim submitted",
                "Your claim for "
                    . $currentItem['category']
                    . " is pending admin review.",
                "claim"
            );

            redirect("my_reports.php?submitted=claim");

        } catch (Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            if (!$claimSaved) {
                delete_claim_image($claimImagePath);
            }

            if ($ex instanceof RuntimeException && !$ex instanceof PDOException) {
                $error = $ex->getMessage();
            } else {
                error_log("claim.php error: " . $ex->getMessage());
                $error = "Your claim could not be submitted. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim item | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>
</head>
<body>
<main class="standalone-page">

    <a class="brand" href="dashboard.php">
        <img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt="">
        <span>PUPick <b>AI</b></span>
    </a>

    <div class="form-card">
        <p class="eyebrow">ITEM CLAIM</p>

        <h1>Claim: <?= e($item['category']) ?></h1>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($status === 'returned'): ?>
            <p class="pill">Already Returned</p>
            <p class="muted">
                This item has already been returned and cannot be claimed again.
            </p>

        <?php elseif ($status === 'matched'): ?>
            <p class="pill">Already Matched</p>
            <p class="muted">
                This item already has a confirmed match and is unavailable for new claims.
            </p>

        <?php elseif ($status !== 'open'): ?>
            <p class="muted">
                This item is currently unavailable for claims.
            </p>

        <?php elseif ($canClaim && $error === ''): ?>
            <p class="muted">
                Explain how you can identify or verify the item. You may also attach a photo that supports your claim. It will only be visible to you, the finder, and administrators. Do not upload IDs, passwords, or other sensitive personal information.
            </p>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <label for="claim_message">
                    Why do you believe this item is yours?
                </label>

                <textarea
                    id="claim_message"
                    name="claim_message"
                    rows="5"
                    maxlength="2000"
                    required
                    placeholder="Describe identifying details..."
                ></textarea>

                <label for="claim_photo">Supporting photo <span class="muted">(optional, JPG/PNG/WEBP, up to 5 MB)</span></label>
                <input id="claim_photo" name="claim_photo" type="file" accept="image/jpeg,image/png,image/webp">
                <small class="muted">Use a photo that helps show ownership. The photo supports review but does not prove ownership on its own.</small>

                <button
                    class="btn primary"
                    type="submit"
                    name="submit_claim"
                    value="1"
                >
                    Submit Claim
                </button>

                <a class="btn secondary" href="items.php">Cancel</a>
            </form>
        <?php endif; ?>

        <p>
            <a href="items.php">Back to found items</a>
        </p>
    </div>
</main>
</body>
</html>