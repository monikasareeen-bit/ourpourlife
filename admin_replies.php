<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$query = "
SELECT 
    r.reply_id,
    r.reply_text,
    r.created_at,
    r.status,
    u.username,
    b.title
FROM replies r
JOIN users u ON r.user_id = u.user_id
JOIN blogs b ON r.blog_id = b.blog_id
ORDER BY r.created_at DESC
";


$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Replies</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body class="admin-body">

<h1 class="admin-title">Manage Replies</h1>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Reply</th>
            <th>User</th>
            <th>Post</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
        <tr>
            <td><?= $row['reply_id'] ?></td>

            <td>
                <?= htmlspecialchars(substr($row['reply_text'], 0, 60)) ?>…
            </td>

            <td><?= htmlspecialchars($row['username']) ?></td>

            <td><?= htmlspecialchars($row['title']) ?></td>

            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>

            <td>
                <a href="delete_reply.php?id=<?= $row['reply_id'] ?>" class="btn-delete">Delete</a>

                <?php if ($row['status'] === 'active'): ?>
                    <a href="block_reply.php?id=<?= $row['reply_id'] ?>" class="btn-warning">Block</a>
                <?php else: ?>
                    <a href="unblock_reply.php?id=<?= $row['reply_id'] ?>" class="btn-success">Unblock</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
