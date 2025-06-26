<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$username = "root";
$password = "";
$dbname = "project";

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Initialize $searchQuery with an empty string
$searchQuery = '';

// Fetch the user's profile image from the database
$profileImage = null; // Default value
if ($is_logged_in) {
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['profile_image'])) {
        $profileImage = 'uploads/' . $user['profile_image'];
    }
}

// Handle search functionality
$searchResults = [];
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    if (!empty($searchQuery)) {
        $query = "SELECT id, name, profile_image FROM users WHERE name LIKE ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$searchQuery%"]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch all profiles from the database (if no search query)
if (empty($searchQuery)) {
    $query = "SELECT id, name, profile_image FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $profiles = $searchResults; // Use search results if a query is present
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generations United - All Profiles</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Add shadow for depth */
            position: fixed; /* Stick to the top */
            top: 0;
            left: 0;
            width: 100%; /* Full width */
            z-index: 1000; /* Ensure it stays above other content */
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
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2); /* Add text shadow */
        }

        nav {
            display: flex;
            align-items: center;
            margin-left: auto; /* Push nav to the right */
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin: 0 10px; /* Reduce margin for compact layout */
            padding: 5px 10px;
            transition: all 0.3s ease; /* Smooth transition for hover effects */
            position: relative;
            white-space: nowrap; /* Prevent text wrapping */
        }

        nav a:hover {
            color: #4a148c; /* Dark purple for hover */
        }

        nav a:hover::after {
            content: '';
            position: absolute;
            left: 50%; /* Start from the middle */
            bottom: -5px;
            width: 50%; /* Half width */
            height: 2px;
            background-color: #4a148c; /* Dark purple underline */
            transform: translateX(-50%); /* Center the underline */
            animation: underline 0.3s ease; /* Smooth underline animation */
        }

        @keyframes underline {
            from {
                width: 0; /* Start with no width */
            }
            to {
                width: 50%; /* Grow to half width */
            }
        }

        /* Active Page Styling */
        nav a.active {
            color: #4a148c; /* Dark purple for active page */
            font-weight: bold;
        }

        nav a.active::after {
            content: '';
            position: absolute;
            left: 50%; /* Start from the middle */
            bottom: -5px;
            width: 50%; /* Half width */
            height: 2px;
            background-color: #4a148c; /* Dark purple underline */
            transform: translateX(-50%); /* Center the underline */
        }

        /* Minimal Search Bar */
        .search-bar {
            margin-left: 20px; /* Add some space between nav and search bar */
            display: flex;
            align-items: center;
            position: relative;
            height: 40px; /* Fixed height */
        }

        .search-bar input[type="text"] {
            padding: 8px 30px 8px 10px; /* Add padding for the icon */
            border: none;
            border-bottom: 2px solid #ffffff; /* Line instead of a box */
            outline: none;
            font-size: 16px;
            width: 150px; /* Smaller width */
            background: transparent;
            color: white;
            transition: width 0.3s ease;
            height: 100%; /* Full height */
            box-sizing: border-box; /* Include padding in height */
        }

        .search-bar input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.7); /* Light opacity placeholder */
        }

        .search-bar input[type="text"]:focus {
            width: 150px; /* Expand on focus */
            border-bottom-color: #4a148c; /* Dark purple on focus */
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
            height: 100%; /* Full height */
            display: flex;
            align-items: center; /* Center the icon vertically */
        }

        .search-bar button:hover {
            color: #4a148c; /* Dark purple on hover */
        }

        /* Profile Icon/Image */
        nav a .profile-image {
            height: 30px;
            width: 30px;
            border-radius: 50%; /* Make it circular */
            object-fit: cover; /* Ensure the image fits properly */
            border: 2px solid white; /* Optional: Add a border */
        }

        nav a i.fa-user-circle {
            font-size: 30px; /* Match the size of the profile image */
            color: white; /* Default icon color */
        }


        /* Profile Grid */
        .profile-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
            margin-top: 100px; /* Adjust for fixed nav */
        }

        .profile-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            padding: 15px;
            width: calc(20% - 20px); /* 5 profiles per row */
            transition: transform 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-5px);
        }

        .profile-card img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .profile-card h3 {
            font-size: 18px;
            margin-bottom: 5px;
            color: #4a148c;
        }
        .profile-card a {
    text-decoration: none;
}


.profile-card a:hover {
    text-decoration: none;
}


        /* Footer */
        footer {
            background-color: #967bb6; /* Lavender */
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<header>
    Welcome to Generations United
</header>

<div class="nav-logo">
    <img src="uploads/symbol.webp" alt="Generations United Logo">
    <span>KIND SOULS</span>
    <div class="search-bar">
        <form action="profiles.php" method="GET">
            <input type="text" name="search" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="search-suggestions"></div>
    </div>
    <nav>
        <a href="index.php">Home</a>
        <a href="community.php">Community Page</a>
        <a href="aboutus.php">About Us</a>
        <a href="profiles.php" class="active">All Profiles</a>
        <a href="profile.php">
            <?php if (!empty($profileImage)): ?>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </a>
    </nav>
</div>

<div class="profile-grid">
    <?php if (!empty($profiles)): ?>
        <?php foreach ($profiles as $profile): 
            $imagePath = !empty($profile['profile_image']) ? 'uploads/' . basename($profile['profile_image']) : 'uploads/nopp.jpg';
            if (!file_exists($imagePath)) {
                $imagePath = 'uploads/nopp.jpg';
            }
        ?>
            <div class="profile-card">
                <a href="profile1.php?id=<?php echo htmlspecialchars($profile['id']); ?>">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>">
                    <h3><?php echo htmlspecialchars($profile['name'] ?? ''); ?></h3>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No profiles found.</p>
    <?php endif; ?>
</div>



<!-- JavaScript for Search Suggestions -->
<script>
    $(document).ready(function () {
        // Clear search input on page load
        $('input[name="search"]').val('');

        const searchInput = $('input[name="search"]');
        const suggestionsContainer = $('.search-suggestions');

        // Clear search input when navigating back
        $(window).on('popstate', function () {
            searchInput.val(''); // Clear the search input
            suggestionsContainer.hide(); // Hide suggestions
        });

        searchInput.on('input', function () {
            const query = $(this).val().trim();

            if (query.length > 0) {
                $.ajax({
                    url: 'search_suggestions.php',
                    type: 'GET',
                    data: { search: query },
                    success: function (response) {
                        const profiles = JSON.parse(response);
                        let suggestionsHtml = '';

                        if (profiles.length > 0) {
                            profiles.forEach(profile => {
                                const imagePath = profile.profile_image ? 'uploads/' + profile.profile_image : 'uploads/nopp.jpg';
                                suggestionsHtml += `
                                    <div class="suggestion-item">
                                        <a href="profile1.php?id=${profile.id}">
                                            <img src="${imagePath}" alt="${profile.name}" class="suggestion-image">
                                            <span>${profile.name}</span>
                                        </a>
                                    </div>
                                `;
                            });
                        } else {
                            suggestionsHtml = '<div class="suggestion-item">No profiles found.</div>';
                        }

                        suggestionsContainer.html(suggestionsHtml).show();
                    },
                    error: function () {
                        suggestionsContainer.html('<div class="suggestion-item">Error fetching suggestions.</div>').show();
                    }
                });
            } else {
                suggestionsContainer.hide();
            }
        });

        // Hide suggestions when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.search-bar').length) {
                suggestionsContainer.hide();
            }
        });
    });
</script>

</body>
</html>