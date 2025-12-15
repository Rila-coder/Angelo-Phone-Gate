<?php
// includes/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'angelo_phone_gate_db');
define('DB_USER', 'root'); // Change if needed
define('DB_PASS', ''); // Change if needed

// Site configuration
define('SITE_NAME', 'Angelo Phone Gate');
define('SITE_URL', 'http://localhost/angelo_phone_gate/');
define('UPLOAD_PATH', 'uploads/');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Check if database exists and create necessary tables
function initializeDatabase() {
    try {
        $pdo = getDBConnection();
        
        // Check if users table exists (basic check)
        $stmt = $pdo->query("SELECT 1 FROM users LIMIT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Auto-initialize on include
initializeDatabase();
?>