<?php
session_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'project');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);





// Delete expired memories (older than 24 hours)
$conn->query("DELETE FROM childhood_memories WHERE expires_at < NOW()");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle memory submission
    if (isset($_POST['memory_text'])) {
        $memory_text = trim($_POST['memory_text']);
        $category = $_POST['category'] ?? null;
        $user_id = $_SESSION['user_id'];
        $image_url = null;

        // Handle image upload
        if (isset($_FILES['memory_image']) && $_FILES['memory_image']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/memories/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['memory_image']['name'], PATHINFO_EXTENSION);
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($file_ext), $valid_extensions)) {
                $file_name = uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['memory_image']['tmp_name'], $file_path)) {
                    $image_url = $file_path;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO childhood_memories (user_id, memory_text, image_url, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $memory_text, $image_url, $category);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['memory_success'] = "Memory shared successfully!";
        header("Location: memories.php");
        exit;
    }
    
    // Handle reactions
    if (isset($_POST['memory_id']) && isset($_POST['emoji'])) {
        $memory_id = $_POST['memory_id'];
        $user_id = $_SESSION['user_id'];
        $emoji = $_POST['emoji'];
        
        // Check if already reacted
        $stmt = $conn->prepare("SELECT id FROM memory_reactions WHERE memory_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $memory_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update reaction
            $stmt = $conn->prepare("UPDATE memory_reactions SET emoji = ? WHERE memory_id = ? AND user_id = ?");
            $stmt->bind_param("sii", $emoji, $memory_id, $user_id);
        } else {
            // Add reaction
            $stmt = $conn->prepare("INSERT INTO memory_reactions (memory_id, user_id, emoji) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $memory_id, $user_id, $emoji);
        }
        $stmt->execute();
        
        // Get updated reaction counts
        $stmt = $conn->prepare("SELECT emoji, COUNT(*) as count FROM memory_reactions WHERE memory_id = ? GROUP BY emoji");
        $stmt->bind_param("i", $memory_id);
        $stmt->execute();
        $reactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'reactions' => $reactions]);
        exit;
    }
}

// Fetch all active memories
$memories = [];
$stmt = $conn->prepare("SELECT cm.*, u.name, u.profile_image 
                       FROM childhood_memories cm 
                       JOIN users u ON cm.user_id = u.id 
                       WHERE cm.expires_at > NOW() 
                       ORDER BY cm.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Get reactions for each memory
    $stmt2 = $conn->prepare("SELECT emoji, COUNT(*) as count FROM memory_reactions WHERE memory_id = ? GROUP BY emoji");
    $stmt2->bind_param("i", $row['id']);
    $stmt2->execute();
    $reactions = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $row['reactions'] = $reactions;
    $memories[] = $row;
    $stmt2->close();
}
$stmt->close();

// Fetch categories
$categories = [];
$stmt = $conn->prepare("SELECT * FROM memory_categories ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

// Helper functions
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff/60)."m ago";
    if ($diff < 86400) return floor($diff/3600)."h ago";
    return floor($diff/86400)."d ago";
}

function get_category_icon($category_name, $categories) {
    foreach ($categories as $category) {
        if ($category['name'] === $category_name) {
            return $category['icon'];
        }
    }
    return 'smile';
}

function get_user_reaction($memory_id, $user_id, $conn) {
    if (!$user_id) return null;
    $stmt = $conn->prepare("SELECT emoji FROM memory_reactions WHERE memory_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $memory_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['emoji'] : null;
}









// Initialize profile image variable
$profileImage = null;

// Check if user is logged in and fetch profile image
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $profileImage = 'uploads/' . $user['profile_image'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Childhood Memories | Kind Souls</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #9c88ff;
            --primary-light: #e0d8ff;
            --primary-dark: #7c6bd6;
            --text: #2d3436;
            --text-light: #636e72;
            --border: #dfe6e9;
            --bg: #f5f6fa;
            --card-bg: #ffffff;
            --success: #00b894;
            --success-bg: #d1f7ed;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background-color: var(--bg);
            margin: 0;
            padding: 0;
            padding-top: 70px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Floating memory button */
        .floating-memory-btn {
             background-color: #7e57c2; /* Darker lavender */
        color: white;
        border: none;
        padding: 12px 15px;
        border-radius: 50px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: fixed;
        bottom: 20px;
        right: 20px;
        //box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
            
        }
        
        .floating-memory-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 16px rgba(156, 136, 255, 0.4);
        }
        
        /* Memory form styling */
        .memory-form-container {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        
        .memory-form {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            width: 100%;
            max-width: 500px;
            position: relative;
            display: none; /* Hidden by default */
        }
        
        .memory-form.active {
            display: block;
        }
        
        .memory-form h2 {
            margin-top: 0;
            color: var(--primary-dark);
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .memory-form h2 i {
            color: var(--primary);
        }
        
        .memory-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            resize: vertical;
            font-family: inherit;
            margin-bottom: 15px;
            transition: border 0.3s;
        }
        
        
        .category-select {
            margin-bottom: 15px;
        }
        
        .category-select p {
            margin-bottom: 8px;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .category-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .category-option {
            display: none;
        }
        
        .category-option + label {
            padding: 8px 16px;
            background: var(--primary-light);
            border-radius: 20px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            color: var(--primary-dark);
            border: 1px solid transparent;
            transition: all 0.3s;
        }
        
        .category-option:checked + label {
            background: var(--primary);
            color: white;
        }
        
        .file-upload {
            margin: 20px 0;
        }
        
        .file-upload label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.875rem;
            color: var(--text-light);
        }
        
        .file-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-input-label {
            padding: 10px 16px;
            background: var(--primary-light);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            color: var(--primary-dark);
            border: 1px dashed var(--primary);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .file-input-label:hover {
            background: var(--primary);
            color: white;
        }
        
        .file-name {
            font-size: 0.875rem;
            color: var(--text-light);
        }
        
        .btn-submit {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            width: 100%;
            font-size: 1rem;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(156, 136, 255, 0.3);
        }
        
        .memories-feed h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--primary-dark);
            text-align: center;
        }
        
        .memories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 0 20px;
        }
        
        .memory-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .memory-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .memory-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }
        
        .memory-user {
            flex-grow: 1;
        }
        
        .user-name {
            font-weight: 600;
            display: block;
            color: var(--text);
        }
        
        .memory-category {
            font-size: 0.75rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 4px;
        }
        
        .time-ago {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        
        .memory-text {
            margin-bottom: 15px;
            white-space: pre-line;
            color: var(--text);
            line-height: 1.6;
        }
        
        .memory-image {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            margin-bottom: 15px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s;
        }
        
        .memory-image:hover {
            transform: scale(1.02);
        }
        
        .memory-actions {
            display: flex;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }
        
        .reactions {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
        }
        
        .reaction-btn {
            
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            position: relative;
            text-decoration: none !important;
        }
        
      
        
        .reaction-count {
            font-size: 0.75rem;
            color: var(--text-light);
            background: white;
            border-radius: 10px;
            padding: 2px 6px;
            margin-left: 4px;
            border: 1px solid var(--border);
            position: static; /* Changed from absolute to static */
            display: inline-block; /* Added to make it visible */
        }
.reaction-btn:hover, .reaction-btn:focus, .reaction-btn:active {
    text-decoration: none !important;
}
        
        .alert-success {
            padding: 12px 16px;
            background: var(--success-bg);
            color: var(--success);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            text-align: center;
            max-width: 350px;
            margin: 0 auto 30px;
        }
        
        .no-memories {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
            grid-column: 1 / -1;
        }
        
        /* Animation for reaction */
        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .reaction-pop {
            animation: pop 0.3s ease;
        }
        
        /* Keep your existing navbar CSS */
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
            text-decoration: none;
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
            text-decoration: none;
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
        
        .notification-badge {
            background-color: #ff4081;
            color: white;
            border-radius: 100%;
            font-size: 10px;
            position: absolute;
            top: -3px;
            right: -2px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            font-weight: bold;
        }
        
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
    </style>
</head>
<body>
    <div class="nav-logo">
    <img src="uploads/symbol.webp" alt="KIND SOULS Logo">
    <span>KIND SOULS</span>
   
    <nav>
        <a href="index.php">Home</a>
        <a href="community.php" >Community Page</a>
        <a href="aboutus.php">About Us</a>
        <a href="profiles.php">All Profiles</a>
        </a>
            <a href="memories.php" class="active"><i class="fas fa-child"></i></a>
        <a href="profile.php">
            <?php if (!empty($profileImage)): ?>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </a>
        </nav>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['memory_success'])): ?>
            <div class="alert-success">
                <?= $_SESSION['memory_success'] ?>
                <?php unset($_SESSION['memory_success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Floating memory button -->
        <div class="floating-memory-btn" id="memoryToggleBtn">
            <i class="fas fa-plus"></i>
        </div>
        
        <!-- Memory form container -->
        <div class="memory-form-container">
            <div class="memory-form" id="memoryForm">
                <h2><i class="fas fa-child"></i> Share Your Memory</h2>
                <form method="post" enctype="multipart/form-data">
                    <textarea name="memory_text" placeholder="What's your happy childhood memory?" required></textarea>
                    
                    <div class="category-select">
                        <p>Category:</p>
                        <div class="category-options">
                            <?php foreach ($categories as $category): ?>
                                <input type="radio" id="cat-<?= $category['id'] ?>" name="category" value="<?= $category['name'] ?>" 
                                       class="category-option" <?= $category['name'] == 'General' ? 'checked' : '' ?>>
                                <label for="cat-<?= $category['id'] ?>">
                                    <i class="fas fa-<?= $category['icon'] ?>"></i>
                                    <?= $category['name'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="file-upload">
                        
                        
                        <input type="file" name="memory_image" id="memory_image" accept="image/*" style="display: none;">
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-share"></i> Share
                    </button>

                </form>
            </div>
        </div>
        

        
        <div class="memories-feed">
            <h2>Recent Memories</h2>
            
            <div class="memories-grid">
                <?php if (empty($memories)): ?>
                    <div class="no-memories">
                        <i class="fas fa-child" style="font-size: 3rem; margin-bottom: 15px; color: var(--primary);"></i>
                        <p>No memories shared yet. Be the first to share!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($memories as $memory): ?>
                        <div class="memory-card" id="memory-<?= $memory['id'] ?>">
                            <div class="memory-header">
    <img src="uploads/<?php echo htmlspecialchars($memory['profile_image'] ?: 'default-profile.jpg'); ?>" 
         class="profile-pic" alt="<?php echo htmlspecialchars($memory['name']); ?>">
    <div class="memory-user">
        <span class="user-name"><?php echo htmlspecialchars($memory['name']); ?></span>
        <?php if ($memory['category']): ?>
            <span class="memory-category">
                <i class="fas fa-<?= get_category_icon($memory['category'], $categories) ?>"></i>
                <?= htmlspecialchars($memory['category']) ?>
            </span>
        <?php endif; ?>
    </div>
    <span class="time-ago"><?= time_ago($memory['created_at']) ?></span>
</div>
                            
                            <div class="memory-content">
                                <p class="memory-text"><?= nl2br(htmlspecialchars($memory['memory_text'])) ?></p>
                                <?php if ($memory['image_url']): ?>
                                    <img src="<?= htmlspecialchars($memory['image_url']) ?>" class="memory-image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="memory-actions">
                                <div class="reactions">
                                    <?php 
                                    $user_reaction = get_user_reaction($memory['id'], $_SESSION['user_id'] ?? null, $conn);
                                    $reaction_emojis = ['❤️'];
                                    
                                    foreach ($reaction_emojis as $emoji): 
                                        $count = 0;
                                        foreach ($memory['reactions'] as $reaction) {
                                            if ($reaction['emoji'] === $emoji) {
                                                $count = $reaction['count'];
                                                break;
                                            }
                                        }
                                    ?>
                                       


                                        <button type="button" class="reaction-btn <?= $user_reaction === $emoji ? 'active' : '' ?>" 
                                                onclick="react(event, <?= $memory['id'] ?>, '<?= $emoji ?>')">
                                            <?= $emoji ?>
                                            <?php if ($count > 0): ?>
                                                <span class="reaction-count"><?= $count ?></span>
                                            <?php endif; ?>
                                        </button>


                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
   // Toggle memory form visibility
    const memoryToggleBtn = document.getElementById('memoryToggleBtn');
    const memoryForm = document.getElementById('memoryForm');
    
    memoryToggleBtn.addEventListener('click', () => {
        memoryForm.classList.toggle('active');
    });
    
    // Show selected file name
    document.getElementById('memory_image').addEventListener('change', function(e) {
        const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
        document.getElementById('file-name').textContent = fileName;
    });
    
    // Handle reactions with animation
    function react(event, memory_id, emoji) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!<?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>) {
            window.location.href = 'login.php';
            return;
        }
        
        // Add pop animation
        event.target.classList.add('reaction-pop');
        setTimeout(() => {
            event.target.classList.remove('reaction-pop');
        }, 300);
        
        const formData = new FormData();
        formData.append('memory_id', memory_id);
        formData.append('emoji', emoji);
        
        fetch('memories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update all reaction buttons for this memory
                const memoryCard = document.getElementById('memory-' + memory_id);
                const reactionBtns = memoryCard.querySelectorAll('.reaction-btn');
                
                // Reset active state
                reactionBtns.forEach(btn => {
                    btn.classList.remove('active');
                    const countSpan = btn.querySelector('.reaction-count');
                    if (countSpan) countSpan.textContent = '0';
                });
        
                // Update counts and active state
                data.reactions.forEach(reaction => {
                    reactionBtns.forEach(btn => {
                        if (btn.textContent.includes(reaction.emoji)) {
                            // Update count
                            let countSpan = btn.querySelector('.reaction-count');
                            if (!countSpan) {
                                countSpan = document.createElement('span');
                                countSpan.className = 'reaction-count';
                                btn.appendChild(countSpan);
                            }
                            countSpan.textContent = reaction.count;
                            
                            // Set active if this is the user's reaction
                            if (button.textContent.includes(reaction.emoji)) {
                                btn.classList.add('active');
                            }
                        }
                    });
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }
    // Add hover effect to memory cards
    document.querySelectorAll('.memory-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>