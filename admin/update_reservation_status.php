<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Function to validate input (sanitize)
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if ID and status are provided
if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status']) || empty($_GET['status'])) {
    $_SESSION['error'] = "Reservation ID and status are required";
    header("Location: reservations.php");
    exit;
}

$reservationId = (int)$_GET['id'];
$status = validateInput($_GET['status']);

// Validate status
$validStatuses = ['pending', 'confirmed', 'seated', 'completed', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    $_SESSION['error'] = "Invalid status";
    header("Location: reservations.php");
    exit;
}

// Connect to database
$conn = getConnection();

// Update status
$sql = "UPDATE reservations SET status = ?, updated_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $reservationId);

if ($stmt->execute()) {
    $_SESSION['success'] = "Reservation status updated to " . ucfirst($status);
} else {
    $_SESSION['error'] = "Failed to update reservation status: " . $conn->error;
}

// Close connection
$conn->close();

// Redirect back to referring page or reservations list
$referrer = $_SERVER['HTTP_REFERER'] ?? 'reservations.php';
header("Location: $referrer");
exit; 