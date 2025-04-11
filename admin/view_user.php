<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin role
requireRole('admin');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid user ID";
    $_SESSION['message_type'] = "error";
    header("Location: users.php");
    exit;
}

$user_id = (int) $_GET['id'];

// Connect to database
$conn = getConnection();

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "User not found";
    $_SESSION['message_type'] = "error";
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Get order count for this user
$order_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE customer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $order_count = $result->fetch_assoc()['count'];
}
$stmt->close();

// Get reservation count for this user
$reservation_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $reservation_count = $result->fetch_assoc()['count'];
}
$stmt->close();

// Get recent orders
$recent_orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}
$stmt->close();

// Get recent reservations
$recent_reservations = [];
$stmt = $conn->prepare("SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_reservations[] = $row;
    }
}
$stmt->close();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Restaurant Ordering and Reservation System</title>
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
                    <a href="reservations.php" class="text-white hover:text-gray-300">Reservations</a>
                    <a href="menu.php" class="text-white hover:text-gray-300">Menu</a>
                    <a href="inventory.php" class="text-white hover:text-gray-300">Inventory</a>
                    <a href="users.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Users</a>
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
            <a href="users.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to Users
            </a>
            <div class="flex justify-between items-center mt-2">
                <h1 class="text-3xl font-bold">User Profile</h1>
                <a href="edit_user.php?id=<?php echo $user_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    <i class="fas fa-edit mr-2"></i> Edit User
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- User Details -->
            <div class="bg-white rounded-lg shadow-md p-6 md:col-span-1">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Personal Information</h2>
                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                        <?php
                            if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                            elseif ($user['role'] === 'manager') echo 'bg-blue-100 text-blue-800';
                            elseif ($user['role'] === 'kitchen') echo 'bg-indigo-100 text-indigo-800';
                            else echo 'bg-gray-100 text-gray-800';
                        ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </div>
                
                <div class="flex items-center justify-center mb-6">
                    <div class="w-24 h-24 rounded-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-user text-4xl text-gray-500"></i>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">User ID</h3>
                        <p class="mt-1">#<?php echo $user['id']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Full Name</h3>
                        <p class="mt-1"><?php echo htmlspecialchars($user['name']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Email Address</h3>
                        <p class="mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Phone Number</h3>
                        <p class="mt-1"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A'; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Joined</h3>
                        <p class="mt-1"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- User Activity -->
            <div class="md:col-span-2 space-y-6">
                <!-- Summary Stats -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Activity Summary</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                                    <i class="fas fa-shopping-bag text-blue-500"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                                    <p class="text-2xl font-bold"><?php echo $order_count; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                    <i class="fas fa-calendar-check text-green-500"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-medium text-gray-500">Total Reservations</h3>
                                    <p class="text-2xl font-bold"><?php echo $reservation_count; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Recent Orders</h2>
                        <?php if ($order_count > 0): ?>
                            <a href="orders.php?search=<?php echo $user['id']; ?>" class="text-primary hover:text-primary-dark text-sm">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($recent_orders) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">#<?php echo $order['id']; ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                        if ($order['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                                        elseif ($order['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                        elseif ($order['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                                        else echo 'bg-blue-100 text-blue-800';
                                                    ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo formatPrice($order['total']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-4">No orders found for this user.</p>
                    <?php endif; ?>
                </div>

                <!-- Recent Reservations -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Recent Reservations</h2>
                        <?php if ($reservation_count > 0): ?>
                            <a href="reservations.php?search=<?php echo $user['id']; ?>" class="text-primary hover:text-primary-dark text-sm">
                                View All <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($recent_reservations) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation ID</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Party Size</th>
                                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recent_reservations as $reservation): ?>
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">#<?php echo $reservation['id']; ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo date('g:i A', strtotime($reservation['reservation_time'])); ?></td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm"><?php echo $reservation['party_size']; ?> people</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                        if ($reservation['status'] === 'confirmed') echo 'bg-green-100 text-green-800';
                                                        elseif ($reservation['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                        elseif ($reservation['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                                        else echo 'bg-blue-100 text-blue-800';
                                                    ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 py-4">No reservations found for this user.</p>
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