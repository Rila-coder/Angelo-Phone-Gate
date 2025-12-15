<?php
// product-details.php - Product Details Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Product Details - Angelo Phone Gate";

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    header('Location: products.php');
    exit;
}

// Get product details
$pdo = getDBConnection();
$product = getProductById($product_id);

if (!$product || $product['status'] != 'active') {
    header('Location: products.php');
    exit;
}

// Get product images
$images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$images_stmt->execute([$product_id]);
$product_images = $images_stmt->fetchAll();

// If no images, use default
if (empty($product_images)) {
    $product_images[] = ['image_url' => 'images/default-product.jpg', 'is_primary' => 1];
}

// Get primary image
$primary_image = $product_images[0]['image_url'];

// Get related products (same category)
$related_stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p 
    WHERE p.category_id = ? AND p.status = 'active' AND p.product_id != ? 
    ORDER BY RAND() 
    LIMIT 4
");
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();

// Handle add to cart
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $success_message = 'Please <a href="login.php" class="alert-link">login</a> to add items to cart.';
    } else {
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
        
        if ($product['stock_quantity'] >= $quantity) {
            addToCart($product_id, $quantity);
            $success_message = '"' . $product['name'] . '" added to cart successfully!';
        } else {
            $success_message = 'Error: Not enough stock available.';
        }
    }
}

// Parse specifications JSON
$specifications = [];
if (!empty($product['specifications']) && $product['specifications'] != 'NULL') {
    $specifications = json_decode($product['specifications'], true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Angelo Phone Gate</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .product-details {
            background: #f8f9fa;
            min-height: 80vh;
            padding: 30px 0;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .product-gallery {
            padding: 30px;
            border-right: 1px solid #eee;
        }
        .main-image {
            text-align: center;
            margin-bottom: 20px;
        }
        .main-image img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }
        .image-thumbnails {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .thumbnail {
            width: 60px;
            height: 60px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .thumbnail:hover,
        .thumbnail.active {
            border-color: #1da1f2;
        }
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 30px;
        }
        .product-category {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .product-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.2;
        }
        .product-brand {
            font-size: 1.1rem;
            color: #1da1f2;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: #1da1f2;
            margin-bottom: 20px;
        }
        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .meta-item {
            text-align: center;
        }
        .meta-label {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        .stock-status {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        .low-stock {
            background: #fff3cd;
            color: #856404;
        }
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px;
            font-weight: 600;
        }
        .btn-add-cart {
            background: linear-gradient(135deg, #ff6b00, #e55a00);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            flex: 1;
        }
        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 0, 0.4);
        }
        .btn-add-cart:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        .specs-section {
            margin-top: 40px;
        }
        .specs-table {
            width: 100%;
            border-collapse: collapse;
        }
        .specs-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .specs-table tr:last-child td {
            border-bottom: none;
        }
        .spec-label {
            font-weight: 600;
            color: #333;
            width: 40%;
            background: #f8f9fa;
        }
        .related-products {
            margin-top: 50px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        .related-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .related-image {
            height: 150px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }
        .related-image img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .related-info {
            padding: 15px;
        }
        .related-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 10px;
            color: #333;
        }
        .related-price {
            font-weight: 700;
            color: #1da1f2;
            font-size: 1.1rem;
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        .breadcrumb-item a {
            color: #1da1f2;
            text-decoration: none;
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
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
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

    <!-- Product Details Content -->
    <div class="product-details">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_id']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>

            <?php if ($success_message): ?>
                <div class="alert <?php echo strpos($success_message, 'Error') !== false || strpos($success_message, 'Please') !== false ? 'alert-warning' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
                    <i class="fas <?php echo strpos($success_message, 'Error') !== false || strpos($success_message, 'Please') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?> me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="product-card">
                <div class="row">
                    <!-- Product Gallery -->
                    <div class="col-lg-6">
                        <div class="product-gallery">
                            <div class="main-image">
                                <img src="<?php echo htmlspecialchars($primary_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     id="mainProductImage">
                            </div>
                            
                            <?php if (count($product_images) > 1): ?>
                            <div class="image-thumbnails">
                                <?php foreach ($product_images as $index => $image): ?>
                                <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeImage('<?php echo htmlspecialchars($image['image_url']); ?>', this)">
                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="col-lg-6">
                        <div class="product-info">
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                            <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                            
                            <div class="product-price">Rs. <?php echo number_format($product['price'], 2); ?></div>
                            
                            <div class="product-meta">
                                <div class="meta-item">
                                    <div class="meta-label">SKU</div>
                                    <div class="meta-value"><?php echo htmlspecialchars($product['sku']); ?></div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">Category</div>
                                    <div class="meta-value"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">Brand</div>
                                    <div class="meta-value"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                                </div>
                            </div>
                            
                            <!-- Stock Status -->
                            <?php
                            $stock_class = '';
                            $stock_text = '';
                            if ($product['stock_quantity'] == 0) {
                                $stock_class = 'out-of-stock';
                                $stock_text = 'Out of Stock';
                            } elseif ($product['stock_quantity'] <= 5) {
                                $stock_class = 'low-stock';
                                $stock_text = 'Low Stock - Only ' . $product['stock_quantity'] . ' left';
                            } else {
                                $stock_class = 'in-stock';
                                $stock_text = 'In Stock';
                            }
                            ?>
                            <div class="stock-status <?php echo $stock_class; ?>">
                                <i class="fas fa-box me-2"></i><?php echo $stock_text; ?>
                            </div>
                            
                            <!-- Add to Cart Form -->
                            <form method="POST" action="">
                                <div class="quantity-selector">
                                    <label for="quantity" class="form-label fw-bold">Quantity:</label>
                                    <input type="number" id="quantity" name="quantity" class="quantity-input" 
                                        value="1" min="1" max="<?php echo $product['stock_quantity']; ?>"
                                        <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                </div>
                                
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button type="submit" name="add_to_cart" class="btn-add-cart">
                                            <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn-add-cart text-decoration-none text-center d-block">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Purchase
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button type="button" class="btn-add-cart" disabled>
                                        <i class="fas fa-cart-plus me-2"></i>Out of Stock
                                    </button>
                                <?php endif; ?>
                            </form>
                            
                            <!-- Product Description -->
                            <?php if (!empty($product['description'])): ?>
                            <div class="description-section mt-4">
                                <h5>Description</h5>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Specifications -->
            <?php if (!empty($specifications)): ?>
            <div class="specs-section">
                <div class="product-card">
                    <div class="product-info">
                        <h4 class="mb-4"><i class="fas fa-list-alt me-2"></i>Specifications</h4>
                        <table class="specs-table">
                            <?php foreach ($specifications as $key => $value): ?>
                            <tr>
                                <td class="spec-label"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                                <td class="spec-value"><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h3 class="mb-4">Related Products</h3>
                <div class="related-grid">
                    <?php foreach ($related_products as $related): 
                        $related_image = $related['primary_image'] ?: 'images/default-product.jpg';
                        $related_stock_class = $related['stock_quantity'] == 0 ? 'out-of-stock' : 
                                              ($related['stock_quantity'] <= 5 ? 'low-stock' : 'in-stock');
                    ?>
                    <div class="related-card">
                        <a href="product-details.php?id=<?php echo $related['product_id']; ?>" class="text-decoration-none">
                            <div class="related-image">
                                <img src="<?php echo htmlspecialchars($related_image); ?>" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <?php if ($related['stock_quantity'] == 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-danger m-2">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="related-info">
                                <div class="related-name"><?php echo htmlspecialchars($related['name']); ?></div>
                                <div class="related-price">Rs. <?php echo number_format($related['price'], 2); ?></div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
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
    
    <!-- Image Gallery JavaScript -->
    <script>
    function changeImage(imageUrl, element) {
        // Update main image
        document.getElementById('mainProductImage').src = imageUrl;
        
        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }
    
    // Quantity validation
    document.getElementById('quantity')?.addEventListener('change', function() {
        const max = parseInt(this.getAttribute('max'));
        const min = parseInt(this.getAttribute('min'));
        let value = parseInt(this.value);
        
        if (value < min) this.value = min;
        if (value > max) this.value = max;
    });
    </script>
</body>
</html>