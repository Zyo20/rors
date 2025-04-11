<?php
include '../includes/header.php';
require_once '../includes/functions.php';

// Check if item ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to menu page if no ID is provided
    header('Location: menu.php');
    exit;
}

$item_id = (int) $_GET['id'];
$item = null;
$category = null;

// Get item details
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ? AND is_available = 1");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $item = $result->fetch_assoc();
    
    // Get category details
    $cat_stmt = $conn->prepare("SELECT * FROM menu_categories WHERE id = ?");
    $cat_stmt->bind_param("i", $item['category_id']);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    
    if ($cat_result->num_rows === 1) {
        $category = $cat_result->fetch_assoc();
    }
    
    $cat_stmt->close();
}

$stmt->close();

// Get related items from the same category
$related_items = [];
if ($item && isset($item['category_id'])) {
    $rel_stmt = $conn->prepare("SELECT * FROM menu_items WHERE category_id = ? AND id != ? AND is_available = 1 ORDER BY RAND() LIMIT 3");
    $rel_stmt->bind_param("ii", $item['category_id'], $item_id);
    $rel_stmt->execute();
    $rel_result = $rel_stmt->get_result();
    
    if ($rel_result->num_rows > 0) {
        while ($row = $rel_result->fetch_assoc()) {
            $related_items[] = $row;
        }
    }
    
    $rel_stmt->close();
}

$conn->close();

// Check if item was found
if (!$item) {
    // Redirect to menu page if item not found
    header('Location: menu.php');
    exit;
}

// Handle add to cart action
if (isset($_POST['add_to_cart'])) {
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    $special_instructions = isset($_POST['special_instructions']) ? sanitize($_POST['special_instructions']) : '';
    
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Initialize cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if item is already in cart
    $item_key = null;
    foreach ($_SESSION['cart'] as $key => $cart_item) {
        if ($cart_item['item_id'] == $item_id) {
            $item_key = $key;
            break;
        }
    }
    
    if ($item_key !== null) {
        // Update existing item
        $_SESSION['cart'][$item_key]['quantity'] += $quantity;
        $_SESSION['cart'][$item_key]['special_instructions'] = $special_instructions;
    } else {
        // Add new item to cart
        $_SESSION['cart'][] = [
            'item_id' => $item_id,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'special_instructions' => $special_instructions
        ];
    }
    
    // Redirect to cart page
    header('Location: cart.php');
    exit;
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-4">
        <a href="menu.php" class="text-primary hover:underline flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Menu
        </a>
    </div>
    
    <?php if ($item): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <div class="md:w-1/2">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-64 md:h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 md:h-full bg-gray-200 flex items-center justify-center">
                            <i class="fas fa-utensils text-6xl text-gray-400"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="md:w-1/2 p-6 md:p-8">
                    <div class="mb-2">
                        <?php if ($category): ?>
                            <span class="bg-secondary text-white text-sm px-3 py-1 rounded-full">
                                <?php echo $category['name']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-3xl font-bold mb-2"><?php echo $item['name']; ?></h1>
                    <div class="text-2xl text-primary font-bold mb-4"><?php echo formatPrice($item['price']); ?></div>
                    
                    <div class="text-gray-700 mb-6">
                        <p><?php echo $item['description']; ?></p>
                    </div>
                    
                    <?php if (!empty($item['allergens'])): ?>
                        <div class="mb-6">
                            <h3 class="font-semibold mb-2">Allergens:</h3>
                            <p class="text-gray-600"><?php echo $item['allergens']; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="menu_item.php?id=<?php echo $item_id; ?>" method="post" class="mb-6">
                        <div class="mb-4">
                            <label for="quantity" class="block text-gray-700 font-medium mb-2">Quantity</label>
                            <div class="flex">
                                <button type="button" id="decrease-quantity" class="bg-gray-200 px-3 py-2 rounded-l-md">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" class="w-16 text-center border-t border-b border-gray-300 py-2">
                                <button type="button" id="increase-quantity" class="bg-gray-200 px-3 py-2 rounded-r-md">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="special_instructions" class="block text-gray-700 font-medium mb-2">Special Instructions (Optional)</label>
                            <textarea id="special_instructions" name="special_instructions" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" placeholder="E.g., No onions, extra spicy, etc."></textarea>
                        </div>
                        
                        <button type="submit" name="add_to_cart" class="w-full bg-primary text-white py-3 px-4 rounded-lg hover:bg-opacity-90 transition flex items-center justify-center">
                            <i class="fas fa-shopping-cart mr-2"></i> Add to Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Related Items -->
        <?php if (count($related_items) > 0): ?>
            <div class="mt-12">
                <h2 class="text-2xl font-bold mb-6">You Might Also Like</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php foreach ($related_items as $related): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                            <?php if (!empty($related['image'])): ?>
                                <img src="<?php echo $related['image']; ?>" alt="<?php echo $related['name']; ?>" class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="text-xl font-bold"><?php echo $related['name']; ?></h3>
                                    <span class="text-primary font-bold"><?php echo formatPrice($related['price']); ?></span>
                                </div>
                                
                                <p class="text-gray-600 mt-2 mb-4"><?php echo $related['description']; ?></p>
                                
                                <div class="flex justify-between items-center">
                                    <a href="menu_item.php?id=<?php echo $related['id']; ?>" class="text-primary hover:underline">View Details</a>
                                    
                                    <button onclick="window.location.href='order.php?add=<?php echo $related['id']; ?>'" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                                        Add to Order
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    // Quantity controls
    document.getElementById('decrease-quantity').addEventListener('click', function() {
        var quantityInput = document.getElementById('quantity');
        var currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
        }
    });
    
    document.getElementById('increase-quantity').addEventListener('click', function() {
        var quantityInput = document.getElementById('quantity');
        quantityInput.value = parseInt(quantityInput.value) + 1;
    });
</script>

<?php include '../includes/footer.php'; ?> 