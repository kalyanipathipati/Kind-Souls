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

if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$event_id = $_GET['id'];

// Check if event exists
$stmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: events.php");
    exit;
}
$stmt->close();

// Check if user is already participating
$stmt = $conn->prepare("SELECT id FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    header("Location: events.php");
    exit;
}
$stmt->close();

// Join the event
$stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'interested')");
$stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

header("Location: events.php");
exit;
?>