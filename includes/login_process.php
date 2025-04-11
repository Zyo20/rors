<?php
session_start();
require_once '../config/database.php';
require_once 'functions.php';

// Initialize error message
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the email and password from the form
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validate email
    if (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Connect to the database
        $conn = getConnection();
        $user = null;
        
        // First, check the users table
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Check if user exists in users table
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $table = 'users';
        } else {
            // If not found in users table, check customers table
            $stmt->close();
            
            // Check if customers table exists
            $tableExists = $conn->query("SHOW TABLES LIKE 'customers'");
            if ($tableExists && $tableExists->num_rows > 0) {
                $stmt = $conn->prepare("SELECT id, name, email, password, 'customer' as role FROM customers WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $table = 'customers';
                }
            }
        }
        
        // Verify user and password
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, create session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_table'] = $table;
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } elseif ($user['role'] === 'kitchen') {
                header('Location: ../kitchen/dashboard.php');
            } elseif ($user['role'] === 'manager') {
                header('Location: ../admin/dashboard.php');
            } else {
                // Redirect to home page for customers
                header('Location: ../index.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
        
        // Close the connection
        if (isset($stmt)) {
            $stmt->close();
        }
        $conn->close();
    }
    
    // If we get here, there was an error, redirect back to login page with error
    $_SESSION['login_error'] = $error;
    header('Location: ../pages/login.php');
    exit;
}
?> 