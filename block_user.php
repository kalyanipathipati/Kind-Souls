<?php
session_start();
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$action = $_POST['action'];

if ($action === "block") {
    // Prevent duplicate blocks
    $stmt = $conn->prepare("INSERT IGNORE INTO blocked_users (blocker_id, blocked_id, blocked_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $user_id, $receiver_id);
    $stmt->execute();
} elseif ($action === "unblock") {
    $stmt = $conn->prepare("DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?");
    $stmt->bind_param("ii", $user_id, $receiver_id);
    $stmt->execute();
}
$stmt->close();
$conn->close();

// Redirect back to chat
header("Location: chat.php?receiver_id=$receiver_id");
exit();
?>
