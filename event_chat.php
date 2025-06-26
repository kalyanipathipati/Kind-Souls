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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get logged-in user's profile image
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$profileImage = $user['profile_image'] ?? 'default-profile.jpg';
$stmt->close();

$event_id = $_GET['id'] ?? 0;

// Verify user is host or participant
$is_participant = false;
$stmt = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
$stmt->execute();
$is_participant = $stmt->get_result()->num_rows > 0;
$stmt->close();

$stmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$is_host = ($event['user_id'] == $_SESSION['user_id']);
$stmt->close();

if (!$is_host && !$is_participant) {
    header("Location: events.php");
    exit;
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && trim($_POST['message']) != '') {
    $message = trim($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO event_chat (event_id, user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $event_id, $_SESSION['user_id'], $message);
    $stmt->execute();
    $stmt->close();
    
    // Redirect to avoid form resubmission
    header("Location: event_chat.php?id=$event_id");
    exit;
}

// Fetch event details
$stmt = $conn->prepare("SELECT e.title, u.name as host_name FROM events e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

// Fetch chat messages
$stmt = $conn->prepare("SELECT ec.*, u.name as user_name, u.profile_image 
                       FROM event_chat ec 
                       JOIN users u ON ec.user_id = u.id 
                       WHERE ec.event_id = ? 
                       ORDER BY ec.sent_at ASC");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> Chat | Kind Souls</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       body {
            background-image: url('uploads/bg2.webp');
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
        }
        
        .chat-container { 
            width: 100%;
            height: 100%;
            background-image: url('uploads/bg2.webp');
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background-color: #9c88ff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 1.2rem;
        }
        
        .chat-messages {
            height: 500px;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
        }
        
        .message { 
            display: flex;
            margin-bottom: 15px;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .message-content {
            flex-grow: 1;
        }
        
        .message-header {
            display: flex;
            align-items: baseline;
            margin-bottom: 5px;
        }
        
        .message-sender {
            font-weight: bold;
            margin-right: 10px;
        }
        
        .message-time {
            font-size: 0.8rem;
            color: #636e72;
        }
        
        .message-text {
            background: white;
            padding: 10px 15px;
            border-radius: 18px;
            display: inline-block;
            max-width: 70%;
            word-wrap: break-word;
        }
        
        .own-message {
            flex-direction: row-reverse;
        }
        
        .own-message .message-content {
            text-align: right;
        }
        
        .own-message .message-text {
            background: #9c88ff;
            color: white;
        }
        
        .own-message .message-header {
            justify-content: flex-end;
        }
        
        .chat-input {
            display: flex;
            padding: 15px;
            background: white;
            border-top: 1px solid #dfe6e9;
        }
        
        .chat-input input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #dfe6e9;
            border-radius: 20px;
            outline: none;
        }
        
        .chat-input button {
            background: #9c88ff;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px 20px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        .chat-input button:hover {
            background: #7c6bd6;
        }
        
        .back-btn {
            display: inline-block;
            margin: 20px;
            color: #9c88ff;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-btn i {
            margin-right: 5px;
        }
        
        .message-info {
            display: flex;
            flex-direction: column;
        }
        
        .own-message .message-info {
            align-items: flex-end;
            margin-right: 10px;
        }
        
        .other-message .message-info {
            align-items: flex-start;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <?php echo htmlspecialchars($event['title']); ?> - Chat Room
            <div style="font-size: 0.9rem;">Hosted by <?php echo htmlspecialchars($event['host_name']); ?></div>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <?php foreach ($messages as $message): ?>
                <?php $is_own_message = ($message['user_id'] == $_SESSION['user_id']); ?>
                <div class="message <?php echo $is_own_message ? 'own-message' : 'other-message'; ?>">
                    <?php if (!$is_own_message): ?>
                        <img src="uploads/<?php echo htmlspecialchars($message['profile_image'] ?: 'default-profile.jpg'); ?>" 
                             class="message-avatar" alt="<?php echo htmlspecialchars($message['user_name']); ?>">
                    <?php endif; ?>
                    
                    <div class="message-info">
                        <div class="message-header">
                            <span class="message-sender"><?php echo htmlspecialchars($message['user_name']); ?></span>
                            <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['sent_at'])); ?></span>
                            <?php if ($is_own_message): ?>
                        <img src="uploads/<?php echo htmlspecialchars($profileImage); ?>" 
                             class="message-avatar" alt="You">
                    <?php endif; ?>
                        </div>
                        <div class="message-text"><?php echo htmlspecialchars($message['message']); ?></div>
                    </div>
                    
                    
                </div>
            <?php endforeach; ?>
        </div>
        
        <form class="chat-input" method="POST">
            <input type="text" name="message" placeholder="Type your message..." required>
            <button type="submit"><i class="fas fa-paper-plane"></i> Send</button>
        </form>
    </div>
    
    <script>
        // Auto-scroll to bottom of chat
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
<?php $conn->close(); ?>