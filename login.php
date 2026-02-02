<?php
session_start();
include "db.php";

$error = "";
$signupSuccess = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {

        // ✅ FETCH ROLE ALSO
        $stmt = $conn->prepare(
            "SELECT user_id, username, password, role, status FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // 🚫 BLOCKED USER CHECK
            if ($user['status'] === 'blocked') {
                $error = "🚫 Your account has been blocked by the administrator.";
            }
            // 🔑 PASSWORD CHECK
            elseif (password_verify($password, $user['password'])) {

                session_regenerate_id(true);
                $_SESSION = [];

                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Account does not exist.";
        }

    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - My Blog</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">

    <div class="auth-container">
        <div class="auth-box">

            <?php if ($signupSuccess): ?>
                <div class="auth-success">
                    🎉 Account created successfully! Please login.
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="auth-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <h1 class="auth-title">Login</h1>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email address" required>
                </div>
                
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Login</button>

                <div class="auth-links">
                    <a href="forgot_password.php">Forget your Password?</a>
                </div>

                <div class="auth-footer">
                    Don't have an account?
                    <a href="signup.php">Sign up here</a>
                </div>
                
                 <div class="auth-back"> <br>
                    <a href="home.php" class="back-home"> 🏠 Back to Home </a>
                </div>
            </form>

        </div>

    </div>
</body>

</html>
