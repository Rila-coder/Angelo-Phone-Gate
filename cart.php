<?php
// cart.php - Shopping Cart Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Shopping Cart - Angelo Phone Gate";

// Get cart items with proper image handling
$cart_items = getCartItems();
$cart_total = getCartTotal();

// If cart is empty but session says it's not, clear the session
if (empty($cart_items) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .cart-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        .summary-card {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            border-radius: 15px;
            padding: 25px;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .product-image-container {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 5px;
        }
        .product-image {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="images/logo.jfif" alt="Angelo Phone Gate Logo" class="logo-img">
                <div class="ms-2">
                    <h4 class="mb-0 text-logo">Angelo <span class="text-phone">PHONE GATE</span></h4>
                    <small class="tagline">Trusted Genuine Forever</small>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php?category=5">Accessories</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>My Account
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="profile.php #order-history"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <?php
                            $cart_count = 0;
                            if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                                $cart_count = array_sum($_SESSION['cart']);
                            }
                            if($cart_count > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item ms-3">
                        <a class="nav-link position-relative" href="cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                                <?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Cart Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h1>
            </div>
        </div>

        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <?php if (empty($cart_items)): ?>
                
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet.</p>
                    <a href="products.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                    </a>
                </div>
                
                <?php else: ?>
                
                <!-- Cart Items List -->
                <div id="cart-items">
                    <?php foreach ($cart_items as $item): 
                        // Get product image - FIXED CODE
                        $pdo = getDBConnection();
                        $image_stmt = $pdo->prepare("
                            SELECT pi.image_url 
                            FROM product_images pi 
                            WHERE pi.product_id = ? 
                            ORDER BY pi.is_primary DESC, pi.sort_order ASC 
                            LIMIT 1
                        ");
                        $image_stmt->execute([$item['product_id']]);
                        $image_result = $image_stmt->fetch();
                        
                        $product_image = $image_result ? $image_result['image_url'] : 'images/default-product.jpg';
                    ?>
                    <div class="cart-item" id="cart-item-<?php echo $item['product_id']; ?>">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <div class="product-image-container">
                                    <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="product-image"
                                         onerror="this.src='images/default-product.jpg'">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($item['brand_name'] ?? ''); ?></p>
                                <p class="text-muted mb-0 small">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                            </div>
                            
                            <div class="col-md-2">
                                <span class="h5 text-primary">Rs. <?php echo number_format($item['price'], 2); ?></span>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="quantity-controls">
                                    <button class="quantity-btn decrease-quantity" 
                                            data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    
                                    <input type="number" 
                                           class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>"
                                           data-product-id="<?php echo $item['product_id']; ?>">
                                    
                                    <button class="quantity-btn increase-quantity" 
                                            data-product-id="<?php echo $item['product_id']; ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="col-md-1 text-end">
                                <button class="btn btn-outline-danger btn-sm remove-from-cart" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        title="Remove from cart">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="row mt-2">
                            <div class="col-12 text-end">
                                <strong>Item Total: Rs. <?php echo number_format($item['total_price'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <h4 class="mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>Rs. <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>Rs. 200.00</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (8%):</span>
                        <span>Rs. <?php echo number_format($cart_total * 0.08, 2); ?></span>
                    </div>
                    
                    <hr style="border-color: rgba(255,255,255,0.3);">
                    
                    <div class="d-flex justify-content-between mb-4">
                        <strong><h5 class="mb-0">Total:</h5></strong>
                        <strong><h5 class="mb-0">Rs. <?php echo number_format($cart_total + 200 + ($cart_total * 0.08), 2); ?></h5></strong>
                    </div>
                    
                    <?php if (!empty($cart_items)): ?>
                    <div class="d-grid gap-2">
                        <a href="checkout.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-lock me-2"></i>Proceed to Checkout
                        </a>
                        <a href="products.php" class="btn btn-outline-light">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Security Features -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-shield-alt me-2 text-success"></i>Secure Shopping</h6>
                    <small class="text-muted">
                        <i class="fas fa-lock me-1"></i>Your personal information is protected<br>
                        <i class="fas fa-truck me-1"></i>Free returns within 14 days<br>
                        <i class="fas fa-headset me-1"></i>24/7 Customer support
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h4>Angelo Phone Gate</h4>
                    <p>Your trusted mobile store for the latest smartphones, best prices, and reliable customer support.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +94 71 123 4567</li>
                        <li><i class="fas fa-envelope me-2"></i> angelophones@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123, Colombo Road, Sri Lanka</li>
                    </ul>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light">Home</a></li>
                        <li><a href="products.php" class="text-light">Products</a></li>
                        <li><a href="products.php?category=5" class="text-light">Accessories</a></li>
                        <li><a href="contact.php" class="text-light">Contact Us</a></li>
                        <li><a href="login.php" class="text-light">Login</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Popular Products</h5>
                    <ul class="list-unstyled">
                        <li>Oppo Reno 12F</li>
                        <li>Samsung Galaxy A15</li>
                        <li>iPhone 14 Pro Max</li>
                        <li>Boat Airdopes 161</li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Follow Us</h5>
                    <div class="social-icons">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook fa-2x"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram fa-2x"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-youtube fa-2x"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-whatsapp fa-2x"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2024 Angelo Phone Gate. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Cart JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🛒 Cart page loaded');
        
        // Update quantity
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                const quantity = parseInt(this.value);
                
                if (quantity < 1) {
                    this.value = 1;
                    return;
                }
                
                updateCartQuantity(productId, quantity);
            });
        });
        
        // Increase quantity
        document.querySelectorAll('.increase-quantity').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                const newQuantity = parseInt(input.value) + 1;
                input.value = newQuantity;
                updateCartQuantity(productId, newQuantity);
            });
        });
        
        // Decrease quantity
        document.querySelectorAll('.decrease-quantity').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                if (parseInt(input.value) > 1) {
                    const newQuantity = parseInt(input.value) - 1;
                    input.value = newQuantity;
                    updateCartQuantity(productId, newQuantity);
                }
            });
        });
        
        // Remove from cart
        document.querySelectorAll('.remove-from-cart').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                removeFromCart(productId, this);
            });
        });
        
        function updateCartQuantity(productId, quantity) {
            console.log(`Updating quantity for product ${productId} to ${quantity}`);
            
            const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
            const decreaseBtn = document.querySelector(`.decrease-quantity[data-product-id="${productId}"]`);
            const increaseBtn = document.querySelector(`.increase-quantity[data-product-id="${productId}"]`);
            
            input.disabled = true;
            if (decreaseBtn) decreaseBtn.disabled = true;
            if (increaseBtn) increaseBtn.disabled = true;
            
            fetch('includes/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Update response:', data);
                
                if (data.success) {
                    showNotification('Cart updated successfully!', 'success');
                    updateCartCount(data.cart_count);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Error: ' + data.message, 'error');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                showNotification('Error updating cart', 'error');
                location.reload();
            });
        }
        
        function removeFromCart(productId, button) {
            console.log(`Removing product ${productId} from cart`);
            
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
                
                fetch('includes/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Remove response:', data);
                    
                    if (data.success) {
                        showNotification('Product removed from cart!', 'success');
                        const cartItem = document.getElementById(`cart-item-${productId}`);
                        if (cartItem) {
                            cartItem.style.opacity = '0.5';
                            setTimeout(() => {
                                cartItem.remove();
                                // Update cart count immediately
                                updateCartCount(data.cart_count);
                                
                                // If no items left, reload to show empty cart message
                                if (data.cart_count === 0) {
                                    setTimeout(() => {
                                        location.reload();
                                    }, 500);
                                } else {
                                    // Update totals without full reload
                                    updateCartTotals();
                                }
                            }, 500);
                        }
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Remove error:', error);
                    showNotification('Error removing product from cart', 'error');
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                });
            }
        }
        
        function updateCartCount(count) {
            console.log(`Updating cart count to: ${count}`);
            const cartBadge = document.querySelector('.navbar .badge');
            if (cartBadge) {
                cartBadge.textContent = count;
            }
        }
        
        function updateCartTotals() {
            setTimeout(() => {
                location.reload();
            }, 1500);
        }
        
        function showNotification(message, type) {
            console.log(`Notification: ${message} (${type})`);
            
            document.querySelectorAll('.custom-notification').forEach(alert => alert.remove());
            
            const notification = document.createElement('div');
            notification.className = `custom-notification alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
            notification.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
            `;
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }
    });
    </script>
</body>
</html>