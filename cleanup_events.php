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

// Mark past events
$currentDateTime = date('Y-m-d H:i:s');
$stmt = $conn->prepare("UPDATE events SET is_past = 1 WHERE end_datetime < ? AND is_past = 0");
$stmt->bind_param("s", $currentDateTime);
$stmt->execute();
$stmt->close();

// Delete events older than 7 days past their end date
$deleteBeforeDate = date('Y-m-d H:i:s', strtotime('-7 days'));
$stmt = $conn->prepare("DELETE FROM events WHERE end_datetime < ?");
$stmt->bind_param("s", $deleteBeforeDate);
$stmt->execute();
$stmt->close();

$conn->close();
?>