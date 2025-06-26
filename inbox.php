<?php
session_start();
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if (!$is_logged_in) {
    echo "<p>Please log in to access your inbox.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox - Private Messages</title>
</head>
<body>

<header>
    <h2>Inbox</h2>
</header>

<nav>
    <a href="index.php">Home</a>
    <a href="community.php">Community Page</a>
    <a href="inbox.php">Inbox</a>
    <a href="profile.php">Your Profile</a>
</nav>

<h3>Your Messages</h3>

<?php
// Fetch messages where the user is the receiver
$inbox_query = $conn->query("SELECT messages.id, messages.message, users.name AS sender_name, messages.timestamp FROM messages JOIN users ON messages.sender_id = users.id WHERE messages.receiver_id = '$user_id' ORDER BY messages.timestamp DESC");

if ($inbox_query->num_rows > 0) {
    while ($message = $inbox_query->fetch_assoc()) {
        echo "<p><strong>" . htmlspecialchars($message['sender_name']) . ":</strong> " . htmlspecialchars($message['message']) . " <br> <small>" . $message['timestamp'] . "</small></p>";
    }
} else {
    echo "<p>No messages yet.</p>";
}
?>

</body>
</html>
