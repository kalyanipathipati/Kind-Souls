//index.php
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

// Initialize $searchQuery with an empty string
$searchQuery = '';

// Fetch the user's profile image from the database
$profileImage = null; // Default value
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
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
        $query = "SELECT id, name, profile_image FROM users WHERE name LIKE ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$searchQuery%"]);
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch the latest 10 profiles from the database (if no search query)
if (empty($searchQuery)) {
    $query = "SELECT id, name, profile_image FROM users ORDER BY created_at DESC LIMIT 10";
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
    <title>Generations United - Home</title>
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
            margin-left: 50px;
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
            width: 100px;
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

        /* Carousel */
        .carousel {
            position: relative;
            width: 100%;
            max-width: 1200px;
            margin: 100px auto 20px;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .carousel-inner {
            display: flex;
            transition: transform 0.5s ease;
        }

        .carousel-item {
            min-width: 100%;
            box-sizing: border-box;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            text-align: center;
            border-radius: 10px;
        }

        .carousel-item h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #4a148c;
        }

        .carousel-item p {
            font-size: 16px;
            color: #333;
        }

        .carousel-control {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 50%;
            font-size: 18px;
            transition: background-color 0.3s ease;
        }

        .carousel-control:hover {
            background-color: rgba(0, 0, 0, 0.8);
        }

        .carousel-control.prev {
            left: 10px;
        }

        .carousel-control.next {
            right: 10px;
        }

        /* Profile Section */
        .profile-section {
            margin: 20px auto;
            max-width: 1200px;
            padding: 20px;
            background-color: transparent;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        .profile-card {
            background-color: transparent;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            padding: 15px;
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
     /* Remove underline */
    transition: text-shadow 0.3s ease; /* Smooth transition for hover effect */
}

.profile-card h3:hover {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2); /* Light shadow on hover */
   
}


.profile-card a {
    text-decoration: none; /* Remove underline from the link */
    color: inherit; /* Inherit the color from the parent (h3) */
}

.profile-card a:hover {
    text-decoration: none; /* Ensure underline doesn't reappear on hover */
}
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }

        .suggestion-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .suggestion-item:hover {
            background-color: #f9f9f9;
        }

        .suggestion-item a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
        }

        .suggestion-image {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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
        <a href="index.php" class="active">Home</a>
        <a href="community.php">Community Page</a>
        <a href="aboutus.php">About Us</a>
        <a href="profiles.php" >All Profiles</a>
        <a href="memories.php" ><i class="fas fa-child"></i></a>
        <a href="profile.php">
            <?php if (!empty($profileImage)): ?>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </a>
    </nav>
</div>

    <!-- Carousel Section -->
    <div class="carousel">
        <div class="carousel-inner">
            <div class="carousel-item">
                <h2>Welcome to Generations United</h2>
                <p>Connecting people across generations to share experiences, knowledge, and stories.</p>
            </div>
            <div class="carousel-item">
                <h2>Our Mission</h2>
                <p>To create a platform where every generation can learn, grow, and thrive together.</p>
            </div>
            <div class="carousel-item">
                <h2>Join Our Community</h2>
                <p>Be a part of a vibrant community that values connection and collaboration.</p>
            </div>
        </div>
        <button class="carousel-control prev" onclick="prevSlide()">&#10094;</button>
        <button class="carousel-control next" onclick="nextSlide()">&#10095;</button>
    </div>

    <!-- Profile Section -->
    <div class="profile-section">
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
    </div>

   

    <!-- JavaScript for Carousel -->
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-item');
        const totalSlides = slides.length;

        function showSlide(index) {
            const offset = -index * 100;
            document.querySelector('.carousel-inner').style.transform = `translateX(${offset}%)`;
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto slide change every 5 seconds
        setInterval(nextSlide, 5000);
    </script>

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