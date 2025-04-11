<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin role (managers shouldn't have access to user management)
requireRole('admin');

// Connect to database
$conn = getConnection();

// Set default values
$users = [];
$totalUsers = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$usersPerPage = 10;
$offset = ($currentPage - 1) * $usersPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';

// Build the query
$countSql = "SELECT COUNT(*) as total FROM users";
$usersSql = "SELECT * FROM users";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(id LIKE '%$search%' OR name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}

if (!empty($roleFilter)) {
    $roleFilter = $conn->real_escape_string($roleFilter);
    $whereConditions[] = "role = '$roleFilter'";
}

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countSql .= $whereClause;
    $usersSql .= $whereClause;
}

// Add order by and limit
$usersSql .= " ORDER BY created_at DESC LIMIT $offset, $usersPerPage";

// Execute count query
$totalResult = $conn->query($countSql);
if ($totalResult && $totalResult->num_rows > 0) {
    $totalUsers = $totalResult->fetch_assoc()['total'];
}

// Execute users query
$usersResult = $conn->query($usersSql);
if ($usersResult && $usersResult->num_rows > 0) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Calculate total pages
$totalPages = ceil($totalUsers / $usersPerPage);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Restaurant Ordering and Reservation System</title>
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Manage Users</h1>
            <a href="add_user.php" class="bg-accent text-white px-4 py-2 rounded-md hover:bg-green-600 transition">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </a>
        </div>

        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-md 
                <?php 
                    if ($messageType === 'success') echo 'bg-green-100 text-green-700';
                    elseif ($messageType === 'error') echo 'bg-red-100 text-red-700';
                    elseif ($messageType === 'info') echo 'bg-blue-100 text-blue-700';
                    else echo 'bg-yellow-100 text-yellow-700';
                ?>">
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
                           placeholder="Search by name, email, or phone">
                </div>
                <div class="w-full md:w-1/4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">User Role</label>
                    <select name="role" id="role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">All Roles</option>
                        <option value="admin" <?php if ($roleFilter === 'admin') echo 'selected'; ?>>Admin</option>
                        <option value="manager" <?php if ($roleFilter === 'manager') echo 'selected'; ?>>Manager</option>
                        <option value="staff" <?php if ($roleFilter === 'staff') echo 'selected'; ?>>Staff</option>
                        <option value="customer" <?php if ($roleFilter === 'customer') echo 'selected'; ?>>Customer</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($roleFilter)): ?>
                    <div class="flex items-end">
                        <a href="users.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Joined
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">#<?php echo $user['id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                                if ($user['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                                elseif ($user['role'] === 'manager') echo 'bg-blue-100 text-blue-800';
                                                elseif ($user['role'] === 'staff') echo 'bg-indigo-100 text-indigo-800';
                                                else echo 'bg-gray-100 text-gray-800';
                                            ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="text-primary hover:text-primary-dark mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No users found.
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
                            <span class="font-medium"><?php echo min($offset + $usersPerPage, $totalUsers); ?></span> of 
                            <span class="font-medium"><?php echo $totalUsers; ?></span> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?>" 
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