<?php
require_once 'db.php';
require_once 'helpers.php';

require_login();

/* =========================================
   VERIFY ADMINISTRATOR
========================================= */
$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId < 1) {
    redirect('login.php');
    exit;
}

$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrators only.');
}

/* =========================================
   STATUS OPTIONS
========================================= */
$statusChoices = ['open', 'matched', 'returned'];
$error = '';
$csrfToken = $_SESSION['admin_items_token'] ?? bin2hex(random_bytes(32));
$_SESSION['admin_items_token'] = $csrfToken;

/* =========================================
   HELPER FUNCTIONS
========================================= */
function adminStatusOptions(string $current, array $statusChoices): string
{
    $current = trim($current);

    $selected = in_array($current, $statusChoices, true)
        ? $current
        : 'open';

    $html = '';

    foreach ($statusChoices as $status) {
        $html .= '<option value="' . e($status) . '"' .
            ($status === $selected ? ' selected' : '') .
            '>' . e(ucfirst($status)) . '</option>';
    }

    return $html;
}

function adminItemCode(string $type, int $id): string
{
    return strtoupper($type) . '-' .
        str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

/* =========================================
   HANDLE POST ACTIONS
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if (!hash_equals($csrfToken, (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Your session expired. Refresh the page and try again.');
        }

        /* -------------------------------------
           CHANGE ITEM STATUS
        ------------------------------------- */
        if ($action === 'status') {
            $type = $_POST['type'] ?? '';
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $status = trim($_POST['status'] ?? '');

            if (!in_array($type, ['lost', 'found'], true)) {
                throw new Exception('Invalid item type.');
            }

            if (!$id || $id < 1) {
                throw new Exception('Invalid item ID.');
            }

            if (!in_array($status, $statusChoices, true)) {
                throw new Exception('Invalid status selected.');
            }

            $table = $type === 'lost' ? 'lost_items' : 'found_items';
            $idColumn = $type === 'lost'
                ? 'lost_item_id'
                : 'found_item_id';

            $stmt = $conn->prepare(
                "UPDATE `$table`
                 SET status = ?
                 WHERE `$idColumn` = ?"
            );

            $stmt->execute([$status, $id]);

            redirect('admin_items.php?saved=status');
            exit;
        }

        /* -------------------------------------
           EDIT ITEM
        ------------------------------------- */
        if ($action === 'edit') {
            $type = $_POST['type'] ?? '';
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!in_array($type, ['lost', 'found'], true)) {
                throw new Exception('Invalid item type.');
            }

            if (!$id || $id < 1) {
                throw new Exception('Invalid item ID.');
            }

            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $date = trim($_POST['item_date'] ?? '');
            $status = trim($_POST['status'] ?? '');

            if ($category === '' || mb_strlen($category, 'UTF-8') > 80) {
                throw new InvalidArgumentException('Enter a category using 1 to 80 characters.');
            }

            if (!in_array($status, $statusChoices, true)) {
                throw new Exception('Invalid status selected.');
            }

            if ($date !== '') {
                $parsedDate = DateTime::createFromFormat('Y-m-d', $date);

                if (
                    !$parsedDate ||
                    $parsedDate->format('Y-m-d') !== $date
                ) {
                    throw new Exception('Please enter a valid date.');
                }
            }

            if ($type === 'lost') {
                $table = 'lost_items';
                $idColumn = 'lost_item_id';
                $locationColumn = 'lost_location';
                $dateColumn = 'date_lost';
            } else {
                $table = 'found_items';
                $idColumn = 'found_item_id';
                $locationColumn = 'found_location';
                $dateColumn = 'date_found';
            }

            $stmt = $conn->prepare(
                "UPDATE `$table`
                 SET item_name = ?,
                     category = ?,
                     description = ?,
                     color = ?,
                     `$locationColumn` = ?,
                     `$dateColumn` = NULLIF(?, ''),
                     status = ?
                 WHERE `$idColumn` = ?"
            );

            $stmt->execute([
                $category,
                $category,
                $description,
                $color,
                $location,
                $date,
                $status,
                $id
            ]);

            redirect('admin_items.php?saved=edit');
            exit;
        }

        /* -------------------------------------
           DELETE ITEM
        ------------------------------------- */
        if ($action === 'delete') {
            $type = $_POST['type'] ?? '';
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!in_array($type, ['lost', 'found'], true)) {
                throw new Exception('Invalid item type.');
            }

            if (!$id || $id < 1) {
                throw new Exception('Invalid item ID.');
            }

            if ($type === 'lost') {
                $table = 'lost_items';
                $idColumn = 'lost_item_id';
            } else {
                $table = 'found_items';
                $idColumn = 'found_item_id';
            }

            $conn->beginTransaction();
            $stmt = $conn->prepare(
                "SELECT image_path FROM `$table` WHERE `$idColumn` = ? FOR UPDATE"
            );
            $stmt->execute([$id]);
            $reportImagePath = $stmt->fetchColumn();

            $stmt = $conn->prepare(
                "SELECT claim_image_path FROM claims WHERE `$idColumn` = ? AND claim_image_path IS NOT NULL"
            );
            $stmt->execute([$id]);
            $claimImagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

            /* Delete associated claims */
            $stmt = $conn->prepare(
                "DELETE FROM claims WHERE `$idColumn` = ?"
            );
            $stmt->execute([$id]);

            /* Delete associated matching records if tables exist */
            foreach (['match_result', 'match_results'] as $matchTable) {
                $stmt = $conn->prepare(
                    "SELECT COUNT(*)
                     FROM information_schema.tables
                     WHERE table_schema = DATABASE()
                       AND table_name = ?"
                );

                $stmt->execute([$matchTable]);

                if ((int)$stmt->fetchColumn() > 0) {
                    $stmt = $conn->prepare(
                        "DELETE FROM `$matchTable`
                         WHERE `$idColumn` = ?"
                    );

                    $stmt->execute([$id]);
                }
            }

            /* Delete the item */
            $stmt = $conn->prepare(
                "DELETE FROM `$table` WHERE `$idColumn` = ?"
            );

            $stmt->execute([$id]);

            $conn->commit();
            delete_report_image(is_string($reportImagePath) ? $reportImagePath : null);
            foreach ($claimImagePaths as $claimImagePath) {
                delete_claim_image((string)$claimImagePath);
            }

            redirect('admin_items.php?saved=delete');
            exit;
        }

        throw new Exception('Invalid action.');

    } catch (Throwable $ex) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log('admin_items.php error: ' . $ex->getMessage());

        $error = $ex instanceof RuntimeException || $ex instanceof InvalidArgumentException
            ? $ex->getMessage()
            : 'The action could not be completed. Please check the item and database, then try again.';
    }
}

/* =========================================
   SEARCH AND FILTERS
========================================= */
$search = trim($_GET['search'] ?? '');
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = trim($_GET['status'] ?? 'all');

if (!in_array($typeFilter, ['all', 'lost', 'found'], true)) {
    $typeFilter = 'all';
}

$validStatusFilters = array_merge(['all'], $statusChoices);

if (!in_array($statusFilter, $validStatusFilters, true)) {
    $statusFilter = 'all';
}

/* =========================================
   FETCH LOST AND FOUND ITEMS
========================================= */
$items = [];

try {
    $queries = [];

    if ($typeFilter === 'all' || $typeFilter === 'lost') {
        $queries[] = "
            SELECT
                'lost' AS item_type,
                lost_item_id AS item_id,
                user_id,
                category,
                description,
                color,
                lost_location AS item_location,
                date_lost AS item_date,
                image_path,
                TRIM(status) AS status,
                created_at
            FROM lost_items
        ";
    }

    if ($typeFilter === 'all' || $typeFilter === 'found') {
        $queries[] = "
            SELECT
                'found' AS item_type,
                found_item_id AS item_id,
                user_id,
                category,
                description,
                color,
                found_location AS item_location,
                date_found AS item_date,
                image_path,
                TRIM(status) AS status,
                created_at
            FROM found_items
        ";
    }

    $sql = implode(' UNION ALL ', $queries);
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allItems as $item) {
        if (
            $statusFilter !== 'all' &&
            trim((string)$item['status']) !== $statusFilter
        ) {
            continue;
        }

        if ($search !== '') {
            $searchable = implode(' ', [
            $item['category'] ?? '',
                $item['description'] ?? '',
                $item['color'] ?? '',
                $item['item_location'] ?? '',
                adminItemCode(
                    $item['item_type'],
                    (int)$item['item_id']
                )
            ]);

            if (stripos($searchable, $search) === false) {
                continue;
            }
        }

        $items[] = $item;
    }

} catch (Throwable $ex) {
    error_log('admin_items.php fetch error: ' . $ex->getMessage());

    $error = 'Unable to load items. Please check your database table and column names.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="pup-logo-Polytechnic_University_of_the_Philippines.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Items | PUPick AI</title>

    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>

    <style>
        /* =====================================
           ADMIN LAYOUT
        ===================================== */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* =====================================
           SIDEBAR
        ===================================== */
        .side {
            width: 250px;
            min-width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            background: #800000;
            color: #ffffff;
            box-sizing: border-box;
            z-index: 1000;
            overflow-y: auto;
        }

        .side-top {
            display: flex;
            flex-direction: column;
        }

        .side .logo {
            padding: 22px 20px;
        }

        .side .logo h2 {
            margin: 0;
            color: #ffffff;
        }

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 15px;
        }

        .side-nav a,
        .side-bottom a {
            display: block;
            padding: 12px 15px;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .side-nav a:hover,
        .side-nav a.active,
        .side-bottom a:hover {
            background: #a52a2a;
            color: #ffd700;
        }

        .side-bottom {
            margin-top: auto;
            padding: 15px;
        }

        /* =====================================
           MAIN CONTENT
        ===================================== */
        .admin-main {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-width: 0;
            padding: 28px;
            box-sizing: border-box;
        }

        .items-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        .items-heading h1 {
            margin: 0;
        }

        /* =====================================
           SEARCH AND FILTERS
        ===================================== */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .filter-form input,
        .filter-form select,
        .filter-form button {
            padding: 10px 12px;
            border-radius: 7px;
        }

        .filter-form input {
            flex: 1 1 220px;
            min-width: 150px;
        }

        .filter-form select {
            min-width: 120px;
        }

        /* =====================================
           ITEMS TABLE
        ===================================== */
        .items-table-wrap {
            width: 100%;
            overflow-x: auto;
            border-radius: 10px;
        }

        .items-help {
            color: #666675;
            line-height: 1.6;
            margin: -8px 0 18px;
        }

        .items-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        .items-table td form {
            margin: 0;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 15px;
            text-transform: capitalize;
            white-space: nowrap;
        }

        /* =====================================
           ITEM ACTIONS
        ===================================== */
        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 210px;
        }

        .item-actions form {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .item-actions input,
        .item-actions select,
        .item-actions textarea,
        .item-actions button {
            width: 100%;
            box-sizing: border-box;
            padding: 8px;
            border-radius: 6px;
        }

        .item-actions textarea {
            resize: vertical;
            min-height: 55px;
        }

        .item-actions button {
            cursor: pointer;
        }

        .edit-form {
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .delete-form button {
            background: #a52222;
            color: #ffffff;
            border: 0;
        }

        /* =====================================
           MESSAGES
        ===================================== */
        .error-message {
            padding: 12px 15px;
            margin-bottom: 16px;
            border-radius: 7px;
            color: #8a1c1c;
            background: #ffe7e7;
        }

        .success-message {
            padding: 12px 15px;
            margin-bottom: 16px;
            border-radius: 7px;
            color: #246b40;
            background: #eaf7ef;
        }

        .empty-state {
            text-align: center;
            padding: 25px;
        }

        .items-results {
            margin: 0 0 12px;
            color: #666675;
            font-size: 13px;
        }

        .report-cell {
            min-width: 135px;
        }

        .report-code {
            display: block;
            font-weight: 800;
            white-space: nowrap;
        }

        .report-cell .badge {
            margin-top: 7px;
        }

        .item-summary {
            min-width: 200px;
        }

        .item-summary strong {
            display: block;
            margin-bottom: 5px;
        }

        .item-summary details summary {
            color: #741a36;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
        }

        .item-summary details p {
            max-width: 300px;
            margin: 8px 0 0;
            color: #666675;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .item-summary small {
            display: block;
            margin-top: 6px;
            color: #77717a;
        }

        .location-cell {
            min-width: 140px;
            overflow-wrap: anywhere;
        }

        .items-table .item-actions {
            min-width: 180px;
        }

        .items-table .item-actions details summary {
            cursor: pointer;
            color: #741a36;
            font-weight: 700;
        }

        /* =====================================
           RESPONSIVE LAYOUT
        ===================================== */
        @media (max-width: 900px) {
            .side {
                width: 210px;
                min-width: 210px;
            }

            .admin-main {
                margin-left: 210px;
                width: calc(100% - 210px);
                padding: 18px;
            }
        }

        @media (max-width: 650px) {
            .admin-layout {
                display: block;
            }

            .side {
                position: relative;
                width: 100%;
                min-width: 0;
                height: auto;
                min-height: auto;
            }

            .side-bottom {
                margin-top: 10px;
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<div class="admin-layout">

    <!-- =================================
         ADMIN SIDEBAR
    ================================== -->
    <aside class="side">

        <div class="side-top">

            <div class="logo">
                <h2>PUPick AI</h2>
            </div>

            <nav class="side-nav">
                <a href="admin.php">Dashboard</a>

                <a href="admin_items.php" class="active">
                    Manage Items
                </a>

                <a href="admin.php#claims">
                    Manage Claims
                </a>

                <a href="ai_matches.php">
                    AI Matches
                </a>
                <a href="chatbot.php">
                    Help Chat
                </a>
            </nav>

        </div>

        <div class="side-bottom">
            <a href="logout.php">Log Out</a>
        </div>

    </aside>

    <!-- =================================
         MAIN CONTENT
    ================================== -->
    <main class="admin-main">

        <div class="items-heading">
            <h1>Manage Items</h1>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error-message">
                <?= e($error) ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['saved']) && in_array($_GET['saved'], ['status', 'edit', 'delete'], true)): ?>
            <div class="success-message">
                <?= $_GET['saved'] === 'status'
                    ? 'Report status updated.'
                    : ($_GET['saved'] === 'edit' ? 'Report details saved.' : 'Report deleted.') ?>
            </div>
        <?php endif; ?>

        <!-- SEARCH AND FILTERS -->
        <p class="items-help">Review lost and found reports, update their status, or correct report details. Open = active report; matched = item reserved or connected; returned = handoff complete.</p>
        <form
            method="GET"
            action="admin_items.php"
            class="filter-form"
        >
            <input
                type="text"
                name="search"
                aria-label="Search items"
                placeholder="Search category, details, location, or report ID..."
                value="<?= e($search) ?>"
            >

            <select name="type" aria-label="Filter by item type">
                <option value="all"
                    <?= $typeFilter === 'all' ? 'selected' : '' ?>>
                    All Items
                </option>

                <option value="lost"
                    <?= $typeFilter === 'lost' ? 'selected' : '' ?>>
                    Lost Items
                </option>

                <option value="found"
                    <?= $typeFilter === 'found' ? 'selected' : '' ?>>
                    Found Items
                </option>
            </select>

            <select name="status" aria-label="Filter by status">
                <option value="all"
                    <?= $statusFilter === 'all' ? 'selected' : '' ?>>
                    All Statuses
                </option>

                <?php foreach ($statusChoices as $status): ?>
                    <option
                        value="<?= e($status) ?>"
                        <?= $statusFilter === $status ? 'selected' : '' ?>
                    >
                        <?= e(ucfirst($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Apply filters</button>

            <a class="admin-filter-reset" href="admin_items.php">Reset filters</a>
        </form>

        <datalist id="category-suggestions">
            <?php foreach (item_category_suggestions() as $suggestion): ?>
                <option value="<?= e($suggestion) ?>"><?= e($suggestion) ?>
            <?php endforeach; ?>
        </datalist>

        <!-- ITEMS TABLE -->
        <p class="items-results"><strong><?= count($items) ?></strong> reports shown</p>
        <div class="items-table-wrap">

            <table class="items-table">

                <thead>
                    <tr>
                        <th>Report</th>
                        <th>Item details</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Manage</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($items)): ?>

                    <tr>
                        <td colspan="5" class="empty-state" data-label="">
                            No items found.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($items as $item): ?>

                        <?php
                            $type = $item['item_type'];
                            $id = (int)$item['item_id'];

                            $code = adminItemCode($type, $id);
                            $currentStatus = trim((string)$item['status']);
                            $location = $item['item_location'] ?? '';
                            $date = $item['item_date'] ?? '';
                        ?>

                        <tr>

                            <td class="report-cell" data-label="Report">
                                <span class="report-code"><?= e($code) ?></span>
                                <span class="badge <?= e($type) ?>"><?= e(ucfirst($type)) ?> · <?= e($currentStatus) ?></span>
                            </td>

                            <td class="item-summary" data-label="Item details">
                                <strong><?= e($item['category'] ?? '') ?></strong>
                                <details>
                                    <summary>View description and color</summary>
                                    <p><?= e($item['description'] ?? '') ?></p>
                                    <?php if (!empty($item['color'])): ?>
                                        <small>Color: <?= e($item['color']) ?></small>
                                    <?php endif; ?>
                                </details>
                            </td>

                            <td class="location-cell" data-label="Location"><?= e($location) ?></td>

                            <td data-label="Date"><?= e($date) ?></td>

                            <td data-label="Manage">
                                <div class="item-actions">
                                    <form method="POST" action="admin_items.php" class="status-form">
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="type" value="<?= e($type) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <label class="visually-hidden" for="status-<?= e($type) ?>-<?= $id ?>">Change status for <?= e($code) ?></label>
                                        <select id="status-<?= e($type) ?>-<?= $id ?>" name="status" required>
                                            <?= adminStatusOptions($currentStatus, $statusChoices) ?>
                                        </select>
                                        <button type="submit" onclick="return confirm('Save this status for the report?')">Save status</button>
                                    </form>

                                    <details>
                                        <summary>Edit report details</summary>
                                        <form method="POST" action="admin_items.php" class="edit-form">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="type" value="<?= e($type) ?>">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <label>Category</label>
                                            <input type="text" name="category" list="category-suggestions" maxlength="80" required value="<?= e($item['category'] ?? '') ?>">
                                            <label>Description</label>
                                            <textarea name="description"><?= e($item['description'] ?? '') ?></textarea>
                                            <label>Color</label>
                                            <input type="text" name="color" value="<?= e($item['color'] ?? '') ?>">
                                            <label>Location</label>
                                            <input type="text" name="location" value="<?= e($location) ?>">
                                            <label>Date</label>
                                            <input type="date" name="item_date" value="<?= e($date) ?>">
                                            <label>Status</label>
                                            <select name="status" required><?= adminStatusOptions($currentStatus, $statusChoices) ?></select>
                                            <button type="submit">Save Changes</button>
                                        </form>
                                    </details>

                                    <form method="POST" action="admin_items.php" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="type" value="<?= e($type) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit">Delete report</button>
                                    </form>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </main>

</div>
</body>
</html>