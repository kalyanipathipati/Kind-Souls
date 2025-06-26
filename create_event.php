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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_type = $_POST['event_type'];
    $online_link = $event_type == 'online' ? trim($_POST['online_link']) : null;
    $location_address = $event_type == 'offline' ? trim($_POST['location_address']) : null;
    $location_city = $event_type == 'offline' ? trim($_POST['location_city']) : null;
    $location_state = $event_type == 'offline' ? trim($_POST['location_state']) : null;
    $location_pincode = $event_type == 'offline' ? trim($_POST['location_pincode']) : null;
    $start_datetime = $_POST['start_datetime'];
    $end_datetime = $_POST['end_datetime'];
    
    // Validate inputs
    if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
        $error = 'Title and date/time are required';
    } elseif ($event_type == 'online' && empty($online_link)) {
        $error = 'Online meeting link is required for online events';
    } elseif ($event_type == 'offline' && (empty($location_address) || empty($location_city))) {
        $error = 'Address and city are required for offline events';
    } elseif (strtotime($start_datetime) >= strtotime($end_datetime)) {
        $error = 'End time must be after start time';
    } else {
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO events (user_id, title, description, event_type, online_link, location_address, location_city, location_state, location_pincode, start_datetime, end_datetime) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssss", $_SESSION['user_id'], $title, $description, $event_type, $online_link, $location_address, $location_city, $location_state, $location_pincode, $start_datetime, $end_datetime);
        
        if ($stmt->execute()) {
            $success = 'Event created successfully!';
            // Auto-join the creator to the event
            $event_id = $stmt->insert_id;
            $stmt2 = $conn->prepare("INSERT INTO event_participants (event_id, user_id, status) VALUES (?, ?, 'going')");
            $stmt2->bind_param("ii", $event_id, $_SESSION['user_id']);
            $stmt2->execute();
            $stmt2->close();
            
            // Reset form on success
            $_POST = array();
        } else {
            $error = 'Error creating event: ' . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Event | Kind Souls</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        h1 {
            color: var(--primary-dark);
            margin-bottom: 25px;
            text-align: center;
            font-size: 2rem;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .error {
            background-color: var(--error-bg);
            color: var(--error);
            border-left: 4px solid var(--error);
        }
        
        .success {
            background-color: var(--success-bg);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        
        input[type="text"],
        input[type="url"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .radio-option input {
            width: auto;
        }
        
        .address-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .address-fields .form-group {
            margin-bottom: 0;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(156, 136, 255, 0.3);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .back-link i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .address-fields {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-calendar-plus"></i> Create New Event</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="title">Event Title</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Event Type</label>
                <div class="radio-group">
                    <label class="radio-option">
                        <input type="radio" id="online" name="event_type" value="online" <?php echo ($_POST['event_type'] ?? 'online') == 'online' ? 'checked' : ''; ?> required>
                        <span>Online Event</span>
                    </label>
                    <label class="radio-option">
                        <input type="radio" id="offline" name="event_type" value="offline" <?php echo ($_POST['event_type'] ?? '') == 'offline' ? 'checked' : ''; ?>>
                        <span>In-Person Event</span>
                    </label>
                </div>
            </div>
            
            <div id="online-fields" style="display: <?php echo ($_POST['event_type'] ?? 'online') == 'online' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="online_link">Meeting Link</label>
                    <input type="url" id="online_link" name="online_link" placeholder="https://meet.google.com/..." value="<?php echo htmlspecialchars($_POST['online_link'] ?? ''); ?>">
                </div>
            </div>
            
            <div id="offline-fields" style="display: <?php echo ($_POST['event_type'] ?? '') == 'offline' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="location_address">Address</label>
                    <input type="text" id="location_address" name="location_address" placeholder="123 Main St" value="<?php echo htmlspecialchars($_POST['location_address'] ?? ''); ?>">
                </div>
                <div class="address-fields">
                    <div class="form-group">
                        <label for="location_city">City</label>
                        <input type="text" id="location_city" name="location_city" placeholder="New York" value="<?php echo htmlspecialchars($_POST['location_city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location_state">State</label>
                        <input type="text" id="location_state" name="location_state" placeholder="NY" value="<?php echo htmlspecialchars($_POST['location_state'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="location_pincode">Postal Code</label>
                        <input type="text" id="location_pincode" name="location_pincode" placeholder="10001" value="<?php echo htmlspecialchars($_POST['location_pincode'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="start_datetime">Start Date & Time</label>
                <input type="datetime-local" id="start_datetime" name="start_datetime" value="<?php echo htmlspecialchars($_POST['start_datetime'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="end_datetime">End Date & Time</label>
                <input type="datetime-local" id="end_datetime" name="end_datetime" value="<?php echo htmlspecialchars($_POST['end_datetime'] ?? ''); ?>" required>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-calendar-check"></i> Create Event
            </button>
        </form>
        
        <a href="events.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Events
        </a>
    </div>
    
    <script>
        document.querySelectorAll('input[name="event_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('online-fields').style.display = 
                    this.value === 'online' ? 'block' : 'none';
                document.getElementById('offline-fields').style.display = 
                    this.value === 'offline' ? 'block' : 'none';
                
                // Reset validation when switching types
                if (this.value === 'online') {
                    document.getElementById('location_address').required = false;
                    document.getElementById('location_city').required = false;
                    document.getElementById('online_link').required = true;
                } else {
                    document.getElementById('location_address').required = true;
                    document.getElementById('location_city').required = true;
                    document.getElementById('online_link').required = false;
                }
            });
        });
        
        // Set initial required states
        document.addEventListener('DOMContentLoaded', function() {
            const eventType = document.querySelector('input[name="event_type"]:checked').value;
            if (eventType === 'online') {
                document.getElementById('online_link').required = true;
            } else {
                document.getElementById('location_address').required = true;
                document.getElementById('location_city').required = true;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>