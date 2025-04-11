<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Check if export is requested
if (!isset($_POST['export'])) {
    header("Location: reservations.php");
    exit;
}

// Get filter parameters
$search = isset($_POST['search']) ? $_POST['search'] : '';
$dateFilter = isset($_POST['date']) ? $_POST['date'] : '';
$statusFilter = isset($_POST['status']) ? $_POST['status'] : '';

// Connect to database
$conn = getConnection();

// Build the query
$sql = "SELECT r.id, r.reservation_date, r.reservation_time, r.party_size, r.status, 
               r.special_request, r.created_at, r.updated_at,
               u.name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(r.id LIKE '%$search%' OR u.name LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

if (!empty($dateFilter)) {
    $dateFilter = $conn->real_escape_string($dateFilter);
    $whereConditions[] = "r.reservation_date = '$dateFilter'";
}

if (!empty($statusFilter)) {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $whereConditions[] = "r.status = '$statusFilter'";
}

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $sql .= $whereClause;
}

// Add order by
$sql .= " ORDER BY r.reservation_date DESC, r.reservation_time DESC";

// Execute query
$result = $conn->query($sql);
if (!$result) {
    $_SESSION['error'] = "Failed to retrieve reservations for export: " . $conn->error;
    header("Location: reservations.php");
    exit;
}

// Create CSV file
$filename = 'reservations_export_' . date('Y-m-d_H-i-s') . '.csv';

// Set headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create file pointer connected to PHP output stream
$output = fopen('php://output', 'w');

// Add BOM to fix UTF-8 in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Set column headers
fputcsv($output, [
    'ID', 
    'Date', 
    'Time', 
    'Customer Name',
    'Customer Email', 
    'Customer Phone',
    'Party Size', 
    'Status', 
    'Special Requests',
    'Created At',
    'Updated At'
]);

// Loop through data and write to CSV
while ($row = $result->fetch_assoc()) {
    $csvRow = [
        $row['id'],
        $row['reservation_date'],
        date('h:i A', strtotime($row['reservation_time'])),
        $row['customer_name'] ?? 'N/A',
        $row['customer_email'] ?? 'N/A',
        $row['customer_phone'] ?? 'N/A',
        $row['party_size'],
        ucfirst($row['status']),
        $row['special_request'] ?? '',
        $row['created_at'],
        $row['updated_at'] ?? ''
    ];
    fputcsv($output, $csvRow);
}

// Close connection
$conn->close();

// Close file pointer
fclose($output);
exit; 