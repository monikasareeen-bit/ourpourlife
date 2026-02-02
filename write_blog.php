<?php
session_start();
include "db.php";

/* ---------------------------
   AUTH CHECK
---------------------------- */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error'] = "You must be logged in to write a blog.";
    header("Location: login.php");
    exit();
}

/* ---------------------------
   SESSION USER DATA
---------------------------- */
$user_id = $_SESSION['user_id'];
$author = $_SESSION['username'] ?? 'Anonymous';

/* ---------------------------
   FETCH TOPICS
---------------------------- */
$topicResult = mysqli_query($conn, "SELECT topic_id, topic_name, emoji FROM topics ORDER BY topic_name");
if (!$topicResult) {
    $_SESSION['error'] = "Failed to fetch categories.";
    header("Location: home.php");
    exit();
}

/* ---------------------------
   FORM HANDLING
---------------------------- */
$error = '';
$category = '';
$title_val = '';
$content_val = '';
$topic_id_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $topic_id = (int) ($_POST['topic_id'] ?? 0);

    // Preserve values in case of error
    $title_val = $title;
    $content_val = $content;
    $topic_id_val = $topic_id;

    // Validate topic
    if ($topic_id > 0) {
        $topic_stmt = $conn->prepare("SELECT topic_name FROM topics WHERE topic_id = ?");
        $topic_stmt->bind_param("i", $topic_id);
        $topic_stmt->execute();
        $res = $topic_stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $category = $row['topic_name'];
        } else {
            $error = "Invalid category selected.";
        }
        $topic_stmt->close();
    } else {
        $error = "Please select a category.";
    }

    /* ---------------------------
       MEDIA UPLOAD
    ---------------------------- */
    $mediaPath = null;
    $uploadDir = 'blogs'; 
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    if (empty($error) && !empty($_FILES['media']['name'])) {
        $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm'];

        if (in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            $mediaPath = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($_FILES['media']['tmp_name'], $mediaPath)) {
                $error = "Media upload failed. Please try again.";
            }
        } else {
            $error = "Invalid file type. Allowed: jpg, jpeg, png, gif, mp4, webm";
        }
    }

    /* ---------------------------
       VALIDATE AND INSERT
    ---------------------------- */
    if (empty($error)) {
        if ($title && $content && $topic_id) {
            $stmt = $conn->prepare(
                "INSERT INTO blogs (user_id, author, topic_id, title, content, media) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("isisss", $user_id, $author, $topic_id, $title, $content, $mediaPath);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Blog published successfully!";
                header("Location: category.php?topic_id=" . $topic_id);
                exit();
            } else {
                $error = "Error saving blog. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "All fields are required.";
        }
    }
}

/* ---------------------------
   HEADER
---------------------------- */
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Write New Blog</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="page-center">

    <div class="write-container">
        <?php if ($category): ?>
            <div style="text-align:center;font-weight:700;color:#764ba2;margin-bottom:10px;">
                Category: <?= htmlspecialchars($category) ?>
            </div>
        <?php endif; ?>

        <h2>Write a New Blog ✨</h2>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <label>Blog Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($title_val) ?>" required>

            <label>Category</label>
            <select name="topic_id" required>
                <option value="">Select Category</option>
                <?php
                mysqli_data_seek($topicResult, 0); // reset pointer
                while ($topic = mysqli_fetch_assoc($topicResult)): ?>
                    <option value="<?= $topic['topic_id'] ?>" <?= $topic_id_val == $topic['topic_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($topic['emoji'] ?? '') ?> <?= htmlspecialchars($topic['topic_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label>Blog Content</label>
            <textarea name="content" required><?= htmlspecialchars($content_val) ?></textarea>

            <div class="file-upload">
                <label for="media" class="file-label">📎 Choose Image / Video</label>
                <span class="file-name" id="file-name">No file chosen</span>
                <input type="file" id="media" name="media" accept="image/*,video/*">
            </div>

            <button type="submit" class="btn-publish">🚀 Publish Blog</button>
        </form>
    </div>

    <div style="max-width:900px;margin:20px auto;">
        <a href="home.php">← Back to Home</a>
    </div>

    <script>
        document.getElementById("media").addEventListener("change", function () {
            document.getElementById("file-name").textContent =
                this.files.length ? this.files[0].name : "No file chosen";
        });
    </script>

</main>
</body>
</html>
