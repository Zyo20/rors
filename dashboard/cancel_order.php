<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to cancel an order";
    $_SESSION['message_type'] = "error";
    header("Location: ../pages/login.php");
    exit;
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
    $_SESSION['message'] = "Invalid request";
    $_SESSION['message_type'] = "error";
    header("Location: my_orders.php");
    exit;
}

$order_id = (int) $_POST['order_id'];
$user_id = $_SESSION['user_id'];

// Connect to database
$conn = getConnection();

// Check if the order belongs to the user and is in pending status
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    // Cancel the order
    $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $success = $stmt->execute();
    
    if ($success) {
        $_SESSION['message'] = "Order has been cancelled successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error cancelling order: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Order not found, already processed, or access denied";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$conn->close();

// Redirect back to order details
header("Location: order_details.php?id=" . $order_id);
exit; 