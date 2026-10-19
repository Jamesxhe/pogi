<?php
require_once "db.php";
require_once "helpers.php";

if (!empty($_SESSION['user_id'])) {
    redirect(($_SESSION['role'] ?? 'student') === 'admin' ? 'admin.php' : 'dashboard.php');
}

$error = "";
$email = "";
$selectedRole = in_array($_GET['role'] ?? '', ['student', 'admin'], true)
    ? $_GET['role']
    : 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? '';

    if (!in_array($selectedRole, ['student', 'admin'], true)) {
        $error = "Choose whether you are signing in as a student or administrator.";
    } elseif ($email === '' || $password === '') {
        $error = "Enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password_hash, role
             FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['role'] !== $selectedRole) {
                $error = "This account does not match the selected account type.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                redirect($user['role'] === 'admin' ? "admin.php" : "dashboard.php");
            }
        } else {
            $error = "Incorrect email or password.";
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
    <title>Log in | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="index.php"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a>
        <p class="eyebrow">PUP LOST AND FOUND</p>
        <h1>Welcome back</h1>
        <p class="muted">Log in to manage your lost-and-found reports.</p>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert success">
                <?php if ($selectedRole === 'admin'): ?>
                    Administrator account created. Sign in using the administrator account type.
                <?php else: ?>
                    Account created. You can now log in.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">
            <label for="role">Sign in as</label>
            <select id="role" name="role" required>
                <option value="student" <?= $selectedRole === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>

            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="150" value="" autocomplete="off" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" value="" autocomplete="new-password" required>

            <button class="btn primary full" type="submit">Log in</button>
        </form>
        <p class="auth-footer">New to PUPick AI? <a href="register.php">Create an account</a></p>
    </main>
</body>
</html>
