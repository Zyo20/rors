<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Check if order ID and status are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status']) || empty($_GET['status'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$status = $_GET['status'];

// Validate status
$valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('Location: orders.php');
    exit;
}

// Connect to database
$conn = getConnection();

// Update the order status
$update_sql = "UPDATE orders SET status = ? WHERE id = ?";

// If order is completed, also set the completed_at timestamp
if ($status === 'delivered') {
    $update_sql = "UPDATE orders SET status = ?, completed_at = NOW() WHERE id = ?";
}

$stmt = $conn->prepare($update_sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("si", $status, $order_id);
$result = $stmt->execute();

if (!$result) {
    die("Execute failed: " . $stmt->error);
}

$stmt->close();
$conn->close();

// Redirect back to the view page
header("Location: view_order.php?id=$order_id&status_updated=1");
exit;
?> 