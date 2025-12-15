<?php
// admin/products.php - Complete Products Management with Image Handling
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Products Management - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

// Handle product actions
$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Ensure uploads directory exists
$upload_dir = '../uploads/products/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $brand_id = $_POST['brand_id'] ?? null;
        $price = $_POST['price'] ?? 0;
        $cost_price = $_POST['cost_price'] ?? null;
        $stock_quantity = $_POST['stock_quantity'] ?? 0;
        $min_stock = $_POST['min_stock'] ?? 5;
        $max_stock = $_POST['max_stock'] ?? 100;
        
        // Handle specifications as JSON
        $specifications = [];
        if (!empty($_POST['spec_keys']) && !empty($_POST['spec_values'])) {
            $keys = $_POST['spec_keys'];
            $values = $_POST['spec_values'];
            
            for ($i = 0; $i < count($keys); $i++) {
                $key = trim($keys[$i]);
                $value = trim($values[$i]);
                
                if (!empty($key) && !empty($value)) {
                    $specifications[$key] = $value;
                }
            }
        }
        $specifications_json = !empty($specifications) ? json_encode($specifications, JSON_PRETTY_PRINT) : null;
        
        $featured = isset($_POST['featured']) ? 1 : 0;

        // Validate required fields
        if (empty($name) || empty($sku) || empty($price)) {
            throw new Exception("Product name, SKU, and price are required.");
        }

        // Check if SKU already exists
        $check_stmt = $pdo->prepare("SELECT product_id FROM products WHERE sku = ?");
        $check_stmt->execute([$sku]);
        if ($check_stmt->fetch()) {
            throw new Exception("Product SKU '$sku' already exists.");
        }

        // Handle image upload
        $uploaded_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']; // Added 'jfif'

                    if (!in_array($file_ext, $allowed_ext)) {
                        throw new Exception("Invalid file type: $file_name. Allowed: JPG, JPEG, PNG, GIF, WEBP, JFIF");
                    }

                    // Generate unique filename
                    $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $uploaded_images[] = 'uploads/products/' . $new_filename;
                    } else {
                        throw new Exception("Failed to upload image: $file_name");
                    }
                }
            }
        }

        // Insert product
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, sku, category_id, brand_id, price, cost_price, stock_quantity, min_stock, max_stock, specifications, featured, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $name, $description, $sku, $category_id, $brand_id, $price, $cost_price, 
            $stock_quantity, $min_stock, $max_stock, $specifications_json, $featured
        ]);
        
        $new_product_id = $pdo->lastInsertId();

        // Insert product images
        if (!empty($uploaded_images)) {
            $image_stmt = $pdo->prepare("
                INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($uploaded_images as $index => $image_url) {
                $is_primary = ($index === 0) ? 1 : 0;
                $image_stmt->execute([$new_product_id, $image_url, $is_primary, $index]);
            }
        }

        $message = "Product '$name' added successfully!";

    } catch (Exception $e) {
        $error = "Error adding product: " . $e->getMessage();
    }
}

// Edit product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $brand_id = $_POST['brand_id'] ?? null;
        $price = $_POST['price'] ?? 0;
        $cost_price = $_POST['cost_price'] ?? null;
        $stock_quantity = $_POST['stock_quantity'] ?? 0;
        $min_stock = $_POST['min_stock'] ?? 5;
        $max_stock = $_POST['max_stock'] ?? 100;
        
        // Handle specifications as JSON
        $specifications = [];
        if (!empty($_POST['spec_keys']) && !empty($_POST['spec_values'])) {
            $keys = $_POST['spec_keys'];
            $values = $_POST['spec_values'];
            
            for ($i = 0; $i < count($keys); $i++) {
                $key = trim($keys[$i]);
                $value = trim($values[$i]);
                
                if (!empty($key) && !empty($value)) {
                    $specifications[$key] = $value;
                }
            }
        }
        $specifications_json = !empty($specifications) ? json_encode($specifications, JSON_PRETTY_PRINT) : null;
        
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'active';

        // Validate required fields
        if (empty($name) || empty($sku) || empty($price)) {
            throw new Exception("Product name, SKU, and price are required.");
        }

        // Check if SKU already exists (excluding current product)
        $check_stmt = $pdo->prepare("SELECT product_id FROM products WHERE sku = ? AND product_id != ?");
        $check_stmt->execute([$sku, $product_id]);
        if ($check_stmt->fetch()) {
            throw new Exception("Product SKU '$sku' already exists.");
        }

        // Handle image uploads
        $uploaded_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']; // Added 'jfif'

                    if (!in_array($file_ext, $allowed_ext)) {
                        throw new Exception("Invalid file type: $file_name. Allowed: JPG, JPEG, PNG, GIF, WEBP, JFIF");
                    }

                    // Generate unique filename
                    $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $uploaded_images[] = 'uploads/products/' . $new_filename;
                    } else {
                        throw new Exception("Failed to upload image: $file_name");
                    }
                }
            }
        }

        // Handle image deletions
        $deleted_images = $_POST['deleted_images'] ?? [];
        if (!empty($deleted_images)) {
            $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE image_id = ?");
            foreach ($deleted_images as $image_id) {
                // Get image path before deleting
                $img_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE image_id = ?");
                $img_stmt->execute([$image_id]);
                $image = $img_stmt->fetch();
                
                if ($image && file_exists('../' . $image['image_url'])) {
                    unlink('../' . $image['image_url']);
                }
                
                $delete_stmt->execute([$image_id]);
            }
        }

        // Update product
        $stmt = $pdo->prepare("
            UPDATE products 
            SET name = ?, description = ?, sku = ?, category_id = ?, brand_id = ?, price = ?, cost_price = ?, 
                stock_quantity = ?, min_stock = ?, max_stock = ?, specifications = ?, featured = ?, status = ?, updated_at = NOW()
            WHERE product_id = ?
        ");
        $stmt->execute([
            $name, $description, $sku, $category_id, $brand_id, $price, $cost_price, 
            $stock_quantity, $min_stock, $max_stock, $specifications_json, $featured, $status, $product_id
        ]);

        // Insert new images
        if (!empty($uploaded_images)) {
            $image_stmt = $pdo->prepare("
                INSERT INTO product_images (product_id, image_url, is_primary, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            
            // Get current max sort order
            $sort_stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) as max_order FROM product_images WHERE product_id = ?");
            $sort_stmt->execute([$product_id]);
            $max_order = $sort_stmt->fetch()['max_order'];
            
            foreach ($uploaded_images as $index => $image_url) {
                $is_primary = ($max_order === -1 && $index === 0) ? 1 : 0; // First image is primary if no images exist
                $image_stmt->execute([$product_id, $image_url, $is_primary, $max_order + $index + 1]);
            }
        }

        $message = "Product '$name' updated successfully!";

    } catch (Exception $e) {
        $error = "Error updating product: " . $e->getMessage();
    }
}

// Delete product - SIMPLE COMPLETE REMOVAL
if ($action === 'delete' && $product_id) {
    try {
        // 1. First delete from order_items table
        $delete_order_items = $pdo->prepare("DELETE FROM order_items WHERE product_id = ?");
        $delete_order_items->execute([$product_id]);
        
        // 2. Delete product images from server and database
        $images_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
        $images_stmt->execute([$product_id]);
        $images = $images_stmt->fetchAll();

        foreach ($images as $image) {
            if ($image['image_url'] && file_exists('../' . $image['image_url'])) {
                unlink('../' . $image['image_url']);
            }
        }

        // 3. Delete from product_images table
        $delete_images = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $delete_images->execute([$product_id]);

        // 4. Finally delete the product itself
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        $message = "Product completely deleted from all pages!";

    } catch (Exception $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Toggle product status
if ($action === 'toggle_status' && $product_id) {
    try {
        $stmt = $pdo->prepare("UPDATE products SET status = IF(status='active', 'inactive', 'active') WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $message = "Product status updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating product status: " . $e->getMessage();
    }
}

// Get categories and brands for forms
$categories = $pdo->query("SELECT category_id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT brand_id, name FROM brands WHERE status = 'active' ORDER BY name")->fetchAll();

// Get product for editing
$product = null;
$product_images = [];
$specifications = [];
if ($product_id && ($action === 'edit' || isset($_POST['edit_product']))) {
    $product_stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $product_stmt->execute([$product_id]);
    $product = $product_stmt->fetch();
    
    if ($product && $product['specifications']) {
        $specifications = json_decode($product['specifications'], true) ?: [];
    }
    
    if ($product) {
        $images_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
        $images_stmt->execute([$product_id]);
        $product_images = $images_stmt->fetchAll();
    }
}

// Get filter parameters for listing
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build products query with filters
$where_conditions = ["1=1"];
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

if (!empty($status)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetch()['total'];
$total_pages = ceil($total_products / $limit);

// Get products for listing
$products_sql = "
    SELECT p.*, c.name as category_name, b.name as brand_name,
           (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE $where_clause
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
";

$products_stmt = $pdo->prepare($products_sql);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();
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
    <link rel="stylesheet" href="../css/style.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin-styles.css">
    <style>
        .products-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 0;
            margin: -20px -20px 30px -20px;
        }
        .product-image-small {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .stock-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-instock { background: #d4edda; color: #155724; }
        .badge-lowstock { background: #fff3cd; color: #856404; }
        .badge-outstock { background: #f8d7da; color: #721c24; }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .badge-draft { background: #e2e3e5; color: #383d41; }
        .action-buttons .btn {
            padding: 4px 8px;
            margin: 2px;
            font-size: 0.8rem;
        }
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin: 5px;
            border: 2px solid #dee2e6;
        }
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .image-item {
            position: relative;
            display: inline-block;
        }
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
        }
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload:hover {
            border-color: #1da1f2;
            background: #f8f9fa;
        }
        .specifications-container {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        .spec-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }
        .spec-input {
            flex: 1;
        }
        .spec-actions {
            width: 40px;
        }
        .add-spec-btn {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .add-spec-btn:hover {
            background: #218838;
        }
        .remove-spec-btn {
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-spec-btn:hover {
            background: #c82333;
        }
        .spec-preview {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        .spec-preview-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .spec-preview-item:last-child {
            border-bottom: none;
        }
        .spec-key {
            font-weight: 600;
            color: #495057;
        }
        .spec-value {
            color: #6c757d;
        }
        .empty-specs {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <div class="admin-header">
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">
                            <i class="fas fa-box me-2"></i>Products Management
                        </h3>
                        <p class="text-muted mb-0">Manage your product catalog</p>
                    </div>
                    <div class="col-auto">
                        <a href="products.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile Overlay -->
            <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Product Form -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                            <?php echo $action === 'add' ? 'Add New Product' : 'Edit Product: ' . htmlspecialchars($product['name'] ?? ''); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data" id="productForm">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="edit_product" value="1">
                            <?php else: ?>
                                <input type="hidden" name="add_product" value="1">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Basic Information -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2">Basic Information</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Product Name *</label>
                                                <input type="text" class="form-control" name="name" 
                                                       value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                                       required placeholder="Enter product name">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">SKU *</label>
                                                <input type="text" class="form-control" name="sku" 
                                                       value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" 
                                                       required placeholder="Product SKU">
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="4" 
                                                      placeholder="Product description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Category</label>
                                                <select class="form-select" name="category_id">
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo $cat['category_id']; ?>" 
                                                            <?php echo ($product['category_id'] ?? '') == $cat['category_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Brand</label>
                                                <select class="form-select" name="brand_id">
                                                    <option value="">Select Brand</option>
                                                    <?php foreach ($brands as $br): ?>
                                                        <option value="<?php echo $br['brand_id']; ?>" 
                                                            <?php echo ($product['brand_id'] ?? '') == $br['brand_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($br['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pricing & Inventory -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2">Pricing & Inventory</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Price (Rs.) *</label>
                                                <input type="number" class="form-control" name="price" 
                                                       value="<?php echo $product['price'] ?? ''; ?>" 
                                                       min="0" step="0.01" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Cost Price (Rs.)</label>
                                                <input type="number" class="form-control" name="cost_price" 
                                                       value="<?php echo $product['cost_price'] ?? ''; ?>" 
                                                       min="0" step="0.01">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Stock Quantity</label>
                                                <input type="number" class="form-control" name="stock_quantity" 
                                                       value="<?php echo $product['stock_quantity'] ?? 0; ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Minimum Stock</label>
                                                <input type="number" class="form-control" name="min_stock" 
                                                       value="<?php echo $product['min_stock'] ?? 5; ?>" 
                                                       min="0">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Maximum Stock</label>
                                                <input type="number" class="form-control" name="max_stock" 
                                                       value="<?php echo $product['max_stock'] ?? 100; ?>" 
                                                       min="0">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Specifications -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2">Specifications</h5>
                                        <div class="specifications-container">
                                            <div id="specificationsList">
                                                <?php if (!empty($specifications)): ?>
                                                    <?php foreach ($specifications as $key => $value): ?>
                                                        <div class="spec-row">
                                                            <input type="text" class="form-control spec-input" name="spec_keys[]" 
                                                                   value="<?php echo htmlspecialchars($key); ?>" 
                                                                   placeholder="Specification name (e.g., RAM, Storage)">
                                                            <input type="text" class="form-control spec-input" name="spec_values[]" 
                                                                   value="<?php echo htmlspecialchars($value); ?>" 
                                                                   placeholder="Specification value (e.g., 8GB, 256GB)">
                                                            <div class="spec-actions">
                                                                <button type="button" class="remove-spec-btn" onclick="removeSpecRow(this)">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <div class="spec-row">
                                                        <input type="text" class="form-control spec-input" name="spec_keys[]" 
                                                               placeholder="Specification name (e.g., RAM, Storage)">
                                                        <input type="text" class="form-control spec-input" name="spec_values[]" 
                                                               placeholder="Specification value (e.g., 8GB, 256GB)">
                                                        <div class="spec-actions">
                                                            <button type="button" class="remove-spec-btn" onclick="removeSpecRow(this)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="add-spec-btn" onclick="addSpecRow()">
                                                <i class="fas fa-plus me-2"></i>Add Specification
                                            </button>
                                            
                                            <!-- Specifications Preview -->
                                            <div class="spec-preview">
                                                <h6 class="mb-3">Preview:</h6>
                                                <div id="specPreview">
                                                    <?php if (!empty($specifications)): ?>
                                                        <?php foreach ($specifications as $key => $value): ?>
                                                            <div class="spec-preview-item">
                                                                <span class="spec-key"><?php echo htmlspecialchars($key); ?>:</span>
                                                                <span class="spec-value"><?php echo htmlspecialchars($value); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="empty-specs">No specifications added yet</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-text mt-2">
                                            Add product specifications as key-value pairs. Common specifications for phones include RAM, Storage, Camera, Display, Battery, etc.
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <!-- Images -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2">Product Images</h5>
                                        
                                        <!-- Existing Images -->
                                        <?php if (!empty($product_images)): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Current Images</label>
                                                <div class="image-preview-container">
                                                    <?php foreach ($product_images as $image): ?>
                                                        <div class="image-item">
                                                            <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                                 class="image-preview" 
                                                                 alt="Product Image">
                                                            <button type="button" class="remove-image" 
                                                                    onclick="removeImage(<?php echo $image['image_id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                            <input type="hidden" name="deleted_images[]" 
                                                                   id="delete_<?php echo $image['image_id']; ?>" value="">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- New Image Upload -->
                                        <div class="mb-3">
                                            <label class="form-label">Add New Images</label>
                                            <div class="file-upload" onclick="document.getElementById('imageInput').click()">
                                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                <p class="mb-1">Click to upload images</p>
                                                <small class="text-muted">JPG, PNG, GIF, WEBP (Max 2MB each)</small>
                                                <input type="file" id="imageInput" name="images[]" 
                                                       class="d-none" multiple accept="image/*">
                                            </div>
                                            <div id="imagePreviews" class="image-preview-container mt-2"></div>
                                        </div>
                                    </div>

                                    <!-- Settings -->
                                    <div class="mb-4">
                                        <h5 class="border-bottom pb-2">Settings</h5>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="featured" 
                                                       value="1" id="featured" 
                                                       <?php echo ($product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="featured">
                                                    Featured Product
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <?php if ($action === 'edit'): ?>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status">
                                                    <option value="active" <?php echo ($product['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?php echo ($product['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    <option value="draft" <?php echo ($product['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>
                                            <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                                        </button>
                                        <a href="products.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <!-- Products Listing -->
                <!-- Filters -->
                <div class="filter-card">
                    <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filters</h5>
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $br): ?>
                                        <option value="<?php echo $br['brand_id']; ?>" <?php echo $brand == $br['brand_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($br['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="recent-table">
                    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Products (<?php echo $total_products; ?>)
                        </h5>
                        <div>
                            <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-box fa-2x mb-2"></i><br>
                                            No products found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): 
                                        $stock_status = $product['stock_quantity'] == 0 ? 'outstock' : ($product['stock_quantity'] <= 5 ? 'lowstock' : 'instock');
                                        $stock_text = $product['stock_quantity'] == 0 ? 'Out of Stock' : ($product['stock_quantity'] <= 5 ? 'Low Stock' : 'In Stock');
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo $product['primary_image'] ? '../' . $product['primary_image'] : '../images/default-product.jpg'; ?>" 
                                                         class="product-image-small me-3" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo $product['featured'] ? '<i class="fas fa-star text-warning me-1"></i>Featured' : ''; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($product['sku']); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong>Rs. <?php echo number_format($product['price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="stock-badge badge-<?php echo $stock_status; ?>">
                                                    <?php echo $stock_text; ?> (<?php echo $product['stock_quantity']; ?>)
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge badge-<?php echo $product['status']; ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="../product-details.php?id=<?php echo $product['product_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank" title="View on Store">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="products.php?action=edit&id=<?php echo $product['product_id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="products.php?action=toggle_status&id=<?php echo $product['product_id']; ?>" 
                                                       class="btn btn-sm btn-outline-<?php echo $product['status'] == 'active' ? 'warning' : 'success'; ?>" 
                                                       title="<?php echo $product['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <button onclick="confirmDelete(<?php echo $product['product_id']; ?>)" 
                                                            class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="p-3 border-top">
                            <nav aria-label="Products pagination">
                                <ul class="pagination justify-content-center mb-0">
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
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(productId) {
            if (confirm('Are you sure you want to completely delete this product? It will be removed from:\n• Products page\n• Home page\n• Search results\n• Cart items\n• All other pages\n\nThis action cannot be undone!')) {
                window.location.href = 'products.php?action=delete&id=' + productId;
            }
        }

        function buildPaginationUrl(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            return 'products.php?' + params.toString();
        }

        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Image upload preview
        document.getElementById('imageInput')?.addEventListener('change', function(e) {
            const previewContainer = document.getElementById('imagePreviews');
            previewContainer.innerHTML = '';
            
            for (let file of this.files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'image-preview';
                        previewContainer.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });

        // Remove existing image
        function removeImage(imageId) {
            if (confirm('Are you sure you want to remove this image?')) {
                document.getElementById('delete_' + imageId).value = imageId;
                document.querySelector(`.remove-image[onclick="removeImage(${imageId})"]`).closest('.image-item').style.display = 'none';
            }
        }

        // Specifications Management
        function addSpecRow() {
            const specsList = document.getElementById('specificationsList');
            const newRow = document.createElement('div');
            newRow.className = 'spec-row';
            newRow.innerHTML = `
                <input type="text" class="form-control spec-input" name="spec_keys[]" 
                       placeholder="Specification name (e.g., RAM, Storage)">
                <input type="text" class="form-control spec-input" name="spec_values[]" 
                       placeholder="Specification value (e.g., 8GB, 256GB)">
                <div class="spec-actions">
                    <button type="button" class="remove-spec-btn" onclick="removeSpecRow(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            specsList.appendChild(newRow);
            updateSpecPreview();
        }

        function removeSpecRow(button) {
            const row = button.closest('.spec-row');
            row.remove();
            updateSpecPreview();
        }

        function updateSpecPreview() {
            const preview = document.getElementById('specPreview');
            const keyInputs = document.querySelectorAll('input[name="spec_keys[]"]');
            const valueInputs = document.querySelectorAll('input[name="spec_values[]"]');
            
            preview.innerHTML = '';
            
            if (keyInputs.length === 0) {
                preview.innerHTML = '<div class="empty-specs">No specifications added yet</div>';
                return;
            }
            
            let hasValidSpecs = false;
            
            for (let i = 0; i < keyInputs.length; i++) {
                const key = keyInputs[i].value.trim();
                const value = valueInputs[i].value.trim();
                
                if (key && value) {
                    hasValidSpecs = true;
                    const specItem = document.createElement('div');
                    specItem.className = 'spec-preview-item';
                    specItem.innerHTML = `
                        <span class="spec-key">${key}:</span>
                        <span class="spec-value">${value}</span>
                    `;
                    preview.appendChild(specItem);
                }
            }
            
            if (!hasValidSpecs) {
                preview.innerHTML = '<div class="empty-specs">No specifications added yet</div>';
            }
        }

        // Update preview when inputs change
        document.addEventListener('input', function(e) {
            if (e.target.name === 'spec_keys[]' || e.target.name === 'spec_values[]') {
                updateSpecPreview();
            }
        });

        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSpecPreview();
        });

        // Form validation
        document.getElementById('productForm')?.addEventListener('submit', function(e) {
            const price = document.querySelector('input[name="price"]').value;
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0.');
                return false;
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