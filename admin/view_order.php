<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];
$conn = getConnection();

// Get order details
$order = null;
$order_items = [];

$sql = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email, 
        c.address as customer_address 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    
    // Get order items
    $items_sql = "SELECT oi.*, mi.name as item_name, mi.price as item_price
                  FROM order_items oi
                  JOIN menu_items mi ON oi.menu_item_id = mi.id
                  WHERE oi.order_id = ?";
    $items_stmt = $conn->prepare($items_sql);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    if ($items_result && $items_result->num_rows > 0) {
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
    }
    $items_stmt->close();
} else {
    // Order not found, redirect back
    header('Location: orders.php');
    exit;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order #<?php echo $order_id; ?> - Restaurant Ordering and Reservation System</title>
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
                    <a href="orders.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Orders</a>
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
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold">Order #<?php echo $order_id; ?></h1>
                <p class="text-gray-600">Created on <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="orders.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Orders
                </a>
                <a href="update_order.php?id=<?php echo $order_id; ?>" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                    <i class="fas fa-edit mr-2"></i> Edit Order
                </a>
                <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                    <a href="update_order_status.php?id=<?php echo $order_id; ?>&status=cancelled" 
                       onclick="return confirm('Are you sure you want to cancel this order?');"
                       class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i> Cancel Order
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Order Info -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Order Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 mb-1">Status</p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                <?php
                                    $status = isset($order['status']) ? $order['status'] : 'unknown';
                                    if ($status === 'delivered' || $status === 'ready') echo 'bg-green-100 text-green-800';
                                    elseif ($status === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                    elseif ($status === 'cancelled') echo 'bg-red-100 text-red-800';
                                    else echo 'bg-blue-100 text-blue-800';
                                ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                        
                        <div>
                            <p class="text-gray-600 mb-1">Payment Status</p>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                <?php
                                    $paymentStatus = isset($order['payment_status']) ? $order['payment_status'] : 'unknown';
                                    if ($paymentStatus === 'paid') echo 'bg-green-100 text-green-800';
                                    elseif ($paymentStatus === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-red-100 text-red-800';
                                ?>">
                                <?php echo ucfirst($paymentStatus); ?>
                            </span>
                        </div>
                        
                        <div>
                            <p class="text-gray-600 mb-1">Order Type</p>
                            <p class="font-medium"><?php echo isset($order['order_type']) ? ucfirst($order['order_type']) : 'Standard'; ?></p>
                        </div>
                        
                        <div>
                            <p class="text-gray-600 mb-1">Payment Method</p>
                            <p class="font-medium"><?php echo isset($order['payment_method']) ? ucfirst($order['payment_method']) : 'N/A'; ?></p>
                        </div>
                        
                        <?php if (isset($order['completed_at']) && !empty($order['completed_at'])): ?>
                        <div>
                            <p class="text-gray-600 mb-1">Completed Date</p>
                            <p class="font-medium"><?php echo date('F d, Y \a\t h:i A', strtotime($order['completed_at'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['notes'])): ?>
                        <div class="md:col-span-2">
                            <p class="text-gray-600 mb-1">Order Notes</p>
                            <p class="font-medium"><?php echo $order['notes']; ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Order Items</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Item
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Price
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $subtotal = 0;
                                foreach ($order_items as $item): 
                                    $item_total = $item['item_price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $item['item_name']; ?></div>
                                        <?php if (!empty($item['special_instructions'])): ?>
                                            <div class="text-xs text-gray-500 italic"><?php echo $item['special_instructions']; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatPrice($item['item_price']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        <?php echo $item['quantity']; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <?php echo formatPrice($item_total); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-500">
                                        Subtotal
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                        <?php echo formatPrice($subtotal); ?>
                                    </td>
                                </tr>
                                <?php if (isset($order['tax']) && $order['tax'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-500">
                                        Tax
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                        <?php echo formatPrice($order['tax']); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-500">
                                        Delivery Fee
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                        <?php echo formatPrice($order['delivery_fee']); ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-right text-base font-bold text-gray-900">
                                        Total
                                    </td>
                                    <td class="px-4 py-3 text-right text-base font-bold text-gray-900">
                                        <?php 
                                        $total = isset($order['total_price']) ? $order['total_price'] : $subtotal;
                                        echo formatPrice($total); 
                                        ?>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4">Customer Information</h2>
                    
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1">Name</p>
                        <p class="font-medium">
                            <?php 
                            if (isset($order['customer_name']) && !empty($order['customer_name'])) {
                                echo $order['customer_name'];
                            } elseif (isset($order['customer_id'])) {
                                echo 'Customer #' . $order['customer_id'];
                            } else {
                                echo 'Guest';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <?php if (isset($order['customer_phone']) && !empty($order['customer_phone'])): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1">Phone</p>
                        <p class="font-medium"><?php echo $order['customer_phone']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['customer_email']) && !empty($order['customer_email'])): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1">Email</p>
                        <p class="font-medium"><?php echo $order['customer_email']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['customer_address']) && !empty($order['customer_address'])): ?>
                    <div class="mb-4">
                        <p class="text-gray-600 mb-1">Address</p>
                        <p class="font-medium"><?php echo nl2br($order['customer_address']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['customer_id']) && !empty($order['customer_id'])): ?>
                    <div class="mt-6">
                        <a href="view_customer.php?id=<?php echo $order['customer_id']; ?>" class="inline-flex items-center text-primary hover:text-primary-dark">
                            <i class="fas fa-user mr-2"></i> View Customer Profile
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Order History -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Order Updates</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Order Created</p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if (isset($order['status']) && $order['status'] !== 'pending'): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Status Updated to <?php echo ucfirst($order['status']); ?></p>
                                <p class="text-xs text-gray-500">
                                    <?php 
                                    if (isset($order['updated_at'])) {
                                        echo date('M d, Y h:i A', strtotime($order['updated_at']));
                                    } else {
                                        echo 'Time not recorded';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($order['payment_status']) && $order['payment_status'] === 'paid'): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Payment Received</p>
                                <p class="text-xs text-gray-500">
                                    <?php 
                                    if (isset($order['payment_date'])) {
                                        echo date('M d, Y h:i A', strtotime($order['payment_date']));
                                    } else {
                                        echo 'Time not recorded';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($order['completed_at']) && !empty($order['completed_at'])): ?>
                        <div class="flex items-start">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <i class="fas fa-check-double"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">Order Completed</p>
                                <p class="text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($order['completed_at'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
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