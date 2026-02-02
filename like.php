<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'login']);
    exit;
}

$target_id   = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$target_type = $_POST['target_type'] ?? '';

if ($target_id <= 0 || !in_array($target_type, ['blog','reply'])) {
    echo json_encode(['error' => 'invalid']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* Toggle like */
$stmt = $conn->prepare("
    SELECT like_id FROM likes 
    WHERE user_id = ? AND target_id = ? AND target_type = ?
");
$stmt->bind_param("iis", $user_id, $target_id, $target_type);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Unlike
    $del = $conn->prepare("
        DELETE FROM likes 
        WHERE user_id = ? AND target_id = ? AND target_type = ?
    ");
    $del->bind_param("iis", $user_id, $target_id, $target_type);
    $del->execute();
    $status = 'unliked';
} else {
    // Like
    $ins = $conn->prepare("
        INSERT INTO likes (user_id, target_id, target_type)
        VALUES (?, ?, ?)
    ");
    $ins->bind_param("iis", $user_id, $target_id, $target_type);
    $ins->execute();
    $status = 'liked';
}

/* Get count */
$countStmt = $conn->prepare("
    SELECT COUNT(*) c FROM likes
    WHERE target_id = ? AND target_type = ?
");
$countStmt->bind_param("is", $target_id, $target_type);
$countStmt->execute();
$count = $countStmt->get_result()->fetch_assoc()['c'];

echo json_encode([
    'status' => $status,
    'count'  => $count
]);
