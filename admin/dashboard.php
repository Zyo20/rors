<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Connect to database
$conn = getConnection();

// Initialize arrays to prevent errors
$recentReservations = [];
$todayReservations = [];
$recentOrders = [];
$lowInventoryItems = [];
$notifications = [];
$totalOrders = 0;
$totalRevenue = 0;
$totalReservations = 0;
$totalCustomers = 0;

// Function to directly get reservation count
function getReservationCount() {
    $conn = getConnection();
    $sql = "SELECT COUNT(*) as total FROM reservations";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['total'];
    } else {
        $count = 0;
    }
    
    $conn->close();
    return $count;
}

// Get direct reservation count
$directReservationCount = getReservationCount();

// Function to directly get customer count
function getCustomerCount() {
    $conn = getConnection();
    $sql = "SELECT COUNT(*) as total FROM customers";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $count = $row['total'];
    } else {
        $count = 0;
    }
    
    $conn->close();
    return $count;
}

// Get direct customer count
$directCustomerCount = getCustomerCount();

// Get tables information to check if they exist
$tables_result = $conn->query("SHOW TABLES");
$tables = [];
if ($tables_result) {
    while ($table = $tables_result->fetch_array(MYSQLI_NUM)) {
        $tables[] = $table[0];
    }
}

// Check if all required tables exist
$all_tables_exist = in_array('users', $tables) && 
                   in_array('reservations', $tables) && 
                   in_array('orders', $tables) && 
                   in_array('inventory', $tables) && 
                   in_array('system_notifications', $tables);

if ($all_tables_exist) {
    // Check reservations table structure
    $res_columns_result = $conn->query("SHOW COLUMNS FROM reservations");
    $res_columns = [];
    if ($res_columns_result) {
        while ($column = $res_columns_result->fetch_assoc()) {
            $res_columns[] = $column['Field'];
        }
    }

    // Get recent reservations - simplified query
    $recentReservationsSql = "SELECT r.*, u.name as customer_name, u.phone as customer_phone
                           FROM reservations r
                           LEFT JOIN users u ON r.user_id = u.id
                           ORDER BY r.reservation_date DESC, r.reservation_time DESC
                           LIMIT 5";
    try {
        $recentReservationsResult = $conn->query($recentReservationsSql);
        
        if ($recentReservationsResult && $recentReservationsResult->num_rows > 0) {
            while ($row = $recentReservationsResult->fetch_assoc()) {
                $recentReservations[] = $row;
            }
        }
    } catch (Exception $e) {
        // Add error to notifications for admin
        $notifications[] = [
            'type' => 'error',
            'message' => 'Error fetching recent reservations: ' . $e->getMessage(),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Get today's reservations
    $todayDate = date('Y-m-d');
    $todayReservationsSql = "SELECT r.*, u.name as customer_name, u.phone as customer_phone
                           FROM reservations r
                           LEFT JOIN users u ON r.user_id = u.id
                           WHERE r.reservation_date = '$todayDate'
                           ORDER BY r.reservation_time ASC";
    try {
        $todayReservationsResult = $conn->query($todayReservationsSql);
        
        if ($todayReservationsResult && $todayReservationsResult->num_rows > 0) {
            while ($row = $todayReservationsResult->fetch_assoc()) {
                $todayReservations[] = $row;
            }
        }
    } catch (Exception $e) {
        // Add error to notifications for admin
        $notifications[] = [
            'type' => 'error',
            'message' => 'Error fetching today\'s reservations: ' . $e->getMessage(),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    // Check orders table structure
    $order_columns_result = $conn->query("SHOW COLUMNS FROM orders");
    $order_columns = [];
    if ($order_columns_result) {
        while ($column = $order_columns_result->fetch_assoc()) {
            $order_columns[] = $column['Field'];
        }
    }

    // Get recent orders if user_id column exists
    if (in_array('user_id', $order_columns)) {
        $recentOrdersSql = "SELECT o.*, u.name as customer_name
                            FROM orders o
                            LEFT JOIN users u ON o.user_id = u.id
                            ORDER BY o.created_at DESC
                            LIMIT 5";
        try {
            $recentOrdersResult = $conn->query($recentOrdersSql);
            
            if ($recentOrdersResult && $recentOrdersResult->num_rows > 0) {
                while ($row = $recentOrdersResult->fetch_assoc()) {
                    $recentOrders[] = $row;
                }
            }
        } catch (Exception $e) {
            // Query failed - add error to notifications
            $notifications[] = [
                'type' => 'error',
                'message' => 'Recent orders query failed: ' . $e->getMessage(),
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    // Get low inventory items
    $lowInventorySql = "SELECT * FROM inventory WHERE quantity <= threshold";
    try {
        $lowInventoryResult = $conn->query($lowInventorySql);
        
        if ($lowInventoryResult && $lowInventoryResult->num_rows > 0) {
            while ($row = $lowInventoryResult->fetch_assoc()) {
                $lowInventoryItems[] = $row;
            }
        }
    } catch (Exception $e) {
        // Query failed - likely schema issue
    }

    // Get system notifications
    $notificationsSql = "SELECT * FROM system_notifications ORDER BY created_at DESC LIMIT 5";
    try {
        $notificationsResult = $conn->query($notificationsSql);
        
        if ($notificationsResult && $notificationsResult->num_rows > 0) {
            while ($row = $notificationsResult->fetch_assoc()) {
                $notifications[] = $row;
            }
        }
    } catch (Exception $e) {
        // Query failed - likely schema issue
    }

    // Calculate key metrics - add error handling
    try {
        $totalOrdersSql = "SELECT COUNT(*) as total FROM orders";
        $totalOrdersResult = $conn->query($totalOrdersSql);
        if ($totalOrdersResult && $totalOrdersResult->num_rows > 0) {
            $totalOrders = $totalOrdersResult->fetch_assoc()['total'];
        }

        // Initialize with a default safe query
        $totalRevenueSql = "SELECT COUNT(*) as total FROM orders";
        
        // Check what column exists in the result
        $testOrderSql = "SELECT * FROM orders LIMIT 1";
        $testOrderResult = $conn->query($testOrderSql);
        if ($testOrderResult && $testOrderResult->num_rows > 0) {
            $orderRow = $testOrderResult->fetch_assoc();
            // Check what column exists in the result
            if (isset($orderRow['total_price'])) {
                $totalRevenueSql = "SELECT SUM(total_price) as total FROM orders";
            } elseif (isset($orderRow['price'])) {
                $totalRevenueSql = "SELECT SUM(price) as total FROM orders";
            } elseif (isset($orderRow['amount'])) {
                $totalRevenueSql = "SELECT SUM(amount) as total FROM orders";
            }
        }
        
        $totalRevenueResult = $conn->query($totalRevenueSql);
        if ($totalRevenueResult && $totalRevenueResult->num_rows > 0) {
            $row = $totalRevenueResult->fetch_assoc();
            $totalRevenue = $row['total'] ?? 0;
        }

        // Simplified query for total reservations
        $totalReservationsSql = "SELECT COUNT(*) as total FROM reservations";
        $totalReservationsResult = $conn->query($totalReservationsSql);
        if ($totalReservationsResult && $totalReservationsResult->num_rows > 0) {
            $row = $totalReservationsResult->fetch_assoc();
            $totalReservations = $row['total'];
        }

        $totalCustomersSql = "SELECT COUNT(*) as total FROM customers";
        $totalCustomersResult = $conn->query($totalCustomersSql);
        if ($totalCustomersResult && $totalCustomersResult->num_rows > 0) {
            $row = $totalCustomersResult->fetch_assoc();
            $totalCustomers = $row['total'];
        }
    } catch (Exception $e) {
        // Add error to notifications for admin
        $notifications[] = [
            'type' => 'error',
            'message' => 'Error calculating metrics: ' . $e->getMessage(),
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
}

// Connection will be closed at the end of the file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant Ordering and Reservation System</title>
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
        <h1 class="text-3xl font-bold mb-8">Management Dashboard</h1>
        
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Orders</p>
                        <h2 class="text-3xl font-bold"><?php echo $totalOrders; ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-full">
                        <i class="fas fa-shopping-bag text-primary text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Revenue</p>
                        <h2 class="text-3xl font-bold"><?php echo formatPrice($totalRevenue); ?></h2>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Reservations</p>
                        <h2 class="text-3xl font-bold"><?php echo $directReservationCount; ?></h2>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-calendar-check text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Customers</p>
                        <h2 class="text-3xl font-bold"><?php echo $directCustomerCount; ?></h2>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-users text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Today's Reservations -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Today's Reservations</h2>
                    <a href="reservations.php" class="text-primary hover:underline">View All</a>
                </div>
                
                <?php if (count($todayReservations) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Party Size</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($todayReservations as $reservation): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php echo date('h:i A', strtotime($reservation['reservation_time'])); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="font-medium text-gray-900"><?php echo $reservation['customer_name'] ?? 'Guest'; ?></div>
                                            <div class="text-sm text-gray-500"><?php echo $reservation['customer_phone'] ?? 'No phone'; ?></div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php echo $reservation['party_size']; ?> people
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                    if ($reservation['status'] === 'confirmed') echo 'bg-green-100 text-green-800';
                                                    elseif ($reservation['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                    elseif ($reservation['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                                    else echo 'bg-gray-100 text-gray-800';
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
                    <div class="text-center py-4 text-gray-500">
                        No reservations for today.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Recent Orders</h2>
                    <a href="orders.php" class="text-primary hover:underline">View All</a>
                </div>
                
                <?php if (count($recentOrders) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            #<?php echo $order['id']; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php echo $order['customer_name'] ?? 'Guest'; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php 
                                                // Try to get price from various possible column names
                                                $price = 0;
                                                if (isset($order['total_price'])) {
                                                    $price = $order['total_price'];
                                                } elseif (isset($order['price'])) {
                                                    $price = $order['price'];
                                                } elseif (isset($order['amount'])) {
                                                    $price = $order['amount'];
                                                } elseif (isset($order['total'])) {
                                                    $price = $order['total'];
                                                }
                                                echo formatPrice($price);
                                            ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php
                                                    if ($order['status'] === 'delivered' || $order['status'] === 'ready') echo 'bg-green-100 text-green-800';
                                                    elseif ($order['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                    elseif ($order['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                                    else echo 'bg-blue-100 text-blue-800';
                                                ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-gray-500">
                        No recent orders.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
            <!-- Low Inventory Alerts -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold">Low Inventory Alerts</h2>
                    <a href="inventory.php" class="text-primary hover:underline">Manage Inventory</a>
                </div>
                
                <?php if (count($lowInventoryItems) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Threshold</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($lowInventoryItems as $item): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo $item['name']; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php echo $item['quantity'] . ' ' . $item['unit']; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php echo $item['threshold'] . ' ' . $item['unit']; ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if ($item['quantity'] <= 0): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Out of Stock
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    Low Stock
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-gray-500">
                        No low inventory items.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- System Notifications -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6">System Notifications</h2>
                
                <?php if (count($notifications) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="border-l-4 
                                <?php
                                    if ($notification['type'] === 'error') echo 'border-red-500 bg-red-50';
                                    elseif ($notification['type'] === 'warning') echo 'border-yellow-500 bg-yellow-50';
                                    else echo 'border-blue-500 bg-blue-50';
                                ?> 
                                p-4 rounded">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <?php if ($notification['type'] === 'error'): ?>
                                            <i class="fas fa-exclamation-circle text-red-500"></i>
                                        <?php elseif ($notification['type'] === 'warning'): ?>
                                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                        <?php else: ?>
                                            <i class="fas fa-info-circle text-blue-500"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm 
                                            <?php
                                                if ($notification['type'] === 'error') echo 'text-red-700';
                                                elseif ($notification['type'] === 'warning') echo 'text-yellow-700';
                                                else echo 'text-blue-700';
                                            ?>">
                                            <?php echo $notification['message']; ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-gray-500">
                        No system notifications.
                    </div>
                <?php endif; ?>
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
<?php 
// Close the connection at the very end of the file
if (isset($conn) && !$conn->connect_errno) {
    $conn->close();
}
?> 