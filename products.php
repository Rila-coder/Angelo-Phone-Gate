<?php
// products.php - Products Catalog Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Products - Angelo Phone Gate";

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12; // Products per page
$offset = ($page - 1) * $limit;

// Get categories and brands for filters
$pdo = getDBConnection();
$categories = $pdo->query("SELECT category_id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT brand_id, name FROM brands WHERE status = 'active' ORDER BY name")->fetchAll();

// Build products query with filters
$where_conditions = ["p.status = 'active'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if (!empty($brand)) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $brand;
}

if (!empty($min_price)) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

$where_clause = implode(' AND ', $where_conditions);

// Build sort order
$sort_orders = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC'
];
$order_by = $sort_orders[$sort] ?? 'p.created_at DESC';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $limit);

// Get products
$products_sql = "
    SELECT p.*, c.name as category_name, b.name as brand_name,
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE $where_clause
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$products_stmt = $pdo->prepare($products_sql);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

// Get price range for filter
$price_range = $pdo->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'")->fetch();
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
        .products-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        .filter-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
            position: sticky;
            top: 20px;
        }
        .filter-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .filter-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .filter-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            font-size: 1.1rem;
        }
        .form-check-label {
            cursor: pointer;
            padding: 5px 0;
            transition: color 0.3s ease;
        }
        .form-check-label:hover {
            color: #1da1f2;
        }
        .price-range-values {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .product-image img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
        }
        .product-info {
            padding: 20px;
        }
        .product-category {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .product-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #333;
            line-height: 1.3;
        }
        .product-brand {
            font-size: 0.9rem;
            color: #1da1f2;
            margin-bottom: 10px;
        }
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1da1f2;
            margin-bottom: 15px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
        }
        .btn-product {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-view {
            background: #f8f9fa;
            color: #333;
        }
        .btn-view:hover {
            background: #e9ecef;
        }
        .btn-cart {
            background: #ff6b00;
            color: white;
        }
        .btn-cart:hover {
            background: #e55a00;
            transform: translateY(-1px);
        }
        .results-info {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .sort-select {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px 12px;
            background: white;
        }
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }
        .page-link {
            border: 1px solid #dee2e6;
            color: #1da1f2;
            padding: 8px 16px;
        }
        .page-item.active .page-link {
            background: #1da1f2;
            border-color: #1da1f2;
        }
        .no-products {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .clear-filters {
            margin-top: 15px;
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
                    <li class="nav-item"><a class="nav-link active" href="products.php">Products</a></li>
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
                
                <!-- Search Form -->
                <form class="d-flex ms-3 mt-2 mt-lg-0" action="products.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-info" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Products Header -->
    <div class="products-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold">Our Products</h1>
            <p class="lead mb-0">Discover the latest smartphones and accessories</p>
        </div>
    </div>

    <!-- Products Content -->
    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h4 class="mb-4"><i class="fas fa-filter me-2"></i>Filters</h4>
                    
                    <!-- MAIN FILTER FORM - This wraps all filters -->
                    <form method="GET" action="products.php" id="filterForm">
                        <!-- Hidden fields to preserve other filters -->
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                        <input type="hidden" name="page" value="1"> <!-- Reset to page 1 when filtering -->

                        <!-- Search Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Search</div>
                            <input type="text" class="form-control" name="search" placeholder="Search products..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   onkeyup="if(event.keyCode===13) document.getElementById('filterForm').submit()">
                            <button type="submit" class="btn btn-primary btn-sm mt-2 w-100">Apply Search</button>
                        </div>

                        <!-- Category Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Categories</div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" value="" id="cat-all" 
                                       <?php echo empty($category) ? 'checked' : ''; ?> 
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="cat-all">All Categories</label>
                            </div>
                            <?php foreach ($categories as $cat): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="category" value="<?php echo $cat['category_id']; ?>" 
                                       id="cat-<?php echo $cat['category_id']; ?>"
                                       <?php echo $category == $cat['category_id'] ? 'checked' : ''; ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="cat-<?php echo $cat['category_id']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Brand Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Brands</div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="brand" value="" id="brand-all"
                                       <?php echo empty($brand) ? 'checked' : ''; ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="brand-all">All Brands</label>
                            </div>
                            <?php foreach ($brands as $br): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="brand" value="<?php echo $br['brand_id']; ?>"
                                       id="brand-<?php echo $br['brand_id']; ?>"
                                       <?php echo $brand == $br['brand_id'] ? 'checked' : ''; ?>
                                       onchange="document.getElementById('filterForm').submit()">
                                <label class="form-check-label" for="brand-<?php echo $br['brand_id']; ?>">
                                    <?php echo htmlspecialchars($br['name']); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Price Filter -->
                        <div class="filter-section">
                            <div class="filter-title">Price Range</div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="min_price" placeholder="Min" 
                                           value="<?php echo htmlspecialchars($min_price); ?>" min="0">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="max_price" placeholder="Max" 
                                           value="<?php echo htmlspecialchars($max_price); ?>" min="0">
                                </div>
                            </div>
                            <div class="price-range-values">
                                <small>Rs. <?php echo number_format($price_range['min_price'], 2); ?></small>
                                <small>Rs. <?php echo number_format($price_range['max_price'], 2); ?></small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-2 w-100">Apply Price</button>
                        </div>
                    </form>

                    <!-- Clear Filters -->
                    <?php if ($search || $category || $brand || $min_price || $max_price): ?>
                    <div class="clear-filters">
                        <a href="products.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-times me-1"></i>Clear All Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="col-lg-9">
                <!-- Results Info and Sort -->
                <div class="results-info">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <?php echo $total_products; ?> product<?php echo $total_products != 1 ? 's' : ''; ?> found
                                <?php if ($search): ?>
                                for "<?php echo htmlspecialchars($search); ?>"
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <form method="GET" class="d-inline-block" id="sortForm">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand); ?>">
                                <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                                <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                                <input type="hidden" name="page" value="1">
                                
                                <select name="sort" class="sort-select" onchange="document.getElementById('sortForm').submit()">
                                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="no-products">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>No products found</h4>
                    <p class="text-muted mb-4">Try adjusting your search or filters</p>
                    <a href="products.php" class="btn btn-primary">Clear Filters</a>
                </div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        $product_image = $product['primary_image'] ?: 'images/default-product.jpg';
                        $stock_status = $product['stock_quantity'] == 0 ? 'Out of Stock' : 
                                       ($product['stock_quantity'] <= 5 ? 'Low Stock' : 'In Stock');
                        $stock_class = $product['stock_quantity'] == 0 ? 'text-danger' : 
                                      ($product['stock_quantity'] <= 5 ? 'text-warning' : 'text-success');
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php if ($product['featured']): ?>
                            <span class="position-absolute top-0 start-0 badge bg-warning m-2">Featured</span>
                            <?php endif; ?>
                            <?php if ($product['stock_quantity'] == 0): ?>
                            <span class="position-absolute top-0 end-0 badge bg-danger m-2">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-brand"><?php echo htmlspecialchars($product['brand_name']); ?></div>
                            <div class="product-price">Rs. <?php echo number_format($product['price'], 2); ?></div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="<?php echo $stock_class; ?>">
                                    <i class="fas fa-box me-1"></i><?php echo $stock_status; ?>
                                </small>
                                <small class="text-muted">SKU: <?php echo htmlspecialchars($product['sku']); ?></small>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product-details.php?id=<?php echo $product['product_id']; ?>" 
                                class="btn-product btn-view">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                    <button class="btn-product btn-cart add-to-cart" 
                                            data-product-id="<?php echo $product['product_id']; ?>"
                                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                        <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                    </button>
                                    <?php else: ?>
                                    <a href="login.php" class="btn-product btn-cart">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login to Buy
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                <button class="btn-product btn-secondary" disabled>
                                    <i class="fas fa-cart-plus me-1"></i>Out of Stock
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Products pagination">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($page - 1); ?>">
                                <i class="fas fa-chevron-left me-1"></i>Previous
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo buildPaginationUrl($page + 1); ?>">
                                Next<i class="fas fa-chevron-right ms-1"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
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
    
    <!-- Add to Cart JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');
                
                // Show loading state
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Adding...';
                this.disabled = true;
                
                fetch('includes/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button state
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                    
                    if (data.success) {
                        // Show success message
                        showNotification('"' + productName + '" added to cart!', 'success');
                        
                        // Update cart count
                        updateCartCount(data.cart_count);
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error adding product to cart', 'error');
                    
                    // Reset button state
                    this.innerHTML = originalHTML;
                    this.disabled = false;
                });
            });
        });
        
        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
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
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }
        
        function updateCartCount(count) {
            const cartBadge = document.querySelector('.navbar .badge');
            if (cartBadge) {
                cartBadge.textContent = count;
            }
        }
    });
    </script>
</body>
</html>

<?php
// Helper function for pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'products.php?' . http_build_query($params);
}
?>