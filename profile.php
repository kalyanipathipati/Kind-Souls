<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "project"; // Replace with actual database name

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php"); // Redirect to login page after logout
    exit();
}

// Handle Image Upload
if (isset($_POST['upload'])) {
    if (!empty($_FILES['profile_image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['profile_image']['name']); // Ensure a unique filename
        $image_tmp_name = $_FILES['profile_image']['tmp_name'];
        $image_folder = "uploads/" . $image_name;

        // Check if the file is an image
        $imageFileType = strtolower(pathinfo($image_folder, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($image_tmp_name, $image_folder)) {
                $user_id = $_SESSION['user_id'];
                $sql = "UPDATE users SET profile_image='$image_name' WHERE id='$user_id'";
                if ($conn->query($sql)) {
                    $_SESSION['profile_image'] = $image_name;
                    echo "<script>alert('Profile image uploaded successfully!');</script>";
                } else {
                    echo "<script>alert('Failed to update database.');</script>";
                }
            } else {
                echo "<script>alert('Failed to upload image.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');</script>";
        }
    } else {
        echo "<script>alert('No file selected.');</script>";
    }
}

// Handle Bio Update
if (isset($_POST['update_bio'])) {
    $bio = $_POST['bio'];
    $user_id = $_SESSION['user_id'];

    $sql = "UPDATE users SET bio='$bio' WHERE id='$user_id'";
    if ($conn->query($sql)) {
        $_SESSION['bio'] = $bio;
        echo "<script>alert('Bio updated successfully!');</script>";
    } else {
        echo "<script>alert('Failed to update bio.');</script>";
    }
}

// Handle Request Deletion
if (isset($_POST['delete_request'])) {
    $request_id = $_POST['request_id'];
    $user_id = $_SESSION['user_id'];
    
    // First delete all comments associated with this request
    $delete_comments_sql = "DELETE FROM comments WHERE request_id = '$request_id'";
    if ($conn->query($delete_comments_sql)) {
        // Then delete the request
        $delete_request_sql = "DELETE FROM requests WHERE id = '$request_id' AND user_id = '$user_id'";
        if ($conn->query($delete_request_sql)) {
            echo "<script>window.location.href='profile.php';</script>";
        } else {
            echo "<script>alert('Failed to delete request.');</script>";
        }
    } else {
        echo "<script>alert('Failed to delete associated comments.');</script>";
    }
}

// Handle Comment Deletion
if (isset($_POST['delete_comment'])) {
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    
    $sql = "DELETE FROM comments WHERE id = '$comment_id' AND user_id = '$user_id'";
    if ($conn->query($sql)) {
        echo "<script>window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Failed to delete comment.');</script>";
    }
}

// Fetch user data if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT name, email, profile_image, bio, created_at FROM users WHERE id='$user_id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['name'] = $row['name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['profile_image'] = $row['profile_image'];
        $_SESSION['bio'] = $row['bio'];
        $_SESSION['created_at'] = $row['created_at'];
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch the user's profile image from the database
$profileImage = null; // Default value
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['profile_image'])) {
        // Construct the full path to the image
        $profileImage = 'uploads/' . $user['profile_image'];

        // Debug: Check if the image file exists
        if (!file_exists($profileImage)) {
            echo "<script>console.log('Image does not exist: " . htmlspecialchars($profileImage) . "');</script>";
        }
    } else {
        echo "<script>console.log('Profile image is empty or not set in the database.');</script>";
    }
} else {
    echo "<script>console.log('Session ID is not set.');</script>";
}

// Fetch requests for the logged-in user
$requests = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT id, request_text, created_at FROM requests WHERE user_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch comments for the logged-in user
$comments = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT c.id, c.comment_text, c.created_at, r.request_text, r.id as request_id 
              FROM comments c 
              JOIN requests r ON c.request_id = r.id 
              WHERE c.user_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generations United - Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* [Previous CSS remains the same until .activity-section] */
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
            background-color: #b39ddb;
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

        /* Active Page Styling */
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

        /* Minimal Search Bar */
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

        /* Profile Info Styling */
        .profile-container {
            background-color: transparent;
            border-radius: 15px;
            padding: 30px;
            margin: 20px auto 30px;
            max-width: 900px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-info {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-info img {
            height: 150px;
            width: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 5px solid #f0e6ff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-info .details {
            flex: 1;
        }

        .profile-info .details h2 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #4a148c;
        }

        .profile-info .details p {
            margin: 5px 0;
            font-size: 16px;
            color: #555;
        }

        .profile-info .details .joined-date {
            font-size: 14px;
            color: #777;
            margin-top: 10px;
        }

        .edit-profile-btn {
            padding: 10px 25px;
            background-color: #4a148c;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            box-shadow: 0 3px 10px rgba(74, 20, 140, 0.3);
        }

        .edit-profile-btn:hover {
            background-color: #6a1b9a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(74, 20, 140, 0.4);
        }

        .edit-profile-btn:active {
            transform: translateY(0);
        }

        /* Edit Profile Section */
        #edit-profile-section {
            background-color: #f9f5ff;
            padding: 25px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid #e0d0ff;
        }

        .bio-container textarea {
            width: 100%;
            max-width: 100%;
            padding: 15px;
            font-size: 16px;
            border: 1px solid #d1c4e9;
            border-radius: 8px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
            transition: border 0.3s;
        }

        .bio-container textarea:focus {
            border-color: #4a148c;
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 20, 140, 0.1);
        }

        .bio-container button {
            padding: 12px 25px;
            background-color: #4a148c;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .bio-container button:hover {
            background-color: #6a1b9a;
        }

        /* Image Upload Form */
        .image-upload-form {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0d0ff;
        }

        .image-upload-form input[type="file"] {
            margin-bottom: 15px;
        }

        .image-upload-form button {
            padding: 12px 25px;
            background-color: #4a148c;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-upload-form button:hover {
            background-color: #6a1b9a;
        }
        
        /* Updated Activity Section */
        .activity-section {
            background-color: transparent;
            border-radius: 15px;
            padding: 30px;
            margin: 30px auto;
            max-width: 1200px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .activity-section h2 {
            color: #4a148c;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0e6ff;
        }

        /* Grid Layout for Requests and Comments */
        .requests-grid, .comments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* Request Item Styling */
        .request-item {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .request-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .request-content {
            margin-bottom: 15px;
            color: #333;
            line-height: 1.6;
            flex-grow: 1;
        }

        .request-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        /* Comment Item Styling */
        .comment-item {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #b39ddb;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .comment-content {
            margin-bottom: 10px;
            color: #333;
            flex-grow: 1;
        }

        .comment-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
        }

        /* Three-dot Menu */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 5px;
            z-index: 1;
            overflow: hidden;
        }

        .dropdown-content a {
            color: #333;
            padding: 8px 12px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background-color 0.3s;
            margin-top:-10px;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: #4a148c;
        }

        .dropdown-content a i {
            margin-right: 8px;
            width: 15px;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination a {
            color: #4a148c;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .pagination a.active {
            background-color: #4a148c;
            color: white;
            border: 1px solid #4a148c;
        }

        .pagination a:hover:not(.active) {
            background-color: #f0e6ff;
        }

        /* No Activity Message */
        .no-activity {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
            grid-column: 1 / -1;
        }

        /* Request Comments */
        .request-comments {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .commenter-info {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .commenter-info img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        /* Highlight user's request in community page */
        .user-request {
            background-color: #f0e6ff;
            border-left: 4px solid #4a148c;
        }

        /* Logout button styling */
        .logout-btn {
            
            display: inline-block;
            padding: 10px 25px;
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            margin-left: 10px;
            
            text-decoration: none;
            box-shadow: 0 3px 10px rgba(211, 47, 47, 0.3);
        }

        .logout-btn:hover {
            background-color: #b71c1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.4);
        }

        .logout-btn:active {
            transform: translateY(0);
        }


      /* Auth Container Styling */
.auth-container {
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 40px;
    margin: 100px auto;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.auth-container h2 {
    color: #4a148c;
    margin-bottom: 15px;
    font-size: 28px;
}

.auth-container p {
    color: #555;
    margin-bottom: 30px;
    font-size: 16px;
}

/* Auth Buttons */
.auth-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 30px;
}

.auth-btn {
    padding: 12px 25px;
    border-radius: 50px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.login-btn {
    background-color: #4a148c;
    color: white;
}

.login-btn:hover {
    background-color: #6a1b9a;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(74, 20, 140, 0.3);
}

.signup-btn {
    background-color: white;
    color: #4a148c;
    border: 2px solid #4a148c;
}

.signup-btn:hover {
    background-color: #f0e6ff;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(74, 20, 140, 0.2);
}

.auth-btn i {
    margin-right: 8px;
}

/* OR Divider */
.or-divider {
    position: relative;
    margin: 25px 0;
    color: #777;
    font-size: 14px;
}

.or-divider:before,
.or-divider:after {
    content: "";
    position: absolute;
    top: 50%;
    width: 40%;
    height: 1px;
    background-color: #ddd;
}

.or-divider:before {
    left: 0;
}

.or-divider:after {
    right: 0;
}

/* Guest Link */
.guest-text {
    color: #555;
    font-size: 15px;
    margin-top: 20px;
}

.guest-link {
    color: #4a148c;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s;
}

.guest-link:hover {
    color: #6a1b9a;
    text-decoration: underline;
}
    </style>
</head>
<body>

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
<br><br><br>


<?php if (isset($_SESSION['user_id'])): ?>
    <div class="profile-container">
        <div class="profile-info">
            <?php if (!empty($_SESSION['profile_image'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($_SESSION['profile_image']); ?>" alt="Profile Image">
            <?php else: ?>
                <img src="uploads/nopp.jpg" alt="Default Profile Image">
            <?php endif; ?>
            <div class="details">
                <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                <p><?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <?php if (!empty($_SESSION['bio'])): ?>
                    <p><?php echo htmlspecialchars($_SESSION['bio']); ?></p>
                <?php endif; ?>
                <p class="joined-date">Member since: <?php echo date('F Y', strtotime($_SESSION['created_at'])); ?></p>
                <button class="edit-profile-btn" onclick="toggleEditProfile()">
                    <i class="fas fa-user-edit"></i> 
                </button>
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>                 </a>
            </div>
        </div>

        <!-- Edit Profile Section (Hidden by Default) -->
        <div id="edit-profile-section" style="display: none;">
            <!-- Bio Form -->
            <div class="bio-container">
                <form action="profile.php" method="post">
                    <label for="bio">Your Bio:</label><br>
                    <textarea name="bio" id="bio" rows="4" placeholder="Tell us about yourself..."><?php echo isset($_SESSION['bio']) ? htmlspecialchars($_SESSION['bio']) : ''; ?></textarea><br>
                    <button type="submit" name="update_bio">
                        <i class="fas fa-save"></i> Update Bio
                    </button>
                </form>
            </div>

            <!-- Profile Image Upload Form -->
            <form action="profile.php" method="post" enctype="multipart/form-data" class="image-upload-form">
                <label for="profile_image">Profile Picture:</label><br>
                <input type="file" name="profile_image" id="profile_image" accept="image/*" required>
                <button type="submit" name="upload">
                    <i class="fas fa-upload"></i> Change Profile Picture
                </button>
            </form>
        </div>
    </div>

    <!-- Requests and Comments Section -->
    <div class="activity-section">
        <h2><i class="fas fa-list-alt"></i> Your Requests</h2>
        <?php if (!empty($requests)): ?>
            <div class="requests-grid">
                <?php foreach ($requests as $request): ?>
                    <div class="request-item">
                        <div class="request-content">
                            <?php echo htmlspecialchars($request['request_text']); ?>
                        </div>
                        <div class="request-meta">
                            <span><?php echo date('M j, Y', strtotime($request['created_at'])); ?></span>
                            
                            <!-- Three-dot menu -->
                            <div class="dropdown">
                                <button class="dropdown-btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="dropdown-content">
                                    <a href="community.php?request_id=<?php echo $request['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="#" onclick="event.preventDefault(); confirmRequestDeletion('delete-form-<?php echo $request['id']; ?>')">
    <i class="fas fa-trash"></i> Delete
</a>                        </div>
                            </div>
                            
                            <!-- Hidden delete form -->
                            <form id="delete-form-<?php echo $request['id']; ?>" action="profile.php" method="post" style="display: none;">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                <input type="hidden" name="delete_request">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-activity">You haven't made any requests yet.</div>
        <?php endif; ?>
    </div>

    <div class="activity-section">
        <h2><i class="fas fa-comments"></i> Your Comments on Others' Requests</h2>
        <?php if (!empty($comments)): ?>
            <div class="comments-grid">
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="comment-content">
                            <?php echo htmlspecialchars($comment['comment_text']); ?>
                        </div>
                        <div class="comment-meta">
                            <div>
                                <span><?php echo date('M j, Y', strtotime($comment['created_at'])); ?></span><br>
                                <span>On: "<?php echo htmlspecialchars(mb_strimwidth($comment['request_text'], 0, 30, '...')); ?>"</span>
                            </div>
                            
                            <!-- Three-dot menu -->
                            <div class="dropdown">
                                <button class="dropdown-btn"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="dropdown-content">
                                    <a href="community.php?request_id=<?php echo $comment['request_id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                   <a href="#" onclick="event.preventDefault(); confirmCommentDeletion('delete-comment-<?php echo $comment['id']; ?>')">
    <i class="fas fa-trash"></i> Delete
</a>                   </div>
                            </div>
                            
                            <!-- Hidden delete form -->
                            <form id="delete-comment-<?php echo $comment['id']; ?>" action="profile.php" method="post" style="display: none;">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                <input type="hidden" name="delete_comment">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-activity">You haven't made any comments yet.</div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="auth-container">
        <h2>Join Our Community</h2>
        <p>Please log in to view your profile or create an account to get started.</p>
        
        <div class="auth-buttons">
            <a href="login.php" class="auth-btn login-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="signup.php" class="auth-btn signup-btn">
                <i class="fas fa-user-plus"></i> Sign Up
            </a>
        </div>
        
        <div class="or-divider">OR</div>
        
        <div class="guest-text">
            Continue as <a href="index.php" class="guest-link">GUEST</a>
        </div>
    </div>
<?php endif; ?>

<script>
    function toggleEditProfile() {
        const editProfileSection = document.getElementById('edit-profile-section');
        if (editProfileSection.style.display === 'none') {
            editProfileSection.style.display = 'block';
        } else {
            editProfileSection.style.display = 'none';
        }
    }
    
    // Close dropdowns when clicking elsewhere
    document.addEventListener('click', function(event) {
        if (!event.target.matches('.dropdown-btn')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.style.display === 'block') {
                    openDropdown.style.display = 'none';
                }
            }
        }
    });

    // Function to handle request deletion with confirmation
    function confirmRequestDeletion(formId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4a148c',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    // Function to handle comment deletion with confirmation
    function confirmCommentDeletion(formId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This comment will be permanently deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4a148c',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>
</body>
</html>