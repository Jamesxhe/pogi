<?php
require_once "db.php";
require_once "helpers.php";

if (!empty($_SESSION['user_id'])) {
    redirect(($_SESSION['role'] ?? 'student') === 'admin' ? 'admin.php' : 'dashboard.php');
}

$errors = [];
$fullName = "";
$studentNumber = "";
$email = "";
$requestedRole = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $studentNumber = trim($_POST['student_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $requestedRole = $_POST['requested_role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!in_array($requestedRole, ['student', 'admin'], true)) {
        $errors[] = "Choose Student or Administrator access.";
    }
    if ($fullName === '' || $email === '' || $password === '') {
        $errors[] = "Please complete all required fields.";
    }
    if ($requestedRole === 'student' && $studentNumber === '') {
        $errors[] = "Enter your student number.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (!$errors) {
        $studentNumberValue = $studentNumber !== '' ? $studentNumber : null;
        $stmt = $conn->prepare(
            "SELECT user_id FROM users
             WHERE email = ? OR (? IS NOT NULL AND student_number = ?)
             LIMIT 1"
        );
        $stmt->execute([$email, $studentNumberValue, $studentNumberValue]);
        if ($stmt->fetch()) {
            $errors[] = "That email or student number is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users
                    (full_name, student_number, email, password_hash, role)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $fullName,
                $studentNumberValue,
                $email,
                $hash,
                $requestedRole
            ]);
            redirect("login.php?registered=1&role=" . urlencode($requestedRole));
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
    <title>Create account | PUPick AI</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js" defer></script>
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="index.php"><img class="brand-mark" src="pup-logo-Polytechnic_University_of_the_Philippines.png" alt=""><span>PUPick <b>AI</b></span></a>
        <p class="eyebrow">PUP LOST AND FOUND</p>
        <h1>Create your account</h1>
        <p class="muted">Create a student or administrator account. Administrator access is granted immediately.</p>

        <?php if ($errors): ?>
            <div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" action="register.php">
            <label for="requested_role">Account type</label>
            <select id="requested_role" name="requested_role" required>
                <option value="student" <?= $requestedRole === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="admin" <?= $requestedRole === 'admin' ? 'selected' : '' ?>>Administrator (full access)</option>
            </select>
            <p class="muted" style="font-size:12px;line-height:1.5">Only select Administrator if you are authorized. This option grants full admin access without approval.</p>

            <label for="full_name">Full name</label>
            <input id="full_name" name="full_name" type="text" maxlength="100" value="<?= e($fullName) ?>" required>

            <label for="student_number">Student number <span class="muted">(required for students)</span></label>
            <input id="student_number" name="student_number" type="text" maxlength="30" value="<?= e($studentNumber) ?>">

            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="150" value="<?= e($email) ?>" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" minlength="8" required>

            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" name="confirm_password" type="password" minlength="8" required>

            <button class="btn primary full" type="submit">Create account</button>
        </form>
        <p class="auth-footer">Already have an account? <a href="login.php">Log in</a></p>
    </main>
</body>
</html>
