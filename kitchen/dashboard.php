<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require kitchen staff role
requireRole('kitchen');

// Get orders with pending, confirmed, or preparing status
$conn = getConnection();
$sql = "SELECT o.*, u.name as customer_name, u.phone as customer_phone
        FROM orders o 
        JOIN customers u ON o.customer_id = u.id
        WHERE o.status IN ('pending', 'confirmed', 'preparing')
        ORDER BY 
            CASE 
                WHEN o.status = 'pending' THEN 1
                WHEN o.status = 'confirmed' THEN 2
                WHEN o.status = 'preparing' THEN 3
                ELSE 4
            END,
            o.created_at ASC";
$result = $conn->query($sql);
$orders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get order items for each order
foreach ($orders as &$order) {
    $order_id = $order['id'];
    $items_sql = "SELECT oi.*, mi.name as item_name, mi.price as item_price
                  FROM order_items oi
                  JOIN menu_items mi ON oi.menu_item_id = mi.id
                  WHERE oi.order_id = ?";
    $stmt = $conn->prepare($items_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $order['items'] = [];
    
    if ($items_result && $items_result->num_rows > 0) {
        $total_price = 0;
        while ($item = $items_result->fetch_assoc()) {
            $order['items'][] = $item;
            $total_price += $item['item_price'] * $item['quantity'];
        }
        $order['total_price'] = $total_price;
    }
    
    $stmt->close();
}

// Update order status if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['confirmed', 'preparing', 'ready', 'cancelled'];
    if (in_array($status, $valid_statuses)) {
        $update_stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $status, $order_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // If order is completed, record the completion time
        if ($status === 'ready') {
            $complete_stmt = $conn->prepare("UPDATE orders SET completed_at = NOW() WHERE id = ?");
            $complete_stmt->bind_param("i", $order_id);
            $complete_stmt->execute();
            $complete_stmt->close();
        }
        
        // Redirect to refresh the page
        header('Location: dashboard.php');
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Dashboard - Restaurant Ordering and Reservation System</title>
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
    <!-- Meta refresh every 60 seconds to update orders -->
    <meta http-equiv="refresh" content="60">
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-dark text-white shadow-md">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-2xl font-bold">RORS Kitchen Dashboard</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                    <a href="../includes/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Kitchen Order Dashboard</h1>
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-xl font-semibold">Active Orders</h2>
                <p class="text-gray-600">Displaying orders that need preparation</p>
            </div>
            
            <button onclick="window.location.reload()" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                <i class="fas fa-sync-alt mr-2"></i> Refresh Orders
            </button>
        </div>
        
        <?php if (count($orders) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 
                        <?php 
                            if ($order['status'] === 'pending') echo 'border-yellow-500';
                            elseif ($order['status'] === 'confirmed') echo 'border-blue-500';
                            elseif ($order['status'] === 'preparing') echo 'border-green-500';
                        ?>">
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-gray-600">Order #<?php echo $order['id']; ?></span>
                                    <h3 class="text-xl font-bold"><?php echo $order['customer_name']; ?></h3>
                                    <p class="text-gray-600"><?php echo $order['customer_phone']; ?></p>
                                </div>
                                <div>
                                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                                        <?php 
                                            if ($order['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                            elseif ($order['status'] === 'confirmed') echo 'bg-blue-100 text-blue-800';
                                            elseif ($order['status'] === 'preparing') echo 'bg-green-100 text-green-800';
                                        ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Order Type: <?php echo isset($order['order_type']) ? ucfirst($order['order_type']) : 'Standard'; ?></span>
                                    <span><?php echo date('h:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                                <span class="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </span>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mb-4">
                                <h4 class="font-semibold mb-2">Order Items:</h4>
                                <ul class="text-gray-700 space-y-2">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li class="flex justify-between">
                                            <div>
                                                <span class="font-medium"><?php echo $item['quantity']; ?> x <?php echo $item['item_name']; ?></span>
                                                <?php if (!empty($item['special_instructions'])): ?>
                                                    <p class="text-sm text-gray-500 italic"><?php echo $item['special_instructions']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span><?php echo formatPrice(isset($item['item_price']) ? $item['item_price'] * $item['quantity'] : 0); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span><?php echo formatPrice(isset($order['total_price']) ? $order['total_price'] : 0); ?></span>
                            </div>
                            
                            <div class="mt-6">
                                <form action="dashboard.php" method="post" class="flex space-x-2">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <button type="submit" name="status" value="confirmed" class="flex-1 bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 transition">
                                            Confirm Order
                                        </button>
                                        <button type="submit" name="status" value="cancelled" class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600 transition">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                    <?php elseif ($order['status'] === 'confirmed'): ?>
                                        <button type="submit" name="status" value="preparing" class="flex-1 bg-green-500 text-white py-2 px-4 rounded hover:bg-green-600 transition">
                                            Start Preparing
                                        </button>
                                        <button type="submit" name="status" value="cancelled" class="bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600 transition">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        
                                    <?php elseif ($order['status'] === 'preparing'): ?>
                                        <button type="submit" name="status" value="ready" class="flex-1 bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition">
                                            Mark as Ready
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                <h3 class="text-xl font-bold mb-2">No Active Orders</h3>
                <p class="text-gray-600">There are no orders pending preparation at the moment.</p>
            </div>
        <?php endif; ?>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Auto refresh the page every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html> 