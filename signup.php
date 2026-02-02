<?php
session_start();
include 'db.php';

/* ---------------------------
   REDIRECT IF ALREADY LOGGED IN
---------------------------- */
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

/* ---------------------------
   HANDLE FORM SUBMISSION
---------------------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $cpassword = $_POST["cpassword"] ?? '';

    /* ---------------------------
       VALIDATION
    ---------------------------- */
    if ($username === '' || $email === '' || $password === '' || $cpassword === '') {
        $_SESSION['error'] = "All fields are required.";
        header('Location: signup.php');
        exit;
    }

    if ($password !== $cpassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: signup.php');
        exit;
    }

    /* ---------------------------
       CHECK IF EMAIL ALREADY REGISTERED
    ---------------------------- */
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Email already registered.";
        $stmt->close();
        header('Location: signup.php');
        exit;
    }
    $stmt->close();

    /* ---------------------------
       HASH PASSWORD AND INSERT USER
    ---------------------------- */
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (email, username, password, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $email, $username, $hashedPass);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Signup successful! You can now login.";
        $stmt->close();
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
        $stmt->close();
        header('Location: signup.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - My Blog</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="auth-body">
    <div class="auth-container">

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="auth-error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="auth-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="auth-box">
            <h1 class="auth-title">Signup</h1>

            <form action="signup.php" method="post" class="auth-form">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>

                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>

                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <div class="form-group">
                    <input type="password" name="cpassword" placeholder="Confirm Password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Signup</button>

                <div class="auth-footer">
                    Already have an account? <a href="login.php"> : Login here </a>
                </div>

                <div class="auth-back">
                    <a href="home.php" class="back-home"> 🏠 Back to Home </a>

                </div>
            </form>
        </div>

    </div>
</body>

</html>
