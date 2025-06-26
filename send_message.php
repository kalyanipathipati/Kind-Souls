<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// Database connection
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate and sanitize inputs
$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate receiver_id and message
if ($receiver_id <= 0) {
    die("Invalid receiver ID.");
}

if (empty($message)) {
    die("Message cannot be empty.");
}

// Escape the message to prevent SQL injection
$message = $conn->real_escape_string($message);

$check_block = $conn->prepare("SELECT * FROM blocked_users WHERE 
    (blocker_id = ? AND blocked_id = ?) OR 
    (blocker_id = ? AND blocked_id = ?)");
$check_block->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
$check_block->execute();
$check_result = $check_block->get_result();
if ($check_result->num_rows > 0) {
    die("Message sending failed: You cannot chat with this user.");
}
$check_block->close();


// Insert the message into the database
$query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $user_id, $receiver_id, $message);

if ($stmt->execute()) {
    echo "Message sent successfully.";
} else {
    die("Error sending message: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>