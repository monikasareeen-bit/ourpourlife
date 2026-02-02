<?php
require "db.php";
include "header.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {

        /* CHECK EMAIL (silent) */
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));

            $stmt = $conn->prepare("
                UPDATE users 
                SET reset_token = ?, reset_expires = ?
                WHERE email = ?
            ");
            $stmt->bind_param("sss", $token, $expires, $email);
            $stmt->execute();
            $stmt->close();

            /*
             * EMAIL SENDING (later)
             * $resetLink = "https://yourdomain.com/reset_password.php?token=$token";
             */

            // TEMP: for testing only
            $success = "Password reset link generated (testing mode):<br>
                        <a href='reset_password.php?token=$token'>Reset Password</a>";
        } else {
            // SECURITY: same message even if email doesn't exist
            $success = "If this email exists, a password reset link has been sent.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<div class="auth-container">

    <div class="auth-box">

        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-text">
            Enter your registered email to receive a password reset link.
        </p>

        <?php if ($error): ?>
            <div class="error-message">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                ✅ <?= $success ?>
            </div>
        <?php endif; ?>

        <form method="post">

            <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required
            >

            <button type="submit" class="btn btn-primary">
                Send Reset Link
            </button>

        </form>

        <div class="auth-links">
            <a href="login.php">← Back to Login</a>
        </div>

    </div>

</div>

</body>
</html>
