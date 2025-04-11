<?php
// Sample menu items installation script for RORS
require_once __DIR__ . '/../config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Installing Sample Menu Items</h2>";

// Connect to database
$conn = getConnection();

// Get all categories
$categoryResult = $conn->query("SELECT id, name FROM menu_categories ORDER BY name");
$categories = [];

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[$row['id']] = $row['name'];
    }
}

if (empty($categories)) {
    echo "<p>No categories found. Please run sample_data.php first to create categories.</p>";
    echo "<p><a href='sample_data.php'>Run Sample Data Script</a></p>";
    $conn->close();
    exit;
}

echo "<pre>Categories found: " . print_r($categories, true) . "</pre>";

// Create a simplified menu with fewer items for testing
$menuItems = [
    // Appetizers
    'Appetizers' => [
        [
            'name' => 'Bruschetta',
            'description' => 'Grilled bread rubbed with garlic and topped with olive oil, salt, tomato, and herbs.',
            'price' => 7.99,
            'allergens' => 'Gluten, Dairy',
            'image_path' => '../assets/images/menu/bruschetta.jpg'
        ],
        [
            'name' => 'Mozzarella Sticks',
            'description' => 'Breaded and deep-fried mozzarella cheese, served with marinara sauce.',
            'price' => 8.99,
            'allergens' => 'Dairy, Gluten',
            'image_path' => '../assets/images/menu/mozzarella_sticks.jpg'
        ]
    ],
    
    // Main Courses
    'Main Courses' => [
        [
            'name' => 'Grilled Salmon',
            'description' => 'Fresh Atlantic salmon fillet, grilled to perfection.',
            'price' => 22.99,
            'allergens' => 'Fish',
            'image_path' => '../assets/images/menu/grilled_salmon.jpg'
        ],
        [
            'name' => 'Fettuccine Alfredo',
            'description' => 'Fettuccine pasta tossed in a creamy Alfredo sauce with Parmesan cheese.',
            'price' => 16.99,
            'allergens' => 'Gluten, Dairy',
            'image_path' => '../assets/images/menu/fettuccine_alfredo.jpg'
        ]
    ]
];

// Insert menu items
echo "<ul>";
foreach ($categories as $cat_id => $cat_name) {
    echo "<li>Processing category: {$cat_name} (ID: {$cat_id})</li>";
    
    if (isset($menuItems[$cat_name])) {
        echo "<li><strong>Adding items to {$cat_name} category:</strong><ul>";
        
        foreach ($menuItems[$cat_name] as $item) {
            // Check if item already exists
            $stmt = $conn->prepare("SELECT id FROM menu_items WHERE name = ? AND category_id = ?");
            $stmt->bind_param("si", $item['name'], $cat_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo "<li>{$item['name']} already exists</li>";
            } else {
                // Insert new item
                $insert_stmt = $conn->prepare("INSERT INTO menu_items (category_id, name, description, price, image_path, allergens) VALUES (?, ?, ?, ?, ?, ?)");
                $insert_stmt->bind_param("issdss", $cat_id, $item['name'], $item['description'], $item['price'], $item['image_path'], $item['allergens']);
                
                if ($insert_stmt->execute()) {
                    echo "<li>Added {$item['name']} (${$item['price']})</li>";
                } else {
                    echo "<li>Failed to add {$item['name']}: " . $conn->error . "</li>";
                }
                $insert_stmt->close();
            }
            $stmt->close();
        }
        
        echo "</ul></li>";
    } else {
        echo "<li>No items defined for category: {$cat_name}</li>";
    }
}
echo "</ul>";

// Create directory for menu images if it doesn't exist
$image_dir = __DIR__ . '/../assets/images/menu';
if (!is_dir($image_dir)) {
    mkdir($image_dir, 0755, true);
    echo "<p>Created directory for menu images.</p>";
}

echo "<p>Sample menu items have been added to the database. Note that the image paths have been set, but you'll need to add actual images.</p>";
echo "<p><a href='../index.php'>Return to homepage</a></p>";
echo "<p><a href='../pages/menu.php'>View Menu</a></p>";

// Close connection
$conn->close();
?> 