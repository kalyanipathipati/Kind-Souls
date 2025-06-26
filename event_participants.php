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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$event_id = $_GET['id'];

// Verify the current user is the event host
$stmt = $conn->prepare("SELECT user_id FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event || $event['user_id'] != $_SESSION['user_id']) {
    header("Location: events.php");
    exit;
}

// Fetch event details
$stmt = $conn->prepare("SELECT title FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

// Fetch participants
$participants = [];
$stmt = $conn->prepare("SELECT ep.*, u.name, u.email, u.profile_image FROM event_participants ep JOIN users u ON ep.user_id = u.id WHERE ep.event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Participants | Kind Souls</title>
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
            --error: #d63031;
            --warning: #fdcb6e;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        body {
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        h1 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .participants-list {
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        tr:hover {
            background-color: rgba(156, 136, 255, 0.05);
        }
        
        .participant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
            margin-right: 10px;
        }
        
        .participant-name {
            display: flex;
            align-items: center;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-going {
            background-color: var(--success-bg);
            color: var(--success);
        }
        
        .status-maybe {
            background-color: var(--warning-bg);
            color: var(--warning-text);
        }
        
        .status-not-going {
            background-color: var(--error-bg);
            color: var(--error);
        }
        
        .action-btn {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .action-btn:hover {
            color: var(--primary-dark);
        }
        
        .action-btn.remove {
            color: var(--error);
        }
        
        .action-btn.remove:hover {
            color: #c0392b;
        }
        
        .no-participants {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .no-participants i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        /* Confirmation modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .modal-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: var(--text);
        }
        
        .modal-message {
            margin-bottom: 25px;
            color: var(--text-light);
        }
        
        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .modal-btn.confirm {
            background-color: var(--error);
            color: white;
        }
        
        .modal-btn.confirm:hover {
            background-color: #c0392b;
        }
        
        .modal-btn.cancel {
            background-color: var(--border);
            color: var(--text);
        }
        
        .modal-btn.cancel:hover {
            background-color: #d1d8e0;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .modal-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .modal-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="events.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
        
        <h1>
            <i class="fas fa-users"></i>
            Participants for: <?php echo htmlspecialchars($event['title']); ?>
        </h1>
        
        <div class="participants-list">
            <?php if (empty($participants)): ?>
                <div class="no-participants">
                    <i class="fas fa-user-friends"></i>
                    <h3>No participants yet</h3>
                    <p>Share your event to get more participants!</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Joined At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $participant): ?>
                            <tr>
                                <td>
                                    <div class="participant-name">
                                        <img src="uploads/<?php echo htmlspecialchars($participant['profile_image'] ?: 'default-profile.jpg'); ?>" 
                                             class="participant-avatar" alt="<?php echo htmlspecialchars($participant['name']); ?>">
                                        <?php echo htmlspecialchars($participant['name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($participant['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($participant['status'])); ?>">
                                        <?php echo ucfirst($participant['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($participant['joined_at'])); ?></td>
                                <td>
                                    <a href="#" class="action-btn remove" 
                                       onclick="confirmRemove(<?php echo $event_id; ?>, <?php echo $participant['user_id']; ?>, '<?php echo htmlspecialchars($participant['name']); ?>')">
                                        <i class="fas fa-user-minus"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-title">Are you sure?</div>
            <div class="modal-message" id="modalMessage">This action cannot be undone.</div>
            <div class="modal-actions">
                <button class="modal-btn confirm" id="confirmBtn">Delete</button>
                <button class="modal-btn cancel" id="cancelBtn">Cancel</button>
            </div>
        </div>
    </div>
    
    <script>
        // Confirmation modal functionality
        const modal = document.getElementById('confirmModal');
        const modalMessage = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        
        let currentEventId, currentUserId;
        
        function confirmRemove(eventId, userId, userName) {
            event.preventDefault();
            currentEventId = eventId;
            currentUserId = userId;
            
            modalMessage.innerHTML = `Are you sure you want to remove <strong>${userName}</strong> from this event?<br>This action cannot be undone.`;
            modal.style.display = 'flex';
        }
        
        confirmBtn.addEventListener('click', function() {
            window.location.href = `remove_participant.php?event_id=${currentEventId}&user_id=${currentUserId}`;
        });
        
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Add hover effect to table rows
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.backgroundColor = 'rgba(156, 136, 255, 0.05)';
            });
            
            row.addEventListener('mouseleave', () => {
                row.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>