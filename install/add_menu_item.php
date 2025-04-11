<?php
// Simple script to add a single menu item
require_once __DIR__ . '/../config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Adding a Test Menu Item</h2>";

// Connect to database
$conn = getConnection();

// Insert sample items directly with SQL
$items = [
    // Add a few items to each category
    // 1 = Appetizers
    // 2 = Main Courses
    // 3 = Desserts
    // 4 = Beverages
    "INSERT INTO menu_items (category_id, name, description, price) VALUES 
    (1, 'Mozzarella Sticks', 'Deep-fried mozzarella cheese sticks', 8.99),
    (1, 'Bruschetta', 'Toasted bread with tomatoes and herbs', 7.99),
    (2, 'Grilled Salmon', 'Fresh salmon fillet grilled to perfection', 22.99),
    (2, 'Fettuccine Alfredo', 'Pasta with creamy Parmesan sauce', 16.99),
    (3, 'Tiramisu', 'Classic Italian dessert with coffee and mascarpone', 8.99),
    (3, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center', 9.99),
    (4, 'Fresh Lemonade', 'Refreshing homemade lemonade', 3.99),
    (4, 'Espresso', 'Rich Italian espresso', 2.99)"
];

foreach ($items as $sql) {
    try {
        if ($conn->query($sql)) {
            echo "<p>Successfully added menu items!</p>";
        } else {
            echo "<p>Error adding menu items: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>Exception: " . $e->getMessage() . "</p>";
    }
}

echo "<p><a href='../index.php'>Return to homepage</a></p>";
echo "<p><a href='../pages/menu.php'>View Menu</a></p>";

// Close connection
$conn->close();
?> 