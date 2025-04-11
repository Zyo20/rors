<?php
// Sample data installation script for RORS
require_once __DIR__ . '/../config/database.php';

echo "<h2>Installing Sample User Accounts</h2>";

// Connect to database
$conn = getConnection();

// Create sample users with different roles
$sampleUsers = [
    [
        'name' => 'Admin User',
        'email' => 'admin@rors.com',
        'phone' => '1234567890',
        'password' => 'admin123',
        'role' => 'admin',
        'dietary_preferences' => ''
    ],
    [
        'name' => 'Kitchen Staff',
        'email' => 'kitchen@rors.com',
        'phone' => '1234567891',
        'password' => 'kitchen123',
        'role' => 'kitchen',
        'dietary_preferences' => ''
    ],
    [
        'name' => 'Manager User',
        'email' => 'manager@rors.com',
        'phone' => '1234567892',
        'password' => 'manager123',
        'role' => 'manager',
        'dietary_preferences' => ''
    ],
    [
        'name' => 'Customer User',
        'email' => 'customer@rors.com',
        'phone' => '1234567893',
        'password' => 'customer123',
        'role' => 'customer',
        'dietary_preferences' => 'No spicy food, vegetarian options preferred'
    ]
];

// Check if users already exist and insert if not
echo "<ul>";
foreach ($sampleUsers as $user) {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<li>User <strong>{$user['email']}</strong> already exists (Role: {$user['role']})</li>";
    } else {
        // Insert new user
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $insert_stmt = $conn->prepare("INSERT INTO users (name, phone, email, dietary_preferences, password, role) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssss", $user['name'], $user['phone'], $user['email'], $user['dietary_preferences'], $hashed_password, $user['role']);
        
        if ($insert_stmt->execute()) {
            echo "<li>Created user <strong>{$user['email']}</strong> with password <strong>{$user['password']}</strong> (Role: {$user['role']})</li>";
        } else {
            echo "<li>Failed to create user {$user['email']}: " . $conn->error . "</li>";
        }
        $insert_stmt->close();
    }
    $stmt->close();
}
echo "</ul>";

// Add some sample menu categories
$categories = [
    ['name' => 'Appetizers', 'description' => 'Start your meal with our delicious appetizers'],
    ['name' => 'Main Courses', 'description' => 'Our signature main dishes'],
    ['name' => 'Desserts', 'description' => 'Sweet treats to finish your meal'],
    ['name' => 'Beverages', 'description' => 'Refreshing drinks']
];

// Check if categories exist and add them if not
echo "<h2>Adding Sample Menu Categories</h2>";
echo "<ul>";
foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT id FROM menu_categories WHERE name = ?");
    $stmt->bind_param("s", $category['name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<li>Category <strong>{$category['name']}</strong> already exists</li>";
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO menu_categories (name, description) VALUES (?, ?)");
        $insert_stmt->bind_param("ss", $category['name'], $category['description']);
        
        if ($insert_stmt->execute()) {
            echo "<li>Added category <strong>{$category['name']}</strong></li>";
        } else {
            echo "<li>Failed to add category {$category['name']}: " . $conn->error . "</li>";
        }
        $insert_stmt->close();
    }
    $stmt->close();
}
echo "</ul>";

echo "<h2>Sample Login Credentials</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Role</th><th>Email</th><th>Password</th></tr>";
foreach ($sampleUsers as $user) {
    echo "<tr>";
    echo "<td>{$user['role']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['password']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p>You can now log in with these credentials to test different parts of the system.</p>";
echo "<p><a href='../index.php'>Return to homepage</a></p>";

// Close connection
$conn->close();
?> 