<?php
session_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP username
define('DB_PASS', '');     // Default XAMPP password is empty
define('DB_NAME', 'project'); // Your database name

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['event_id']) || !isset($_GET['user_id'])) {
    header("Location: events.php");
    exit;
}

$event_id = $_GET['event_id'];
$user_id = $_GET['user_id'];

// Verify the current user is the event host
$stmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event || $event['user_id'] != $_SESSION['user_id']) {
    header("Location: events.php");
    exit;
}

// Remove the participant
$stmt = $conn->prepare("DELETE FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$stmt->close();

header("Location: event_participants.php?id=" . $event_id);
exit;
?>