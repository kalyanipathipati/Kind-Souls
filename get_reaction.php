<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['memory_id'])) {
    die(json_encode(['error' => 'Invalid request']));
}

$memory_id = $_GET['memory_id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT emoji FROM memory_reactions WHERE memory_id = ? AND user_id = ?");
$stmt->bind_param("ii", $memory_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->num_rows > 0 ? $result->fetch_assoc() : ['emoji' => null]);
?>