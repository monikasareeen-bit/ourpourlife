<?php
require "admin_check.php";
require "../db.php";

$result = $conn->query("
    SELECT t.*, COUNT(b.blog_id) total
    FROM topics t
    LEFT JOIN blogs b ON b.topic_id = t.topic_id
    GROUP BY t.topic_id
");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Topics</title>
</head>

<body>

    <?php include "layout/header.php"; ?>

    <h2>Manage Topics</h2>
    <a href="add_topic.php">➕ Add Topic</a>

    <table>
        <tr>
            <th>Emoji</th>
            <th>Name</th>
            <th>Blogs</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['emoji'] ?></td>
                <td><?= $row['topic_name'] ?></td>
                <td><?= $row['total'] ?></td>
                <td>
                    <a href="edit_topic.php?id=<?= $row['topic_id'] ?>">✏️</a>
                    <a href="delete_topic.php?id=<?= $row['topic_id'] ?>" onclick="return confirm('Delete?')">🗑</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    </div>
</body>

</html>