<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view order details";
    $_SESSION['message_type'] = "error";
    header("Location: ../pages/login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid order ID";
    $_SESSION['message_type'] = "error";
    header("Location: my_orders.php");
    exit;
}

$order_id = (int) $_GET['id'];
$user_id = $_SESSION['user_id'];
$order = null;
$order_items = [];

// Get order details
$conn = getConnection();

// Check if the order belongs to the user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    $_SESSION['message'] = "Order not found or access denied";
    $_SESSION['message_type'] = "error";
    header("Location: my_orders.php");
    exit;
}
$stmt->close();

// Get order items
$stmt = $conn->prepare("SELECT oi.*, mi.name, mi.image 
                      FROM order_items oi 
                      JOIN menu_items mi ON oi.menu_item_id = mi.id 
                      WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $order_items[] = $row;
    }
}
$stmt->close();
$conn->close();

// Calculate order subtotal and total
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Restaurant Ordering and Reservation System</title>
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
    <?php include '../includes/navbar.php'; ?>
    
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="mb-6">
            <a href="my_orders.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to My Orders
            </a>
            <h1 class="text-3xl font-bold mt-2">Order Details</h1>
        </div>
        
        <?php displayMessage(); ?>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="p-6 border-b">
                <div class="flex flex-wrap justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold mb-2">Order #<?php echo $order['id']; ?></h2>
                        <p class="text-gray-600">
                            <span class="mr-4">
                                <i class="far fa-calendar-alt mr-1"></i> 
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </span>
                            <span>
                                <i class="far fa-clock mr-1"></i> 
                                <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <?php
                        $statusClass = '';
                        switch ($order['status']) {
                            case 'pending':
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'processing':
                                $statusClass = 'bg-blue-100 text-blue-800';
                                break;
                            case 'completed':
                                $statusClass = 'bg-green-100 text-green-800';
                                break;
                            case 'cancelled':
                                $statusClass = 'bg-red-100 text-red-800';
                                break;
                            default:
                                $statusClass = 'bg-gray-100 text-gray-800';
                        }
                        ?>
                        <span class="px-4 py-2 inline-flex text-sm font-semibold rounded-full <?php echo $statusClass; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-bold mb-2">Order Items</h3>
            </div>
            
            <div class="divide-y">
                <?php foreach ($order_items as $item): ?>
                    <div class="p-6 flex items-center">
                        <div class="flex-shrink-0 mr-4">
                            <img src="<?php echo !empty($item['image']) ? '../' . $item['image'] : '../assets/images/default-food.jpg'; ?>" 
                                 alt="<?php echo $item['name']; ?>" 
                                 class="w-16 h-16 object-cover rounded-md">
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-bold"><?php echo $item['name']; ?></h4>
                            <p class="text-gray-600 text-sm">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="font-bold"><?php echo formatPrice($item['price']); ?></p>
                            <p class="text-gray-600 text-sm">Subtotal: <?php echo formatPrice($item['price'] * $item['quantity']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h3 class="text-lg font-bold mb-4">Order Summary</h3>
                
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    
                    <?php if (isset($order['delivery_fee']) && $order['delivery_fee'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Delivery Fee:</span>
                            <span><?php echo formatPrice($order['delivery_fee']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax:</span>
                            <span><?php echo formatPrice($order['tax_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-accent">
                            <span>Discount:</span>
                            <span>-<?php echo formatPrice($order['discount_amount']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="border-t pt-4">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total:</span>
                        <span><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($order['status'] === 'pending'): ?>
                <div class="p-6 bg-gray-50 border-t flex justify-end">
                    <form action="cancel_order.php" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition">
                            <i class="fas fa-times mr-2"></i> Cancel Order
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html> 