<?php
// Start a session (optional, if needed for authentication or storing form data)
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Form</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to CSS file if needed -->
</head>
<body>
    <h2>Profile Form</h2>
    <form action="submit.php" method="POST" enctype="multipart/form-data">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required><br><br>
        
        <label for="phoneno">Phone Number:</label>
        <input type="text" id="phone_no" name="phone_no" required><br><br>
        
        <label for="mail_id">Email ID:</label>
        <input type="email" id="mail_id" name="mail_id" required><br><br>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        
        <label for="images">Upload Image:</label>
        <input type="file" id="images" name="images" accept="image/*" required><br><br>
        
        <input type="submit" value="Submit">
    </form>
</body>
</html>
