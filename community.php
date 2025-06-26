<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
$currentDate = date('Y-m-d');
$deleteExpired = "DELETE FROM requests WHERE last_date_for_help IS NOT NULL AND last_date_for_help < :currentDate";
$stmt = $pdo->prepare($deleteExpired);
$stmt->execute(['currentDate' => $currentDate]);
$is_logged_in = false;
$user_id = null;
$profileImage = null;
$requests = []; 
$unread_count = 0;
$notification = null;
$notification_type = '';

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    $user_id = $_SESSION['user_id'];

    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && !empty($user['profile_image'])) {
        $profileImage = 'uploads/' . $user['profile_image'];
    }

    $unread_query = "SELECT COUNT(*) AS unread_count FROM messages WHERE receiver_id = :user_id AND is_read = 0";
    $unread_stmt = $pdo->prepare($unread_query);
    $unread_stmt->execute(['user_id' => $user_id]);
    $unread_result = $unread_stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $unread_result['unread_count'] ?? 0;
}


if (isset($_POST['submit_request']) && $is_logged_in) {
    $request_text = $_POST['request_text'];
    $help_type = $_POST['help_type'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $urgency = $_POST['urgency'] ?? 'medium';
    $contact_preference = $_POST['contact_preference'] ?? '';
    $additional_details = $_POST['additional_details'] ?? '';
    $last_date_for_help = $_POST['last_date_for_help'];

    $query = "INSERT INTO requests (user_id, request_text, help_type, address, city, state, pincode, urgency, contact_preference, additional_details, last_date_for_help, created_at) 
              VALUES (:user_id, :request_text, :help_type, :address, :city, :state, :pincode, :urgency, :contact_preference, :additional_details, :last_date_for_help, NOW())";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'user_id' => $user_id,
        'request_text' => $request_text,
        'help_type' => $help_type,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'pincode' => $pincode,
        'urgency' => $urgency,
        'contact_preference' => $contact_preference,
        'additional_details' => $additional_details,
        'last_date_for_help' => date('Y-m-d', strtotime($last_date_for_help))
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['notification'] = 'Request submitted successfully!';
        $_SESSION['notification_type'] = 'success';
        header("Location: community.php");
        exit();
    } else {
        $_SESSION['notification'] = 'Error: Unable to submit request.';
        $_SESSION['notification_type'] = 'error';
    }
}

if (isset($_POST['submit_comment']) && $is_logged_in) {
    $request_id = $_POST['request_id'];
    $comment_text = $_POST['comment_text'];

    $insert_comment = "INSERT INTO comments (request_id, user_id, comment_text, created_at) VALUES (:request_id, :user_id, :comment_text, NOW())";
    $stmt = $pdo->prepare($insert_comment);
    $stmt->execute([
        'request_id' => $request_id,
        'user_id' => $user_id,
        'comment_text' => $comment_text
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['notification'] = 'Comment posted successfully!';
        $_SESSION['notification_type'] = 'success';
        header("Location: community.php");
        exit();
    } else {
        $_SESSION['notification'] = 'Error: Unable to post comment.';
        $_SESSION['notification_type'] = 'error';
    }
}

// Handle post deletion
if (isset($_POST['delete_post']) && $is_logged_in) {
    $post_id = $_POST['post_id'];
    
    
    $check_query = "SELECT id FROM requests WHERE id = :post_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['post_id' => $post_id, 'user_id' => $user_id]);
    $post_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post_exists) {
        
        $delete_comments = "DELETE FROM comments WHERE request_id = :post_id";
        $stmt = $pdo->prepare($delete_comments);
        $stmt->execute(['post_id' => $post_id]);
        
        
        $delete_post = "DELETE FROM requests WHERE id = :post_id";
        $stmt = $pdo->prepare($delete_post);
        $stmt->execute(['post_id' => $post_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = 'Post deleted successfully!';
            $_SESSION['notification_type'] = 'success';
            header("Location: community.php");
            exit();
        } else {
            $_SESSION['notification'] = 'Error: Unable to delete post.';
            $_SESSION['notification_type'] = 'error';
        }
    } else {
        $_SESSION['notification'] = 'Error: You don\'t have permission to delete this post.';
        $_SESSION['notification_type'] = 'error';
    }
}


if (isset($_POST['delete_comment']) && $is_logged_in) {
    $comment_id = $_POST['comment_id'];
    
    // First check if the comment belongs to the user
    $check_query = "SELECT id FROM comments WHERE id = :comment_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
    $comment_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment_exists) {
        $delete_comment = "DELETE FROM comments WHERE id = :comment_id";
        $stmt = $pdo->prepare($delete_comment);
        $stmt->execute(['comment_id' => $comment_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = 'Comment deleted successfully!';
            $_SESSION['notification_type'] = 'success';
            header("Location: community.php");
            exit();
        } else {
            $_SESSION['notification'] = 'Error: Unable to delete comment.';
            $_SESSION['notification_type'] = 'error';
        }
    } else {
        $_SESSION['notification'] = 'Error: You don\'t have permission to delete this comment.';
        $_SESSION['notification_type'] = 'error';
    }
}

// Handle post edit
if (isset($_POST['edit_post']) && $is_logged_in) {
    $post_id = $_POST['post_id'];
    $edited_text = $_POST['edited_text'];
    $help_type = $_POST['help_type'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $urgency = $_POST['urgency'] ?? 'medium';
    $contact_preference = $_POST['contact_preference'] ?? '';
    $additional_details = $_POST['additional_details'] ?? '';
    $last_date_for_help = $_POST['last_date_for_help'] ?? null;
    
    // First check if the post belongs to the user
    $check_query = "SELECT id FROM requests WHERE id = :post_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['post_id' => $post_id, 'user_id' => $user_id]);
    $post_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post_exists) {
        $update_post = "UPDATE requests SET 
                        request_text = :edited_text,
                        help_type = :help_type,
                        address = :address,
                        city = :city,
                        state = :state,
                        pincode = :pincode,
                        urgency = :urgency,
                        contact_preference = :contact_preference,
                        additional_details = :additional_details,
                        last_date_for_help = :last_date_for_help
                        WHERE id = :post_id";
        $stmt = $pdo->prepare($update_post);
        $stmt->execute([
            'edited_text' => $edited_text,
            'help_type' => $help_type,
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'pincode' => $pincode,
            'urgency' => $urgency,
            'contact_preference' => $contact_preference,
            'additional_details' => $additional_details,
            'last_date_for_help' => $last_date_for_help ? date('Y-m-d', strtotime($last_date_for_help)) : null,
            'post_id' => $post_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = 'Post updated successfully!';
            $_SESSION['notification_type'] = 'success';
            header("Location: community.php");
            exit();
        } else {
            $_SESSION['notification'] = 'Error: Unable to update post.';
            $_SESSION['notification_type'] = 'error';
        }
    } else {
        $_SESSION['notification'] = 'Error: You don\'t have permission to edit this post.';
        $_SESSION['notification_type'] = 'error';
    }
}

// Handle comment edit
if (isset($_POST['edit_comment']) && $is_logged_in) {
    $comment_id = $_POST['comment_id'];
    $edited_text = $_POST['edited_text'];
    
    // First check if the comment belongs to the user
    $check_query = "SELECT id FROM comments WHERE id = :comment_id AND user_id = :user_id";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->execute(['comment_id' => $comment_id, 'user_id' => $user_id]);
    $comment_exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment_exists) {
        $update_comment = "UPDATE comments SET comment_text = :edited_text WHERE id = :comment_id";
        $stmt = $pdo->prepare($update_comment);
        $stmt->execute(['edited_text' => $edited_text, 'comment_id' => $comment_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['notification'] = 'Comment updated successfully!';
            $_SESSION['notification_type'] = 'success';
            header("Location: community.php");
            exit();
        } else {
            $_SESSION['notification'] = 'Error: Unable to update comment.';
            $_SESSION['notification_type'] = 'error';
        }
    } else {
        $_SESSION['notification'] = 'Error: You don\'t have permission to edit this comment.';
        $_SESSION['notification_type'] = 'error';
    }
}

// Check for any notifications in session
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    $notification_type = $_SESSION['notification_type'];
    unset($_SESSION['notification']);
    unset($_SESSION['notification_type']);
}

// Handle filter submission - change this section:
$filter_city = isset($_GET['filter_city']) ? trim($_GET['filter_city']) : '';
$filter_state = isset($_GET['filter_state']) ? trim($_GET['filter_state']) : '';
$filter_help_type = isset($_GET['filter_help_type']) ? trim($_GET['filter_help_type']) : '';
$filter_urgency = isset($_GET['filter_urgency']) ? trim($_GET['filter_urgency']) : '';

// Base query for fetching requests
$query = "SELECT requests.id, requests.request_text, requests.help_type, requests.address, 
                 requests.city, requests.state, requests.pincode, requests.urgency, 
                 requests.contact_preference, requests.additional_details, requests.created_at, requests.last_date_for_help,
                 users.name, users.id AS user_id, users.profile_image 
          FROM requests 
          JOIN users ON requests.user_id = users.id 
          WHERE 1=1";

$params = [];

// Add filters to query if they are set - modify this section:
if (!empty($filter_city)) {
    $query .= " AND requests.city = :city";
    $params[':city'] = $filter_city;
}

if (!empty($filter_state)) {
    $query .= " AND requests.state = :state";
    $params[':state'] = $filter_state;
}

if (!empty($filter_help_type)) {
    $query .= " AND requests.help_type = :help_type";
    $params[':help_type'] = $filter_help_type;
}

if (!empty($filter_urgency)) {
    $query .= " AND requests.urgency = :urgency";
    $params[':urgency'] = $filter_urgency;
}

$query .= " ORDER BY requests.created_at DESC";

// Prepare and execute the query - modify this section:
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare comments for each request
foreach ($requests as &$request) {
    $comment_query = "SELECT comments.id, users.id AS user_id, users.name, users.profile_image, comments.comment_text, comments.created_at 
                      FROM comments 
                      JOIN users ON comments.user_id = users.id 
                      WHERE comments.request_id = :request_id 
                      ORDER BY comments.created_at ASC";
    $comment_stmt = $pdo->prepare($comment_query);
    $comment_stmt->execute(['request_id' => $request['id']]);
    $request['comments'] = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($request); // Break the reference

// Get distinct values for filter dropdowns
$cities_query = "SELECT DISTINCT city FROM requests WHERE city IS NOT NULL AND city != '' ORDER BY city";
$states_query = "SELECT DISTINCT state FROM requests WHERE state IS NOT NULL AND state != '' ORDER BY state";
$help_types_query = "SELECT DISTINCT help_type FROM requests WHERE help_type IS NOT NULL AND help_type != '' ORDER BY help_type";

$cities = $pdo->query($cities_query)->fetchAll(PDO::FETCH_COLUMN);
$states = $pdo->query($states_query)->fetchAll(PDO::FETCH_COLUMN);
$help_types = $pdo->query($help_types_query)->fetchAll(PDO::FETCH_COLUMN);     
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KIND SOULS - Community</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
             /* Basic Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Arial', sans-serif;
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

    /* Notification System */
    .notification {
        position: fixed;
        top: 80px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1001;
        display: flex;
        align-items: center;
        animation: slideIn 0.5s forwards, fadeOut 0.5s forwards 3s;
        max-width: 350px;
        border-left: 5px solid;
    }

    .notification.success {
        background-color: #b39ddb; /* Lavender */
        border-left-color: #7e57c2; /* Darker lavender */
    }

    .notification.error {
        background-color: #d1c4e9; /* Light lavender */
        border-left-color: #b39ddb; /* Lavender */
        color: #4a148c; /* Dark purple for text */
    }

    .notification i {
        margin-right: 10px;
        font-size: 20px;
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }

    /* Navigation Bar */
    .nav-logo {
        display: flex;
        align-items: center;
        padding: 10px;
        background-color: #b39ddb; /* Darker lavender */
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
        color: #4a148c; /* Light lavender */
    }

    nav a:hover::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -5px;
        width: 50%;
        height: 2px;
        background-color: #4a148c; /* Light lavender */
        transform: translateX(-50%);
        animation: underline 0.3s ease;
    }

    @keyframes underline {
        from { width: 0; }
        to { width: 50%; }
    }

    /* Active Page Styling */
    nav a.active {
        color: #4a148c; /* Light lavender */
        font-weight: bold;
    }

    nav a.active::after {
        content: '';
        position: absolute;
        left: 50%;
        bottom: -5px;
        width: 50%;
        height: 2px;
        background-color: #4a148c; /* Light lavender */
        transform: translateX(-50%);
    }

    /* Notification badge */

    /* Notification badge */





.notification-badge {
    background-color: #ff4081; /* Pink accent */
    color: white;
    border-radius: 100%;
    font-size: 10px; /* Adjusted text size */
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

    /* Profile image */
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

    /* Main Content */

    /* Request Section */
    .request-section {
        margin-bottom: 30px;
    }

    .request-section h2 {
        font-size: 24px;
        margin-bottom: 20px;
        color: #7e57c2; /* Darker lavender */
        border-bottom: 2px solid #d1c4e9; /* Light lavender */
        padding-bottom: 10px;
    }

    /* Plus Button */
    .plus-button {
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
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 1000;
    }






    .plus-button:hover {
        background-color: #9575cd; /* Medium lavender */
        transform: scale(1.1);
    }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1001;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 25px;
        border-radius: 10px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        border-top: 5px solid #7e57c2; /* Darker lavender */
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close:hover {
        color: #7e57c2; /* Darker lavender */
    }

    /* Request Form */
    .request-form textarea {
        width: 100%;
        padding: 15px;
        border: 1px solid #d1c4e9; /* Light lavender */
        border-radius: 8px;
        font-size: 16px;
        margin-bottom: 15px;
        resize: vertical;
        min-height: 120px;
    }

    .request-form button {
        background-color: #7e57c2; /* Darker lavender */
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 100%;
    }

    .request-form button:hover {
        background-color: #9575cd; /* Medium lavender */
    }

    /* Request Grid */
    .requests-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }

    .request-card {
        background-color: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        padding: 20px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        min-height: 250px;
        display: flex;
        flex-direction: column;
        border-top: 3px solid #b39ddb; /* Lavender */
    }

    .request-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }

    .request-content {
        flex: 1;
    }

    .request-user {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        justify-content: space-between;
    }

    .request-user-info {
        display: flex;
        align-items: center;
    }

    .request-user-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
        border: 2px solid #d1c4e9; /* Light lavender */
    }

    .request-user-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 2px;
    }

    .request-date {
        font-size: 12px;
        color: #999;
    }

    .request-text {
       margin: 15px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #b39ddb; /* Lavender accent */
    }

    /* 3 Dots Menu */
    .menu-dots {
        position: relative;
        display: inline-block;
    }

    .menu-dots-btn {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 18px;
        padding: 5px;
    }

    .menu-dots-btn:hover {
        color: #7e57c2; /* Darker lavender */
    }

    .menu-dropdown {
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 120px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 6px;
        z-index: 1;
        display: none;
        border: 1px solid #d1c4e9; /* Light lavender */
    }

    .menu-dropdown.show {
        display: block;
    }

    .menu-dropdown button {
        width: 100%;
        text-align: left;
        padding: 8px 12px;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 14px;
        color: #333;
        display: flex;
        align-items: center;
    }

    .menu-dropdown button:hover {
        background-color: #f3e5f5; /* Very light lavender */
    }

    .menu-dropdown button i {
        margin-right: 8px;
    }

    .edit-option {
        color: #7e57c2; /* Darker lavender */
    }

    .delete-option {
        color: #e53935; /* Red for delete */
    }

    /* Confirmation Modal */
    .confirmation-modal {
        display: none;
        position: fixed;
        z-index: 1002;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .confirmation-content {
        background-color: #fefefe;
        margin: 20% auto;
        padding: 25px;
        border-radius: 10px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        text-align: center;
        border-top: 5px solid #7e57c2; /* Darker lavender */
    }

    .confirmation-content h3 {
        color: #7e57c2; /* Darker lavender */
        margin-bottom: 10px;
    }

    .confirmation-content p {
        color: #666;
        margin-bottom: 20px;
    }

    .confirmation-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }

    .confirm-btn, .cancel-btn {
        padding: 8px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .confirm-btn {
        background-color: #7e57c2; /* Darker lavender */
        color: white;
        border: none;
    }

    .confirm-btn:hover {
        background-color: #9575cd; /* Medium lavender */
    }

    .cancel-btn {
        background-color: #f5f5f5;
        color: #7e57c2; /* Darker lavender */
        border: 1px solid #d1c4e9; /* Light lavender */
    }

    .cancel-btn:hover {
        background-color: #d1c4e9; /* Light lavender */
    }

    /* Action Icons */
    .action-icons {
        display: flex;
        gap: 10px;
    }

    .action-icon {
        color: #666;
        cursor: pointer;
        font-size: 18px;
        transition: color 0.3s ease;
    }

    .action-icon:hover {
        color: #7e57c2; /* Darker lavender */
    }

    /* Instagram-style Comments Section */
    .comments-container {
        display: none;
        margin-top: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding-right: 5px;
    }

    .comments-container.show {
        display: block;
    }

    .comment-item {
        margin-bottom: 8px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        position: relative;
    }

    .comment-item:last-child {
        border-bottom: none;
    }

    .comment-user {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }

    .comment-user-img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 8px;
        border: 1px solid #d1c4e9; /* Light lavender */
    }

    .comment-username {
        font-weight: 600;
        font-size: 13px;
        color: #262626;
    }

    .comment-text {
        font-size: 13px;
        line-height: 1.4;
        color: #262626;
        margin-left: 32px;
        word-break: break-word;
    }

    .comment-date {
        font-size: 10px;
        color: #999;
        margin-left: 32px;
        margin-top: 2px;
    }

    /* Comment Form */
    .comment-form {
        margin-top: 15px;
        display: flex;
        align-items: center;
        border-top: 1px solid #efefef;
        padding-top: 15px;
    }

    .comment-form textarea {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #d1c4e9; /* Light lavender */
        border-radius: 20px;
        font-size: 13px;
        resize: none;
        min-height: 40px;
        max-height: 80px;
        outline: none;
    }

    .comment-form button {
        background-color: #7e57c2; /* Darker lavender */
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        margin-left: 10px;
    }

    .comment-form button:hover {
        background-color: #9575cd; /* Medium lavender */
    }

    /* Edit Form */
    .edit-form {
        display: none;
        margin-top: 10px;
    }

    .edit-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #d1c4e9; /* Light lavender */
        border-radius: 6px;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
    }

    .edit-form-buttons {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .edit-form-buttons button {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .save-edit {
        background-color: #7e57c2; /* Darker lavender */
        color: white;
        border: none;
    }

    .save-edit:hover {
        background-color: #9575cd; /* Medium lavender */
    }

    .cancel-edit {
        background-color: #f5f5f5;
        color: #7e57c2; /* Darker lavender */
        border: 1px solid #d1c4e9; /* Light lavender */
    }

    .cancel-edit:hover {
        background-color: #d1c4e9; /* Light lavender */
    }

    /* Footer */
    footer {
        background-color: #7e57c2; /* Darker lavender */
        color: white;
        text-align: center;
        padding: 15px;
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .requests-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .requests-grid {
            grid-template-columns: 1fr;
        }
        
        .main-content {
            margin: 80px auto 60px;
            padding: 20px;
        }
        
        .request-card {
            padding: 15px;
        }
        
        .modal-content {
            width: 95%;
            margin: 20% auto;
        }
        
        nav a {
            margin: 0 5px;
            font-size: 16px;
        }
    }

    @media (max-width: 480px) {
        .nav-container {
            flex-direction: column;
            align-items: flex-end;
        }
        
        nav {
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        
        nav a {
            font-size: 14px;
            padding: 3px 5px;
        }
        
        .comment-form {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .comment-form button {
            margin-left: 0;
            margin-top: 8px;
            align-self: flex-end;
        }
    }
    .filter-section {
        background-color: transparent;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #e0e0e0;
    }

    .filter-section h2 {
        font-size: 20px;
        margin-bottom: 20px;
        color: #5e35b1;
        display: flex;
        align-items: center;
    }

    .filter-section h2 i {
        margin-right: 10px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        flex: 1;
        min-width: 180px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
        font-weight: 500;
    }

    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1c4e9;
        border-radius: 6px;
        font-size: 14px;
        background-color: white;
        transition: all 0.3s ease;
    }

    .filter-group select:focus, 
    .filter-group input:focus {
        border-color: #7e57c2;
        box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
        outline: none;
    }

    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .apply-filters {
        background-color: #7e57c2;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .apply-filters:hover {
        background-color: #5e35b1;
    }

    .reset-filters {
        color: #7e57c2;
        text-decoration: none;
        font-size: 14px;
        padding: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .reset-filters:hover {
        color: #5e35b1;
        text-decoration: underline;
    }

    /* Enhanced Request Form */
    .modal-content {
        background-color: white;
        border-radius: 12px;
        padding: 30px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border-top: 4px solid #7e57c2;
    }

    .modal-content h3 {
        color: #5e35b1;
        margin-bottom: 25px;
        font-size: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .request-form .form-group {
        margin-bottom: 20px;
    }

    .request-form label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        color: #555;
        font-weight: 500;
    }

    .request-form input[type="text"],
    .request-form input[type="email"],
    .request-form input[type="tel"],
    .request-form select,
    .request-form textarea {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #d1c4e9;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        background-color: white;
    }

    .request-form textarea {
        min-height: 120px;
        resize: vertical;
    }

    .request-form input:focus,
    .request-form select:focus,
    .request-form textarea:focus {
        border-color: #7e57c2;
        box-shadow: 0 0 0 3px rgba(126, 87, 194, 0.2);
        outline: none;
    }

    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .form-row .form-group {
        flex: 1;
    }

    .request-form button[type="submit"] {
        background-color: #7e57c2;
        color: white;
        border: none;
        padding: 14px 25px;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .request-form button[type="submit"]:hover {
        background-color: #5e35b1;
        transform: translateY(-2px);
    }

    /* Enhanced Dropdown Select */
    .request-form select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 16px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 15px;
        }
        
        .filter-group {
            min-width: 100%;
        }
        
        .filter-actions {
            width: 100%;
            justify-content: flex-end;
        }
    }

    /* Excel-like filter chips */
    .active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .filter-chip {
        background-color: #ede7f6;
        color: #5e35b1;
        padding: 5px 12px;
        border-radius: 16px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .filter-chip .remove-filter {
        color: #7e57c2;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
    }

    .filter-chip .remove-filter:hover {
        color: #5e35b1;
    }

    /* Calendar icon styling */
  .calendar-icon {
        position: fixed;
        bottom: 20px;
        left: 20px;
        background-color: #7e57c2;
        color: white;
        padding: 12px 15px;
        border-radius: 50px;
        font-size: 16px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .calendar-icon:hover {
        background-color: #5e35b1;
        transform: translateY(-3px);
    }


.login-prompt a {
    display: inline-block;
    background-color: #7e57c2;
    color: white;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 8px rgba(126, 87, 194, 0.2);
    position: relative;
    overflow: hidden;
}

.login-prompt a:hover {
    background-color: #5e35b1;
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(126, 87, 194, 0.3);
}

.login-prompt a:active {
    transform: translateY(0);
}

.login-prompt a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: 0.5s;
}

.login-prompt a:hover::before {
    left: 100%;
}

.login-prompt .icon {
    margin-right: 10px;
}

/* Optional: Add a decorative element */

.request-deadline {
    margin-bottom: 0px;
    
    background-color: #fff3e0;
    
    font-size: 13px;
    color: #e65100;
    display: flex;
    align-items: center;
    gap: 8px;
    
}

.request-deadline i {
    font-size: 14px;
    color: #e65100;
}

/* Add this to make sure the date input looks good */
input[type="date"] {
    padding: 10px;
    border: 1px solid #d1c4e9;
    border-radius: 6px;
    font-family: inherit;
    width: 100%;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}


    </style>
</head>
<body>

<!-- Notification System -->
<?php if ($notification): ?>
    <div class="notification <?php echo $notification_type; ?>">
        <i class="fas <?php echo $notification_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($notification); ?>
    </div>
<?php endif; ?>

<div class="nav-logo">
    <img src="uploads/symbol.webp" alt="KIND SOULS Logo">
    <span>KIND SOULS</span>
   
    <nav>
        <a href="index.php">Home</a>
        <a href="community.php" class="active">Community Page</a>
        <a href="aboutus.php">About Us</a>
        <a href="profiles.php">All Profiles</a>
        <a href="users_list.php">
            <i class="fas fa-envelope message-icon"></i>
            <?php if ($is_logged_in && $unread_count > 0): ?>
                <span class='notification-badge'><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php">
            <?php if (!empty($profileImage)): ?>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Image" class="profile-image">
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
            <?php endif; ?>
        </a>
    </nav>
</div>
<br>

<!-- Add this to your community.php file -->

<a href="events.php" class="calendar-icon" title="View Events">
    <i class="fas fa-calendar-alt"></i>
</a>

<div class="request-section">

<?php if ($is_logged_in): ?>
        <button id="openRequestModal" class="plus-button">
            <i class="fas fa-plus"></i>
        </button>
    <?php endif; ?>



        <?php if ($is_logged_in): ?>
        <div id="requestModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3><i class="fas fa-plus-circle"></i> Create a Help Request</h3>
                <form method="POST" class="request-form">
                    <div class="form-group">
                        <label for="request_text"></i> Request Title*</label>
                        <input type="text" name="request_text" required placeholder="Brief title of your request">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="help_type"><i class="fas fa-tag"></i> Type of Help Needed*</label>
                            <select name="help_type" required>
                                <option value="">Select help type</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                                <option value="Pet">Pet</option>
                                <option value="Food">Food</option>
                                <option value="Clothing">Clothing</option>
                                <option value="Shelter">Shelter</option>
                                <option value="Medical">Medical</option>
                                <option value="Education">Education</option>
                                <option value="Financial">Financial</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="urgency"><i class="fas fa-exclamation-circle"></i> Urgency Level*</label>
                            <select name="urgency" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address"><i class="fas fa-map-marked-alt"></i> Address*</label>
                        <textarea name="address" required placeholder="Your full address"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city"><i class="fas fa-city"></i> City*</label>
                            <input type="text" name="city" required placeholder="Your city">
                        </div>
                        
                        <div class="form-group">
                            <label for="state"><i class="fas fa-map"></i> State*</label>
                            <input type="text" name="state" required placeholder="Your state">
                        </div>
                        
                        <div class="form-group">
                            <label for="pincode"><i class="fas fa-map-pin"></i> Pincode*</label>
                            <input type="text" name="pincode" required placeholder="Your area pincode">
                        </div>
                    </div>
                    
                   
                    
                    <div class="form-group">
                        <label for="additional_details"><i class="fas fa-info-circle"></i> Additional Details</label>
                        <textarea name="additional_details" placeholder="Any additional information that might be helpful"></textarea>
                   <div class="form-group">
    <label for="last_date_for_help"><i class="fas fa-calendar-times"></i> Last Date for Help*</label>
    <input type="date" name="last_date_for_help" id="last_date_for_help" min="<?php echo date('Y-m-d'); ?>" required>
    <small class="form-text text-muted">This request will be automatically removed after this date.</small>
</div>
                    <button type="submit" name="submit_request">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                </form>
            </div>
        </div>
<?php else: ?>
    <div class="login-prompt">
        <p><i class="fas fa-exclamation-circle icon"></i> You need to be logged in to submit a help request to the community.</p>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Log In</a>
    </div>
<?php endif; ?>

    </div>
<br>
<br> 

<div class="main-content">
    <!-- Enhanced Filter Section -->
    <div class="filter-section">
        <h2><i class="fas fa-filter"></i> Filters</h2>
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="filter_city"><i class="fas fa-city"></i> City</label>
                <select id="filter_city" name="filter_city">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo htmlspecialchars($city); ?>" <?php echo $filter_city === $city ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_state"><i class="fas fa-map-marker-alt"></i> State</label>
                <select id="filter_state" name="filter_state">
                    <option value="">All States</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo htmlspecialchars($state); ?>" <?php echo $filter_state === $state ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($state); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>
            
            <div class="filter-group">
                <label for="filter_help_type"><i class="fas fa-hands-helping"></i> Help Type</label>
                <select id="filter_help_type" name="filter_help_type">
                    <option value="">All Types</option>
                    <?php foreach ($help_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_help_type === $type ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="filter_urgency"><i class="fas fa-clock"></i> Urgency</label>
                <select id="filter_urgency" name="filter_urgency">
                    <option value="">All Urgency Levels</option>
                    <option value="low" <?php echo $filter_urgency === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filter_urgency === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filter_urgency === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="urgent" <?php echo $filter_urgency === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="apply-filters">
                    <i class="fas fa-check"></i> Apply
                </button>
                <a href="community.php" class="reset-filters">
                    <i class="fas fa-undo"></i> 
                </a>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php if ($filter_city || $filter_state || $filter_help_type || $filter_urgency): ?>
        <div class="active-filters">
            <?php if ($filter_city): ?>
            <div class="filter-chip">
                City: <?php echo htmlspecialchars($filter_city); ?>
                <a href="community.php?<?php echo http_build_query(array_merge($_GET, ['filter_city' => ''])); ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($filter_state): ?>
            <div class="filter-chip">
                State: <?php echo htmlspecialchars($filter_state); ?>
                <a href="community.php?<?php echo http_build_query(array_merge($_GET, ['filter_state' => ''])); ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($filter_help_type): ?>
            <div class="filter-chip">
                Type: <?php echo htmlspecialchars($filter_help_type); ?>
                <a href="community.php?<?php echo http_build_query(array_merge($_GET, ['filter_help_type' => ''])); ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($filter_urgency): ?>
            <div class="filter-chip">
                Urgency: <?php echo ucfirst(htmlspecialchars($filter_urgency)); ?>
                <a href="community.php?<?php echo http_build_query(array_merge($_GET, ['filter_urgency' => ''])); ?>" class="remove-filter">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<div class="requests-grid">
        <?php if (empty($requests)): ?>
            <div class="no-requests">
                <p>No requests found matching your filters.</p>
                <a href="community.php">Clear filters</a>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $request): ?>
                <div class="request-card">
                    <div class="request-content">
                        <div class="request-user">
                            <div class="request-user-info">
                                <img src="uploads/<?php echo htmlspecialchars($request['profile_image'] ?: 'nopp.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($request['name']); ?>" 
                                     class="request-user-img">
                                <div>
                                    <div class="request-user-name"><?php echo htmlspecialchars($request['name']); ?></div>
                                    <div class="request-date"><?php echo date('M j, Y', strtotime($request['created_at'])); ?></div>
                                </div>
                                     
                            </div>
                            <?php if ($is_logged_in && $request['user_id'] == $user_id): ?>
                            <div class="menu-dots">
                                <button class="menu-dots-btn" onclick="toggleMenu(this)"><i class="fas fa-ellipsis-h"></i></button>
                                <div class="menu-dropdown">
                                    <button class="edit-option" onclick="toggleEditForm('request', <?php echo $request['id']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="delete-option" onclick="showConfirmation('post', <?php echo $request['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($request['last_date_for_help'])): ?>
    <div class="request-deadline">
        <i class="fas fa-clock"></i>: <?php echo date('M j', strtotime($request['last_date_for_help'])); ?>
    </div>
<?php endif; ?>
                           <?php endif; ?>
                        </div>
                        
                        <div class="request-meta">
                            <span class="request-type"><?php echo htmlspecialchars($request['help_type']); ?></span>
                            
                            <span class="request-urgency <?php echo htmlspecialchars($request['urgency']); ?>">
                                <?php echo ucfirst(htmlspecialchars($request['urgency'])); ?>
                            </span>
                        </div>
                        
                        <div class="request-text" id="request-text-<?php echo $request['id']; ?>">
                            <h3><?php echo nl2br(htmlspecialchars($request['request_text'])); ?></h3>
                            <?php if (!empty($request['additional_details'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($request['additional_details'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="request-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($request['city'] . ', ' . $request['state'] . ' - ' . $request['pincode']); ?>
                        </div>
                        <div class="request-date">Last Date :<?php echo date('M j, Y', strtotime($request['last_date_for_help'])); ?></div> 
                        <div class="request-contact">
                            
                        </div>
                        
                        <div class="edit-form" id="edit-form-request-<?php echo $request['id']; ?>" style="display: none;">
                            <form method="POST">
                                <input type="hidden" name="post_id" value="<?php echo $request['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Request Title*</label>
                                    <input type="text" name="edited_text" value="<?php echo htmlspecialchars($request['request_text']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Type of Help Needed*</label>
                                    <select name="help_type" required>
                                        <option value="Food" <?php echo $request['help_type'] === 'Food' ? 'selected' : ''; ?>>Food</option>
                                        <option value="Clothing" <?php echo $request['help_type'] === 'Clothing' ? 'selected' : ''; ?>>Clothing</option>
                                        <option value="Shelter" <?php echo $request['help_type'] === 'Shelter' ? 'selected' : ''; ?>>Shelter</option>
                                        <option value="Medical" <?php echo $request['help_type'] === 'Medical' ? 'selected' : ''; ?>>Medical</option>
                                        <option value="Education" <?php echo $request['help_type'] === 'Education' ? 'selected' : ''; ?>>Education</option>
                                        <option value="Financial" <?php echo $request['help_type'] === 'Financial' ? 'selected' : ''; ?>>Financial</option>
                                        <option value="Other" <?php echo $request['help_type'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Address*</label>
                                    <textarea name="address" required><?php echo htmlspecialchars($request['address']); ?></textarea>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>City*</label>
                                        <input type="text" name="city" value="<?php echo htmlspecialchars($request['city']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>State*</label>
                                        <input type="text" name="state" value="<?php echo htmlspecialchars($request['state']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Pincode*</label>
                                        <input type="text" name="pincode" value="<?php echo htmlspecialchars($request['pincode']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Urgency Level*</label>
                                        <select name="urgency" required>
                                            <option value="low" <?php echo $request['urgency'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                            <option value="medium" <?php echo $request['urgency'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="high" <?php echo $request['urgency'] === 'high' ? 'selected' : ''; ?>>High</option>
                                            <option value="urgent" <?php echo $request['urgency'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Preferred Contact Method*</label>
                                        <select name="contact_preference" required>
                                            <option value="message" <?php echo $request['contact_preference'] === 'message' ? 'selected' : ''; ?>>Message</option>
                                            <option value="phone" <?php echo $request['contact_preference'] === 'phone' ? 'selected' : ''; ?>>Phone Call</option>
                                            <option value="email" <?php echo $request['contact_preference'] === 'email' ? 'selected' : ''; ?>>Email</option>
                                            <option value="whatsapp" <?php echo $request['contact_preference'] === 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Additional Details</label>
                                    <textarea name="additional_details"><?php echo htmlspecialchars($request['additional_details']); ?></textarea>
                                </div>
                                
                                <div class="edit-form-buttons">
                                    <button type="submit" name="edit_post" class="save-edit">Save Changes</button>
                                    <button type="button" onclick="toggleEditForm('request', <?php echo $request['id']; ?>)" class="cancel-edit">Cancel</button>
                                </div>
                             <div class="form-group">
    <label>Last Date for Help*</label>
    <input type="date" name="last_date_for_help" value="<?php echo !empty($request['last_date_for_help']) ? date('Y-m-d', strtotime($request['last_date_for_help'])) : date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
    <small class="form-text text-muted">This request will be automatically removed after this date.</small>
</div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="request-actions">
    <div class="action-icons">
        <?php if ($is_logged_in && $request['user_id'] != $user_id): ?>
            <a href="chat.php?receiver_id=<?php echo $request['user_id']; ?>" title="Chat with <?php echo htmlspecialchars($request['name']); ?>">
                <i class="fas fa-comments action-icon"></i>
            </a>
        <?php endif; ?>
        
        <i class="fas fa-comment action-icon" 
           onclick="toggleComments(<?php echo $request['id']; ?>)" 
           title="View comments"></i>
    </div>
</div>
                    
                    <div class="comments-container" id="comments-<?php echo $request['id']; ?>">
    <div class="comments-section">
        <?php foreach ($request['comments'] as $comment): ?>
            <div class="comment-item" id="comment-<?php echo $comment['id']; ?>">
                <div class="comment-user">
                    <img src="uploads/<?php echo htmlspecialchars($comment['profile_image'] ?: 'nopp.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($comment['name']); ?>" 
                         class="comment-user-img">
                    <div class="comment-username"><?php echo htmlspecialchars($comment['name']); ?></div>
                    
                    <!-- Add chat button for request owner to chat with commenters -->
                    <?php if ($is_logged_in && $request['user_id'] == $user_id && $comment['user_id'] != $user_id): ?>
                        <a href="chat.php?receiver_id=<?php echo $comment['user_id']; ?>" 
                           title="Chat with <?php echo htmlspecialchars($comment['name']); ?>"
                           style="margin-left: auto; margin-right: 10px;">
                            <i class="fas fa-comments action-icon"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($is_logged_in && $comment['user_id'] == $user_id): ?>
                    <div class="menu-dots" style="margin-left: auto;">
                        <button class="menu-dots-btn" onclick="toggleMenu(this)"><i class="fas fa-ellipsis-h"></i></button>
                        <div class="menu-dropdown">
                            <button class="edit-option" onclick="toggleEditForm('comment', <?php echo $comment['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="delete-option" onclick="showConfirmation('comment', <?php echo $comment['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="comment-text" id="comment-text-<?php echo $comment['id']; ?>">
                    <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                </div>
                <div class="comment-date">
                    <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                </div>
                
                <div class="edit-form" id="edit-form-comment-<?php echo $comment['id']; ?>" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                        <textarea name="edited_text" class="edit-textarea"><?php echo htmlspecialchars($comment['comment_text']); ?></textarea>
                        <div class="edit-form-buttons">
                            <button type="submit" name="edit_comment" class="save-edit">Save</button>
                            <button type="button" onclick="toggleEditForm('comment', <?php echo $comment['id']; ?>)" class="cancel-edit">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ($is_logged_in): ?>
            <form method="POST" class="comment-form">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                <textarea name="comment_text" required placeholder="Write a comment..."></textarea>
                <button type="submit" name="submit_comment">Post</button>
            </form>
        <?php endif; ?>
    </div>
</div>
                        </div>
                    
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmationModal" class="confirmation-modal">
    <div class="confirmation-content">
        <h3>Are you sure?</h3>
        <p>This action cannot be undone.</p>
        <div class="confirmation-buttons">
            <button id="confirmDelete" class="confirm-btn">Delete</button>
            <button id="cancelDelete" class="cancel-btn">Cancel</button>
        </div>
    </div>
</div>

<script>
    // Toggle comments visibility
    function toggleComments(requestId) {
        const container = document.getElementById(`comments-${requestId}`);
        container.classList.toggle('show');
    }

    // Modal functionality
    const modal = document.getElementById("requestModal");
    const btn = document.getElementById("openRequestModal");
    const span = document.getElementsByClassName("close")[0];

    btn.onclick = function() {
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }




    // Toggle dropdown menu
    function toggleMenu(element) {
        const menu = element.nextElementSibling;
        menu.classList.toggle('show');
        
        // Close other open menus
        document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
            if (dropdown !== menu && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });
    }

    // Close menus when clicking elsewhere
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.menu-dots')) {
            document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Toggle edit form
    function toggleEditForm(type, id) {
        const content = document.getElementById(`${type}-text-${id}`);
        const editForm = document.getElementById(`edit-form-${type}-${id}`);
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            editForm.style.display = 'none';
        } else {
            content.style.display = 'none';
            editForm.style.display = 'block';
            
            // Focus and select the text for better UX
            const textarea = editForm.querySelector('textarea');
            if (textarea) {
                textarea.focus();
                textarea.select();
            }
        }
        
        // Close any open dropdown menus
        document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }

    // Confirmation modal for delete actions
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    const cancelDeleteBtn = document.getElementById('cancelDelete');
    let currentDeleteType = '';
    let currentDeleteId = 0;

    function showConfirmation(type, id) {
        currentDeleteType = type;
        currentDeleteId = id;
        confirmationModal.style.display = 'block';
        
        // Close any open dropdown menus
        document.querySelectorAll('.menu-dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    }

    confirmDeleteBtn.onclick = function() {
        // Create a form dynamically and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'community.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = currentDeleteType === 'post' ? 'post_id' : 'comment_id';
        input.value = currentDeleteId;
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = currentDeleteType === 'post' ? 'delete_post' : 'delete_comment';
        deleteInput.value = '1';
        
        form.appendChild(input);
        form.appendChild(deleteInput);
        document.body.appendChild(form);
        form.submit();
    };

    cancelDeleteBtn.onclick = function() {
        confirmationModal.style.display = 'none';
    };

    window.onclick = function(event) {
        if (event.target == confirmationModal) {
            confirmationModal.style.display = 'none';
        }
    };

    // Auto-hide notification after 3 seconds
    const notification = document.querySelector('.notification');
    if (notification) {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Clear all filters when reset button is clicked
    document.querySelector('.reset-filters').addEventListener('click', function(e) {
        document.getElementById('filter_city').value = '';
        document.getElementById('filter_state').value = '';
        document.getElementById('filter_help_type').value = '';
        document.getElementById('filter_urgency').value = '';
    });
    
    // Submit form when filter selections change (optional)
    const filterSelects = document.querySelectorAll('.filter-group select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
});
</script>

</body>
</html>