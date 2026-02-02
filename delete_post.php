<?php
session_start();
require "db.php";

$error = '';

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    $error = "You must be logged in to delete a post.";
}

$user_id = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'user';
$post_id = (int)($_GET['id'] ?? 0);

if (!$error && $post_id <= 0) {
    $error = "Invalid post ID.";
}

/* FETCH POST WITH TIME CHECK */
$post = null;

if (!$error) {
    $stmt = $conn->prepare("
        SELECT blog_id, topic_id, user_id,
        TIMESTAMPDIFF(HOUR, created_at, NOW()) AS hours_since_creation
        FROM blogs 
        WHERE blog_id=?
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        $error = "Post not found.";
    }
}

/* PERMISSION CHECK WITH 24-HOUR RESTRICTION */
if (!$error) {
    if ($role === 'admin') {
        // Admin can always delete
    } elseif ($post['user_id'] == $user_id) {
        // Check if within 24 hours
        if ($post['hours_since_creation'] >= 24) {
            $error = "You can only delete posts within 24 hours of creation. This post was created " . 
                     $post['hours_since_creation'] . " hours ago.";
        }
    } else {
        $error = "You are not allowed to delete this post.";
    }
}

/* DELETE PROCESS */
if (!$error) {

    /* DELETE BLOG LIKES ONLY */
    $stmt = $conn->prepare("
        DELETE FROM likes 
        WHERE target_id=? AND target_type='blog'
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();

    /* DELETE REPLY LIKES */
    $stmt = $conn->prepare("
        DELETE l FROM likes l
        INNER JOIN replies r ON l.target_id = r.reply_id
        WHERE r.blog_id=? AND l.target_type='reply'
    ");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();

    /* DELETE REPLIES */
    $stmt = $conn->prepare("DELETE FROM replies WHERE blog_id=?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $stmt->close();

    /* DELETE POST */
    $stmt = $conn->prepare("DELETE FROM blogs WHERE blog_id=?");
    $stmt->bind_param("i", $post_id);

    if (!$stmt->execute()) {
        $error = "Failed to delete the post. Please try again.";
    }

    $stmt->close();
}

/* ERROR DISPLAY */
if ($error) {
    include "header.php";
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
    include "footer.php";
    exit;
}

/* SUCCESS REDIRECT */
$_SESSION['success'] = "Blog deleted successfully.";
header("Location: category.php?topic_id=" . $post['topic_id']);
exit;