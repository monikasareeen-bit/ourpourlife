<?php
session_start();
include "db.php";

/* ---------------------------
   PAGINATION SETUP
---------------------------- */
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* ---------------------------
   FETCH BLOGS
---------------------------- */
$blogs = [];
$error = '';

try {
    $stmt = $conn->prepare("SELECT * FROM blogs ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $blogs[] = $row;
        }
    } else {
        $error = "Failed to fetch blogs from database.";
    }

    $stmt->close();
} catch (Exception $e) {
    $error = "An unexpected error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Blogs</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="inner-page">

<!-- NAVBAR -->
<nav class="navbar">
    <h1>✍️ My Blog</h1>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="view_blogs.php" class="active">Browse Blogs</a>
        <a href="category.php">Categories</a>
    </div>
</nav>

<!-- BLOG LIST -->
<div class="blogs-container">

    <h2 style="text-align:center; margin-bottom:30px;">
        📚 Browse All Blogs
    </h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="text-align:center; color:#991b1b; font-weight:bold;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (empty($blogs)): ?>
        <p style="text-align:center;">No blogs available.</p>
    <?php else: ?>
        <?php foreach ($blogs as $blog): ?>
            <div class="blog-card">
                <h2><?= htmlspecialchars($blog['title']) ?></h2>

                <div class="blog-meta">
                    Category: <strong><?= htmlspecialchars(ucfirst($blog['category'] ?? 'General')) ?></strong> |
                    <?= date("F j, Y", strtotime($blog['created_at'])) ?>
                </div>

                <div class="blog-excerpt">
                    <?= htmlspecialchars(substr(strip_tags($blog['content']), 0, 250)) ?>...
                </div>

                <a href="view_post.php?id=<?= (int)$blog['id'] ?>">
                    Read More →
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<hr>
<a href="home.php">← Back to Home</a>

</body>
</html>

<?php $conn->close(); ?>
