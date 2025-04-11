<?php
include '../includes/header.php';
require_once '../includes/functions.php';

// Get categories and menu items
$conn = getConnection();

// Get all categories
$categorySql = "SELECT * FROM menu_categories ORDER BY name ASC";
$categoryResult = $conn->query($categorySql);
$categories = [];

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get all menu items
$menuSql = "SELECT * FROM menu_items WHERE is_available = 1 ORDER BY category_id, name ASC";
$menuResult = $conn->query($menuSql);
$menuItems = [];

if ($menuResult && $menuResult->num_rows > 0) {
    while ($row = $menuResult->fetch_assoc()) {
        $menuItems[$row['category_id']][] = $row;
    }
}

$conn->close();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8 text-center">Our Menu</h1>
    
    <!-- Category Navigation -->
    <div class="mb-8">
        <div class="flex flex-wrap justify-center bg-white rounded-lg shadow-md p-4 sticky top-0">
            <?php if (count($categories) > 0): ?>
                <?php foreach ($categories as $category): ?>
                    <a href="#category-<?php echo $category['id']; ?>" class="px-4 py-2 m-1 rounded-md hover:bg-primary hover:text-white transition">
                        <?php echo $category['name']; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Menu Items by Category -->
    <?php if (count($categories) > 0): ?>
        <?php foreach ($categories as $category): ?>
            <div id="category-<?php echo $category['id']; ?>" class="mb-12">
                <h2 class="text-2xl font-bold mb-6 pb-2 border-b-2 border-primary"><?php echo $category['name']; ?></h2>
                
                <?php if (!empty($category['description'])): ?>
                    <p class="text-gray-600 mb-6"><?php echo $category['description']; ?></p>
                <?php endif; ?>
                
                <?php if (isset($menuItems[$category['id']]) && count($menuItems[$category['id']]) > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($menuItems[$category['id']] as $item): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-4">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-xl font-bold"><?php echo $item['name']; ?></h3>
                                        <span class="text-primary font-bold"><?php echo formatPrice($item['price']); ?></span>
                                    </div>
                                    
                                    <p class="text-gray-600 mt-2 mb-4"><?php echo $item['description']; ?></p>
                                    
                                    <?php if (!empty($item['allergens'])): ?>
                                        <div class="text-sm text-gray-500 mb-4">
                                            <span class="font-semibold">Allergens:</span> <?php echo $item['allergens']; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex justify-between items-center">
                                        <a href="menu_item.php?id=<?php echo $item['id']; ?>" class="text-primary hover:underline">View Details</a>
                                        
                                        <button onclick="window.location.href='order.php?add=<?php echo $item['id']; ?>'" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition">
                                            Add to Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 italic">No items available in this category.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center p-8 bg-gray-100 rounded-lg">
            <p class="text-gray-600">No menu items available at the moment. Please check back later.</p>
        </div>
    <?php endif; ?>
    
    <!-- Dietary Information -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <h2 class="text-xl font-bold mb-4">Dietary Information</h2>
        <p class="mb-4">We are committed to accommodating various dietary requirements. Please inform our staff about any allergies or dietary restrictions when placing your order.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="font-semibold mb-2">We can accommodate:</h3>
                <ul class="list-disc pl-5 space-y-1 text-gray-700">
                    <li>Vegetarian Options</li>
                    <li>Vegan Options</li>
                    <li>Gluten-Free Options</li>
                    <li>Nut-Free Options</li>
                    <li>Dairy-Free Options</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold mb-2">Allergen Information:</h3>
                <p class="text-gray-700">Our menu clearly indicates common allergens, but please ask our staff for detailed information about specific dishes.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 