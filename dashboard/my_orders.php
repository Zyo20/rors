<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['message'] = "Please login to view your orders";
    $_SESSION['message_type'] = "error";
    header("Location: ../pages/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = [];

// Get user's orders
$conn = getConnection();
$stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                       FROM orders o 
                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                       WHERE o.customer_id = ? 
                       GROUP BY o.id 
                       ORDER BY o.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Restaurant Ordering and Reservation System</title>
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">My Orders</h1>
            <a href="../pages/order.php" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                <i class="fas fa-plus mr-2"></i> Place New Order
            </a>
        </div>
        
        <?php displayMessage(); ?>
        
        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">No Orders Yet</h2>
                <p class="text-gray-600 mb-6">You haven't placed any orders yet.</p>
                <a href="../pages/menu.php" class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90 transition">
                    Browse Our Menu
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500"><?php echo date('M d, Y g:i A', strtotime($order['created_at'])); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-500"><?php echo $order['item_count']; ?> item(s)</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?php echo formatPrice($order['total_amount']); ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="text-primary hover:text-primary-dark">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html> 