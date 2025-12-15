<?php
// admin/brands.php - Brands Management
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Brands Management - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

// Handle brand actions
$action = $_GET['action'] ?? '';
$brand_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Add new brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error = "Brand name is required.";
    } else {
        try {
            // Check if brand already exists
            $check_stmt = $pdo->prepare("SELECT brand_id FROM brands WHERE name = ?");
            $check_stmt->execute([$name]);
            
            if ($check_stmt->fetch()) {
                $error = "Brand '$name' already exists.";
            } else {
                // Handle logo upload
                $logo_path = null;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/brands/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array(strtolower($file_extension), $allowed_extensions)) {
                        $filename = 'brand_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $logo_path = 'images/brands/' . $filename;
                        
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $logo_path)) {
                            // File uploaded successfully
                        } else {
                            $error = "Failed to upload logo.";
                        }
                    } else {
                        $error = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF, WEBP";
                    }
                }
                
                if (!$error) {
                    $stmt = $pdo->prepare("
                        INSERT INTO brands (name, description, logo, status) 
                        VALUES (?, ?, ?, 'active')
                    ");
                    $stmt->execute([$name, $description, $logo_path]);
                    $message = "Brand '$name' added successfully!";
                }
            }
        } catch (Exception $e) {
            $error = "Error adding brand: " . $e->getMessage();
        }
    }
}

// Edit brand
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_brand'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $error = "Brand name is required.";
    } else {
        try {
            // Check if brand name already exists (excluding current brand)
            $check_stmt = $pdo->prepare("SELECT brand_id FROM brands WHERE name = ? AND brand_id != ?");
            $check_stmt->execute([$name, $brand_id]);
            
            if ($check_stmt->fetch()) {
                $error = "Brand '$name' already exists.";
            } else {
                // Handle logo upload
                $logo_path = $_POST['current_logo'] ?? null;
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/brands/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array(strtolower($file_extension), $allowed_extensions)) {
                        $filename = 'brand_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $logo_path = 'images/brands/' . $filename;
                        
                        if (move_uploaded_file($_FILES['logo']['tmp_name'], '../' . $logo_path)) {
                            // Delete old logo if exists
                            if (!empty($_POST['current_logo']) && file_exists('../' . $_POST['current_logo'])) {
                                unlink('../' . $_POST['current_logo']);
                            }
                        } else {
                            $error = "Failed to upload logo.";
                        }
                    } else {
                        $error = "Invalid file type. Allowed: JPG, JPEG, PNG, GIF, WEBP";
                    }
                }
                
                if (!$error) {
                    $stmt = $pdo->prepare("
                        UPDATE brands 
                        SET name = ?, description = ?, logo = ?, status = ? 
                        WHERE brand_id = ?
                    ");
                    $stmt->execute([$name, $description, $logo_path, $status, $brand_id]);
                    $message = "Brand '$name' updated successfully!";
                }
            }
        } catch (Exception $e) {
            $error = "Error updating brand: " . $e->getMessage();
        }
    }
}

// Delete brand
if ($action === 'delete' && $brand_id) {
    try {
        // Check if brand has products
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE brand_id = ?");
        $check_stmt->execute([$brand_id]);
        $product_count = $check_stmt->fetch()['product_count'];
        
        if ($product_count > 0) {
            $error = "Cannot delete brand with products. There are $product_count products from this brand.";
        } else {
            // Get brand info to delete logo
            $brand_stmt = $pdo->prepare("SELECT logo FROM brands WHERE brand_id = ?");
            $brand_stmt->execute([$brand_id]);
            $brand = $brand_stmt->fetch();
            
            // Delete logo file if exists
            if ($brand && $brand['logo'] && file_exists('../' . $brand['logo'])) {
                unlink('../' . $brand['logo']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM brands WHERE brand_id = ?");
            $stmt->execute([$brand_id]);
            $message = "Brand deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error deleting brand: " . $e->getMessage();
    }
}

// Toggle brand status
if ($action === 'toggle_status' && $brand_id) {
    try {
        $stmt = $pdo->prepare("UPDATE brands SET status = IF(status='active', 'inactive', 'active') WHERE brand_id = ?");
        $stmt->execute([$brand_id]);
        $message = "Brand status updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating brand status: " . $e->getMessage();
    }
}

// Get brands with product counts
$brands_sql = "
    SELECT 
        b.*,
        COUNT(p.product_id) as product_count,
        COALESCE(SUM(p.stock_quantity), 0) as total_stock,
        COALESCE(AVG(p.price), 0) as avg_price
    FROM brands b 
    LEFT JOIN products p ON b.brand_id = p.brand_id AND p.status = 'active'
    GROUP BY b.brand_id 
    ORDER BY b.name ASC
";

$brands = $pdo->query($brands_sql)->fetchAll();

// Get brand statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_brands,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_brands,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_brands,
        (SELECT COUNT(*) FROM products) as total_products,
        (SELECT COUNT(DISTINCT brand_id) FROM products WHERE brand_id IS NOT NULL) as brands_with_products
    FROM brands
")->fetch();
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
        .brands-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 0;
            margin: -20px -20px 30px -20px;
        }
        .brand-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #f8f9fa;
            transition: all 0.3s ease;
            height: 100%;
            text-align: center;
        }
        .brand-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #1da1f2;
        }
        .brand-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin: 0 auto 15px;
            padding: 10px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .brand-logo-placeholder {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 2rem;
        }
        .brand-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .brand-stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            display: block;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #7f8c8d;
            text-transform: uppercase;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .brand-actions .btn {
            padding: 6px 12px;
            margin: 2px;
            font-size: 0.8rem;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-3px);
        }
        .stats-card.total { border-left-color: #3498db; }
        .stats-card.active { border-left-color: #28a745; }
        .stats-card.inactive { border-left-color: #e74c3c; }
        .stats-card.products { border-left-color: #f39c12; }
        .stats-card.with-products { border-left-color: #6f42c1; }
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .logo-preview {
            max-width: 100px;
            max-height: 100px;
            margin: 10px auto;
            display: block;
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
        .file-upload.dragover {
            border-color: #1da1f2;
            background: #e3f2fd;
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
                            <i class="fas fa-trademark me-2"></i>Brands Management
                        </h3>
                        <p class="text-muted mb-0">Manage your phone and accessory brands</p>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                            <i class="fas fa-plus me-2"></i>Add New Brand
                        </button>
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

            <!-- Brand Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card total">
                        <div class="stats-number text-primary"><?php echo $stats['total_brands']; ?></div>
                        <div class="stats-label">Total Brands</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card active">
                        <div class="stats-number text-success"><?php echo $stats['active_brands']; ?></div>
                        <div class="stats-label">Active</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card inactive">
                        <div class="stats-number text-danger"><?php echo $stats['inactive_brands']; ?></div>
                        <div class="stats-label">Inactive</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card with-products">
                        <div class="stats-number text-purple"><?php echo $stats['brands_with_products']; ?></div>
                        <div class="stats-label">With Products</div>
                    </div>
                </div>
            </div>

            <!-- Brands Grid -->
            <div class="row">
                <?php if (empty($brands)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-trademark fa-3x text-muted mb-3"></i>
                        <h4>No Brands Found</h4>
                        <p class="text-muted mb-4">Start by adding your first phone brand.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
                            <i class="fas fa-plus me-2"></i>Add First Brand
                        </button>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($brands as $brand): ?>
                    <div class="col-xl-4 col-lg-6 mb-4">
                        <div class="brand-card">
                            <?php if ($brand['logo']): ?>
                                <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" class="brand-logo">
                            <?php else: ?>
                                <div class="brand-logo-placeholder">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="brand-name">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </div>
                            
                            <?php if ($brand['description']): ?>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($brand['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <span class="status-badge badge-<?php echo $brand['status']; ?>">
                                    <?php echo ucfirst($brand['status']); ?>
                                </span>
                            </div>
                            
                            <div class="brand-stats">
                                <div class="stat-item">
                                    <span class="stat-number text-primary"><?php echo $brand['product_count']; ?></span>
                                    <span class="stat-label">Products</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number text-success"><?php echo $brand['total_stock']; ?></span>
                                    <span class="stat-label">In Stock</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number text-warning">Rs. <?php echo number_format($brand['avg_price'], 2); ?></span>
                                    <span class="stat-label">Avg Price</span>
                                </div>
                            </div>
                            
                            <div class="brand-actions mt-3">
                                <a href="products.php?brand=<?php echo $brand['brand_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View Products">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        data-bs-toggle="modal" data-bs-target="#editBrandModal<?php echo $brand['brand_id']; ?>"
                                        title="Edit Brand">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="brands.php?action=toggle_status&id=<?php echo $brand['brand_id']; ?>" 
                                   class="btn btn-sm btn-outline-<?php echo $brand['status'] == 'active' ? 'warning' : 'success'; ?>" 
                                   title="<?php echo $brand['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $brand['brand_id']; ?>)" 
                                        class="btn btn-sm btn-outline-danger" title="Delete Brand">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Brand Modal -->
                    <div class="modal fade" id="editBrandModal<?php echo $brand['brand_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Brand - <?php echo htmlspecialchars($brand['name']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <input type="hidden" name="edit_brand" value="1">
                                    <input type="hidden" name="current_logo" value="<?php echo htmlspecialchars($brand['logo'] ?? ''); ?>">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand Name *</label>
                                                    <input type="text" class="form-control" name="name" 
                                                           value="<?php echo htmlspecialchars($brand['name']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($brand['description'] ?? ''); ?></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status">
                                                        <option value="active" <?php echo $brand['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo $brand['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Brand Logo</label>
                                                    <?php if ($brand['logo']): ?>
                                                    <div class="mb-2">
                                                        <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" alt="Current Logo" class="logo-preview">
                                                        <small class="text-muted d-block">Current logo</small>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="file-upload" onclick="document.getElementById('logo<?php echo $brand['brand_id']; ?>').click()">
                                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                        <p class="mb-1">Click to upload logo</p>
                                                        <small class="text-muted">JPG, PNG, GIF, WEBP (Max 2MB)</small>
                                                        <input type="file" id="logo<?php echo $brand['brand_id']; ?>" name="logo" class="d-none" accept="image/*">
                                                    </div>
                                                    <div id="fileInfo<?php echo $brand['brand_id']; ?>" class="mt-2"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Brand</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Brand Modal -->
    <div class="modal fade" id="addBrandModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="add_brand" value="1">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Brand Name *</label>
                                    <input type="text" class="form-control" name="name" placeholder="Enter brand name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="4" placeholder="Brand description (optional)"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Brand Logo</label>
                                    <div class="file-upload" onclick="document.getElementById('newLogo').click()">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-1">Click to upload logo</p>
                                        <small class="text-muted">JPG, PNG, GIF, WEBP (Max 2MB)</small>
                                        <input type="file" id="newLogo" name="logo" class="d-none" accept="image/*">
                                    </div>
                                    <div id="newFileInfo" class="mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Brand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function confirmDelete(brandId) {
        if (confirm('Are you sure you want to delete this brand? This action cannot be undone.')) {
            window.location.href = 'brands.php?action=delete&id=' + brandId;
        }
    }
    
    // Mobile menu functionality
    function toggleMobileMenu() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.mobile-overlay');
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    }
    
    // File upload handlers
    function setupFileUpload(inputId, infoId) {
        const input = document.getElementById(inputId);
        const info = document.getElementById(infoId);
        
        input.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                
                if (fileSize > 2) {
                    info.innerHTML = '<span class="text-danger">File too large! Max 2MB allowed.</span>';
                    this.value = '';
                } else {
                    info.innerHTML = `
                        <span class="text-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Selected: ${file.name} (${fileSize} MB)
                        </span>
                    `;
                }
            }
        });
    }
    
    // Initialize file uploads
    document.addEventListener('DOMContentLoaded', function() {
        <?php foreach ($brands as $brand): ?>
        setupFileUpload('logo<?php echo $brand['brand_id']; ?>', 'fileInfo<?php echo $brand['brand_id']; ?>');
        <?php endforeach; ?>
        setupFileUpload('newLogo', 'newFileInfo');
        
        // Auto-close modals on successful action
        <?php if ($message): ?>
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
        <?php endif; ?>
    });
    </script>
</body>
</html>