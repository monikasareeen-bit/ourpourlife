<?php
session_start();
require 'db.php';

$error = '';

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    $error = "You must be logged in to delete a reply.";
}

$user_id = $_SESSION['user_id'] ?? 0;
$role    = $_SESSION['role'] ?? 'user';

$reply_id = (int)($_GET['id'] ?? 0);
if (!$error && $reply_id <= 0) {
    $error = "Invalid reply ID.";
}

/* FETCH REPLY WITH TIME CHECK */
$reply = null;

if (!$error) {
    $stmt = $conn->prepare("
        SELECT reply_id, user_id, blog_id,
        TIMESTAMPDIFF(HOUR, created_at, NOW()) AS hours_since_creation
        FROM replies 
        WHERE reply_id=?
    ");
    $stmt->bind_param("i", $reply_id);
    $stmt->execute();
    $reply = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reply) {
        $error = "Reply not found.";
    }
}

/* PERMISSION CHECK WITH 24-HOUR RESTRICTION */
if (!$error) {
    if ($role === 'admin') {
        // Admin can always delete
    } elseif ($reply['user_id'] == $user_id) {
        // Check if within 24 hours
        if ($reply['hours_since_creation'] >= 24) {
            $error = "You can only delete replies within 24 hours of creation. This reply was created " . 
                     $reply['hours_since_creation'] . " hours ago.";
        }
    } else {
        $error = "You are not allowed to delete this reply.";
    }
}

/* DELETE PROCESS */
if (!$error) {

    /* DELETE REPLY LIKES */
    $stmt = $conn->prepare("
        DELETE FROM likes 
        WHERE target_id=? AND target_type='reply'
    ");
    $stmt->bind_param("i", $reply_id);
    $stmt->execute();
    $stmt->close();

    /* DELETE REPLY */
    $stmt = $conn->prepare("DELETE FROM replies WHERE reply_id=?");
    $stmt->bind_param("i", $reply_id);

    if (!$stmt->execute()) {
        $error = "Failed to delete reply. Please try again.";
    }

    $stmt->close();
}

/* ERROR DISPLAY */
if ($error) {
    include 'header.php';
    ?>
    <main class="page-error">
        <div class="error-message">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <div style="text-align:center; margin-top:20px;">
            <a href="javascript:history.back()">← Go Back</a>
        </div>
    </main>
    <?php
    include 'footer.php';
    exit;
}

/* SUCCESS REDIRECT */
$_SESSION['success'] = "Reply deleted successfully.";
header("Location: blog.php?id=" . $reply['blog_id']);
exit;