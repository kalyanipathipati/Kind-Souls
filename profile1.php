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

// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Fetch the user's detailed information from the database
    $query = "SELECT name, email, profile_image, created_at, bio FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Use the null coalescing operator to handle null values
        $name = htmlspecialchars($user['name'] ?? '');
        $email = htmlspecialchars($user['email'] ?? '');
        $profileImage = 'uploads/' . htmlspecialchars($user['profile_image'] ?? 'nopp.jpg');
        $created_at = htmlspecialchars($user['created_at'] ?? '');
        $bio = htmlspecialchars($user['bio'] ?? '');

        // Fetch all requests for the user from the requests table
        $requestsQuery = "SELECT id, request_text, created_at FROM requests WHERE user_id = ?";
        $requestsStmt = $pdo->prepare($requestsQuery);
        $requestsStmt->execute([$userId]);
        $requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch comments for each request
        foreach ($requests as &$request) {
            $requestId = $request['id'];
            $commentsQuery = "SELECT comment_text, created_at FROM comments WHERE request_id = ?";
            $commentsStmt = $pdo->prepare($commentsQuery);
            $commentsStmt->execute([$requestId]);
            $request['comments'] = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        die("User not found.");
    }
} else {
    die("Invalid request.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $name; ?>'s Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-image: url('uploads/bg2.webp');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 40px 20px;
        }

        /* Profile Container */
        .profile-container {
            max-width: 1200px;
            width: 100%;
            background-color: transparent;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(8px);
            margin: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow-y: auto;
            max-height: 90vh;
        }

        /* Profile Header */
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .profile-header img {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            margin-right: 25px;
            border: 3px solid #fff;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .profile-header img:hover {
            transform: scale(1.03);
        }

        .profile-header .profile-info {
            flex: 1;
        }

        .profile-header h1 {
            font-size: 32px;
            margin: 0 0 8px 0;
            color: #222;
            font-weight: 600;
        }

        .profile-header p {
            font-size: 16px;
            color: #666;
            margin: 0 0 8px 0;
        }

        .profile-header .created-at {
            font-size: 14px;
            color: #888;
            font-style: italic;
            display: flex;
            align-items: center;
        }

        .profile-header .created-at i {
            margin-right: 6px;
            font-size: 13px;
        }

        /* Profile Details */
        .profile-details {
            margin-top: 30px;
        }

        .profile-details h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #444;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .profile-details h2 i {
            margin-right: 10px;
            color: #555;
        }

        .profile-details p {
            font-size: 16px;
            line-height: 1.7;
            color: #555;
            margin-bottom: 25px;
        }

        /* Requests Grid Layout */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        /* Request Card */
        .request-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 20px;
            position: relative;
            overflow: hidden;
            height: 250px; /* Fixed height for uniform cards */
            display: flex;
            flex-direction: column;
        }

        .request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .request-card-content {
            flex: 1;
            overflow: hidden;
        }

        .request-card h3 {
            font-size: 16px;
            margin: 0 0 10px 0;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .request-card h3 i {
            margin-right: 8px;
            color: #666;
        }

        .request-card p {
            font-size: 14px;
            line-height: 1.5;
            color: #444;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .request-card .post-meta {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .request-card .created-at {
            font-size: 12px;
            color: #999;
        }

        .request-card .created-at i {
            margin-right: 6px;
            font-size: 11px;
        }

        /* Dropdown Menu */
        .dropdown {
            
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 16px;
            
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .dropdown-toggle:hover {
            	
            background-color: rgba(0, 0, 0, 0.05);
            color: #9678b6;
        }

        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 0%;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 5px 0;
            min-width: 120px;
            z-index: 100;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-item {
            padding: 8px 15px;
            font-size: 14px;
            color: #333;
            text-decoration: none;
            display: block;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #9678b6;
        }

        .dropdown-item i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }

        /* No Content Messages */
        .no-requests {
            text-align: center;
            padding: 30px;
            color: #999;
            font-style: italic;
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            grid-column: 1 / -1;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(150, 120, 182, 0.4);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(150, 120, 182, 0.6);
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .requests-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-header img {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .profile-container {
                padding: 25px;
            }
            
            .requests-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .request-card {
            animation: fadeIn 0.4s ease forwards;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $profileImage; ?>" alt="<?php echo $name; ?>">
            <div class="profile-info">
                <h1><?php echo $name; ?></h1>
                <p><?php echo $email; ?></p>
                <p class="created-at"><i class="far fa-calendar-alt"></i> Member since: <?php echo $created_at; ?></p>
            </div>
        </div>
        <div class="profile-details">
            <h2><i class="far fa-user"></i> About Me</h2>
            <p><?php echo $bio; ?></p>
            <h2><i class="far fa-file-alt"></i> Requests</h2>
            
            <?php if (!empty($requests)): ?>
                <div class="requests-grid">
                    <?php foreach ($requests as $request): ?>
                        <div class="request-card">
                            <div class="request-card-content">
                                <h3><i class="far fa-file-alt"></i> Request</h3>
                                <p><?php echo htmlspecialchars($request['request_text']); ?></p>
                            </div>
                            
                            <div class="post-meta">
                                <span class="created-at"><i class="far fa-clock"></i> <?php echo htmlspecialchars($request['created_at']); ?></span>
                                <div class="dropdown">
                                    <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="community.php?request_id=<?php echo $request['id']; ?>" class="dropdown-item">
                                            <i class="far fa-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-requests">No requests found</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
    // Close all dropdowns unless clicked on .dropdown-toggle or its child
    if (!event.target.closest('.dropdown-toggle')) {
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }
});

    });

    function toggleDropdown(button) {
        // Close all other dropdowns first
        const allDropdowns = document.querySelectorAll('.dropdown-menu');
        allDropdowns.forEach(dropdown => {
            if (dropdown !== button.nextElementSibling) {
                dropdown.classList.remove('show');
            }
        });
        
        // Toggle the clicked dropdown
        const dropdownMenu = button.nextElementSibling;
        dropdownMenu.classList.toggle('show');
    }
    </script>
</body>
</html>