<?php
// admin/customers.php - Customers Management
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Customers Management - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

// Handle customer actions
$action = $_GET['action'] ?? '';
$customer_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Toggle customer status
if ($action === 'toggle_status' && $customer_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = IF(status='active', 'inactive', 'active') WHERE user_id = ?");
        $stmt->execute([$customer_id]);
        $message = "Customer status updated successfully.";
    } catch (Exception $e) {
        $error = "Error updating customer status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10; // Customers per page
$offset = ($page - 1) * $limit;

// Build customers query with filters
$where_conditions = ["u.role = 'customer'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Build sort order
$sort_orders = [
    'newest' => 'u.created_at DESC',
    'oldest' => 'u.created_at ASC',
    'name_asc' => 'u.full_name ASC',
    'name_desc' => 'u.full_name DESC',
    'orders_high' => 'total_orders DESC',
    'orders_low' => 'total_orders ASC',
    'spent_high' => 'total_spent DESC',
    'spent_low' => 'total_spent ASC'
];

$order_by = $sort_orders[$sort] ?? 'u.created_at DESC';

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM users u 
    WHERE $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_customers = $count_stmt->fetch()['total'];
$total_pages = ceil($total_customers / $limit);

// Get customers with their statistics - FIXED: Get data directly from orders table
$customers_sql = "
    SELECT 
        u.*,
        COALESCE(o.total_orders, 0) as total_orders,
        COALESCE(o.total_spent, 0) as total_spent,
        o.last_order_date,
        COALESCE(FLOOR(o.total_spent / 100), 0) as loyalty_points,
        COALESCE(o.completed_orders, 0) as completed_orders,
        COALESCE(o.all_orders, 0) as all_orders
    FROM users u 
    LEFT JOIN (
        SELECT 
            user_id,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            MAX(created_at) as last_order_date,
            SUM(CASE WHEN status IN ('delivered', 'shipped', 'confirmed', 'processing') THEN 1 ELSE 0 END) as completed_orders,
            COUNT(*) as all_orders
        FROM orders 
        WHERE user_id IS NOT NULL
        GROUP BY user_id
    ) o ON u.user_id = o.user_id
    WHERE $where_clause
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$customers_stmt = $pdo->prepare($customers_sql);
$customers_stmt->execute($params);
$customers = $customers_stmt->fetchAll();

// Get customer statistics for cards - FIXED: Get data directly from orders table
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_customers,
        COALESCE(SUM(o.total_orders), 0) as total_orders,
        COALESCE(SUM(o.total_spent), 0) as total_revenue,
        CASE 
            WHEN COUNT(*) > 0 THEN COALESCE(SUM(o.total_orders), 0) / COUNT(*)
            ELSE 0 
        END as avg_orders_per_customer,
        COUNT(CASE WHEN u.status = 'active' THEN 1 END) as active_customers,
        COUNT(CASE WHEN o.total_orders > 0 THEN 1 END) as repeat_customers,
        COUNT(CASE WHEN o.total_orders = 0 OR o.total_orders IS NULL THEN 1 END) as new_customers
    FROM users u 
    LEFT JOIN (
        SELECT 
            user_id,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent
        FROM orders 
        WHERE user_id IS NOT NULL
        GROUP BY user_id
    ) o ON u.user_id = o.user_id
    WHERE u.role = 'customer'
")->fetch();

// Debug: Check if statistics are working
error_log("Customer Stats - Total: " . $stats['total_customers'] . ", Orders: " . $stats['total_orders'] . ", Revenue: " . $stats['total_revenue']);
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
    /* ==========================
       CUSTOMERS PAGE STYLES
    ========================== */
    .customers-header {
        background: linear-gradient(135deg, #1da1f2, #0b82c0);
        color: white;
        padding: 30px 0;
        margin: -20px -20px 30px -20px;
    }

    .customer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1da1f2, #0b82c0);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .customer-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .badge-new { background: #d4edda; color: #155724; }
    .badge-regular { background: #cce7ff; color: #004085; }
    .badge-vip { background: #e6ccff; color: #4b0082; }

    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .badge-active { background: #d4edda; color: #155724; }
    .badge-inactive { background: #f8d7da; color: #721c24; }
    .badge-suspended { background: #fff3cd; color: #856404; }

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
    .stats-card.repeat { border-left-color: #6f42c1; }
    .stats-card.orders { border-left-color: #f39c12; }
    .stats-card.revenue { border-left-color: #e74c3c; }
    .stats-card.avg { border-left-color: #17a2b8; }

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

    .customer-actions .btn {
        padding: 4px 8px;
        margin: 2px;
        font-size: 0.8rem;
    }

    .loyalty-points {
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        color: #856404;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
    }

    /* ==========================
       RESPONSIVE FIXES - ENHANCED
    ========================== */

    /* Large Desktop */
    @media (min-width: 1200px) {
        .admin-content {
            padding: 30px;
        }
    }

    /* Tablet */
    @media (max-width: 991.98px) {
        .filter-card .row.g-3 {
            gap: 10px !important;
        }
        
        .filter-card .col-md-4,
        .filter-card .col-md-3,
        .filter-card .col-md-2 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }
        
        .customer-actions {
            display: flex !important;
            flex-direction: column !important;
            gap: 3px !important;
        }
        
        .customer-actions .btn {
            width: 100% !important;
            margin: 0 !important;
        }
        
        .stats-card {
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 1.5rem;
        }
    }

    /* Mobile */
    @media (max-width: 767.98px) {
        /* Hide columns progressively */
        .table thead th:nth-child(5),
        .table tbody td:nth-child(5),
        .table thead th:nth-child(6), 
        .table tbody td:nth-child(6) {
            display: none;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .stats-number {
            font-size: 1.3rem;
        }
        
        .stats-label {
            font-size: 0.8rem;
        }
        
        .admin-header .row {
            flex-direction: column;
            text-align: center;
        }
        
        .admin-header .col-auto {
            margin-top: 10px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575.98px) {
        .table thead th:nth-child(3),
        .table tbody td:nth-child(3),
        .table thead th:nth-child(4),
        .table tbody td:nth-child(4) {
            display: none;
        }
        
        .table h6 {
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        .customer-badge {
            font-size: 0.6rem;
            padding: 2px 6px;
        }
        
        .admin-content {
            padding: 10px;
        }
        
        .filter-card,
        .recent-table {
            padding: 15px;
        }
        
        /* Stats grid - 2 columns */
        .col-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 5px;
        }
    }

    /* Extra Small Mobile */
    @media (max-width: 399.98px) {
        .table thead th:nth-child(2),
        .table tbody td:nth-child(2) {
            display: none;
        }
        
        .table th,
        .table td {
            padding: 6px 3px;
        }
        
        .admin-content {
            padding: 5px;
        }
        
        .filter-card,
        .recent-table {
            padding: 10px;
        }
        
        /* Single column stats */
        .col-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        
        .mobile-menu-btn {
            top: 10px;
            left: 10px;
            padding: 6px 10px;
            font-size: 1rem;
        }
    }

    /* Force responsive table */
    .table-responsive {
        -webkit-overflow-scrolling: touch;
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
                            <i class="fas fa-users me-2"></i>Customers Management
                        </h3>
                        <p class="text-muted mb-0">Manage your valuable customers</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">
                            <i class="fas fa-chart-line me-1"></i>
                            Total Revenue: Rs. <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
                        </span>
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

            <!-- Customer Statistics -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card total">
                        <div class="stats-number text-primary"><?php echo $stats['total_customers']; ?></div>
                        <div class="stats-label">Total Customers</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card active">
                        <div class="stats-number text-success"><?php echo $stats['active_customers']; ?></div>
                        <div class="stats-label">Active</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card repeat">
                        <div class="stats-number text-purple"><?php echo $stats['repeat_customers']; ?></div>
                        <div class="stats-label">Repeat Buyers</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card orders">
                        <div class="stats-number text-warning"><?php echo $stats['total_orders']; ?></div>
                        <div class="stats-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card revenue">
                        <div class="stats-number text-danger">Rs.
                            <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?>
                        </div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card avg">
                        <div class="stats-number text-info">
                            <?php echo number_format($stats['avg_orders_per_customer'], 1); ?>
                        </div>
                        <div class="stats-label">Avg Orders/Customer</div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stats-card" style="border-left-color: #20c997;">
                        <div class="stats-number" style="color: #20c997;"><?php echo $stats['new_customers']; ?></div>
                        <div class="stats-label">New Customers (No Orders)</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card" style="border-left-color: #fd7e14;">
                        <div class="stats-number" style="color: #fd7e14;">
                            <?php echo $stats['total_customers'] > 0 ? round(($stats['repeat_customers'] / $stats['total_customers']) * 100, 1) : 0; ?>%
                        </div>
                        <div class="stats-label">Repeat Customer Rate</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stats-card" style="border-left-color: #6f42c1;">
                        <div class="stats-number" style="color: #6f42c1;">
                            Rs. <?php echo $stats['total_customers'] > 0 ? number_format($stats['total_revenue'] / $stats['total_customers'], 2) : 0; ?>
                        </div>
                        <div class="stats-label">Avg Revenue/Customer</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Customer Filters</h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" placeholder="Search customers..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active
                                </option>
                                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive
                                </option>
                                <option value="suspended" <?php echo $status == 'suspended' ? 'selected' : ''; ?>>
                                    Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First
                                </option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First
                                </option>
                                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z
                                </option>
                                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to
                                    A</option>
                                <option value="orders_high" <?php echo $sort == 'orders_high' ? 'selected' : ''; ?>>Most
                                    Orders</option>
                                <option value="orders_low" <?php echo $sort == 'orders_low' ? 'selected' : ''; ?>>Fewest
                                    Orders</option>
                                <option value="spent_high" <?php echo $sort == 'spent_high' ? 'selected' : ''; ?>>Highest
                                    Spenders</option>
                                <option value="spent_low" <?php echo $sort == 'spent_low' ? 'selected' : ''; ?>>Lowest
                                    Spenders</option>
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

            <!-- Customers Table -->
            <div class="recent-table">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Customers (<?php echo $total_customers; ?>)
                    </h5>
                    <div>
                        <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Loyalty</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-users fa-2x mb-2"></i><br>
                                        No customers found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer):
                                    $initial = strtoupper(substr($customer['full_name'], 0, 1));
                                    $customer_type = $customer['total_orders'] == 0 ? 'new' :
                                        ($customer['total_orders'] >= 5 ? 'vip' : 'regular');
                                    $type_labels = [
                                        'new' => ['class' => 'badge-new', 'text' => 'New'],
                                        'regular' => ['class' => 'badge-regular', 'text' => 'Regular'],
                                        'vip' => ['class' => 'badge-vip', 'text' => 'VIP']
                                    ];
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="customer-avatar me-3">
                                                    <?php echo $initial; ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($customer['full_name']); ?>
                                                    </h6>
                                                    <span
                                                        class="<?php echo $type_labels[$customer_type]['class']; ?> customer-badge">
                                                        <?php echo $type_labels[$customer_type]['text']; ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">
                                                        Joined:
                                                        <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                                <?php if ($customer['phone']): ?>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars($customer['phone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <strong><?php echo $customer['total_orders'] ?? 0; ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $customer['completed_orders'] ?? 0; ?> completed
                                                </small>
                                                <?php if ($customer['last_order_date']): ?>
                                                    <br><small class="text-muted">
                                                        Last: <?php echo date('M j', strtotime($customer['last_order_date'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>Rs. <?php echo number_format($customer['total_spent'] ?? 0, 2); ?></strong>
                                            <?php if ($customer['total_orders'] > 0): ?>
                                                <br><small class="text-muted">
                                                    Avg: Rs.
                                                    <?php echo number_format(($customer['total_spent'] ?? 0) / ($customer['total_orders'] ?? 1), 2); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($customer['loyalty_points'] > 0): ?>
                                                <div class="loyalty-points">
                                                    <i class="fas fa-star me-1"></i><?php echo $customer['loyalty_points']; ?> pts
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted">No points</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge badge-<?php echo $customer['status']; ?>">
                                                <?php echo ucfirst($customer['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="customer-actions">
                                                <a href="customer-details.php?id=<?php echo $customer['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="orders.php?search=<?php echo urlencode($customer['email']); ?>"
                                                    class="btn btn-sm btn-outline-info" title="View Orders">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </a>
                                                <a href="customers.php?action=toggle_status&id=<?php echo $customer['user_id']; ?>"
                                                    class="btn btn-sm btn-outline-<?php echo $customer['status'] == 'active' ? 'warning' : 'success'; ?>"
                                                    title="<?php echo $customer['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </a>
                                                <a href="mailto:<?php echo htmlspecialchars($customer['email']); ?>"
                                                    class="btn btn-sm btn-outline-secondary" title="Send Email">
                                                    <i class="fas fa-envelope"></i>
                                                </a>
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
                        <nav aria-label="Customers pagination">
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
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        // Enhanced mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            const body = document.querySelector('body');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
        }

        // Close menu when clicking on links
        document.querySelectorAll('.sidebar-menu .nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    toggleMobileMenu();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.querySelector('.admin-sidebar');
                const overlay = document.querySelector('.mobile-overlay');
                const body = document.querySelector('body');
                
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                body.style.overflow = '';
            }
        });

        // Pagination helper
        function buildPaginationUrl(page) {
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            return 'customers.php?' + params.toString();
        }
    </script>
</body>

</html>

<?php
// Helper function for pagination URLs
function buildPaginationUrl($page)
{
    $params = $_GET;
    $params['page'] = $page;
    return 'customers.php?' . http_build_query($params);
}
?>