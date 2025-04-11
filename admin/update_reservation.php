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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Reservation ID is required";
    header("Location: reservations.php");
    exit;
}

$reservationId = (int)$_GET['id'];
$errors = [];
$success = false;

// Connect to database
$conn = getConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $status = validateInput($_POST['status'] ?? '');
    $reservationDate = validateInput($_POST['reservation_date'] ?? '');
    $reservationTime = validateInput($_POST['reservation_time'] ?? '');
    $partySize = (int)($_POST['party_size'] ?? 0);
    $tableNumber = validateInput($_POST['table_number'] ?? '');
    $specialRequests = validateInput($_POST['special_request'] ?? '');
    
    // Validation
    if (empty($status)) {
        $errors[] = "Status is required";
    }
    
    if (empty($reservationDate)) {
        $errors[] = "Reservation date is required";
    }
    
    if (empty($reservationTime)) {
        $errors[] = "Reservation time is required";
    }
    
    if ($partySize <= 0) {
        $errors[] = "Party size must be greater than 0";
    }
    
    // If no errors, update reservation
    if (empty($errors)) {
        $sql = "UPDATE reservations SET 
                status = ?, 
                reservation_date = ?, 
                reservation_time = ?, 
                party_size = ?, 
                special_request = ?,
                updated_at = NOW()
                WHERE id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", 
            $status, 
            $reservationDate, 
            $reservationTime, 
            $partySize, 
            $specialRequests, 
            $reservationId
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Reservation updated successfully";
            $success = true;
        } else {
            $errors[] = "Failed to update reservation: " . $conn->error;
        }
    }
}

// Get reservation details
$sql = "SELECT r.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Reservation not found";
    header("Location: reservations.php");
    exit;
}

$reservation = $result->fetch_assoc();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservation - Restaurant Ordering and Reservation System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#F59E0B',
                        accent: '#10B981',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-dark text-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold">RORS Admin</a>
                </div>
                <nav class="hidden md:flex space-x-6">
                    <a href="dashboard.php" class="text-white hover:text-gray-300">Dashboard</a>
                    <a href="orders.php" class="text-white hover:text-gray-300">Orders</a>
                    <a href="reservations.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Reservations</a>
                    <a href="menu.php" class="text-white hover:text-gray-300">Menu</a>
                    <a href="inventory.php" class="text-white hover:text-gray-300">Inventory</a>
                    <a href="users.php" class="text-white hover:text-gray-300">Users</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                    <a href="../includes/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="view_reservation.php?id=<?php echo $reservationId; ?>" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to Reservation Details
            </a>
            <h1 class="text-3xl font-bold mt-2">Edit Reservation #<?php echo $reservationId; ?></h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Errors:</p>
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>Reservation updated successfully.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <form action="update_reservation.php?id=<?php echo $reservationId; ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Reservation Details</h2>
                            
                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                                    <option value="pending" <?php if ($reservation['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="confirmed" <?php if ($reservation['status'] === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                                    <option value="seated" <?php if ($reservation['status'] === 'seated') echo 'selected'; ?>>Seated</option>
                                    <option value="completed" <?php if ($reservation['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                                    <option value="cancelled" <?php if ($reservation['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="reservation_date" class="block text-sm font-medium text-gray-700 mb-1">Reservation Date</label>
                                <input type="date" name="reservation_date" id="reservation_date" value="<?php echo $reservation['reservation_date']; ?>" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="reservation_time" class="block text-sm font-medium text-gray-700 mb-1">Reservation Time</label>
                                <input type="time" name="reservation_time" id="reservation_time" value="<?php echo $reservation['reservation_time']; ?>" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="party_size" class="block text-sm font-medium text-gray-700 mb-1">Party Size</label>
                                <input type="number" name="party_size" id="party_size" value="<?php echo $reservation['party_size']; ?>" min="1" max="20" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                            </div>
                        </div>
                        
                        <div>
                            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Customer Information</h2>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                                <div class="text-gray-700 bg-gray-100 px-3 py-2 rounded">
                                    <?php echo $reservation['customer_name'] ?? 'N/A'; ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Customer details must be updated in the user management section</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Email</label>
                                <div class="text-gray-700 bg-gray-100 px-3 py-2 rounded">
                                    <?php echo $reservation['customer_email'] ?? 'N/A'; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Customer Phone</label>
                                <div class="text-gray-700 bg-gray-100 px-3 py-2 rounded">
                                    <?php echo $reservation['customer_phone'] ?? 'N/A'; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="special_request" class="block text-sm font-medium text-gray-700 mb-1">Special Requests</label>
                                <textarea name="special_request" id="special_request" rows="4" 
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"><?php echo $reservation['special_request'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t pt-6 flex justify-end">
                        <a href="view_reservation.php?id=<?php echo $reservationId; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition mr-4">
                            Cancel
                        </a>
                        <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                            Update Reservation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 