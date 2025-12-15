<?php
// includes/remove_from_cart.php

session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Remove item from cart
    if (isset($_SESSION['cart'][$product_id])) {
        // Remove the product from cart
        unset($_SESSION['cart'][$product_id]);
        
        // Save cart to database if user is logged in
        if (isset($_SESSION['user_id'])) {
            saveCartToDatabase($_SESSION['user_id'], $_SESSION['cart']);
        }
        
        // Calculate NEW cart count after removal
        $cart_count = 0;
        if (!empty($_SESSION['cart'])) {
            $cart_count = array_sum($_SESSION['cart']);
        }
        
        // Log for debugging
        error_log("Removed product $product_id from cart. New count: $cart_count");
        
        echo json_encode([
            'success' => true,
            'message' => 'Product removed from cart successfully!',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found in cart']);
    }

} catch (Exception $e) {
    error_log("Remove from cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>