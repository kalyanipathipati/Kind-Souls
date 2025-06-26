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

$event_id = $_GET['event_id'] ?? 0;
$last_id = $_GET['last_id'] ?? 0;

// Verify user has access to this event
$stmt = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
$stmt->execute();
$is_participant = $stmt->get_result()->num_rows > 0;
$stmt->close();

$stmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$is_host = ($event['user_id'] == $_SESSION['user_id']);
$stmt->close();

if (!$is_host && !$is_participant) {
    http_response_code(403);
    exit;
}

// Fetch new messages
$stmt = $conn->prepare("SELECT ec.*, u.name as user_name, u.profile_image 
                       FROM event_chat ec 
                       JOIN users u ON ec.user_id = u.id 
                       WHERE ec.event_id = ? AND ec.id > ?
                       ORDER BY ec.sent_at ASC");
$stmt->bind_param("ii", $event_id, $last_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($messages);

$conn->close();
?>