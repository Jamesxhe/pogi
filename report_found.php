<?php
require_once "db.php";
require_once "helpers.php";
require_login();

$errors = [];
$form = [
    'category' => '',
    'description' => '',
    'color' => '',
    'found_location' => '',
    'date_found' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $field => $_default) {
        $form[$field] = trim((string)($_POST[$field] ?? ''));
    }
    $category = $form['category'];
    $description = $form['description'];
    $color = $form['color'];
    $location = $form['found_location'];
    $dateFound = $form['date_found'];
    if ($category === '' || $description === '' || $location === '' || $dateFound === '') {
        $errors[] = "Please complete the required fields to submit your report.";
    }
    if (mb_strlen($category, 'UTF-8') > 80) {
        $errors[] = "Category must be 80 characters or fewer.";
    }
    if (mb_strlen($description, 'UTF-8') > 1000) {
        $errors[] = "Description must be 1,000 characters or fewer.";
    }
    if (mb_strlen($location, 'UTF-8') > 150 || mb_strlen($color, 'UTF-8') > 50) {
        $errors[] = "Location or color is too long. Please shorten it and try again.";
    }
    if ($dateFound !== '') {
        $parsedDate = DateTime::createFromFormat('!Y-m-d', $dateFound);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $dateFound || $dateFound > date('Y-m-d')) {
            $errors[] = "Please choose a valid date that is not in the future.";
        }
    }
    if (!$errors) {
        $imagePath = null;
        try {
            $imagePath = save_uploaded_image($_FILES['photo'] ?? [], 'reports');
            $stmt = $conn->prepare(
                "INSERT INTO found_items (user_id, item_name, category, description, color, found_location, date_found, image_path, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')"
            );
            // Keep the legacy required column populated for existing database installations.
            $stmt->execute([$_SESSION['user_id'], $category, $category, $description, $color, $location, $dateFound, $imagePath]);
        } catch (Throwable $ex) {
            delete_report_image($imagePath);
            error_log('PUPick found report submission error: ' . $ex->getMessage());
            $errors[] = $ex instanceof RuntimeException && !$ex instanceof PDOException
                ? $ex->getMessage()
                : 'Your report could not be saved. Please try again.';
        }
    }
    if (!$errors) {
        $newId = (int)$conn->lastInsertId();

        // Compare against all open lost reports; only save reasonably similar matches (35%+).
        $stmt = $conn->query("SELECT lost_item_id, user_id, category, description, color FROM lost_items WHERE status = 'open'");
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($candidates as $candidate) {
            $score = item_similarity_score(
                ['category' => $category, 'description' => $description, 'color' => $color],
                $candidate
            );
            if ($score < 35) continue;
            $conn->prepare("INSERT INTO match_result (lost_item_id, found_item_id, match_score, match_status) VALUES (?, ?, ?, 'pending')")
                ->execute([(int)$candidate['lost_item_id'], $newId, $score]);
            if ((int)$candidate['user_id'] !== (int)$_SESSION['user_id']) {
                create_notification($conn, (int)$candidate['user_id'], "Possible item match", "A found-item report may match your lost item (similarity: {$score}%): " . $category, "match");
            }
        }
        create_notification($conn, (int)$_SESSION['user_id'], "Found item report submitted", "Your report for " . $category . " has been saved.", "system");
        redirect("my_reports.php?submitted=found");
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Report found item | PUPick AI</title><link rel="stylesheet" href="style.css"><script src="theme.js" defer></script></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand sidebar-brand" href="dashboard.php"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a><p class="side-label">MENU</p><a class="nav-link" href="dashboard.php">⌂ &nbsp; Dashboard</a><a class="nav-link" href="report_lost.php">＋ &nbsp; Report lost item</a><a class="nav-link active" href="report_found.php">＋ &nbsp; Report found item</a><a class="nav-link" href="items.php">▤ &nbsp; Browse items</a><a class="nav-link" href="my_reports.php">▣ &nbsp; My reports</a><a class="nav-link" href="notifications.php">♧ &nbsp; Notifications</a><a class="nav-link" href="chatbot.php">💬 &nbsp; Help chat</a></aside>
<main class="main-content"><header class="topbar"><div><p class="eyebrow">STEP 1 OF 1 · FOUND ITEM</p><h1>Report a found item</h1></div><a class="btn secondary" href="dashboard.php">← Dashboard</a></header>
<div class="form-card"><h2>Tell us what you found</h2><p class="muted">Choose a category, then add a few details so we can help return it to its owner. Fields marked * are required.</p>
<?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
<div class="form-grid"><div class="span-2"><label for="category">Category *</label><input id="category" name="category" list="category-suggestions" maxlength="80" value="<?= e($form['category']) ?>" placeholder="Choose a suggestion or type your own" autocomplete="off" required><datalist id="category-suggestions"><?php foreach (item_category_suggestions() as $suggestion): ?><option value="<?= e($suggestion) ?>"><?= e($suggestion) ?></option><?php endforeach; ?></datalist><small class="muted">No suitable category? Type your own and continue.</small></div>
<div><label for="color">Color <span class="muted">(optional)</span></label><input id="color" name="color" maxlength="50" value="<?= e($form['color']) ?>" placeholder="For example: black"></div>
<div><label for="date_found">Date found *</label><input id="date_found" name="date_found" type="date" max="<?= e(date('Y-m-d')) ?>" value="<?= e($form['date_found']) ?>" required></div>
<div class="span-2"><label for="found_location">Where did you find it? *</label><input id="found_location" name="found_location" maxlength="150" value="<?= e($form['found_location']) ?>" placeholder="For example: library, canteen" required><small class="muted">A building, room, or nearby landmark is helpful.</small></div>
<div class="span-2"><label for="description">What does it look like? *</label><textarea id="description" name="description" rows="4" maxlength="1000" required placeholder="Add details such as brand, size, or other visible features. Do not reveal a private serial number or security detail."><?= e($form['description']) ?></textarea><small class="muted">Keep unique ownership details private so the owner can verify them.</small></div>
<div class="span-2"><label for="photo">Photo <span class="muted">(optional, JPG/PNG/WEBP, up to 5 MB)</span></label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp"><small class="muted">Photograph only the found item. Hide unique details that an owner should use to verify it.</small></div></div>
<button class="btn primary" type="submit">Submit found-item report</button> <a class="btn secondary" href="dashboard.php">Cancel</a></form></div></main></div></body></html>
