<?php
session_start();
require_once "../db.php";

/* ---------------------------
   ADMIN PROTECTION
---------------------------- */
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "You must be an admin to perform this action.";
    header("Location: ../login.php");
    exit();
}

/* ---------------------------
   GET INPUTS
---------------------------- */
$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if ($id <= 0) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: user.php");
    exit();
}

/* ---------------------------
   PREVENT BLOCKING ADMINS
---------------------------- */
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    $stmt->close();
    header("Location: user.php");
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

if ($user['role'] === 'admin' && $action === 'block') {
    $_SESSION['error'] = "Cannot block an admin user.";
    header("Location: user.php");
    exit();
}

/* ---------------------------
   PERFORM ACTION
---------------------------- */
try {
    if ($action === 'block') {
        $stmt = $conn->prepare("UPDATE users SET status='blocked' WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "User blocked successfully.";
    } elseif ($action === 'unblock') {
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "User unblocked successfully.";
    } else {
        $_SESSION['error'] = "Unknown action.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "An error occurred. Please try again.";
}

/* ---------------------------
   REDIRECT BACK
---------------------------- */
header("Location: user.php");
exit();
?>
