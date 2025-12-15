<?php
// includes/functions.php

require_once 'config.php';

/**
 * Get featured products for homepage
 */
/**
 * Get featured products for homepage - DEBUG VERSION
 */
function getFeaturedProducts($limit = 6) {
    echo "<!-- DEBUG: Function called with limit: $limit -->";
    
    try {
        $pdo = getDBConnection();
        echo "<!-- DEBUG: Database connected -->";
        
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            LEFT JOIN brands b ON p.brand_id = b.brand_id
            WHERE p.status = 'active' 
              AND p.featured = 1
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        
        echo "<!-- DEBUG: SQL: $sql -->";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        $products = $stmt->fetchAll();
        echo "<!-- DEBUG: Found " . count($products) . " featured products -->";
        
        return $products;
        
    } catch (Exception $e) {
        echo "<!-- DEBUG: ERROR: " . $e->getMessage() . " -->";
        error_log("Error in getFeaturedProducts: " . $e->getMessage());
        return [];
    }
}


/**
 * Get recent products
 */
function getRecentProducts($limit = 8) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            WHERE p.status = 'active'
            ORDER BY p.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting recent products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get product by ID
 */
function getProductById($product_id) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            WHERE p.product_id = ?
        ");
        $stmt->execute([$product_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting product: " . $e->getMessage());
        return null;
    }
}

/**
 * User authentication functions
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // RESTORE CART FROM DATABASE
            loadCartFromDatabase($user['user_id']);
            
            // Update last login (optional)
            $updateStmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Register new user
 */
function registerUser($email, $password, $full_name, $phone = null) {
    $pdo = getDBConnection();
    
    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return "Email already exists";
        }
        
        // Insert new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, phone, role, status) 
            VALUES (?, ?, ?, ?, 'customer', 'active')
        ");
        $stmt->execute([$email, $hashed_password, $full_name, $phone]);
        
        // Also insert into customers table
        $user_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO customers (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return "Registration failed: " . $e->getMessage();
    }
}

/**
 * Cart functions
 */
function addToCart($product_id, $quantity = 1) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
}

function removeFromCart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

function getCartItems() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }

    $pdo = getDBConnection();
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        // Check if product exists and is active
        $product = getProductById($product_id);
        
        if ($product && $product['status'] == 'active') {
            $total_price = $product['price'] * $quantity;
            
            $cart_items[] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'total_price' => $total_price,
                'stock_quantity' => $product['stock_quantity'],
                'sku' => $product['sku'],
                'brand_name' => $product['brand_name'] ?? ''
            ];
        } else {
            // Remove deleted product from cart
            unset($_SESSION['cart'][$product_id]);
        }
    }

    return $cart_items;
}

function getCartTotal() {
    $cart_items = getCartItems();
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['total_price'];
    }
    return $total;
}

/**
 * Cart Database Persistence Functions
 */
function saveCartToDatabase($user_id, $cart_data) {
    $pdo = getDBConnection();
    
    try {
        $cart_json = json_encode($cart_data);
        $stmt = $pdo->prepare("UPDATE users SET cart_data = ? WHERE user_id = ?");
        $stmt->execute([$cart_json, $user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Save cart error: " . $e->getMessage());
        return false;
    }
}

function loadCartFromDatabase($user_id) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT cart_data FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if ($result && !empty($result['cart_data'])) {
            $cart_data = json_decode($result['cart_data'], true);
            if (is_array($cart_data)) {
                $_SESSION['cart'] = $cart_data;
                return true;
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Load cart error: " . $e->getMessage());
        return false;
    }
}

/**
 * Utility functions
 */
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

function displayError($message) {
    return '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
}

function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

/**
 * Get settings
 */
function getSetting($key) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['value'] : null;
    } catch (PDOException $e) {
        error_log("Error getting setting: " . $e->getMessage());
        return null;
    }
}