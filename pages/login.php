<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get login error if any
$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Get relative path to root
$root_path = '..';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Restaurant Ordering and Reservation System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#F59E0B',
                        accent: '#10B981',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="../index.php" class="text-2xl font-bold text-primary">RORS</a>
                <div>
                    <a href="register.php" class="text-gray-700 hover:text-primary">Register</a>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="flex-grow flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full">
            <h1 class="text-3xl font-bold text-center mb-6">Login</h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="../includes/login_process.php" method="post">
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-dark transition">
                        Login
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-gray-600">
                        Don't have an account? 
                        <a href="register.php" class="text-primary hover:underline">Register here</a>
                    </p>
                </div>
            </form>
        </div>
    </main>
    
    <footer class="bg-dark text-white py-4">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> Restaurant Ordering and Reservation System. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 