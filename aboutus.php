<?php
// Start the session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection (using PDO)
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

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

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

// Fetch admin information with images
$admins = [];
try {
    $query = "SELECT name, email, phone, image FROM admin";
    $stmt = $pdo->query($query);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail for admin display - don't show errors to users
}

// Testimonials data
$testimonials = [
    [
        'quote' => "Generations United has helped me connect with younger professionals who bring fresh perspectives to my business challenges.",
        'author' => "Robert Johnson, 62",
        'role' => "Business Owner"
    ],
    [
        'quote' => "As a recent graduate, I've gained invaluable mentorship from experienced professionals through this platform.",
        'author' => "Sarah Williams, 24",
        'role' => "Marketing Associate"
    ],
    [
        'quote' => "This platform bridges the generation gap in ways I never thought possible. Highly recommended!",
        'author' => "Maria Garcia, 45",
        'role' => "Community Leader"
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generations United - About Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
            from { width: 0; }
            to { width: 50%; }
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

        /* Footer */
        footer {
            background-color: #967bb6;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            left: 0;
            bottom: 0;
            width: 100%;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
        }

        /* About Container */
        .about-container {
            max-width: 1200px;
            margin: 100px auto 80px;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .about-container h1 {
            color: #4a148c;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.8rem;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        
        .about-container h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #4a148c, #b39ddb);
            border-radius: 2px;
        }
        
        .about-container p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 25px;
            text-align: justify;
            color: #444;
        }
        
        /* Mission Vision Section */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin: 50px 0;
        }
        
        .mission, .vision {
            background-color: #f0e6ff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .mission:hover, .vision:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .mission h2, .vision h2 {
            color: #4a148c;
            margin-bottom: 20px;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 10px;
        }
        
        .mission h2::after, .vision h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #4a148c;
        }
        
        .mission p, .vision p {
            font-size: 1.05rem;
            color: #555;
        }
        
        /* Admin Section */
        .admin-section {
            margin-top: 60px;
        }
        
        .admin-section h2 {
            color: #4a148c;
            font-size: 2rem;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        .admin-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #4a148c, #b39ddb);
            border-radius: 2px;
        }
        
        .admin-section p {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 40px;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .admin-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            text-align: center;
            border-top: 4px solid #4a148c;
        }
        
        .admin-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .admin-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 4px solid #f0e6ff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .admin-card h3 {
            color: #4a148c;
            margin-bottom: 10px;
            font-size: 1.4rem;
        }
        
        .admin-card p {
            margin-bottom: 8px;
            font-size: 1rem;
            text-align: center;
            color: #666;
        }
        
        .admin-card .contact {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        
        .contact i {
            color: #4a148c;
            margin-right: 8px;
        }
        
        /* Testimonials Section */
        .testimonials-section {
            margin: 80px 0 40px;
        }
        
        .testimonials-section h2 {
            color: #4a148c;
            font-size: 2rem;
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            padding-bottom: 15px;
        }
        
        .testimonials-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #4a148c, #b39ddb);
            border-radius: 2px;
        }
        
        .testimonials-slider {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        .testimonials-track {
            display: flex;
            transition: transform 0.5s ease;
        }
        
        .testimonial {
            min-width: 100%;
            padding: 0 20px;
            box-sizing: border-box;
        }
        
        .testimonial-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: relative;
        }
        
        .testimonial-content::before {
            content: '\201C';
            font-family: Georgia, serif;
            font-size: 60px;
            color: #f0e6ff;
            position: absolute;
            top: 10px;
            left: 20px;
            line-height: 1;
        }
        
        .testimonial-quote {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 20px;
            font-style: italic;
            position: relative;
            z-index: 1;
        }
        
        .testimonial-author {
            font-weight: 700;
            color: #4a148c;
            font-size: 1.1rem;
        }
        
        .testimonial-role {
            color: #777;
            font-size: 0.9rem;
            display: block;
            margin-top: 5px;
        }
        
        .slider-nav {
            text-align: center;
            margin-top: 30px;
        }
        
        .slider-dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            background-color: #ddd;
            border-radius: 50%;
            margin: 0 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .slider-dot.active {
            background-color: #4a148c;
        }
        
        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 60px 0;
        }
        
        .stat-card {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 30px 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-top: 4px solid #4a148c;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #4a148c;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .mission-vision {
                grid-template-columns: 1fr;
            }
            
            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-logo {
                flex-direction: column;
                padding: 10px 0;
            }
            
            nav {
                margin: 10px auto 0;
            }
            
            .search-bar {
                margin: 10px auto 0;
            }
            
            .about-container {
                padding: 30px 20px;
                margin-top: 150px;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
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
        <a href="aboutus.php" class="active">About Us</a>
        <a href="profiles.php">All Profiles</a>
        <a href="profile.php">
            <?php if (!empty($profileImage)): ?>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </a>
    </nav>
</div>

<div class="about-container">
    <h1 class="animate__animated animate__fadeIn">About Kind Souls</h1>
    
    <p class="animate__animated animate__fadeIn animate__delay-1s">Welcome to Kind Souls, a platform dedicated to bridging the gap between different generations and fostering meaningful connections. Our mission is to create a space where wisdom meets innovation, where experience meets fresh perspectives, and where every generation can learn from and support one another.</p>
    
    <div class="mission-vision animate__animated animate__fadeIn animate__delay-1s">
        <div class="mission">
            <h2>Our Mission</h2>
            <p>To create an inclusive community that values the contributions of all generations, facilitating knowledge sharing, mutual support, and intergenerational understanding. We believe that by bringing together diverse age groups, we can create solutions that are more innovative, inclusive, and effective.</p>
        </div>
        
        <div class="vision">
            <h2>Our Vision</h2>
            <p>A world where age is not a barrier but a bridge, where different generations work together to solve problems, share stories, and create a better future for all. We envision communities where intergenerational collaboration is the norm rather than the exception.</p>
        </div>
    </div>
    

    
   
            
            <div class="slider-nav">
                <?php for ($i = 0; $i < count($testimonials); $i++): ?>
                    <span class="slider-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <div class="admin-section animate__animated animate__fadeIn animate__delay-3s">
        <h2>Our Team</h2>
        <p>Meet the dedicated administrators who work behind the scenes to keep Kind Souls running smoothly. Our team brings together diverse experiences and perspectives to create a platform that serves all generations.</p>
        
        <div class="admin-grid">
            <?php foreach ($admins as $admin): ?>
                <div class="admin-card">
                    <?php if (!empty($admin['image'])): ?>
                        <img src="uploads/admin/<?php echo htmlspecialchars($admin['image']); ?>" alt="<?php echo htmlspecialchars($admin['name']); ?>" class="admin-image">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/120" alt="Default Admin Image" class="admin-image">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($admin['name']); ?></h3>
                    <div class="contact">
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($admin['phone']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



<script>
    // Testimonial Slider
    document.addEventListener('DOMContentLoaded', function() {
        const track = document.querySelector('.testimonials-track');
        const dots = document.querySelectorAll('.slider-dot');
        const testimonials = document.querySelectorAll('.testimonial');
        let currentIndex = 0;
        
        function goToTestimonial(index) {
            track.style.transform = `translateX(-${index * 100}%)`;
            
            // Update dots
            dots.forEach(dot => dot.classList.remove('active'));
            dots[index].classList.add('active');
            
            currentIndex = index;
        }
        
        // Click event for dots
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                goToTestimonial(index);
            });
        });
        
        // Auto-advance every 5 seconds
        setInterval(() => {
            currentIndex = (currentIndex + 1) % testimonials.length;
            goToTestimonial(currentIndex);
        }, 5000);
    });
</script>

</body>
</html>