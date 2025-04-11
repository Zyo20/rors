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
    $_SESSION['message'] = "Invalid menu item ID";
    $_SESSION['message_type'] = "error";
    header("Location: menu.php");
    exit;
}

$item_id = (int) $_GET['id'];

// Connect to database
$conn = getConnection();

// Initialize variables
$name = '';
$description = '';
$price = '';
$category_id = '';
$is_available = 1;
$image = '';
$errors = [];

// Get categories for dropdown
$categories = [];
$categoriesQuery = "SELECT id, name FROM menu_categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch current menu item data
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "Menu item not found";
    $_SESSION['message_type'] = "error";
    header("Location: menu.php");
    exit;
}

$item = $result->fetch_assoc();
$stmt->close();

// Pre-fill form with existing data
$name = $item['name'];
$description = $item['description'] ?? '';
$price = $item['price'];
$category_id = $item['category_id'] ?? '';
$is_available = $item['is_available'];
$image = $item['image'] ?? '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $category_id = trim($_POST['category_id'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image = trim($_POST['image'] ?? '');
    
    // Perform validation
    if (empty($name)) {
        $errors[] = "Item name is required";
    }
    
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = "Valid price is required";
    }
    
    // If no errors, update the menu item
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, is_available = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssdsisi", $name, $description, $price, $category_id, $is_available, $image, $item_id);
        
        if ($stmt->execute()) {
            // Redirect to menu page with success message
            $_SESSION['message'] = "Menu item updated successfully";
            $_SESSION['message_type'] = "success";
            header("Location: menu.php");
            exit;
        } else {
            $errors[] = "Error updating menu item: " . $conn->error;
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
    <title>Edit Menu Item - Restaurant Ordering and Reservation System</title>
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
        <div class="mb-6">
            <a href="menu.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to Menu
            </a>
            <h1 class="text-3xl font-bold mt-2">Edit Menu Item</h1>
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

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category ID</label>
                        <select name="category_id" id="category_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                        <input type="number" name="price" id="price" value="<?php echo htmlspecialchars($price); ?>" 
                               step="0.01" min="0" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                        <input type="url" name="image" id="image" value="<?php echo htmlspecialchars($image); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                               placeholder="https://example.com/image.jpg">
                        <p class="text-xs text-gray-500 mt-1">Enter the full URL to an image (e.g., https://example.com/image.jpg)</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="description" rows="4"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_available" id="is_available" value="1"
                                   <?php echo $is_available ? 'checked' : ''; ?>
                                   class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary">
                            <label for="is_available" class="ml-2 block text-sm text-gray-700">
                                Item is available for ordering
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="menu.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-save mr-2"></i> Update Menu Item
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