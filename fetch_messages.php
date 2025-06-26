<?php
session_start();
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];

$query = "SELECT * FROM messages WHERE 
          (sender_id='$user_id' AND receiver_id='$receiver_id') 
          OR (sender_id='$receiver_id' AND receiver_id='$user_id') 
          ORDER BY timestamp ASC";

$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $is_sender = ($row['sender_id'] == $user_id);
    $class = $is_sender ? "sent" : "received";
    
    echo "<div class='message $class'>
            <div class='bubble'>" . htmlspecialchars($row['message']) . "</div>
          </div>";
}
?>
