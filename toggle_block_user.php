<?php
session_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

if (!isset($_SESSION['user_id']) {
    die("Unauthorized access");
}

$blocker_id = $_SESSION['user_id'];
$blocked_id = $_POST['blocked_id'];

// Check if the user is already blocked
$check_query = "SELECT * FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $blocker_id, $blocked_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Unblock the user
    $delete_query = "DELETE FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $blocker_id, $blocked_id);
    $delete_stmt->execute();
    echo "unblocked";
} else {
    // Block the user
    $insert_query = "INSERT INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $blocker_id, $blocked_id);
    $insert_stmt->execute();
    echo "blocked";
}
?>