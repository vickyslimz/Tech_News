<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
}

// Redirect to home if already logged in
function redirectIfAuthenticated() {
    if (isset($_SESSION['user_id'])) {
        header("Location: /");
        exit;
    }
}

// Check if user has admin role
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>