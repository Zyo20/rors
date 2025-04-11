<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Initialize error message
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $dietary_preferences = isset($_POST['dietary_preferences']) ? sanitize($_POST['dietary_preferences']) : '';
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!validatePhone($phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Connect to the database
        $conn = getConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already exists. Please choose a different email or login.';
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, dietary_preferences, password, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            $stmt->bind_param("sssss", $name, $phone, $email, $dietary_preferences, $hashed_password);
            
            if ($stmt->execute()) {
                // Registration successful, create session
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'customer';
                
                // Redirect to homepage
                header('Location: ../index.php');
                exit;
            } else {
                $error = 'Registration failed. Please try again later.';
            }
        }
        
        // Close connection
        $stmt->close();
        $conn->close();
    }
    
    // If we get here, there was an error, redirect back to register page with error
    $_SESSION['register_error'] = $error;
    header('Location: ../pages/register.php');
    exit;
}
?> 