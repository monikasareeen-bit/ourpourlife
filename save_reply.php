<?php
session_start();
require 'db.php';

/* ---------------------------
   CHECK LOGIN
---------------------------- */
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to reply.";
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* ---------------------------
   GET POST DATA
---------------------------- */
$blog_id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
$parent_id = isset($_POST['parent_reply_id']) && $_POST['parent_reply_id'] !== '' ? (int)$_POST['parent_reply_id'] : null;
$reply_text = trim($_POST['reply'] ?? '');

/* ---------------------------
   VALIDATE INPUT
---------------------------- */
if ($blog_id <= 0) {
    $_SESSION['error'] = "Invalid blog ID.";
    header('Location: blog.php?id=' . $blog_id);
    exit;
}

if ($reply_text === '') {
    $_SESSION['error'] = "Reply cannot be empty.";
    header('Location: blog.php?id=' . $blog_id . '&reply=1');
    exit;
}

/* ---------------------------
   HANDLE MEDIA UPLOAD
---------------------------- */
$mediaPath = null;
$uploadDir = 'replies';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (!empty($_FILES['reply_file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['reply_file']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','mp4','webm'];

    if (in_array($ext, $allowed)) {
        $filename = uniqid() . '.' . $ext;
        $mediaPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($_FILES['reply_file']['tmp_name'], $mediaPath)) {
            $_SESSION['error'] = "Failed to upload file.";
            header('Location: blog.php?id=' . $blog_id . '&reply=1');
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid file type. Allowed: jpg, jpeg, png, gif, mp4, webm.";
        header('Location: blog.php?id=' . $blog_id . '&reply=1');
        exit;
    }
}

/* ---------------------------
   INSERT REPLY
---------------------------- */
$stmt = $conn->prepare("
    INSERT INTO replies (blog_id, user_id, parent_id, reply_text, media, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$stmt->bind_param("iiiss", $blog_id, $user_id, $parent_id, $reply_text, $mediaPath);

if ($stmt->execute()) {
    $_SESSION['success'] = "Reply posted successfully!";
} else {
    $_SESSION['error'] = "Failed to post reply. Please try again.";
}

$stmt->close();

/* ---------------------------
   REDIRECT BACK TO BLOG
---------------------------- */
header('Location: blog.php?id=' . $blog_id . ($parent_id ? '&reply=' . $parent_id : '&reply=1'));
exit;
?>
