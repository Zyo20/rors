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
$reservations = [];
$totalReservations = 0;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$reservationsPerPage = 10;
$offset = ($currentPage - 1) * $reservationsPerPage;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$countSql = "SELECT COUNT(*) as total FROM reservations";
$reservationsSql = "SELECT r.*, u.name as customer_name, u.phone as customer_phone
                   FROM reservations r
                   LEFT JOIN users u ON r.user_id = u.id";

// Add search and filter conditions if provided
$whereConditions = [];
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $whereConditions[] = "(r.id LIKE '%$search%' OR u.name LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

if (!empty($dateFilter)) {
    $dateFilter = $conn->real_escape_string($dateFilter);
    $whereConditions[] = "r.reservation_date = '$dateFilter'";
}

if (!empty($statusFilter)) {
    $statusFilter = $conn->real_escape_string($statusFilter);
    $whereConditions[] = "r.status = '$statusFilter'";
}

// Combine where conditions if any
if (!empty($whereConditions)) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    $countSql .= $whereClause;
    $reservationsSql .= $whereClause;
}

// Add order by and limit
$reservationsSql .= " ORDER BY r.reservation_date DESC, r.reservation_time DESC LIMIT $offset, $reservationsPerPage";

// Execute count query
$totalResult = $conn->query($countSql);
if ($totalResult && $totalResult->num_rows > 0) {
    $totalReservations = $totalResult->fetch_assoc()['total'];
}

// Execute reservations query
$reservationsResult = $conn->query($reservationsSql);
if ($reservationsResult && $reservationsResult->num_rows > 0) {
    while ($row = $reservationsResult->fetch_assoc()) {
        $reservations[] = $row;
    }
}

// Calculate total pages
$totalPages = ceil($totalReservations / $reservationsPerPage);

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Restaurant Ordering and Reservation System</title>
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
                    <a href="reservations.php" class="text-white hover:text-gray-300 border-b-2 border-secondary">Reservations</a>
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
            <h1 class="text-3xl font-bold">Manage Reservations</h1>
            <a href="#" onclick="document.getElementById('exportForm').submit();" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                <i class="fas fa-file-export mr-2"></i> Export Reservations
            </a>
            <form id="exportForm" action="export_reservations.php" method="post" class="hidden">
                <input type="hidden" name="export" value="1">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo $search; ?>">
                <?php endif; ?>
                <?php if (!empty($dateFilter)): ?>
                    <input type="hidden" name="date" value="<?php echo $dateFilter; ?>">
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
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($dateFilter); ?>" 
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                </div>
                <div class="w-full md:w-1/4">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if ($statusFilter === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="confirmed" <?php if ($statusFilter === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="seated" <?php if ($statusFilter === 'seated') echo 'selected'; ?>>Seated</option>
                        <option value="completed" <?php if ($statusFilter === 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if ($statusFilter === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($dateFilter) || !empty($statusFilter)): ?>
                    <div class="flex items-end">
                        <a href="reservations.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-times mr-2"></i> Clear
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reservations Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Party Size
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Special Requests
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($reservations) > 0): ?>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('h:i A', strtotime($reservation['reservation_time'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $reservation['customer_name']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $reservation['customer_phone'] ?? 'N/A'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $reservation['party_size']; ?> people</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                                if ($reservation['status'] === 'confirmed' || $reservation['status'] === 'seated' || $reservation['status'] === 'completed') echo 'bg-green-100 text-green-800';
                                                elseif ($reservation['status'] === 'pending') echo 'bg-yellow-100 text-yellow-800';
                                                elseif ($reservation['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                                else echo 'bg-blue-100 text-blue-800';
                                            ?>">
                                            <?php echo ucfirst($reservation['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">
                                            <?php echo $reservation['special_requests'] ?? 'None'; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="view_reservation.php?id=<?php echo $reservation['id']; ?>" class="text-primary hover:text-primary-dark mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="update_reservation.php?id=<?php echo $reservation['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($reservation['status'] !== 'cancelled' && $reservation['status'] !== 'completed'): ?>
                                            <a href="update_reservation_status.php?id=<?php echo $reservation['id']; ?>&status=cancelled" 
                                               onclick="return confirm('Are you sure you want to cancel this reservation?');"
                                               class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No reservations found.
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
                            <span class="font-medium"><?php echo min($offset + $reservationsPerPage, $totalReservations); ?></span> of 
                            <span class="font-medium"><?php echo $totalReservations; ?></span> results
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($dateFilter) ? '&date=' . urlencode($dateFilter) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($dateFilter) ? '&date=' . urlencode($dateFilter) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?>" 
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