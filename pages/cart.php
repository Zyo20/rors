<?php
include '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Initialize cart variables
$cart_items = [];
$cart_total = 0;

// Retrieve cart items if they exist
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_items = $_SESSION['cart'];
    
    // Calculate cart total
    foreach ($cart_items as $item) {
        $cart_total += $item['price'] * $item['quantity'];
    }
}

// Handle remove from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $item_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        $_SESSION['message'] = "Item removed from cart";
        $_SESSION['message_type'] = "success";
        header("Location: cart.php");
        exit;
    }
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $item_id => $quantity) {
        $item_id = (int)$item_id;
        $quantity = (int)$quantity;
        
        if (isset($_SESSION['cart'][$item_id])) {
            if ($quantity > 0 && $quantity <= 20) {
                $_SESSION['cart'][$item_id]['quantity'] = $quantity;
            } elseif ($quantity <= 0) {
                unset($_SESSION['cart'][$item_id]);
            }
        }
    }
    
    $_SESSION['message'] = "Cart updated successfully";
    $_SESSION['message_type'] = "success";
    header("Location: cart.php");
    exit;
}

// Handle empty cart
if (isset($_GET['empty'])) {
    unset($_SESSION['cart']);
    $_SESSION['message'] = "Cart emptied successfully";
    $_SESSION['message_type'] = "success";
    header("Location: cart.php");
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Your Shopping Cart</h1>
    
    <?php displayMessage(); ?>
    
    <?php if (empty($cart_items)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Your Cart is Empty</h2>
            <p class="text-gray-600 mb-6">Looks like you haven't added any items to your cart yet.</p>
            <a href="menu.php" class="bg-primary text-white px-6 py-3 rounded-md hover:bg-opacity-90 transition">
                Browse Our Menu
            </a>
        </div>
    <?php else: ?>
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Cart Items Section -->
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <form action="" method="post">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Item
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Price
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Quantity
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Subtotal
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($cart_items as $item_id => $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-16 w-16">
                                                    <img class="h-16 w-16 rounded-md object-cover" 
                                                         src="<?php echo !empty($item['image']) ? '../' . $item['image'] : '../assets/images/default-food.jpg'; ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo formatPrice($item['price']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="number" name="quantity[<?php echo $item_id; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="20" 
                                                   class="w-16 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?remove=<?php echo $item_id; ?>" class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="px-6 py-4 bg-gray-50 flex justify-between items-center">
                            <a href="menu.php" class="inline-flex items-center text-primary hover:text-primary-dark">
                                <i class="fas fa-arrow-left mr-2"></i> Continue Shopping
                            </a>
                            <div class="flex space-x-2">
                                <a href="?empty=1" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition"
                                   onclick="return confirm('Are you sure you want to empty your cart?')">
                                    <i class="fas fa-trash mr-2"></i> Empty Cart
                                </a>
                                <button type="submit" name="update_cart" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                                    <i class="fas fa-sync-alt mr-2"></i> Update Cart
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary Section -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                    
                    <div class="border-t border-b py-4 mb-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <?php if (isset($tax_rate) && $tax_rate > 0): 
                            $tax_amount = $cart_total * ($tax_rate / 100);
                            $total_with_tax = $cart_total + $tax_amount;
                        ?>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-600">Tax (<?php echo $tax_rate; ?>%)</span>
                                <span class="font-medium"><?php echo formatPrice($tax_amount); ?></span>
                            </div>
                            <div class="flex justify-between font-bold text-lg pt-2">
                                <span>Total</span>
                                <span><?php echo formatPrice($total_with_tax); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flex justify-between font-bold text-lg pt-2">
                                <span>Total</span>
                                <span><?php echo formatPrice($cart_total); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if (isLoggedIn()): ?>
                            <a href="checkout.php" class="block w-full bg-secondary text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                                Proceed to Checkout
                            </a>
                        <?php else: ?>
                            <p class="text-gray-600 text-sm mb-2">Please log in to complete your order</p>
                            <a href="login.php?redirect=cart.php" class="block w-full bg-primary text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                                Log In to Continue
                            </a>
                            <a href="register.php?redirect=cart.php" class="block w-full bg-gray-200 text-gray-700 text-center px-4 py-3 rounded-md font-semibold hover:bg-gray-300 transition">
                                Create Account
                            </a>
                        <?php endif; ?>
                        
                        <a href="order.php" class="block w-full bg-white border border-primary text-primary text-center px-4 py-3 rounded-md font-semibold hover:bg-primary hover:text-white transition">
                            Go to Online Ordering
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?> 