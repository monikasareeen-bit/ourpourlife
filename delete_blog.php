<?php
session_start();
require "db.php";

/* DEFAULT ERROR */
$error = '';

/* AUTH CHECK */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $error = "You must be logged in to delete a blog.";
}

/* VALIDATE BLOG ID */
$blog_id = (int)($_GET['id'] ?? 0);
if (!$error && $blog_id <= 0) {
    $error = "Invalid blog ID.";
}

/* FETCH BLOG (ownership check) */
if (!$error) {
    $stmt = $conn->prepare("SELECT user_id FROM blogs WHERE blog_id=?");
    $stmt->bind_param("i", $blog_id);
    $stmt->execute();
    $blog = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$blog) {
        $error = "Blog not found.";
    }
}

/* PERMISSION CHECK */
if (
    !$error &&
    $blog['user_id'] != $_SESSION['user_id'] &&
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    $error = "You are not allowed to delete this blog.";
}

/* DELETE BLOG */
if (!$error) {
    $stmt = $conn->prepare("DELETE FROM blogs WHERE blog_id=?");
    $stmt->bind_param("i", $blog_id);

    if (!$stmt->execute()) {
        $error = "Failed to delete blog. Please try again.";
    }

    $stmt->close();
}

/* REDIRECT OR SHOW ERROR */
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

/* SUCCESS */
header("Location: home.php?success=deleted");
exit;
