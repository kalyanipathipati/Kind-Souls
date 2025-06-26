<?php
// Start the session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection (example using PDO)
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

// Fetch matching profiles based on the search query
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    if (!empty($searchQuery)) {
        $query = "SELECT id, name, profile_image FROM users WHERE name LIKE ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$searchQuery%"]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return the results as JSON
        header('Content-Type: application/json');
        echo json_encode($profiles);
        exit;
    }
}

// If no search query, return an empty array
header('Content-Type: application/json');
echo json_encode([]);