<?php
session_start();
require 'db.php';
include 'header.php';

$error = '';

/* AUTH CHECK */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'] ?? 'user';

$blog_id = (int)($_GET['id'] ?? 0);
if ($blog_id <= 0) {
    $error = "Invalid blog ID.";
}

/* FETCH BLOG */
$blog = null;

if (!$error) {
    $stmt = $conn->prepare("SELECT * FROM blogs WHERE blog_id=?");
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
    $blog['user_id'] != $user_id &&
    $role !== 'admin'
) {
    $error = "You are not allowed to edit this blog.";
}

/* HANDLE UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $title   = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if ($title === '' || $content === '') {
        $error = "Title and content cannot be empty.";
    } else {
        $stmt = $conn->prepare("
            UPDATE blogs 
            SET title=?, content=? 
            WHERE blog_id=?
        ");
        $stmt->bind_param("ssi", $title, $content, $blog_id);

        if ($stmt->execute()) {
            header("Location: blog.php?id=" . $blog_id . "&success=updated");
            exit;
        } else {
            $error = "Failed to update blog. Please try again.";
        }

        $stmt->close();
    }
}
?>

<main class="auth-page">

<?php if ($error): ?>

    <div class="error-message">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <div style="text-align:center; margin-top:20px;">
        <a href="javascript:history.back()">← Go Back</a>
    </div>

<?php else: ?>

    <form method="post" class="auth-box">
        <h2>Edit Blog</h2>

        <input
            type="text"
            name="title"
            value="<?= htmlspecialchars($blog['title']) ?>"
            required
        >

        <textarea
            name="content"
            rows="8"
            required
        ><?= htmlspecialchars($blog['content']) ?></textarea>

        <button type="submit">Update</button>
    </form>

<?php endif; ?>

</main>

<?php include 'footer.php'; ?>
