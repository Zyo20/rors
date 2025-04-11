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
$inventoryItems = [];
$totalItems = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$countSql = "SELECT COUNT(*) as total FROM inventory";
$inventorySql = "SELECT * FROM inventory";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
}

if (!empty($statusFilter)) {
    if ($statusFilter === 'low') {
        $whereConditions[] = "quantity <= threshold";
    } elseif ($statusFilter === 'out') {
        $whereConditions[] = "quantity <= 0";
    } elseif ($statusFilter === 'ok') {
        $whereConditions[] = "quantity > threshold";
    }
}

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countSql .= $whereClause;
    $inventorySql .= $whereClause;
}

// Add order by and limit
$inventorySql .= " ORDER BY name LIMIT $offset, $itemsPerPage";

// Execute count query
$totalResult = $conn->query($countSql);
if ($totalResult && $totalResult->num_rows > 0) {
    $totalItems = $totalResult->fetch_assoc()['total'];
}

// Execute inventory items query
$inventoryResult = $conn->query($inventorySql);
if ($inventoryResult && $inventoryResult->num_rows > 0) {
    while ($row = $inventoryResult->fetch_assoc()) {
        $inventoryItems[] = $row;
    }
}

// Calculate total pages
$totalPages = ceil($totalItems / $itemsPerPage);

// Handle item deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete the item
    $deleteSql = "DELETE FROM inventory WHERE id = $id";
    if ($conn->query($deleteSql)) {
        $message = "Inventory item deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Error deleting inventory item: " . $conn->error;
        $messageType = "error";
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Restaurant Ordering and Reservation System</title>
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
                    <a href="inventory.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Inventory</a>
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
            <h1 class="text-3xl font-bold">Manage Inventory</h1>
            <a href="add_inventory_item.php" class="bg-accent text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                <i class="fas fa-plus mr-2"></i> Add Inventory Item
            </a>
        </div>

        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form action="" method="get" class="flex flex-col md:flex-row gap-4">
                <div class="flex-grow">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                           placeholder="Search by item name or description">
                </div>
                <div class="w-full md:w-1/4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Stock Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">All Items</option>
                        <option value="ok" <?php if ($statusFilter === 'ok') echo 'selected'; ?>>In Stock</option>
                        <option value="low" <?php if ($statusFilter === 'low') echo 'selected'; ?>>Low Stock</option>
                        <option value="out" <?php if ($statusFilter === 'out') echo 'selected'; ?>>Out of Stock</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($statusFilter)): ?>
                    <div class="flex items-end">
                        <a href="inventory.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Inventory Items Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Current Quantity
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Threshold
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Last Updated
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($inventoryItems) > 0): ?>
                            <?php foreach ($inventoryItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-sm text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $item['quantity'] . ' ' . $item['unit']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $item['threshold'] . ' ' . $item['unit']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                                if ($item['quantity'] <= 0) echo 'bg-red-100 text-red-800';
                                                elseif ($item['quantity'] <= $item['threshold']) echo 'bg-yellow-100 text-yellow-800';
                                                else echo 'bg-green-100 text-green-800';
                                            ?>">
                                            <?php 
                                                if ($item['quantity'] <= 0) echo 'Out of Stock';
                                                elseif ($item['quantity'] <= $item['threshold']) echo 'Low Stock';
                                                else echo 'In Stock';
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            <?php echo isset($item['updated_at']) ? date('M d, Y', strtotime($item['updated_at'])) : 'N/A'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="update_inventory.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="inventory.php?delete=<?php echo $item['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this inventory item? This cannot be undone.');"
                                           class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No inventory items found.
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
                            <span class="font-medium"><?php echo min($offset + $itemsPerPage, $totalItems); ?></span> of 
                            <span class="font-medium"><?php echo $totalItems; ?></span> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
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