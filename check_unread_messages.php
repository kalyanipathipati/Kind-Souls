<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'project';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if ($is_logged_in) {
    $unread_query = "SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = :user_id AND is_read = 0";
    $unread_stmt = $pdo->prepare($unread_query);
    $unread_stmt->execute(['user_id' => $user_id]);
    $unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];

    echo json_encode(['unread_count' => $unread_count]);
} else {
    echo json_encode(['unread_count' => 0]);
}
?>