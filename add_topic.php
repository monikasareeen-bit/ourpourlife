<?php
require "admin_check.php";
require "../db.php";

$msg = "";
if ($_POST) {
    $stmt = $conn->prepare("INSERT INTO topics (topic_name, emoji, color) VALUES (?,?,?)");
    $stmt->bind_param("sss", $_POST['topic'], $_POST['emoji'], $_POST['color']);
    $stmt->execute();
    $msg = "✅ Topic added";
}
?>
<!DOCTYPE html>
<html>

<body>
    <?php include "layout/header.php"; ?>
    <h2>Add Topic</h2>
    <?= $msg ?>
    <form method="post">
        <input name="topic" required placeholder="Topic">
        <input name="emoji" placeholder="Emoji">
        <input name="color" placeholder="CSS Gradient">
        <button>Add</button>
    </form>
    </div>
</body>

</html>