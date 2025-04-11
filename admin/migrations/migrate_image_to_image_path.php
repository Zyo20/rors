<?php
session_start();
require_once '../../includes/functions.php';
require_once '../../config/database.php';

// Require admin role
if (!hasRole('admin')) {
    requireRole('admin');
}

// Connect to database
$conn = getConnection();
$migrated = 0;
$errors = [];

// Check if the 'image_path' column exists
$checkColumnSql = "SHOW COLUMNS FROM menu_items LIKE 'image_path'";
$checkResult = $conn->query($checkColumnSql);
$columnExists = $checkResult && $checkResult->num_rows > 0;

if (!$columnExists) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Add the 'image_path' column
        $alterSql = "ALTER TABLE menu_items ADD COLUMN image_path VARCHAR(255) AFTER image";
        if (!$conn->query($alterSql)) {
            throw new Exception("Error adding image_path column: " . $conn->error);
        }
        
        // Copy data from 'image' to 'image_path'
        $updateSql = "UPDATE menu_items SET image_path = image WHERE image IS NOT NULL AND image != ''";
        if ($conn->query($updateSql)) {
            $migrated = $conn->affected_rows;
        } else {
            throw new Exception("Error copying data: " . $conn->error);
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Successfully added image_path column and migrated $migrated items.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['message'] = "Migration failed: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "The image_path column already exists. No migration needed.";
    $_SESSION['message_type'] = "info";
}

// Close connection
$conn->close();

// Redirect back to menu page
header("Location: ../menu.php");
exit;
?> 