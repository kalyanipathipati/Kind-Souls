<?php
session_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP username
define('DB_PASS', '');     // Default XAMPP password is empty
define('DB_NAME', 'project'); // Your database name

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

if (!isset($_GET['memory_id'])) {
    exit;
}

$memory_id = $_GET['memory_id'];

// Fetch comments for this memory
$stmt = $conn->prepare("SELECT mc.*, u.name, u.profile_image 
                       FROM memory_comments mc 
                       JOIN users u ON mc.user_id = u.id 
                       WHERE mc.memory_id = ? 
                       ORDER BY mc.created_at DESC");
$stmt->bind_param("i", $memory_id);
$stmt->execute();
$result = $stmt->get_result();

while ($comment = $result->fetch_assoc()): ?>
    <div class="comment-item">
        <img src="<?= htmlspecialchars($comment['profile_image'] ?: 'images/default-profile.jpg') ?>" class="comment-pic">
        <div class="comment-content">
            <div class="comment-header">
                <span class="comment-name"><?= htmlspecialchars($comment['name']) ?></span>
                <span class="comment-time"><?= time_ago($comment['created_at']) ?></span>
            </div>
            <p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
        </div>
    </div>
<?php endwhile;

$stmt->close();
$conn->close();

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff/60)." minutes ago";
    if ($diff < 86400) return floor($diff/3600)." hours ago";
    return floor($diff/86400)." days ago";
}
?>