<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch users with unread counts
if ($is_logged_in) {
    $users_query = "
        SELECT 
            u.id, 
            u.name, 
            u.profile_image,
            MAX(m.timestamp) as last_message_time,
            SUM(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 ELSE 0 END) as unread_count,
            (
                SELECT m2.message 
                FROM messages m2 
                WHERE (m2.sender_id = u.id AND m2.receiver_id = ?) OR (m2.receiver_id = u.id AND m2.sender_id = ?)
                ORDER BY m2.timestamp DESC 
                LIMIT 1
            ) as last_message,
            (
                SELECT m3.is_read
                FROM messages m3
                WHERE ((m3.sender_id = u.id AND m3.receiver_id = ?) OR (m3.receiver_id = u.id AND m3.sender_id = ?))
                ORDER BY m3.timestamp DESC
                LIMIT 1
            ) as last_message_status
        FROM users u
        JOIN messages m ON (
            (u.id = m.sender_id AND m.receiver_id = ?) OR 
            (u.id = m.receiver_id AND m.sender_id = ?))
        WHERE u.id != ?
        GROUP BY u.id, u.name, u.profile_image
        ORDER BY last_message_time DESC
    ";
    $users_stmt = $conn->prepare($users_query);
    $users_stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
    $users_stmt->close();
} else {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f5fe;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(123, 104, 238, 0.1);
            overflow: hidden;
        }
        
        h1 {
            color: #6a4c93;
            margin: 0;
            padding: 20px 25px;
            font-size: 24px;
            font-weight: 600;
            border-bottom: 1px solid #eae2f8;
            background-color: #faf9ff;
        }
        
        .user-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            padding: 16px 25px;
            border-bottom: 1px solid #f0ebfa;
            transition: background-color 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        
        .user-item:hover {
            background-color: #f9f7ff;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 18px;
            border: 2px solid #f0ebfa;
            box-shadow: 0 2px 4px rgba(106, 76, 147, 0.1);
        }
        
        .user-info {
            flex-grow: 1;
            overflow: hidden;
        }
        
        .user-name {
            font-weight: 500;
            color: #4a3a6a;
            text-decoration: none;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
            font-size: 15px;
        }
        
        .user-name.unread {
            font-weight: 600;
            color: #2a1b4a;
        }
        
        .last-message-container {
            display: flex;
            align-items: center;
            width: 100%;
            position: relative;
        }
        
        .last-message-preview {
            font-size: 14px;
            color: #7a6f8b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-grow: 1;
            padding-right: 60px;
        }
        
        .last-message-preview.unread {
            font-weight: 500;
            color: #4a3a6a;
        }
        
        .unread-badge {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            background-color: #7b68ee;
            color: white;
            border-radius: 12px;
            padding: 3px 8px;
            font-size: 12px;
            font-weight: 600;
            min-width: 22px;
            text-align: center;
        }
        
        .no-users {
            color: #7a6f8b;
            font-size: 15px;
            padding: 40px 20px;
            text-align: center;
        }
        
        .login-prompt {
            text-align: center;
            padding: 40px 20px;
            color: #7a6f8b;
            font-size: 15px;
        }
        
        .last-message-time {
            position: absolute;
            right: 0;
            top: 0;
            font-size: 12px;
            color: #a99ec1;
            font-weight: 500;
        }
        
        .last-message-time.unread {
            color: #7b68ee;
            font-weight: 600;
        }
        
        .message-info {
            display: flex;
            justify-content: space-between;
            width: 100%;
            position: relative;
            margin-bottom: 4px;
        }
        
        .empty-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            margin-right: 18px;
            background-color: #eae2f8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #7b68ee;
            font-size: 20px;
            border: 2px solid #f0ebfa;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            
            h1 {
                padding: 18px 20px;
            }
            
            .user-item {
                padding: 14px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Messages</h1>
        <ul class="user-list">
            <?php if ($is_logged_in): ?>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        $has_unread = $user['unread_count'] > 0;
                        $last_message_is_unread = isset($user['last_message_status']) && $user['last_message_status'] == 0;
                        ?>
                        <li class="user-item" onclick="window.location='chat.php?receiver_id=<?php echo $user['id']; ?>'">
                            <div class="avatar-container">
                                <?php if (!empty($user['profile_image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" class="user-avatar">
                                <?php else: ?>
                                    <div class="empty-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="user-info">
                                <div class="message-info">
                                    <span class="user-name <?php echo $has_unread ? 'unread' : ''; ?>">
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </span>
                                    <span class="last-message-time <?php echo $has_unread ? 'unread' : ''; ?>">
                                        <?php 
                                        if (!empty($user['last_message_time'])) {
                                            echo date("g:i a", strtotime($user['last_message_time']));
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="last-message-container">
                                    <span class="last-message-preview <?php echo $has_unread ? 'unread' : ''; ?>">
                                        <?php 
                                        if (!empty($user['last_message'])) {
                                            echo htmlspecialchars($user['last_message']);
                                        } else {
                                            echo "No messages yet";
                                        }
                                        ?>
                                    </span>
                                    <?php if ($has_unread): ?>
                                        <span class="unread-badge"><?php echo $user['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="no-users">No messages yet. Start a conversation!</li>
                <?php endif; ?>
            <?php else: ?>
                <li class="login-prompt">Please log in to view your messages.</li>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        // JavaScript to periodically check for new unread messages
        setInterval(function() {
            if (<?php echo $is_logged_in ? 'true' : 'false'; ?>) {
                fetch('check_unread_messages.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.users) {
                            data.users.forEach(user => {
                                const userItem = document.querySelector(`.user-item[onclick*="receiver_id=${user.id}"]`);
                                if (!userItem) return;
                                
                                // Update unread badge
                                const badgeElement = userItem.querySelector('.unread-badge');
                                
                                if (user.unread_count > 0) {
                                    // Add or update badge
                                    if (badgeElement) {
                                        badgeElement.textContent = user.unread_count;
                                    } else {
                                        const messageContainer = userItem.querySelector('.last-message-container');
                                        if (messageContainer) {
                                            messageContainer.insertAdjacentHTML('beforeend', `<span class="unread-badge">${user.unread_count}</span>`);
                                        }
                                    }
                                    
                                    // Add unread styles
                                    userItem.querySelector('.user-name').classList.add('unread');
                                    userItem.querySelector('.last-message-preview').classList.add('unread');
                                    userItem.querySelector('.last-message-time').classList.add('unread');
                                } else {
                                    // Remove all unread indicators
                                    if (badgeElement) badgeElement.remove();
                                    
                                    // Remove unread styles
                                    userItem.querySelector('.user-name').classList.remove('unread');
                                    userItem.querySelector('.last-message-preview').classList.remove('unread');
                                    userItem.querySelector('.last-message-time').classList.remove('unread');
                                }
                                
                                // Update last message preview
                                if (user.last_message) {
                                    const previewElement = userItem.querySelector('.last-message-preview');
                                    if (previewElement) {
                                        previewElement.textContent = user.last_message;
                                    }
                                }
                                
                                // Update timestamp
                                if (user.last_message_time) {
                                    const timeElement = userItem.querySelector('.last-message-time');
                                    if (timeElement) {
                                        timeElement.textContent = new Date(user.last_message_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    }
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Error checking unread messages:', error));
            }
        }, 5000); // Check every 5 seconds
    </script>
</body>
</html>