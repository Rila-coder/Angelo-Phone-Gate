<?php
// Start session and include configuration
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
function checkDarkMode() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT value FROM settings WHERE key_name = 'enable_dark_mode'");
        $dark_mode = $stmt->fetchColumn();
        return $dark_mode == '1';
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angelo Phone Gate - Your Trusted Mobile Store</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
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
                <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
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
                                $cart_count = count($_SESSION['cart']);
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
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">0</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <form class="d-flex ms-3 mt-2 mt-lg-0" action="products.php" method="GET">
                <input class="form-control me-2" type="search" name="search" placeholder="Search products...">
                <button class="btn btn-info" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</nav>

<!-- Hero Carousel -->
<div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
        <!-- Slide 1 -->
        <div class="carousel-item active">
            <img src="images/slide1.jpg" class="d-block w-100" alt="Latest Smartphones" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h2>Welcome to Angelo Phone Gate</h2>
                <p>Your Trusted Genuine Mobile Store</p>
                <a href="products.php" class="btn btn-primary btn-lg">Shop Now</a>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="carousel-item">
            <img src="images/slide2.jpg" class="d-block w-100" alt="Latest Smartphones" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h2>Latest Smartphones</h2>
                <p>Find the best mobile devices at best prices</p>
                <a href="products.php" class="btn btn-primary btn-lg">Explore Devices</a>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="carousel-item">
            <img src="images/slide3.jpg" class="d-block w-100" alt="Accessories" style="height: 500px; object-fit: cover;">
            <div class="carousel-caption d-none d-md-block">
                <h2>Accessories & More</h2>
                <p>Headphones, Chargers, Cases & More</p>
                <a href="products.php?category=5" class="btn btn-primary btn-lg">Browse Accessories</a>
            </div>
        </div>
    </div>
    
    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Optimized Hybrid Approach -->
<div class="brand-showcase py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Popular Brands</h2>
        
        <div class="row justify-content-center g-4">
            <?php
            function findBrandLogo($brandName) {
                $brandName = trim($brandName);
                $basePath = 'images/brands/';
                
                // Clean brand name for filename matching
                $cleanName = strtolower(preg_replace('/[^a-z0-9]/', '-', $brandName));
                $cleanName = preg_replace('/-+/', '-', $cleanName); // Remove multiple dashes
                
                // Priority 1: Direct filename match with different extensions
                $extensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.jfif'];
                foreach ($extensions as $ext) {
                    $filePath = $basePath . $cleanName . $ext;
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
                
                // Priority 2: Known brand mappings (add your specific cases here)
                $knownMappings = [
                    'motorola' => 'motorola.png',
                    'moto' => 'boat.png',
                    'oppo' => 'oppo.png',
                    'boat' => 'boat.png',
                    'jbl' => 'jbl.png',
                ];
                
                $lowerName = strtolower($brandName);
                if (isset($knownMappings[$lowerName]) && file_exists($basePath . $knownMappings[$lowerName])) {
                    return $basePath . $knownMappings[$lowerName];
                }
                
                // Priority 3: Try without cleaning (exact match)
                foreach ($extensions as $ext) {
                    $filePath = $basePath . strtolower($brandName) . $ext;
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
                
                // Final fallback
                return $basePath . 'default-brand.png';
            }

            $pdo = getDBConnection();
            $brands = $pdo->query("SELECT brand_id, name FROM brands WHERE status = 'active' LIMIT 7")->fetchAll();
            

            foreach ($brands as $brand):
                $brand_logo = findBrandLogo($brand['name']);
            ?>
            <div class="col-auto text-center">
                <a href="products.php?brand=<?php echo $brand['brand_id']; ?>">
                    <img src="<?php echo $brand_logo; ?>" 
                         alt="<?php echo htmlspecialchars($brand['name']); ?>" 
                         class="brand-logo img-fluid"
                         onerror="this.src='images/brands/default-brand.png'">
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($brands)): ?>
        <div class="text-center">
            <p class="text-muted">No brands available</p>
        </div>
        <?php endif; ?>
    </div>
</div>


<!-- Store Highlights -->
<section class="store-highlights py-5 bg-dark text-white">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <img src="images/icons/dealer.png" alt="Authorized Dealer" class="highlight-icon mb-3">
                <h5><strong>AUTHORIZED DEALER</strong></h5>
                <p class="mb-0">Buy Smart, Buy Genuine</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <img src="images/icons/warranty.png" alt="Warranty" class="highlight-icon mb-3">
                <h5><strong>ANGELO WARRANTY</strong></h5>
                <p class="mb-0">Reliable Coverage, Hassle-Free Service</p>
            </div>
            
            <div class="col-md-4 mb-4">
                <img src="images/icons/trcsl.png" alt="TRCSL Approved" class="highlight-icon mb-3">
                <h5><strong>TRCSL APPROVED</strong></h5>
                <p class="mb-0">Government Approved</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="featured-products py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        <div class="row" id="featured-products">
            <!-- Products will be loaded dynamically via JavaScript/PHP -->
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading products...</span>
                </div>
                <p class="mt-2">Loading featured products...</p>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-outline-primary btn-lg">View All Products</a>
        </div>
    </div>
</section>

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
<!-- Custom JS -->
<script src="js/script.js"></script>

<script>
// Load featured products via AJAX
document.addEventListener('DOMContentLoaded', function() {
    fetch('includes/get_featured_products.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('featured-products').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('featured-products').innerHTML = 
                '<div class="col-12 text-center text-danger"><p>Error loading products. Please try again later.</p></div>';
        });
});
</script>

</body>
</html>