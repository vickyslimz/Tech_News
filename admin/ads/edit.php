<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /php-blog/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: manage.php");
    exit;
}

$pageTitle = "Edit Advertisement";

try {
    $stmt = $pdo->prepare("SELECT * FROM advertisements WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $ad = $stmt->fetch();
    
    if (!$ad) {
        header("Location: manage.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Process form submission similar to create.php

include __DIR__ . '/../../includes/header.php';
?>

<!-- Similar structure to create.php but pre-filled with $ad data -->