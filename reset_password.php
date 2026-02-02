<?php
include "db.php";
include "header.php";

if (!isset($_GET['token'])) {
    $error =("Invalid request");
}

$token = $_GET['token'];

// Check token
$stmt = $conn->prepare(
    "SELECT user_id FROM users WHERE reset_token=? AND reset_expires > NOW()"
);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $error =("Token expired or invalid");
}

$user = $result->fetch_assoc();

if (isset($_POST['reset'])) {

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // update query example
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();

        $success = "Password reset successfully";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

<div class="auth-container">

    <div class="auth-box">

        <h2 class="auth-title">Reset Password</h2>
        


        
<form method="POST">
    <input type="password" name="password" required placeholder="New password">

    <input type="password" name="confirm_password" required placeholder="Confirm new password">

    <button type="submit" name="reset">Reset Password</button>
</form>


        <div class="auth-links">
            <a href="login.php">← Back to Login</a>
        </div>

    </div>

</div>

</body>
</html>


