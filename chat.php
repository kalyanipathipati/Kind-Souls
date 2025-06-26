 
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli("localhost", "root", "", "project");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id']; // Logged-in user
$receiver_id = isset($_GET['receiver_id']) ? $_GET['receiver_id'] : null;

if (!$receiver_id) {
    die("Please select a user to chat with.");
}

// Mark messages as read when the chat is opened
$mark_as_read_query = "UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0";
$mark_as_read_stmt = $conn->prepare($mark_as_read_query);
$mark_as_read_stmt->bind_param("ii", $user_id, $receiver_id);
$mark_as_read_stmt->execute();
$mark_as_read_stmt->close();

// Fetch receiver's details
$receiver_query = "SELECT name, profile_image FROM users WHERE id = ?";
$receiver_stmt = $conn->prepare($receiver_query);
$receiver_stmt->bind_param("i", $receiver_id);
$receiver_stmt->execute();
$receiver_result = $receiver_stmt->get_result();
$receiver = $receiver_result->fetch_assoc();
$receiver_stmt->close();



// Check if this user is blocked
$check_block_query = "SELECT * FROM blocked_users WHERE blocker_id = ? AND blocked_id = ?";
$check_block_stmt = $conn->prepare($check_block_query);
$check_block_stmt->bind_param("ii", $user_id, $receiver_id);
$check_block_stmt->execute();
$is_blocked = $check_block_stmt->get_result()->num_rows > 0;
$check_block_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($receiver['name']); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Base Styles */
        body {
            background-image: url('uploads/bg2.webp');
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
             /* Background image from uploads/ */
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-container {
            
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background-color: #fff;
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
        }

        .chat-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }

        .chat-header h2 {
            margin: 0;
            font-size: 18px;
            color: #262626;
        }

        #chat-box {
            background-image: url('uploads/bg2.webp');
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background-color: rgba(250, 250, 250, 0.9); /* Semi-transparent background */
        }

        .message {
            margin-bottom: 12px;
            display: flex;
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message .bubble {
            max-width: 70%;
            padding: 10px 16px;
            border-radius: 20px;
            position: relative;
        }

        .message.sent .bubble {
            background-color: #3797f0;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .message.received .bubble {
            background-color: #efefef;
            color: #262626;
            border-bottom-left-radius: 4px;
        }

        .message .timestamp {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
            display: block;
            text-align: right;
        }

        .chat-input {
            display: flex;
            padding: 12px;
            background-color: #fff;
            border-top: 1px solid #e0e0e0;
        }
        .message {
    display: flex;
    margin-bottom: 12px;
}

.message.sent {
    justify-content: flex-end;
}

.message.received {
    justify-content: flex-start;
}

.message .bubble {
    max-width: 70%;
    padding: 10px 16px;
    border-radius: 20px;
    position: relative;
    font-size: 14px;
}

.message.sent .bubble {
    background-color: #3797f0;
    color: white;
    border-bottom-right-radius: 4px;
}

.message.received .bubble {
    background-color: #efefef;
    color: black;
    border-bottom-left-radius: 4px;
}


        #message {
            flex: 1;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
            margin-right: 12px;
        }

        #send {
            padding: 10px 20px;
            background-color: #3797f0;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        #send:hover {
            background-color: #2680e0;
        }

        /* Theme Switcher */
        .theme-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .theme-switcher button {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .theme-switcher button:hover {
            transform: scale(1.1);
        }

        .theme-switcher button.light {
            background-color: #f0f0f0;
        }

        .theme-switcher button.dark {
            background-color: #333;
        }

        .theme-switcher button.purple {
            background-color: #6a1b9a;
        }

        /* Dark Theme */
        body.dark-theme {
            background-color: #121212;
            color: #ffffff;
        }

        body.dark-theme .chat-container {
            background-color: rgba(30, 30, 30, 0.9); /* Semi-transparent dark background */
        }

        body.dark-theme .chat-header {
            background-color: #1e1e1e;
            border-bottom: 1px solid #333;
        }

        body.dark-theme .chat-header h2 {
            color: #ffffff;
        }

        body.dark-theme #chat-box {
            background-color: rgba(18, 18, 18, 0.9); /* Semi-transparent dark background */
        }

        body.dark-theme .message.sent .bubble {
            background-color: #0a84ff;
            color: #ffffff;
        }

        body.dark-theme .message.received .bubble {
            background-color: #333;
            color: #ffffff;
        }

        body.dark-theme .chat-input {
            background-color: #1e1e1e;
            border-top: 1px solid #333;
        }

        body.dark-theme #message {
            background-color: #333;
            color: #ffffff;
            border: 1px solid #444;
        }

        body.dark-theme #send {
            background-color: #0a84ff;
            color: #ffffff;
        }

        body.dark-theme #send:hover {
            background-color: #0066cc;
        }

        /* Purple Theme */
        body.purple-theme {
            background-color: #f5f5f5;
        }

        body.purple-theme .chat-container {
            background-color: rgba(255, 255, 255, 0.9); /* Semi-transparent white background */
        }

        body.purple-theme .chat-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
        }

        body.purple-theme .chat-header h2 {
            color: #6a1b9a;
        }

        body.purple-theme #chat-box {
            background-color: rgba(245, 245, 245, 0.9); /* Semi-transparent light background */
        }

        body.purple-theme .message.sent .bubble {
            background-color: #6a1b9a;
            color: #ffffff;
        }

        body.purple-theme .message.received .bubble {
            background-color: #e1bee7;
            color: #000000;
        }

        body.purple-theme .chat-input {
            background-color: #ffffff;
            border-top: 1px solid #e0e0e0;
        }

        body.purple-theme #message {
            background-color: #f5f5f5;
            color: #000000;
            border: 1px solid #e0e0e0;
        }

        body.purple-theme #send {
            background-color: #6a1b9a;
            color: #ffffff;
        }

        body.purple-theme #send:hover {
            background-color: #4a148c;
        }
        /* Block Form Styles */
#blockForm {
    position: fixed;
    top: 20px;
    right: 150px; /* Adjusted to appear left of theme buttons */
    z-index: 100;
   
}

#blockForm button {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    background-color: #ff4444;
    color: white;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    font-weight: 500;
    
}

#blockForm button:hover {
    background-color: #cc0000;
    transform: translateY(-2px);
}

/* Dark Theme Adjustments */
body.dark-theme #blockForm button {
    background-color: #ff6666;
    color: #fff;
}

body.dark-theme #blockForm button:hover {
    background-color: #ff4444;
}

/* Purple Theme Adjustments */
body.purple-theme #blockForm button {
    background-color: #d32f2f;
    color: #fff;
}

body.purple-theme #blockForm button:hover {
    background-color: #b71c1c;
}

/* Theme Switcher Adjustment */
.theme-switcher {
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 10px;
    align-items: center; /* Align with block button */
}
    </style>
</head>
<body class="light-theme">

<div class="theme-switcher">
    <button class="light" onclick="setTheme('light')"></button>
    <button class="dark" onclick="setTheme('dark')"></button>
    <button class="purple" onclick="setTheme('purple')"></button>
</div>

<div class="chat-container">
    <div class="chat-header">
        <img src="uploads/<?php echo htmlspecialchars($receiver['profile_image']); ?>" alt="<?php echo htmlspecialchars($receiver['name']); ?>">
        <h2><?php echo htmlspecialchars($receiver['name']); ?></h2>
    </div>
    <div id="chat-box">
        <!-- Messages will load here -->
    </div>
    <div class="chat-input">
        <textarea id="message" placeholder="Type your message..."></textarea>
        <button id="send">Send</button>
    </div>
</div>
<form id="blockForm" method="POST" action="block_user.php">
    <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
    <input type="hidden" name="action" value="<?php echo $is_blocked ? 'unblock' : 'block'; ?>">
    <button type="submit"><?php echo $is_blocked ? 'Unblock' : 'Block'; ?></button>
</form>
<div id="confirmModal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%);
background:white; padding:20px 30px; border-radius:12px; box-shadow:0 0 10px rgba(0,0,0,0.3); z-index:999;">
    <p id="confirmText" style="margin:0 0 20px;">Are you sure you want to block this user?</p>
    <div style="text-align:right;">
        <button id="cancelBtn" style="margin-right:10px; padding:6px 14px;">Cancel</button>
        <button id="confirmBtn" style="padding:6px 14px; background:#d32f2f; color:#fff; border:none; border-radius:6px;">Yes</button>
    </div>
</div>
<script>
$(document).ready(function() {
    // Function to load messages
    function loadMessages() {
        $.ajax({
            url: "fetch_messages.php",
            type: "POST",
            data: { receiver_id: <?php echo $receiver_id; ?> },
            success: function(data) {
                $("#chat-box").html(data);
                // Scroll to the bottom of the chat box
                $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);
            }
        });
    }

    // Load messages every second (real-time effect)
    setInterval(loadMessages, 1000);

    // Send message
    $("#send").click(function() {
        var message = $("#message").val();
        if (message !== "") {
            $.post("send_message.php", {
                receiver_id: <?php echo $receiver_id; ?>,
                message: message
            }, function(response) {
                $("#message").val(""); // Clear the input
                loadMessages(); // Refresh messages
            });
        }
    });

    // Initial load
    loadMessages();
});

// Theme Switcher
function setTheme(theme) {
    document.body.className = theme + '-theme';
}
document.getElementById('blockForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent form from submitting immediately
    const form = this;
    const action = form.querySelector('input[name="action"]').value;

    // Set the confirmation message dynamically
    const confirmText = document.getElementById('confirmText');
    confirmText.textContent = action === 'block'
        ? 'Are you sure you want to block this user?'
        : 'Are you sure you want to unblock this user?';

    // Show the modal
    document.getElementById('confirmModal').style.display = 'block';

    // Handle button actions
    document.getElementById('cancelBtn').onclick = function() {
        document.getElementById('confirmModal').style.display = 'none';
    };

    document.getElementById('confirmBtn').onclick = function() {
        document.getElementById('confirmModal').style.display = 'none';
        form.submit(); // Submit the form only if confirmed
    };
});
</script>

</body>
</html>