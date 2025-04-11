<?php
// Start output buffering to prevent "headers already sent" warnings
ob_start();

session_start();
require_once __DIR__ . '/../config/database.php';

// Determine if we're in a subdirectory
$isSubDirectory = strpos($_SERVER['PHP_SELF'], '/pages/') !== false;
$baseUrl = $isSubDirectory ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Ordering and Reservation System</title>
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
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center">
                <a href="<?php echo $baseUrl; ?>index.php" class="text-2xl font-bold text-primary">RORS</a>
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="<?php echo $baseUrl; ?>index.php" class="text-gray-700 hover:text-primary">Home</a>
                <a href="<?php echo $baseUrl; ?>pages/menu.php" class="text-gray-700 hover:text-primary">Menu</a>
                <a href="<?php echo $baseUrl; ?>pages/reservation.php" class="text-gray-700 hover:text-primary">Reservations</a>
                <a href="<?php echo $baseUrl; ?>pages/order.php" class="text-gray-700 hover:text-primary">Order Online</a>
                <a href="<?php echo $baseUrl; ?>pages/contact.php" class="text-gray-700 hover:text-primary">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-gray-700 hover:text-primary">
                            <span><?php echo $_SESSION['user_name']; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden group-hover:block">
                            <?php if ($_SESSION['user_role'] !== 'customer'): ?>
                                <a href="<?php echo $baseUrl . $_SESSION['user_role']; ?>/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                            <?php endif; ?>
                            <a href="<?php echo $baseUrl; ?>pages/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="<?php echo $baseUrl; ?>dashboard/my_orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Orders</a>
                            <a href="<?php echo $baseUrl; ?>pages/my_reservations.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Reservations</a>
                            <hr class="my-1">
                            <a href="<?php echo $baseUrl; ?>includes/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>pages/login.php" class="text-gray-700 hover:text-primary">Login</a>
                    <a href="<?php echo $baseUrl; ?>pages/register.php" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-primary-dark">Register</a>
                <?php endif; ?>
                <a href="<?php echo $baseUrl; ?>pages/cart.php" class="text-gray-700 hover:text-primary relative">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-secondary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo count($_SESSION['cart']); ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="md:hidden">
                <button id="mobile-menu-btn" class="text-gray-700">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </nav>
        <div id="mobile-menu" class="hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?php echo $baseUrl; ?>index.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Home</a>
                <a href="<?php echo $baseUrl; ?>pages/menu.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Menu</a>
                <a href="<?php echo $baseUrl; ?>pages/reservation.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Reservations</a>
                <a href="<?php echo $baseUrl; ?>pages/order.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Order Online</a>
                <a href="<?php echo $baseUrl; ?>pages/contact.php" class="block px-3 py-2 text-gray-700 hover:bg-gray-100">Contact</a>
            </div>
        </div>
    </header>
    <main class="flex-grow container mx-auto px-4 py-8"> 