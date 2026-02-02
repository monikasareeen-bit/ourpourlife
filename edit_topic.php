<?php
require "admin_check.php";
require "../db.php";

$error = '';
$success = '';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $error = "Invalid topic ID.";
}

/* FETCH TOPIC */
$topic = null;

if (!$error) {
    $stmt = $conn->prepare("
        SELECT topic_id, topic_name, emoji, color
        FROM topics
        WHERE topic_id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $topic = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$topic) {
        $error = "Topic not found.";
    }
}

/* UPDATE TOPIC */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $topic_name = trim($_POST['topic'] ?? '');
    $emoji      = trim($_POST['emoji'] ?? '');
    $color      = trim($_POST['color'] ?? '');

    if ($topic_name === '') {
        $error = "Topic name cannot be empty.";
    } else {

        $stmt = $conn->prepare("
            UPDATE topics 
            SET topic_name = ?, emoji = ?, color = ?
            WHERE topic_id = ?
        ");
        $stmt->bind_param("sssi", $topic_name, $emoji, $color, $id);

        if ($stmt->execute()) {
            header("Location: admin_topics.php?updated=1");
            exit;
        } else {
            $error = "Failed to update topic.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Topic</title>
    <link rel="stylesheet" href="admin.css">
</head>

<body>

<?php include "layout/header.php"; ?>

<div class="admin-container">

    <h2>Edit Topic</h2>

    <?php if ($error): ?>
        <div class="error-message">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <p><a href="admin_topics.php">← Back to Topics</a></p>

    <?php else: ?>

        <form method="post" class="admin-form">

            <label>Topic Name</label>
            <input
                type="text"
                name="topic"
                value="<?= htmlspecialchars($topic['topic_name']) ?>"
                required
            >

            <label>Emoji</label>
            <input
                type="text"
                name="emoji"
                value="<?= htmlspecialchars($topic['emoji']) ?>"
                placeholder="✨"
            >

            <label>Color</label>
            <input
                type="text"
                name="color"
                value="<?= htmlspecialchars($topic['color']) ?>"
                placeholder="#ff69b4"
            >

            <button type="submit">Update Topic</button>
            <a href="admin_topics.php" class="btn-secondary">Cancel</a>

        </form>

    <?php endif; ?>

</div>

</body>
</html>
