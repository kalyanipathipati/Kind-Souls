<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
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

// Verify admin exists
$stmt = $pdo->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    // Admin doesn't exist in database
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// Admin functions
function getAllUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getBlockedUsers($pdo) {
    $stmt = $pdo->query("SELECT b.*, 
                        blocker.name as blocker_name, 
                        blocked.name as blocked_name 
                        FROM blocked_users b
                        JOIN users blocker ON b.blocker_id = blocker.id
                        JOIN users blocked ON b.blocked_id = blocked.id
                        ORDER BY b.blocked_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
    } elseif (isset($_POST['unblock_user'])) {
        $blockId = $_POST['block_id'];
        $stmt = $pdo->prepare("DELETE FROM blocked_users WHERE id = ?");
        $stmt->execute([$blockId]);
    }
    
    // Refresh the page after action
    header("Location: admin.php");
    exit();
}

// Get all data
$users = getAllUsers($pdo);
$blockedUsers = getBlockedUsers($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6c5ce7;
            --secondary-color: #a29bfe;
            --dark-color: #2d3436;
            --light-color: #f5f6fa;
            --danger-color: #d63031;
            --success-color: #00b894;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .admin-section {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
            margin-bottom: 40px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .admin-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.1);
        }
        
        .section-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 18px 25px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-header i {
            margin-right: 12px;
            font-size: 22px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background-color: rgba(108, 92, 231, 0.05);
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .action-btn i {
            margin-right: 6px;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(214, 48, 49, 0.2);
        }
        
        .unblock-btn {
            background-color: var(--success-color);
            color: white;
        }
        
        .unblock-btn:hover {
            background-color: #00a884;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 184, 148, 0.2);
        }
        
        .logout-btn {
            background-color: white;
            color: var(--danger-color);
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
        }
        
        .logout-btn:hover {
            background-color: var(--danger-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(214, 48, 49, 0.2);
        }
        
        .logout-btn i {
            margin-right: 6px;
        }
        
        .welcome-message {
            margin-right: 20px;
            color: white;
            font-weight: 500;
        }
        
        .admin-actions {
            display: flex;
            align-items: center;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-item {
            text-align: center;
            padding: 0 20px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 16px;
            color: var(--dark-color);
            opacity: 0.8;
        }
        
        .divider {
            height: 60px;
            width: 1px;
            background-color: #eee;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background-color: rgba(0, 184, 148, 0.1);
            color: var(--success-color);
        }
        
        .status-blocked {
            background-color: rgba(214, 48, 49, 0.1);
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h2><i class="fas fa-shield-alt"></i> KIND SOULS - Admin Dashboard</h2>
        <div class="admin-actions">
            <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
            <a href="admin_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="admin-container">
        <!-- Stats Overview -->
        <div class="stats-card">
            <div class="stat-item">
                <div class="stat-number"><?php echo count($users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="divider"></div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($blockedUsers); ?></div>
                <div class="stat-label">Blocked Users</div>
            </div>
            <div class="divider"></div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count(array_unique(array_column($blockedUsers, 'blocker_id'))); ?></div>
                <div class="stat-label">Users Who Blocked</div>
            </div>
        </div>
        
        <!-- Users Section -->
        <div class="admin-section">
            <div class="section-header">
                <i class="fas fa-user-cog"></i> Users Management
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): 
                        $isBlocked = false;
                        foreach ($blockedUsers as $blocked) {
                            if ($blocked['blocked_id'] == $user['id']) {
                                $isBlocked = true;
                                break;
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $isBlocked ? 'status-blocked' : 'status-active'; ?>">
                                    <?php echo $isBlocked ? 'Blocked' : 'Active'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Blocked Users Section -->
        <div class="admin-section">
            <div class="section-header">
                <i class="fas fa-user-slash"></i> Blocked Users Management
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Blocker</th>
                        <th>Blocked User</th>
                        <th>Blocked At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blockedUsers as $block): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($block['id']); ?></td>
                            <td><?php echo htmlspecialchars($block['blocker_name']); ?></td>
                            <td><?php echo htmlspecialchars($block['blocked_name']); ?></td>
                            <td><?php echo date('M j, Y H:i', strtotime($block['blocked_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="block_id" value="<?php echo $block['id']; ?>">
                                    <button type="submit" name="unblock_user" class="action-btn unblock-btn">
                                        <i class="fas fa-user-check"></i> Unblock
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>