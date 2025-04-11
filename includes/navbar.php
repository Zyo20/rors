<?php
// Determine if we're in a subdirectory and the correct path back to root
$current_path = $_SERVER['PHP_SELF'];
$in_dashboard = strpos($current_path, '/dashboard/') !== false;
$in_pages = strpos($current_path, '/pages/') !== false;
$baseUrl = '';

if ($in_dashboard) {
    $baseUrl = '../';
} elseif ($in_pages) {
    $baseUrl = '../';
}

// Get cart count for display
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>

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
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'customer'): ?>
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
                <?php if ($cart_count > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-secondary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
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

<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
</script> 