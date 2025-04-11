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
$menuItems = [];
$totalItems = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Get all categories for filter dropdown - temporarily disable since we're not sure what column to use
$categories = [];
/*
$categoriesSql = "SELECT DISTINCT type as category FROM menu_items ORDER BY type";
$categoriesResult = $conn->query($categoriesSql);
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
*/

// Build the query
$countSql = "SELECT COUNT(*) as total FROM menu_items";
$menuSql = "SELECT m.*, 
           (SELECT COUNT(*) FROM order_items oi WHERE oi.menu_item_id = m.id) as order_count
           FROM menu_items m";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(m.name LIKE '%$search%' OR m.description LIKE '%$search%')";
}

// Disable category filtering since we don't know the correct column name yet
/*
if (!empty($categoryFilter)) {
    $categoryFilter = $conn->real_escape_string($categoryFilter);
    $whereConditions[] = "m.type = '$categoryFilter'";
}
*/

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countSql .= $whereClause;
    $menuSql .= $whereClause;
}

// Add order by and limit
$menuSql .= " ORDER BY m.name LIMIT $offset, $itemsPerPage";

// Execute count query
$totalResult = $conn->query($countSql);
if ($totalResult && $totalResult->num_rows > 0) {
    $totalItems = $totalResult->fetch_assoc()['total'];
}

// Execute menu items query
$menuResult = $conn->query($menuSql);
if ($menuResult && $menuResult->num_rows > 0) {
    while ($row = $menuResult->fetch_assoc()) {
        $menuItems[] = $row;
    }
}

// Calculate total pages
$totalPages = ceil($totalItems / $itemsPerPage);

// Handle item deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if item is used in any orders
    $checkSql = "SELECT COUNT(*) as count FROM order_items WHERE menu_item_id = $id";
    $checkResult = $conn->query($checkSql);
    $isUsed = $checkResult && $checkResult->fetch_assoc()['count'] > 0;
    
    if ($isUsed) {
        $message = "Cannot delete this menu item as it is used in orders. You can disable it instead.";
        $messageType = "error";
    } else {
        // Delete the item
        $deleteSql = "DELETE FROM menu_items WHERE id = $id";
        if ($conn->query($deleteSql)) {
            $message = "Menu item deleted successfully.";
            $messageType = "success";
        } else {
            $message = "Error deleting menu item: " . $conn->error;
            $messageType = "error";
        }
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
    <title>Manage Menu - Restaurant Ordering and Reservation System</title>
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
                    <a href="menu.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Menu</a>
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
            <h1 class="text-3xl font-bold">Manage Menu</h1>
            <div class="flex space-x-2">
                <a href="migrations/migrate_image_to_image_path.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition" title="Add image_path column to support future URL storage">
                    <i class="fas fa-sync-alt mr-2"></i> Fix Image Storage
                </a>
                <a href="add_menu_item.php" class="bg-accent text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                    <i class="fas fa-plus mr-2"></i> Add Menu Item
                </a>
            </div>
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
                           placeholder="Search by name or description">
                </div>
                <!-- Temporarily removed category filter
                <div class="w-full md:w-1/4">
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php if ($categoryFilter === $category) echo 'selected'; ?>>
                                <?php echo ucfirst($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                -->
                <div class="flex items-end">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($categoryFilter)): ?>
                    <div class="flex items-end">
                        <a href="menu.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Menu Items Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Image
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Orders
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($menuItems) > 0): ?>
                            <?php foreach ($menuItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="h-12 w-12 object-cover rounded-md">
                                        <?php else: ?>
                                            <div class="h-12 w-12 bg-gray-200 rounded-md flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-sm text-gray-500 max-w-xs truncate"><?php echo htmlspecialchars($item['description']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                                // Display category if we can find it in any column
                                                if (isset($item['category'])) {
                                                    echo ucfirst($item['category']);
                                                } elseif (isset($item['type'])) {
                                                    echo ucfirst($item['type']);
                                                } elseif (isset($item['item_type'])) {
                                                    echo ucfirst($item['item_type']);
                                                } else {
                                                    echo "N/A";
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo formatPrice($item['price']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $item['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $item['order_count']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="edit_menu_item.php?id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($item['order_count'] == 0): ?>
                                            <a href="menu.php?delete=<?php echo $item['id']; ?>" 
                                               onclick="return confirm('Are you sure you want to delete this item? This cannot be undone.');"
                                               class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" title="This item has been ordered and cannot be deleted" class="text-gray-400 cursor-not-allowed">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                    No menu items found.
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
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($categoryFilter) ? '&category=' . urlencode($categoryFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($categoryFilter) ? '&category=' . urlencode($categoryFilter) : ''; ?>" 
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