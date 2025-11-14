<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/auth.php';

// Security check
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /php-blog/login.php");
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: manage.php");
    exit;
}

// Toggle ad status
try {
    $stmt = $pdo->prepare("
        UPDATE advertisements 
        SET is_active = NOT is_active 
        WHERE id = :id
    ");
    $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    // Optional: Log this action
    // logActivity($_SESSION['user_id'], "Toggled ad status for ID: {$_POST['id']}");
    
} catch (PDOException $e) {
    // Log error and redirect
    error_log("Ad toggle failed: " . $e->getMessage());
}

// Redirect back to management page
header("Location: manage.php");
exit;
?>