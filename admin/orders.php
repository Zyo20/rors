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

// Set default values
$orders = [];
$totalOrders = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$ordersPerPage = 10;
$offset = ($currentPage - 1) * $ordersPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$countSql = "SELECT COUNT(*) as total FROM orders";
$ordersSql = "SELECT o.* 
             FROM orders o";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(o.id LIKE '%$search%')";
}

if (!empty($statusFilter)) {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $whereConditions[] = "o.status = '$statusFilter'";
}

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countSql .= $whereClause;
    $ordersSql .= $whereClause;
}

// Add order by and limit
$ordersSql .= " ORDER BY o.created_at DESC LIMIT $offset, $ordersPerPage";

// Execute count query
$totalResult = $conn->query($countSql);
if ($totalResult && $totalResult->num_rows > 0) {
    $totalOrders = $totalResult->fetch_assoc()['total'];
}

// Execute orders query
$ordersResult = $conn->query($ordersSql);
if ($ordersResult && $ordersResult->num_rows > 0) {
    while ($row = $ordersResult->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Calculate total pages
$totalPages = ceil($totalOrders / $ordersPerPage);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Restaurant Ordering and Reservation System</title>
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
            <h1 class="text-3xl font-bold">Manage Orders</h1>
            <a href="#" onclick="document.getElementById('exportForm').submit();" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                <i class="fas fa-file-export mr-2"></i> Export Orders
            </a>
            <form id="exportForm" action="export_orders.php" method="post" class="hidden">
                <input type="hidden" name="export" value="1">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo $search; ?>">
                <?php endif; ?>
                <?php if (!empty($statusFilter)): ?>
                    <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                <?php endif; ?>
            </form>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="" method="get" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-1/4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if ($statusFilter === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="processing" <?php if ($statusFilter === 'processing') echo 'selected'; ?>>Processing</option>
                        <option value="ready" <?php if ($statusFilter === 'ready') echo 'selected'; ?>>Ready</option>
                        <option value="delivered" <?php if ($statusFilter === 'delivered') echo 'selected'; ?>>Delivered</option>
                        <option value="cancelled" <?php if ($statusFilter === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <div class="flex items-end">
                        <a href="orders.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payment
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php 
                                            if (isset($order['customer_name'])) {
                                                echo $order['customer_name'];
                                            } elseif (isset($order['customer_id'])) {
                                                echo 'Customer #' . $order['customer_id'];
                                            } else {
                                                echo 'Guest';
                                            }
                                            ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php 
                                            if (isset($order['customer_phone'])) {
                                                echo $order['customer_phone'];
                                            } elseif (isset($order['phone'])) {
                                                echo $order['phone'];
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php 
                                            $price = null;
                                            if (isset($order['total_price'])) {
                                                $price = $order['total_price'];
                                            } elseif (isset($order['price'])) {
                                                $price = $order['price'];
                                            } elseif (isset($order['amount'])) {
                                                $price = $order['amount'];
                                            } elseif (isset($order['total'])) {
                                                $price = $order['total'];
                                            }
                                            
                                            if ($price !== null) {
                                                echo function_exists('formatPrice') ? formatPrice($price) : '$' . number_format($price, 2);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                                $status = isset($order['status']) ? $order['status'] : 'unknown';
                                                if ($status === 'delivered' || $status === 'ready') echo 'bg-green-100 text-green-800';
                                                elseif ($status === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                elseif ($status === 'cancelled') echo 'bg-red-100 text-red-800';
                                                else echo 'bg-blue-100 text-blue-800';
                                            ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                                $paymentStatus = isset($order['payment_status']) ? $order['payment_status'] : 'unknown';
                                                if ($paymentStatus === 'paid') echo 'bg-green-100 text-green-800';
                                                elseif ($paymentStatus === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                else echo 'bg-red-100 text-red-800';
                                            ?>">
                                            <?php echo ucfirst($paymentStatus); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" class="text-primary hover:text-primary-dark mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="update_order.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                            <a href="update_order_status.php?id=<?php echo $order['id']; ?>&status=cancelled" 
                                               onclick="return confirm('Are you sure you want to cancel this order?');"
                                               class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $ordersPerPage, $totalOrders); ?></span> of 
                            <span class="font-medium"><?php echo $totalOrders; ?></span> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            // Display page numbers
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4 && $totalPages > 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium <?php echo $i === $currentPage ? 'bg-primary text-white' : 'text-gray-700 bg-white hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 