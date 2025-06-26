<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$database = "project"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']); // Store plain text password (not recommended - see note below)

    // First check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows > 0) {
        // Email already exists
        echo "<script>alert('This email is already registered. Please use a different email address.');</script>";
    } else {
        // Email doesn't exist, proceed with registration
        $sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Registration successful! Please log in.'); window.location.href = 'login.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Generations United</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">	
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        /* Main Content */
        main {
            margin-top: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
            padding: 20px;
        }

        /* Sign Up Container */
        .signup-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 200%;
            max-width: 500px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .signup-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .signup-container h2 {
            color: #4a148c;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        .signup-form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #7e57c2;
            box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
            outline: none;
        }

        .form-group input::placeholder {
            color: #aaa;
        }

        .btn {
            background-color: #7e57c2;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #6a45b0;
            box-shadow: 0 5px 15px rgba(126, 87, 194, 0.4);
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #7e57c2;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #4a148c;
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background-color: #967bb6;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .signup-container {
                padding: 30px 20px;
            }
            
            .nav-logo span {
                font-size: 18px;
            }
            
            nav a {
                font-size: 16px;
                margin: 0 5px;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <div class="nav-logo">
        <img src="uploads/symbol.webp" alt="Generations United Logo">
        <span>KIND SOULS</span>
        
        <nav>
            <a href="index.php">Home</a>
            <a href="community.php">Community Page</a>
            <a href="aboutus.php">About Us</a>
            <a href="profiles.php">All Profiles</a>
            <a href="profile.php" class="active">
                <?php if (!empty($profileImage)): ?>
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
            </a>
        </nav>
    </div>

    <main>
        <div class="signup-container">
            <h2>Create Your Account</h2>
            <form method="POST" action="signup.php" class="signup-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                
                <button type="submit" class="btn">Register Now</button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in</a>
                </div>
            </form>
        </div>
    </main>

   

</body>
</html>