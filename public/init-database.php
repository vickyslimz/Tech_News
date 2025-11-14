<?php
require_once '../includes/db_connect.php';

try {
    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        role ENUM('admin', 'author', 'reader') DEFAULT 'reader'
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        excerpt VARCHAR(500),
        slug VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        user_id INT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS post_categories (
        post_id INT,
        category_id INT,
        PRIMARY KEY (post_id, category_id),
        FOREIGN KEY (post_id) REFERENCES posts(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )");
    
    // Create admin user
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, email, password, role) 
               VALUES ('admin', 'admin@blog.com', '$password', 'admin')");
    
    echo "Database initialized successfully!";
    
} catch (PDOException $e) {
    die("Error initializing database: " . $e->getMessage());
}