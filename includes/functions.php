<?php
// Include database connection function
require_once __DIR__ . '/../config/database.php';

// Function to sanitize user input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate phone number
function validatePhone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user has role
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /rors/pages/login.php');
        exit;
    }
}

// Function to require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /rors/index.php');
        exit;
    }
}

// Function to format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Function to format time
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Function to get user data
function getUserData($userId) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

// Function to check if item is in cart
function isInCart($itemId) {
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    foreach ($_SESSION['cart'] as $cartItem) {
        if ($cartItem['item_id'] == $itemId) {
            return true;
        }
    }
    
    return false;
}

// Function to get cart total
function getCartTotal() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

// Function to check if inventory is low
function checkLowInventory() {
    $conn = getConnection();
    $sql = "SELECT * FROM inventory WHERE quantity <= threshold";
    $result = $conn->query($sql);
    $lowItems = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $lowItems[] = $row;
        }
    }
    
    $conn->close();
    return $lowItems;
}

// Function to log system notification
function logSystemNotification($type, $message) {
    $conn = getConnection();
    $stmt = $conn->prepare("INSERT INTO system_notifications (type, message) VALUES (?, ?)");
    $stmt->bind_param("ss", $type, $message);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Function to get unread notifications
function getUnreadNotifications() {
    $conn = getConnection();
    $sql = "SELECT * FROM system_notifications WHERE is_read = 0 ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $notifications = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    $conn->close();
    return $notifications;
}

// Function to mark notification as read
function markNotificationAsRead($notificationId) {
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE system_notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Function to display success or error messages from session
function displayMessage() {
    if(isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
        $type = $_SESSION['message_type'];
        $message = $_SESSION['message'];
        
        // Define the CSS classes based on message type
        $bgColor = 'bg-gray-100';
        $textColor = 'text-gray-700';
        $iconClass = 'fa-info-circle';
        
        if($type === 'success') {
            $bgColor = 'bg-green-100';
            $textColor = 'text-green-700';
            $iconClass = 'fa-check-circle';
        } elseif($type === 'error') {
            $bgColor = 'bg-red-100';
            $textColor = 'text-red-700';
            $iconClass = 'fa-exclamation-circle';
        } elseif($type === 'warning') {
            $bgColor = 'bg-yellow-100';
            $textColor = 'text-yellow-700';
            $iconClass = 'fa-exclamation-triangle';
        }
        
        // Display the message
        echo '<div class="mb-6 p-4 rounded-md ' . $bgColor . ' ' . $textColor . '">';
        echo '<div class="flex items-center">';
        echo '<i class="fas ' . $iconClass . ' mr-3 text-lg"></i>';
        echo '<div>' . $message . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Clear the message from session
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?> 