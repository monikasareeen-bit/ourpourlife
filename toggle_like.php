<?php
session_start();
require "db.php";

/* ---------------------------
   CHECK LOGIN
---------------------------- */
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => false,
        "error" => "You must be logged in to like."
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$target_type = $_POST['target_type'] ?? ''; // blog | reply | post

/* ---------------------------
   VALIDATE INPUT
---------------------------- */
if ($target_id <= 0 || !in_array($target_type, ['blog', 'reply', 'post'])) {
    echo json_encode([
        "status" => false,
        "error" => "Invalid target."
    ]);
    exit;
}

try {
    /* ---------------------------
       CHECK EXISTING LIKE
    ---------------------------- */
    $check = $conn->prepare("
        SELECT like_id FROM likes 
        WHERE user_id=? AND target_id=? AND target_type=?
    ");
    $check->bind_param("iis", $user_id, $target_id, $target_type);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        // UNLIKE
        $del = $conn->prepare("
            DELETE FROM likes 
            WHERE user_id=? AND target_id=? AND target_type=?
        ");
        $del->bind_param("iis", $user_id, $target_id, $target_type);
        $del->execute();
    } else {
        // LIKE
        $ins = $conn->prepare("
            INSERT INTO likes (user_id, target_id, target_type) 
            VALUES (?,?,?)
        ");
        $ins->bind_param("iis", $user_id, $target_id, $target_type);
        $ins->execute();
    }

    /* ---------------------------
       GET UPDATED LIKE COUNT
    ---------------------------- */
    $count = $conn->prepare("
        SELECT COUNT(*) AS c FROM likes 
        WHERE target_id=? AND target_type=?
    ");
    $count->bind_param("is", $target_id, $target_type);
    $count->execute();
    $c = $count->get_result()->fetch_assoc();

    echo json_encode([
        "status" => true,
        "count" => (int)$c['c']
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status" => false,
        "error" => "Something went wrong. Please try again."
    ]);
}
?>
