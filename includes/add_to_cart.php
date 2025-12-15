<?php
// includes/add_to_cart.php

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
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Check if product exists and is available
    $stmt = $pdo->prepare("
        SELECT product_id, name, price, stock_quantity, status 
        FROM products 
        WHERE product_id = ? AND status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        exit;
    }

    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.'
        ]);
        exit;
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add item to cart or update quantity
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    // Save cart to database if user is logged in
    if (isset($_SESSION['user_id'])) {
        saveCartToDatabase($_SESSION['user_id'], $_SESSION['cart']);
    }

    // Calculate total cart count
    $cart_count = array_sum($_SESSION['cart']);

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Product added to cart successfully!',
        'cart_count' => $cart_count,
        'product_name' => $product['name'],
        'product_price' => $product['price'],
        'quantity' => $_SESSION['cart'][$product_id]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Add to cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>