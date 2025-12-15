<?php
// admin/categories.php - Categories Management
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Categories Management - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

// Handle category actions
$action = $_GET['action'] ?? '';
$category_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Add new category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $color = trim($_POST['color'] ?? '#1da1f2');
    
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        try {
            // Check if category already exists
            $check_stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ?");
            $check_stmt->execute([$name]);
            
            if ($check_stmt->fetch()) {
                $error = "Category '$name' already exists.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO categories (name, description, icon, color, status) 
                    VALUES (?, ?, ?, ?, 'active')
                ");
                $stmt->execute([$name, $description, $icon, $color]);
                $message = "Category '$name' added successfully!";
            }
        } catch (Exception $e) {
            $error = "Error adding category: " . $e->getMessage();
        }
    }
}

// Edit category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fa-tag');
    $color = trim($_POST['color'] ?? '#1da1f2');
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        try {
            // Check if category name already exists (excluding current category)
            $check_stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND category_id != ?");
            $check_stmt->execute([$name, $category_id]);
            
            if ($check_stmt->fetch()) {
                $error = "Category '$name' already exists.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET name = ?, description = ?, icon = ?, color = ?, status = ?, updated_at = NOW() 
                    WHERE category_id = ?
                ");
                $stmt->execute([$name, $description, $icon, $color, $status, $category_id]);
                $message = "Category '$name' updated successfully!";
            }
        } catch (Exception $e) {
            $error = "Error updating category: " . $e->getMessage();
        }
    }
}

// Delete category
if ($action === 'delete' && $category_id) {
    try {
        // Check if category has products
        $check_stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ?");
        $check_stmt->execute([$category_id]);
        $product_count = $check_stmt->fetch()['product_count'];
        
        if ($product_count > 0) {
            $error = "Cannot delete category with products. There are $product_count products in this category.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $message = "Category deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Toggle category status
if ($action === 'toggle_status' && $category_id) {
    try {
        $stmt = $pdo->prepare("UPDATE categories SET status = IF(status='active', 'inactive', 'active') WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $message = "Category status updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating category status: " . $e->getMessage();
    }
}

// Get categories with product counts
$categories_sql = "
    SELECT 
        c.*,
        COUNT(p.product_id) as product_count,
        COALESCE(SUM(p.stock_quantity), 0) as total_stock
    FROM categories c 
    LEFT JOIN products p ON c.category_id = p.category_id AND p.status = 'active'
    GROUP BY c.category_id 
    ORDER BY c.name ASC
";

$categories = $pdo->query($categories_sql)->fetchAll();

// Get category statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_categories,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_categories,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_categories,
        (SELECT COUNT(*) FROM products) as total_products
    FROM categories
")->fetch();

// Common FontAwesome icons for categories
$common_icons = [
    'fa-mobile-alt' => 'Mobile Phone',
    'fa-headphones' => 'Headphones', 
    'fa-charging-station' => 'Charger',
    'fa-tablet-alt' => 'Tablet',
    'fa-mobile' => 'Accessory',
    'fa-tag' => 'General',
    'fa-box' => 'Package',
    'fa-gift' => 'Gift',
    'fa-star' => 'Featured',
    'fa-bolt' => 'Fast',
    'fa-shield-alt' => 'Warranty',
    'fa-truck' => 'Delivery',
    'fa-home' => 'Home',
    'fa-briefcase' => 'Business'
];
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
        .categories-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 0;
            margin: -20px -20px 30px -20px;
        }
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #1da1f2;
            transition: all 0.3s ease;
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .category-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: white;
        }
        .category-name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
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
        .category-actions .btn {
            padding: 6px 12px;
            margin: 2px;
            font-size: 0.8rem;
        }
        .color-preview {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid #ddd;
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
        .icon-preview {
            font-size: 1.5rem;
            margin-right: 10px;
            color: #1da1f2;
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
                            <i class="fas fa-tags me-2"></i>Categories Management
                        </h3>
                        <p class="text-muted mb-0">Organize your product categories</p>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add New Category
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

            <!-- Category Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card total">
                        <div class="stats-number text-primary"><?php echo $stats['total_categories']; ?></div>
                        <div class="stats-label">Total Categories</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card active">
                        <div class="stats-number text-success"><?php echo $stats['active_categories']; ?></div>
                        <div class="stats-label">Active</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card inactive">
                        <div class="stats-number text-danger"><?php echo $stats['inactive_categories']; ?></div>
                        <div class="stats-label">Inactive</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="stats-card products">
                        <div class="stats-number text-warning"><?php echo $stats['total_products']; ?></div>
                        <div class="stats-label">Total Products</div>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="row">
                <?php if (empty($categories)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                        <h4>No Categories Found</h4>
                        <p class="text-muted mb-4">Start by adding your first product category.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add First Category
                        </button>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                    <div class="col-xl-4 col-lg-6 mb-4">
                        <div class="category-card">
                            <div class="category-icon" style="background: <?php echo $category['color']; ?>">
                                <i class="fas <?php echo $category['icon']; ?>"></i>
                            </div>
                            
                            <div class="category-name">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </div>
                            
                            <?php if ($category['description']): ?>
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="status-badge badge-<?php echo $category['status']; ?>">
                                    <?php echo ucfirst($category['status']); ?>
                                </span>
                                <span class="color-preview" style="background: <?php echo $category['color']; ?>"></span>
                            </div>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-number text-primary"><?php echo $category['product_count']; ?></span>
                                    <span class="stat-label">Products</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number text-success"><?php echo $category['total_stock']; ?></span>
                                    <span class="stat-label">In Stock</span>
                                </div>
                            </div>
                            
                            <div class="category-actions mt-3">
                                <a href="products.php?category=<?php echo $category['category_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" title="View Products">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal<?php echo $category['category_id']; ?>"
                                        title="Edit Category">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="categories.php?action=toggle_status&id=<?php echo $category['category_id']; ?>" 
                                   class="btn btn-sm btn-outline-<?php echo $category['status'] == 'active' ? 'warning' : 'success'; ?>" 
                                   title="<?php echo $category['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <button onclick="confirmDelete(<?php echo $category['category_id']; ?>)" 
                                        class="btn btn-sm btn-outline-danger" title="Delete Category">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal<?php echo $category['category_id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Category</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="">
                                    <input type="hidden" name="edit_category" value="1">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Category Name *</label>
                                            <input type="text" class="form-control" name="name" 
                                                   value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Icon</label>
                                                <select class="form-select" name="icon">
                                                    <?php foreach ($common_icons as $icon_value => $icon_label): ?>
                                                    <option value="<?php echo $icon_value; ?>" 
                                                        <?php echo $category['icon'] == $icon_value ? 'selected' : ''; ?>>
                                                        <i class="fas <?php echo $icon_value; ?> me-2"></i><?php echo $icon_label; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Color</label>
                                                <input type="color" class="form-control" name="color" 
                                                       value="<?php echo htmlspecialchars($category['color']); ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="active" <?php echo $category['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $category['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Category</button>
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

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="add_category" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" class="form-control" name="name" placeholder="Enter category name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Category description (optional)"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Icon</label>
                                <select class="form-select" name="icon">
                                    <?php foreach ($common_icons as $icon_value => $icon_label): ?>
                                    <option value="<?php echo $icon_value; ?>">
                                        <i class="fas <?php echo $icon_value; ?> me-2"></i><?php echo $icon_label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Color</label>
                                <input type="color" class="form-control" name="color" value="#1da1f2">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function confirmDelete(categoryId) {
        if (confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            window.location.href = 'categories.php?action=delete&id=' + categoryId;
        }
    }
    
    // Mobile menu functionality
    function toggleMobileMenu() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.mobile-overlay');
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    }
    
    // Auto-close modals on successful action
    <?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>