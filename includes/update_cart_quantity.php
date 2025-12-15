<?php
// includes/update_cart_quantity.php

session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$product_id || !$quantity) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
    exit;
}

try {
    // Check stock availability
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    if ($quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available']);
        exit;
    }
    
    // Update cart quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] = $quantity;
        
        // Save cart to database if user is logged in
        if (isset($_SESSION['user_id'])) {
            saveCartToDatabase($_SESSION['user_id'], $_SESSION['cart']);
        }
        
        $cart_count = array_sum($_SESSION['cart']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not in cart']);
    }

} catch (Exception $e) {
    error_log("Update cart quantity error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>