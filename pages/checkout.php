<?php
include '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'pages/checkout.php';
    $_SESSION['message'] = "Please login to complete checkout";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['message'] = "Your cart is empty";
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = $_SESSION['cart'];
$cart_total = 0;
$tax_rate = 8; // 8% tax - could be stored in config or database
$delivery_fee = 5.00; // Default delivery fee - could be dynamic

// Get user information
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Calculate cart total
foreach ($cart_items as $item) {
    $cart_total += $item['price'] * $item['quantity'];
}

// Calculate tax and final total
$tax_amount = $cart_total * ($tax_rate / 100);
$order_total = $cart_total + $tax_amount;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_type = sanitize($_POST['delivery_type'] ?? 'pickup');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $address = sanitize($_POST['address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Apply delivery fee if delivery is selected
    if ($delivery_type === 'delivery') {
        $order_total += $delivery_fee;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Create new order
        $stmt = $conn->prepare("INSERT INTO orders (customer_id, status, total_amount, tax_amount, delivery_fee, delivery_type, payment_method, address, notes, created_at) 
                               VALUES (?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $delivery_fee_value = ($delivery_type === 'delivery') ? $delivery_fee : 0;
        
        $stmt->bind_param("idddssss", 
                         $user_id, 
                         $order_total, 
                         $tax_amount, 
                         $delivery_fee_value,
                         $delivery_type,
                         $payment_method,
                         $address,
                         $notes);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        if ($order_id) {
            // Add order items
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            foreach ($_SESSION['cart'] as $item) {
                $item_id = $item['id'];
                $quantity = $item['quantity'];
                $price = $item['price'];
                
                $stmt->bind_param("iiid", $order_id, $item_id, $quantity, $price);
                $stmt->execute();
            }
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Set success message
            $_SESSION['message'] = "Order placed successfully! Your order number is #" . $order_id;
            $_SESSION['message_type'] = "success";
            
            // Redirect to confirmation page
            header("Location: ../dashboard/order_details.php?id=" . $order_id);
            exit;
        }
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Error placing order: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Checkout</h1>
    
    <?php displayMessage(); ?>
    
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Order Form -->
        <div class="lg:w-2/3">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Delivery Options</h2>
                
                <form action="" method="post">
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Delivery Type</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="delivery_type" value="pickup" class="form-radio" checked>
                                <span class="ml-2">Pickup</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="delivery_type" value="delivery" class="form-radio">
                                <span class="ml-2">Delivery (+$<?php echo number_format($delivery_fee, 2); ?>)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6 delivery-address hidden">
                        <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Delivery Address</label>
                        <textarea id="address" name="address" rows="3" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                placeholder="Enter your complete delivery address"></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Payment Method</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="cash" class="form-radio" checked>
                                <span class="ml-2">Cash on Delivery/Pickup</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="payment_method" value="card" class="form-radio">
                                <span class="ml-2">Credit Card</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6 card-details hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="card_number" class="block text-gray-700 text-sm font-bold mb-2">Card Number</label>
                                <input type="text" id="card_number" name="card_number" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                       placeholder="1234 5678 9012 3456">
                            </div>
                            <div>
                                <label for="card_name" class="block text-gray-700 text-sm font-bold mb-2">Name on Card</label>
                                <input type="text" id="card_name" name="card_name" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                       placeholder="John Doe">
                            </div>
                            <div>
                                <label for="expiry_date" class="block text-gray-700 text-sm font-bold mb-2">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                       placeholder="MM/YY">
                            </div>
                            <div>
                                <label for="cvv" class="block text-gray-700 text-sm font-bold mb-2">CVV</label>
                                <input type="text" id="cvv" name="cvv" 
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                       placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" 
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                placeholder="Special instructions for your order"></textarea>
                    </div>
                    
                    <div class="border-t pt-4 mt-6">
                        <button type="submit" class="bg-secondary text-white px-6 py-3 rounded-md font-semibold hover:bg-opacity-90 transition">
                            Place Order
                        </button>
                        <a href="cart.php" class="ml-4 text-primary hover:text-primary-dark">
                            Return to Cart
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="lg:w-1/3">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                
                <div class="divide-y">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="py-3 flex justify-between">
                            <div>
                                <span class="font-medium"><?php echo $item['quantity']; ?> x </span>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </div>
                            <span class="font-medium"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-t border-b py-4 my-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium"><?php echo formatPrice($cart_total); ?></span>
                    </div>
                    
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Tax (<?php echo $tax_rate; ?>%)</span>
                        <span class="font-medium"><?php echo formatPrice($tax_amount); ?></span>
                    </div>
                    
                    <div class="flex justify-between mb-2 delivery-fee hidden">
                        <span class="text-gray-600">Delivery Fee</span>
                        <span class="font-medium"><?php echo formatPrice($delivery_fee); ?></span>
                    </div>
                    
                    <div class="flex justify-between font-bold text-lg pt-2">
                        <span>Total</span>
                        <span id="order-total"><?php echo formatPrice($order_total); ?></span>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-2"></i>
                        <p>Your order will be prepared as soon as you place it. For delivery orders, please allow 30-45 minutes for delivery.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delivery type change
    const deliveryRadios = document.querySelectorAll('input[name="delivery_type"]');
    const deliveryAddress = document.querySelector('.delivery-address');
    const deliveryFee = document.querySelector('.delivery-fee');
    const orderTotal = document.getElementById('order-total');
    const baseTotal = <?php echo $order_total; ?>;
    const deliveryFeeAmount = <?php echo $delivery_fee; ?>;
    
    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'delivery') {
                deliveryAddress.classList.remove('hidden');
                deliveryFee.classList.remove('hidden');
                orderTotal.textContent = '$' + (baseTotal + deliveryFeeAmount).toFixed(2);
            } else {
                deliveryAddress.classList.add('hidden');
                deliveryFee.classList.add('hidden');
                orderTotal.textContent = '$' + baseTotal.toFixed(2);
            }
        });
    });
    
    // Handle payment method change
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    const cardDetails = document.querySelector('.card-details');
    
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'card') {
                cardDetails.classList.remove('hidden');
            } else {
                cardDetails.classList.add('hidden');
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?> 