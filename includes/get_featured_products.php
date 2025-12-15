<?php
// includes/get_featured_products.php - FIXED VERSION

require_once 'config.php';
require_once 'functions.php';

try {
    // Get products directly with simple query
    $pdo = getDBConnection();
    // Change this part in your SQL query:
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, b.name as brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.status = 'active' 
        AND p.featured = 1  -- Add this line to filter featured products
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();

    if (empty($products)) {
        echo '
        <div class="col-12 text-center">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No products found. 
            </div>
        </div>';
        exit;
    }

    // Display products
    foreach ($products as $product) {
        // FIX: Handle product images properly
        $product_image = 'images/default-product.jpg'; // Default image

        // Try to get image from product_images table first
        $image_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
        $image_stmt->execute([$product['product_id']]);
        $image = $image_stmt->fetch();

        if ($image && !empty($image['image_url'])) {
            $product_image = $image['image_url'];
        }
        // If no image in product_images table, try the images JSON column
        else if (!empty($product['images']) && $product['images'] != 'NULL') {
            $images_array = json_decode($product['images'], true);
            if (!empty($images_array) && !empty($images_array[0])) {
                $product_image = $images_array[0];
            }
        }

        // Determine stock status
        $stock_status = '';
        $stock_class = '';
        if ($product['stock_quantity'] == 0) {
            $stock_status = 'Out of Stock';
            $stock_class = 'text-danger';
        } elseif ($product['stock_quantity'] <= 5) {
            $stock_status = 'Low Stock';
            $stock_class = 'text-warning';
        } else {
            $stock_status = 'In Stock';
            $stock_class = 'text-success';
        }

        echo '
        <div class="col-md-4 col-lg-4 mb-4">
            <div class="card product-card h-100">
                <div class="position-relative">
                    <img src="' . htmlspecialchars($product_image) . '" 
                        class="card-img-top" 
                        alt="' . htmlspecialchars($product['name']) . '"
                        style="height: 200px; object-fit: contain; padding: 15px; background: #f8f9fa;">
                    ' . ($product['featured'] ? '<span class="position-absolute top-0 start-0 badge bg-warning m-2">Featured</span>' : '') . '
                    ' . ($product['stock_quantity'] == 0 ? '<span class="position-absolute top-0 end-0 badge bg-danger m-2">Out of Stock</span>' : '') . '
                </div>
                
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">' . htmlspecialchars($product['name']) . '</h5>
                    <p class="card-text text-muted small">
                        ' . htmlspecialchars($product['brand_name']) . ' • ' . htmlspecialchars($product['category_name']) . '
                    </p>
                    
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="h5 text-primary mb-0">Rs. ' . number_format($product['price'], 2) . '</span>
                            <small class="' . $stock_class . '"><i class="fas fa-box me-1"></i>' . $stock_status . '</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="product-details.php?id=' . $product['product_id'] . '" 
                            class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>';

                if ($product['stock_quantity'] > 0) {
                    if (isLoggedIn()) {
                        echo '
                            <button class="btn btn-warning btn-sm add-to-cart" 
                                    data-product-id="' . $product['product_id'] . '"
                                    data-product-name="' . htmlspecialchars($product['name']) . '">
                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                            </button>';
                    } else {
                        echo '
                            <a href="login.php" class="btn btn-warning btn-sm">
                                <i class="fas fa-sign-in-alt me-1"></i>Login to Buy
                            </a>';
                    }
                } else {
                    echo '
                            <button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-cart-plus me-1"></i>Out of Stock
                            </button>';
                }

                echo '
                        </div>
                    </div>
                </div>
            </div>
        </div>';
    }

} catch (Exception $e) {
    error_log("Error in get_featured_products.php: " . $e->getMessage());
    echo '
    <div class="col-12 text-center">
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Error loading products. Please try again later.
        </div>
    </div>';
}
?>