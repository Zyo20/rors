<?php
include '../includes/header.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'pages/order.php';
    $_SESSION['message'] = "Please login to place an order";
    $_SESSION['message_type'] = "error";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$categories = [];
$cart_items = [];
$cart_total = 0;

// Get menu categories
$conn = getConnection();
$sql = "SELECT * FROM menu_categories";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

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
        header("Location: order.php");
        exit;
    }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($item_id > 0 && $quantity > 0) {
        // Get item details
        $stmt = $conn->prepare("SELECT id, name, price, image FROM menu_items WHERE id = ? AND is_available = 1");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $item = $result->fetch_assoc();
            
            // Check if item already in cart
            if (isset($_SESSION['cart'][$item_id])) {
                $_SESSION['cart'][$item_id]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$item_id] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'image' => $item['image'],
                    'quantity' => $quantity
                ];
            }
            
            $_SESSION['message'] = "Item added to cart";
            $_SESSION['message_type'] = "success";
            header("Location: order.php");
            exit;
        }
        $stmt->close();
    }
}

// Handle checkout action
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    // Create new order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, status, total_amount, created_at) VALUES (?, 'pending', ?, NOW())");
    $stmt->bind_param("id", $user_id, $cart_total);
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
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to order confirmation
        $_SESSION['message'] = "Order placed successfully";
        $_SESSION['message_type'] = "success";
        header("Location: ../dashboard/my_orders.php");
        exit;
    }
}

$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Online Ordering</h1>
    
    <?php displayMessage(); ?>
    
    <div class="flex flex-col md:flex-row gap-6">
        <!-- Menu Section -->
        <div class="md:w-2/3">
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold mb-4"><?php echo $category['name']; ?></h2>
                        
                        <?php
                        // Get menu items for this category
                        $conn = getConnection();
                        $stmt = $conn->prepare("SELECT * FROM menu_items WHERE category_id = ? AND is_available = 1 ORDER BY name");
                        $stmt->bind_param("i", $category['id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $items = [];
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $items[] = $row;
                            }
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                        
                        <?php if (count($items) > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($items as $item): ?>
                                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                        <img src="<?php echo !empty($item['image_path']) ? '../' . $item['image_path'] : '../assets/images/default-food.jpg'; ?>" 
                                             alt="<?php echo $item['name']; ?>" 
                                             class="w-full h-40 object-cover">
                                        <div class="p-4">
                                            <h3 class="text-lg font-bold mb-1"><?php echo $item['name']; ?></h3>
                                            <p class="text-gray-600 text-sm mb-2"><?php echo substr($item['description'], 0, 80); ?>...</p>
                                            <p class="text-primary font-bold mb-3"><?php echo formatPrice($item['price']); ?></p>
                                            
                                            <form action="" method="post" class="flex items-center">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" name="quantity" value="1" min="1" max="10" 
                                                       class="w-16 rounded-md border-gray-300 mr-2">
                                                <button type="submit" name="add_to_cart" 
                                                        class="bg-primary text-white px-3 py-1 rounded-md text-sm hover:bg-opacity-90 transition">
                                                    Add to Cart
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">No items available in this category.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-gray-100 p-6 rounded-lg text-center">
                    <p class="text-gray-600">No menu categories available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Cart Section -->
        <div class="md:w-1/3">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Your Cart</h2>
                    <a href="cart.php" class="text-primary hover:text-primary-dark">
                        <i class="fas fa-external-link-alt mr-1"></i> View Cart
                    </a>
                </div>
                
                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-6">
                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Your cart is empty</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4 mb-6">
                        <?php 
                        $count = 0;
                        foreach ($cart_items as $item): 
                            $count++;
                            // Show only first 3 items in the mini cart
                            if ($count <= 3):
                        ?>
                            <div class="flex items-center justify-between border-b pb-3">
                                <div class="flex items-center">
                                    <img src="<?php echo !empty($item['image_path']) ? '../' . $item['image_path'] : '../assets/images/default-food.jpg'; ?>" 
                                         alt="<?php echo $item['name']; ?>" 
                                         class="w-12 h-12 object-cover rounded-md mr-3">
                                    <div>
                                        <h4 class="font-semibold"><?php echo $item['name']; ?></h4>
                                        <div class="flex items-center text-sm">
                                            <span><?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-bold mr-3"><?php echo formatPrice($item['price'] * $item['quantity']); ?></span>
                                    <a href="?remove=<?php echo $item['id']; ?>" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        
                        // If there are more items, show a message
                        if (count($cart_items) > 3):
                        ?>
                            <div class="text-center text-sm text-gray-500">
                                <a href="cart.php" class="text-primary hover:underline">
                                    View all <?php echo count($cart_items); ?> items in your cart
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="border-t pt-4">
                        <div class="flex justify-between text-lg font-bold mb-4">
                            <span>Total:</span>
                            <span><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div class="space-y-2">
                            <a href="cart.php" class="block w-full bg-primary text-white py-2 rounded-md font-semibold text-center hover:bg-opacity-90 transition">
                                View Cart
                            </a>
                            
                            <a href="checkout.php" class="block w-full bg-secondary text-white py-2 rounded-md font-semibold text-center hover:bg-opacity-90 transition">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 