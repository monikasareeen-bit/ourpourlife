<?php
require "admin_check.php";
require "../db.php";

$error = '';

$topic_id = (int)($_GET['id'] ?? 0);

/* VALIDATE ID */
if ($topic_id <= 0) {
    $error = "Invalid topic ID.";
}

/* CHECK IF TOPIC EXISTS */
if (!$error) {
    $stmt = $conn->prepare("SELECT topic_id FROM topics WHERE topic_id=?");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $topic = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$topic) {
        $error = "Topic not found.";
    }
}

/* CHECK IF TOPIC HAS BLOGS */
if (!$error) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM blogs WHERE topic_id=?");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    if ($count > 0) {
        $error = "This topic has blogs. Please delete or move blogs first.";
    }
}

/* DELETE TOPIC */
if (!$error) {
    $stmt = $conn->prepare("DELETE FROM topics WHERE topic_id=?");

    if (!$stmt->bind_param("i", $topic_id) || !$stmt->execute()) {
        $error = "Failed to delete topic. Please try again.";
    }

    $stmt->close();
}

/* ERROR DISPLAY */
if ($error) {
    include "admin_header.php";
    ?>
    <main class="page-error">
        <div class="error-message">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <div style="text-align:center; margin-top:20px;">
            <a href="admin_topics.php">← Back to Topics</a>
        </div>
    </main>
    <?php
    include "admin_footer.php";
    exit;
}

/* SUCCESS REDIRECT */
header("Location: admin_topics.php?success=topic_deleted");
exit;
