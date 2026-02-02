<?php
session_start();
include "db.php";
require 'admin_check.php'; // ensure only admin can do this

/* ---------------------------
   VALIDATE INPUTS
---------------------------- */
if (!isset($_GET['id'], $_GET['action'])) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);
$action = $_GET['action'];

/* ---------------------------
   PREVENT SELF ACTION
---------------------------- */
if ($user_id === ($_SESSION['user_id'] ?? 0)) {
    $_SESSION['error'] = "You cannot perform this action on yourself.";
    header("Location: users.php");
    exit;
}

/* ---------------------------
   DECIDE NEW STATUS
---------------------------- */
if ($action === 'block') {
    $new_status = 'blocked';
} elseif ($action === 'unblock') {
    $new_status = 'active';
} else {
    $_SESSION['error'] = "Unknown action.";
    header("Location: users.php");
    exit;
}

/* ---------------------------
   FETCH USER AND CHECK ROLE
---------------------------- */
$check = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

/* ---------------------------
   PREVENT BLOCKING ADMINS
---------------------------- */
if ($user['role'] === 'admin') {
    $_SESSION['error'] = "Cannot modify an admin user.";
    header("Location: users.php");
    exit;
}

/* ---------------------------
   UPDATE STATUS
---------------------------- */
$stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
$stmt->bind_param("si", $new_status, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "User status updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update user status. Please try again.";
}

$stmt->close();

/* ---------------------------
   REDIRECT BACK
---------------------------- */
header("Location: users.php");
exit;
?>
