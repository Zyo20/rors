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
$customers = [];
$menu_items = [];

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order information
    $status = $_POST['status'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $customer_id = isset($_POST['customer_id']) && !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
    $notes = $_POST['notes'] ?? '';
    
    // Validate inputs
    if (empty($status)) {
        $error_message = 'Order status is required.';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update order
            $update_sql = "UPDATE orders SET 
                           status = ?, 
                           payment_status = ?,
                           notes = ?";
            
            $params = [$status, $payment_status, $notes];
            $types = "sss";
            
            // Add customer_id if provided
            if ($customer_id) {
                $update_sql .= ", customer_id = ?";
                $params[] = $customer_id;
                $types .= "i";
            }
            
            // Add completed_at timestamp if status is 'completed'
            if ($status === 'completed') {
                $update_sql .= ", completed_at = NOW()";
            }
            
            $update_sql .= " WHERE id = ?";
            $params[] = $order_id;
            $types .= "i";
            
            $stmt = $conn->prepare($update_sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param($types, ...$params);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Handle order items updates if provided
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item_id => $item_data) {
                    $quantity = (int)$item_data['quantity'];
                    $special_instructions = $item_data['special_instructions'] ?? '';
                    
                    if ($quantity > 0) {
                        // Update existing item
                        $update_item_sql = "UPDATE order_items SET quantity = ?, special_instructions = ? WHERE id = ? AND order_id = ?";
                        $update_item_stmt = $conn->prepare($update_item_sql);
                        $update_item_stmt->bind_param("isii", $quantity, $special_instructions, $item_id, $order_id);
                        $update_item_stmt->execute();
                        $update_item_stmt->close();
                    } else {
                        // Remove item if quantity is 0
                        $delete_item_sql = "DELETE FROM order_items WHERE id = ? AND order_id = ?";
                        $delete_item_stmt = $conn->prepare($delete_item_sql);
                        $delete_item_stmt->bind_param("ii", $item_id, $order_id);
                        $delete_item_stmt->execute();
                        $delete_item_stmt->close();
                    }
                }
            }
            
            // Add new items if provided
            if (isset($_POST['new_items']) && is_array($_POST['new_items'])) {
                foreach ($_POST['new_items'] as $new_item) {
                    if (!empty($new_item['menu_item_id']) && !empty($new_item['quantity'])) {
                        $menu_item_id = (int)$new_item['menu_item_id'];
                        $quantity = (int)$new_item['quantity'];
                        $special_instructions = $new_item['special_instructions'] ?? '';
                        
                        if ($quantity > 0) {
                            $add_item_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, special_instructions) VALUES (?, ?, ?, ?)";
                            $add_item_stmt = $conn->prepare($add_item_sql);
                            $add_item_stmt->bind_param("iiis", $order_id, $menu_item_id, $quantity, $special_instructions);
                            $add_item_stmt->execute();
                            $add_item_stmt->close();
                        }
                    }
                }
            }
            
            // Commit transaction
            $conn->commit();
            $success_message = 'Order has been updated successfully.';
            
            // Redirect to view page after successful update
            header("Location: view_order.php?id=$order_id&success=1");
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = 'Error updating order: ' . $e->getMessage();
        }
    }
}

// Get order details
$sql = "SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email 
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

// Get list of customers for dropdown
$customers_sql = "SELECT id, name, phone FROM customers ORDER BY name";
$customers_result = $conn->query($customers_sql);
if ($customers_result && $customers_result->num_rows > 0) {
    while ($customer = $customers_result->fetch_assoc()) {
        $customers[] = $customer;
    }
}

// Get list of menu items for adding new items
$menu_sql = "SELECT id, name, price, category_id FROM menu_items WHERE is_available = 1 ORDER BY category_id, name";
$menu_result = $conn->query($menu_sql);
if ($menu_result && $menu_result->num_rows > 0) {
    while ($item = $menu_result->fetch_assoc()) {
        $menu_items[] = $item;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order #<?php echo $order_id; ?> - Restaurant Ordering and Reservation System</title>
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
                <h1 class="text-3xl font-bold">Update Order #<?php echo $order_id; ?></h1>
                <p class="text-gray-600">Created on <?php echo date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?></p>
            </div>
            <div>
                <a href="view_order.php?id=<?php echo $order_id; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                    <i class="fas fa-eye mr-2"></i> View Order
                </a>
            </div>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <p class="font-bold">Error:</p>
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        
        <form id="update-order-form" action="update_order.php?id=<?php echo $order_id; ?>" method="post">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Order Info -->
                <div class="md:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-bold mb-4">Order Details</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                    <option value="pending" <?php if ($order['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="preparing" <?php if ($order['status'] === 'preparing') echo 'selected'; ?>>Preparing</option>
                                    <option value="ready" <?php if ($order['status'] === 'ready') echo 'selected'; ?>>Ready</option>
                                    <option value="served" <?php if ($order['status'] === 'served') echo 'selected'; ?>>Served</option>
                                    <option value="completed" <?php if ($order['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                                    <option value="cancelled" <?php if ($order['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                                <select name="payment_status" id="payment_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                    <option value="pending" <?php if (isset($order['payment_status']) && $order['payment_status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="paid" <?php if (isset($order['payment_status']) && $order['payment_status'] === 'paid') echo 'selected'; ?>>Paid</option>
                                    <option value="failed" <?php if (isset($order['payment_status']) && $order['payment_status'] === 'failed') echo 'selected'; ?>>Failed</option>
                                    <option value="refunded" <?php if (isset($order['payment_status']) && $order['payment_status'] === 'refunded') echo 'selected'; ?>>Refunded</option>
                                </select>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Order Notes</label>
                                <textarea name="notes" id="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"><?php echo isset($order['notes']) ? htmlspecialchars($order['notes']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-bold mb-4">Order Items</h2>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 mb-4">
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
                                            Special Instructions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo $item['item_name']; ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo formatPrice($item['item_price']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            <input type="number" name="items[<?php echo $item['id']; ?>][quantity]" value="<?php echo $item['quantity']; ?>" 
                                                   min="0" class="w-16 text-center rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        </td>
                                        <td class="px-4 py-4">
                                            <input type="text" name="items[<?php echo $item['id']; ?>][special_instructions]" 
                                                   value="<?php echo htmlspecialchars($item['special_instructions'] ?? ''); ?>" 
                                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                                   placeholder="Special instructions">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Add New Item Section -->
                        <div class="mt-6">
                            <h3 class="text-lg font-medium mb-2">Add New Item</h3>
                            
                            <div id="new-items-container">
                                <div class="new-item grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-50 rounded-md">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Menu Item</label>
                                        <select name="new_items[0][menu_item_id]" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                            <option value="">Select an item</option>
                                            <?php 
                                            $current_category = '';
                                            foreach ($menu_items as $menu_item):
                                                if ($current_category !== $menu_item['category']):
                                                    if ($current_category !== '') echo '</optgroup>';
                                                    $current_category = $menu_item['category'];
                                                    echo '<optgroup label="' . ucfirst($current_category) . '">';
                                                endif;
                                            ?>
                                                <option value="<?php echo $menu_item['id']; ?>"><?php echo $menu_item['name']; ?> (<?php echo formatPrice($menu_item['price']); ?>)</option>
                                            <?php 
                                            endforeach;
                                            if ($current_category !== '') echo '</optgroup>';
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                        <input type="number" name="new_items[0][quantity]" value="1" min="1" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
                                        <input type="text" name="new_items[0][special_instructions]" 
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                               placeholder="Special instructions">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="button" id="add-item-btn" class="mt-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-plus mr-2"></i> Add Another Item
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-bold mb-4">Customer Information</h2>
                        
                        <div class="mb-4">
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                            <select name="customer_id" id="customer_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                <option value="">Select a customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php if (isset($order['customer_id']) && $order['customer_id'] == $customer['id']) echo 'selected'; ?>>
                                    <?php echo $customer['name']; ?> (<?php echo $customer['phone']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <p class="text-gray-600 mb-1">Current Customer</p>
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
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between mb-4">
                            <button type="submit" name="update_order" value="1" class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="view_order.php?id=<?php echo $order_id; ?>" class="w-full text-center bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add item button functionality
            const addItemBtn = document.getElementById('add-item-btn');
            const newItemsContainer = document.getElementById('new-items-container');
            let itemCount = 1;
            
            addItemBtn.addEventListener('click', function() {
                const newItemTemplate = document.querySelector('.new-item').cloneNode(true);
                
                // Update the name attributes
                const selects = newItemTemplate.querySelectorAll('select');
                const inputs = newItemTemplate.querySelectorAll('input');
                
                selects.forEach(select => {
                    select.name = select.name.replace(/\[\d+\]/, '[' + itemCount + ']');
                    select.value = '';
                });
                
                inputs.forEach(input => {
                    input.name = input.name.replace(/\[\d+\]/, '[' + itemCount + ']');
                    if (input.type === 'number') {
                        input.value = '1';
                    } else {
                        input.value = '';
                    }
                });
                
                newItemsContainer.appendChild(newItemTemplate);
                itemCount++;
            });
        });
    </script>
</body>
</html> 