<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$database = "project"; // Change this to your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve form data
$name = $_POST['name'];
$phone_no = $_POST['phone_no'];
$mail_id = $_POST['mail_id'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hashing password for security

// Handle image upload
$target_dir = "uploads/"; // Folder to store images
$target_file = $target_dir . basename($_FILES["images"]["name"]);
$imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
$uploadOk = 1;

// Check if file is an actual image
$check = getimagesize($_FILES["images"]["tmp_name"]);
if($check === false) {
    echo "File is not an image.";
    $uploadOk = 0;
}

// Allow only certain file formats
if(!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
    echo "Only JPG, JPEG, PNG & GIF files are allowed.";
    $uploadOk = 0;
}

// Check if everything is fine before uploading
if ($uploadOk == 1) {
    if (move_uploaded_file($_FILES["images"]["tmp_name"], $target_file)) {
        // Insert data into the database
        $sql = "INSERT INTO profiles (name, phone_no, mail_id, password, images) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $phone_no, $mail_id, $password, $target_file);
        
        if ($stmt->execute()) {
            echo "Profile created successfully!";
        } else {
            echo "Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo "Error uploading the image.";
    }
} else {
    echo "File upload failed.";
}

$conn->close();
?>