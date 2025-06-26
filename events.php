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

// Initialize profile image variable
$profileImage = null;

// Fetch user profile image
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

// Fetch all events with host profile images
$events = [];
$stmt = $conn->prepare("SELECT e.*, u.name as host_name, u.profile_image as host_profile_image 
                       FROM events e JOIN users u ON e.user_id = u.id 
                       ORDER BY e.start_datetime DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// Fetch events user is participating in
$participating_events = [];
$stmt = $conn->prepare("SELECT event_id FROM event_participants WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $participating_events[] = $row['event_id'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Events | Kind Souls</title>
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
            --success-bg: #d1f7ed;
            --error-bg: #ffebee;
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
            padding: 0;
            padding-top: 70px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Navbar styling */
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
        
        /* Events page styling */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            color: var(--primary-dark);
            font-size: 2rem;
            font-weight: 600;
        }
        
        .create-event-btn {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .create-event-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(156, 136, 255, 0.3);
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }
        
        @media (max-width: 992px) {
            .events-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .events-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .event-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .event-header {
            background-color: var(--primary-light);
            padding: 15px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .host-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }
        
        .event-host-info {
            flex-grow: 1;
        }
        
        .event-host-name {
            font-weight: 600;
            color: var(--primary-dark);
        }
        
        .event-title {
            font-size: 1.2rem;
            margin-top: 5px;
            color: var(--text);
        }
        
        .event-body {
            padding: 15px;
            flex-grow: 1;
        }
        
        .event-description {
            color: var(--text-light);
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        
        .event-details {
            margin-bottom: 15px;
        }
        
        .event-detail {
            display: flex;
            margin-bottom: 10px;
        }
        
        .event-detail-icon {
            color: var(--primary);
            width: 24px;
            text-align: center;
            margin-right: 10px;
        }
        
        .event-detail-text {
            flex-grow: 1;
            font-size: 0.9rem;
        }
        
        .event-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-top: 1px solid var(--border);
            background-color: #fafafa;
        }
        
        .event-status {
            font-size: 0.85rem;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .host-status {
            background-color: var(--primary);
            color: white;
        }
        
        .participant-status {
            background-color: var(--success);
            color: white;
        }
        
        .dropdown {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 100;
        }
        
        .dropdown-toggle {
            background: rgba(255, 255, 255, 0.8);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--primary-dark);
            padding: 5px 10px;
            border-radius: 50%;
            transition: all 0.2s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.9);
            color: var(--primary);
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 35px;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 101;
            display: none;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-menu a {
            color: var(--text);
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dropdown-menu a:hover {
            background-color: var(--primary-light);
        }
        
        .join-btn {
            background-color: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .join-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .leave-btn {
            background-color: #f8f9fa;
            color: var(--text-light);
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border: 1px solid var(--border);
        }
        
        .leave-btn:hover {
            background-color: #e9ecef;
        }
        
        .no-events {
            text-align: center;
            grid-column: 1 / -1;
            padding: 40px;
            color: var(--text-light);
        }
        
        .no-events i {
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
    <div class="nav-logo">
        <img src="uploads/symbol.webp" alt="KIND SOULS Logo">
        <span>KIND SOULS</span>
        
        <nav>
            <a href="index.php">Home</a>
            <a href="community.php">Community Page</a>
            <a href="aboutus.php">About Us</a>
            <a href="profiles.php">All Profiles</a>
            <a href="memories.php"><i class="fas fa-child"></i></a>
            <a href="events.php" class="active"><i class="fas fa-calendar-alt"></i></a>
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
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Events</h1>
            <a href="create_event.php" class="create-event-btn">
                <i class="fas fa-plus"></i> Create Event
            </a>
        </div>
        
        <div class="events-grid">
            <?php if (empty($events)): ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No events found</h3>
                    <p>Be the first to create an event and bring our community together!</p>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="event-card">
                        <!-- Add dropdown at the top -->
                        <?php if ($event['user_id'] == $_SESSION['user_id']): ?>
                            <div class="dropdown">
                                <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="edit_event.php?id=<?php echo $event['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="#" class="delete-event-link" 
                                       data-event-id="<?php echo $event['id']; ?>" 
                                       data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    <a href="event_participants.php?id=<?php echo $event['id']; ?>">
                                        <i class="fas fa-users"></i> View Participants
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-header">
                            <img src="uploads/<?php echo htmlspecialchars($event['host_profile_image'] ?: 'default-profile.jpg'); ?>" 
                                 class="host-profile-pic" alt="<?php echo htmlspecialchars($event['host_name']); ?>">
                            <div class="event-host-info">
                                <div class="event-host-name"><?php echo htmlspecialchars($event['host_name']); ?></div>
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            </div>
                        </div>
                        
                        <div class="event-body">
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <div class="event-details">
                                <div class="event-detail">
                                    <div class="event-detail-icon"><i class="far fa-clock"></i></div>
                                    <div class="event-detail-text">
                                        <strong>When:</strong><br>
                                        <?php echo date('M j, Y g:i A', strtotime($event['start_datetime'])); ?><br>
                                        to <?php echo date('M j, Y g:i A', strtotime($event['end_datetime'])); ?>
                                    </div>
                                </div>
                                
                                <?php if ($event['event_type'] == 'online'): ?>
                                    <div class="event-detail">
                                        <div class="event-detail-icon"><i class="fas fa-video"></i></div>
                                        <div class="event-detail-text">
                                            <strong>Online Event:</strong><br>
                                            <a href="<?php echo htmlspecialchars($event['online_link']); ?>" target="_blank">Join Meeting</a>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="event-detail">
                                        <div class="event-detail-icon"><i class="fas fa-map-marker-alt"></i></div>
                                        <div class="event-detail-text">
                                            <strong>Location:</strong><br>
                                            <?php echo htmlspecialchars($event['location_address']); ?>,<br>
                                            <?php echo htmlspecialchars($event['location_city']); ?>, 
                                            <?php echo htmlspecialchars($event['location_state']); ?> - 
                                            <?php echo htmlspecialchars($event['location_pincode']); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="event-actions">
                            <?php if ($event['user_id'] == $_SESSION['user_id']): ?>
                                <span class="event-status host-status">
                                    <i class="fas fa-crown"></i> Your Event
                                </span>
                            <?php else: ?>
                                <?php if (in_array($event['id'], $participating_events)): ?>
                                    <span class="event-status participant-status">
                                        <i class="fas fa-check-circle"></i> You're participating
                                    </span>
                                    <a href="leave_event.php?id=<?php echo $event['id']; ?>" class="leave-btn">
                                        Leave
                                    </a>
                                <?php else: ?>
                                    <a href="join_event.php?id=<?php echo $event['id']; ?>" class="join-btn">
                                        Join Event
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                                  <?php if ($event['user_id'] == $_SESSION['user_id'] || in_array($event['id'], $participating_events)): ?>
        <a href="event_chat.php?id=<?php echo $event['id']; ?>" class="join-btn" style="background-color: #6c5ce7;">
            <i class="fas fa-comments"></i> 
        </a>
    <?php endif; ?>
</div>
                          
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <div class="modal-title">Are you sure?</div>
            <div class="modal-message" id="modalMessage">This action cannot be undone.</div>
            <div class="modal-actions">
                <button class="modal-btn confirm" id="confirmDeleteBtn">Delete</button>
                <button class="modal-btn cancel" id="cancelBtn">Cancel</button>
            </div>
        </div>
    </div>

<script>
    // Toggle dropdown menu
    function toggleDropdown(button) {
        event.preventDefault();
        event.stopPropagation();
        const dropdown = button.parentElement.querySelector('.dropdown-menu');
        const isShowing = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.dropdown-menu.show').forEach(openDropdown => {
            if (openDropdown !== dropdown) {
                openDropdown.classList.remove('show');
            }
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('show');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu.show').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });

    // Stop propagation for dropdown menu clicks
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Add hover effect to event cards
    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
            card.style.boxShadow = '';
        });
    });

    // Confirmation modal functionality
    const modal = document.getElementById('confirmModal');
    const modalMessage = document.getElementById('modalMessage');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    let currentDeleteUrl = '';

    // Set up event listeners for delete links
    document.querySelectorAll('.delete-event-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const eventId = this.getAttribute('data-event-id');
            const eventTitle = this.getAttribute('data-event-title');
            currentDeleteUrl = `delete_event.php?id=${eventId}`;
            modalMessage.innerHTML = `Are you sure you want to delete <strong>"${eventTitle}"</strong>?<br>This action cannot be undone.`;
            modal.style.display = 'flex';
        });
    });

    confirmDeleteBtn.addEventListener('click', function() {
        window.location.href = currentDeleteUrl;
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
</script>
</body>
</html>
<?php $conn->close(); ?>