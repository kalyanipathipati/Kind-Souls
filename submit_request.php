<?php
// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "project"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $database);

// Check Connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $address = $conn->real_escape_string($_POST['address']);

    // Insert data into the database
    $sql = "INSERT INTO requests (name, description, address) VALUES ('$name', '$description', '$address')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>
                alert('Request submitted successfully!');
                window.location.href = 'community.php';
              </script>";
    } else {
        echo "<script>
                alert('Error: " . $conn->error . "');
                window.location.href = 'request.php';
              </script>";
    }
}

// Close Connection
$conn->close();
?>
