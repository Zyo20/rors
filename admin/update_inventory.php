<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin or manager role
if (!hasRole('admin') && !hasRole('manager')) {
    requireRole('admin');
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid inventory item ID";
    $_SESSION['message_type'] = "error";
    header("Location: inventory.php");
    exit;
}

$item_id = (int) $_GET['id'];

// Connect to database
$conn = getConnection();

// Initialize variables
$name = '';
$description = '';
$quantity = '';
$unit = '';
$threshold = '';
$errors = [];

// Fetch current inventory item data
$stmt = $conn->prepare("SELECT * FROM inventory WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Inventory item not found";
    $_SESSION['message_type'] = "error";
    header("Location: inventory.php");
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

// Pre-fill form with existing data
$name = $item['name'];
$description = $item['description'] ?? '';
$quantity = $item['quantity'];
$unit = $item['unit'];
$threshold = $item['threshold'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $threshold = trim($_POST['threshold'] ?? '');
    
    // Perform validation
    if (empty($name)) {
        $errors[] = "Item name is required";
    }
    
    if (empty($quantity) || !is_numeric($quantity)) {
        $errors[] = "Valid quantity is required";
    }
    
    if (empty($unit)) {
        $errors[] = "Unit of measurement is required";
    }
    
    if (empty($threshold) || !is_numeric($threshold)) {
        $errors[] = "Valid threshold value is required";
    }
    
    // If no errors, update the inventory item
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE inventory SET name = ?, description = ?, quantity = ?, unit = ?, threshold = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssdsdi", $name, $description, $quantity, $unit, $threshold, $item_id);
        
        if ($stmt->execute()) {
            // Redirect to inventory page with success message
            $_SESSION['message'] = "Inventory item updated successfully";
            $_SESSION['message_type'] = "success";
            header("Location: inventory.php");
            exit;
        } else {
            $errors[] = "Error updating inventory item: " . $conn->error;
        }
        
        $stmt->close();
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
    <title>Update Inventory - Restaurant Ordering and Reservation System</title>
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
        <div class="mb-6">
            <a href="inventory.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to Inventory
            </a>
            <h1 class="text-3xl font-bold mt-2">Update Inventory Item</h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-md">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form action="" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="2"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Current Quantity *</label>
                        <input type="number" name="quantity" id="quantity" value="<?php echo htmlspecialchars($quantity); ?>" 
                               step="0.01" min="0" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    </div>

                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement *</label>
                        <input type="text" name="unit" id="unit" value="<?php echo htmlspecialchars($unit); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" 
                               placeholder="e.g. kg, liters, pieces" required>
                    </div>

                    <div>
                        <label for="threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold *</label>
                        <input type="number" name="threshold" id="threshold" value="<?php echo htmlspecialchars($threshold); ?>" 
                               step="0.01" min="0" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                        <p class="text-xs text-gray-500 mt-1">Set the minimum quantity before an item is considered low in stock</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="inventory.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-save mr-2"></i> Update Inventory
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 