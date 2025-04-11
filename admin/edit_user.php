<?php
session_start();
require_once '../includes/functions.php';
require_once '../config/database.php';

// Require admin role
requireRole('admin');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid user ID";
    $_SESSION['message_type'] = "error";
    header("Location: users.php");
    exit;
}

$user_id = (int) $_GET['id'];

// Connect to database
$conn = getConnection();

// Initialize variables
$name = '';
$email = '';
$phone = '';
$role = '';
$errors = [];

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "User not found";
    $_SESSION['message_type'] = "error";
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Pre-fill form with existing data
$name = $user['name'];
$email = $user['email'];
$phone = $user['phone'] ?? '';
$role = $user['role'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Perform validation
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required";
    } else {
        // Check if email already exists (excluding current user)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $errors[] = "Email address already in use";
        }
        
        $stmt->close();
    }
    
    // Check password only if provided
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    // If no errors, update the user
    if (empty($errors)) {
        // Begin transaction to ensure data consistency
        $conn->begin_transaction();
        
        try {
            // Get the current role
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_user = $result->fetch_assoc();
            $current_role = $current_user['role'];
            $stmt->close();
            
            if (!empty($password)) {
                // Update with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $role, $hashed_password, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
            }
            $stmt->execute();
            $stmt->close();
            
            // Handle customer table based on role changes
            if ($role === 'customer') {
                // Check if there's already a record in customers table
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM customers WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $customer_exists = $row['count'] > 0;
                $stmt->close();
                
                if (!$customer_exists) {
                    // Insert into customers table
                    $stmt = $conn->prepare("INSERT INTO customers (id, name, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("isss", $user_id, $name, $email, $phone);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Update existing customer record
                    $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
            } else if ($current_role === 'customer' && $role !== 'customer') {
                // User was a customer but no longer is, we can either:
                // Option 1: Remove from customers table
                // $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
                // $stmt->bind_param("i", $user_id);
                // $stmt->execute();
                // $stmt->close();
                
                // Option 2: Keep the record (we'll do this to preserve history)
                // No action needed
            }
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to user details page with success message
            $_SESSION['message'] = "User updated successfully";
            $_SESSION['message_type'] = "success";
            header("Location: view_user.php?id=" . $user_id);
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Error updating user: " . $e->getMessage();
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
    <title>Edit User - Restaurant Ordering and Reservation System</title>
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
        <div class="mb-6">
            <a href="view_user.php?id=<?php echo $user_id; ?>" class="inline-flex items-center text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to User Profile
            </a>
            <h1 class="text-3xl font-bold mt-2">Edit User</h1>
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
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                               placeholder="Optional">
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">User Role *</label>
                        <select name="role" id="role" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="customer" <?php if ($role === 'customer') echo 'selected'; ?>>Customer</option>
                            <option value="kitchen" <?php if ($role === 'kitchen') echo 'selected'; ?>>Kitchen</option>
                            <option value="manager" <?php if ($role === 'manager') echo 'selected'; ?>>Manager</option>
                            <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Change Password (Optional)</h3>
                        <p class="text-sm text-gray-500 mb-4">Leave blank to keep the current password</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" name="password" id="password" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                               minlength="6">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <a href="view_user.php?id=<?php echo $user_id; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition mr-2">
                        Cancel
                    </a>
                    <button type="submit" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark transition">
                        <i class="fas fa-save mr-2"></i> Update User
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