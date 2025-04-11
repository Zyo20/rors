<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Reservation ID is required";
    header("Location: reservations.php");
    exit;
}

$reservationId = (int)$_GET['id'];

// Connect to database
$conn = getConnection();

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
    <title>View Reservation - Restaurant Ordering and Reservation System</title>
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
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="reservations.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Reservations
                </a>
                <h1 class="text-3xl font-bold mt-2">Reservation #<?php echo $reservation['id']; ?></h1>
            </div>
            <div class="flex space-x-4">
                <a href="update_reservation.php?id=<?php echo $reservation['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-2"></i> Edit Reservation
                </a>
                <?php if ($reservation['status'] !== 'cancelled' && $reservation['status'] !== 'completed'): ?>
                    <a href="update_reservation_status.php?id=<?php echo $reservation['id']; ?>&status=cancelled" 
                       onclick="return confirm('Are you sure you want to cancel this reservation?');"
                       class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">
                        <i class="fas fa-times-circle mr-2"></i> Cancel Reservation
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Reservation Details</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="font-medium">Status:</div>
                            <div>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php
                                        if ($reservation['status'] === 'confirmed' || $reservation['status'] === 'seated' || $reservation['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                        elseif ($reservation['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                        elseif ($reservation['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                        else echo 'bg-blue-100 text-blue-800';
                                    ?>">
                                    <?php echo ucfirst($reservation['status']); ?>
                                </span>
                            </div>
                            
                            <div class="font-medium">Date:</div>
                            <div><?php echo date('F d, Y', strtotime($reservation['reservation_date'])); ?></div>
                            
                            <div class="font-medium">Time:</div>
                            <div><?php echo date('h:i A', strtotime($reservation['reservation_time'])); ?></div>
                            
                            <div class="font-medium">Party Size:</div>
                            <div><?php echo $reservation['party_size']; ?> people</div>
                            
                            <div class="font-medium">Created At:</div>
                            <div><?php echo date('M d, Y h:i A', strtotime($reservation['created_at'])); ?></div>
                            
                            <?php if (isset($reservation['updated_at']) && !empty($reservation['updated_at'])): ?>
                                <div class="font-medium">Last Updated:</div>
                                <div><?php echo date('M d, Y h:i A', strtotime($reservation['updated_at'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Customer Information</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="font-medium">Name:</div>
                            <div><?php echo $reservation['customer_name'] ?? 'N/A'; ?></div>
                            
                            <div class="font-medium">Email:</div>
                            <div><?php echo $reservation['customer_email'] ?? 'N/A'; ?></div>
                            
                            <div class="font-medium">Phone:</div>
                            <div><?php echo $reservation['customer_phone'] ?? 'N/A'; ?></div>
                        </div>

                        <h2 class="text-xl font-semibold mt-6 mb-4 border-b pb-2">Special Requests</h2>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <?php echo !empty($reservation['special_requests']) ? nl2br(htmlspecialchars($reservation['special_requests'])) : 'None'; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t">
                    <h2 class="text-xl font-semibold mb-4">Status History</h2>
                    
                    <div class="flex justify-center">
                        <div class="relative">
                            <!-- Status Timeline -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-full bg-gray-200 h-1"></div>
                            </div>
                            
                            <div class="relative flex justify-between">
                                <?php 
                                $statuses = ['pending', 'confirmed', 'seated', 'completed'];
                                $currentStatusIndex = array_search($reservation['status'], $statuses);
                                if ($reservation['status'] === 'cancelled') {
                                    $currentStatusIndex = -1;
                                }
                                
                                foreach ($statuses as $index => $status): 
                                    $isActive = $index <= $currentStatusIndex;
                                    $isComplete = $index < $currentStatusIndex;
                                ?>
                                    <div class="text-center">
                                        <div class="w-8 h-8 mx-auto rounded-full flex items-center justify-center 
                                            <?php echo $isActive ? 'bg-primary text-white' : 'bg-white border-2 border-gray-200 text-gray-400'; ?>">
                                            <?php if ($isComplete): ?>
                                                <i class="fas fa-check"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs mt-2 <?php echo $isActive ? 'font-semibold text-primary' : 'text-gray-500'; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($reservation['status'] === 'cancelled'): ?>
                        <div class="mt-6 text-center text-red-600">
                            <i class="fas fa-exclamation-triangle mr-2"></i> This reservation has been cancelled
                        </div>
                    <?php endif; ?>
                </div>
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