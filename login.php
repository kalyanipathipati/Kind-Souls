<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "project";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            echo "<script>alert('Login successful!'); window.location.href = 'profile.php';</script>";
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Generations United</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
           /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            padding: 20px;
        }

        body {
            background-image: url('uploads/bg2.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* Navigation Bar */
        .nav-logo {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #b39ddb; /* Lavender */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-logo img {
            height: 50px;
            width: 50px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .nav-logo span {
            color: #ffffff;
            font-size: 22px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        nav {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin: 0 10px;
            padding: 5px 10px;
            transition: all 0.3s ease;
            position: relative;
            white-space: nowrap;
        }

        nav a:hover {
            color: #4a148c;
        }

        nav a:hover::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            width: 50%;
            height: 2px;
            background-color: #4a148c;
            transform: translateX(-50%);
            animation: underline 0.3s ease;
        }

        @keyframes underline {
            from {
                width: 0;
            }
            to {
                width: 50%;
            }
        }

        nav a.active {
            color: #4a148c;
            font-weight: bold;
        }

        nav a.active::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -5px;
            width: 50%;
            height: 2px;
            background-color: #4a148c;
            transform: translateX(-50%);
        }

        /* Search Bar */
        .search-bar {
            margin-left: 20px;
            display: flex;
            align-items: center;
            position: relative;
            height: 40px;
        }

        .search-bar input[type="text"] {
            padding: 8px 30px 8px 10px;
            border: none;
            border-bottom: 2px solid #ffffff;
            outline: none;
            font-size: 16px;
            width: 150px;
            background: transparent;
            color: white;
            transition: width 0.3s ease;
            height: 100%;
            box-sizing: border-box;
        }

        .search-bar input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-bar input[type="text"]:focus {
            width: 150px;
            border-bottom-color: #4a148c;
        }

        .search-bar button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            transition: color 0.3s ease;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .search-bar button:hover {
            color: #4a148c;
        }

        /* Profile Icon/Image */
        nav a .profile-image {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        nav a i.fa-user-circle {
            font-size: 30px;
            color: white;
        }

        .login-container {
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top:80px;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #4a148c;
        }

        .login-form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .login-btn {
            background-color: #4a148c;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background-color: #6a1b9a;
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
        }

        .signup-link a {
            color: #4a148c;
            text-decoration: none;
            font-weight: 500;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: #d32f2f;
            text-align: center;
            margin-bottom: 20px;
        }

        footer {
            background-color: #967bb6;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .profile-image {
            height: 30px;
            width: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .fa-user-circle {
            font-size: 30px;
            color: white;
        }
    </style>
</head>
<body>

<div class="nav-logo">
    <img src="uploads/symbol.webp" alt="Generations United Logo">
    <span>Generations United</span>
    
    <nav>
        <a href="index.php">Home</a>
        <a href="community.php">Community Page</a>
        <a href="aboutus.php">About Us</a>
        <a href="profiles.php">All Profiles</a>
        <a href="profile.php" class="active">
            <i class="fas fa-user-circle"></i>
        </a>
    </nav>
</div>

<div class="login-container">
    <h2>Login</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="login.php" class="login-form">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="login-btn">Login</button>
    </form>
    
    <div class="signup-link">
        Don't have an account? <a href="signup.php">Sign up here</a>
    </div>
</div>



</body>
</html>