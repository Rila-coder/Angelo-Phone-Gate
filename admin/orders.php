<?php
// admin/orders.php - Orders Management (UPDATED VERSION)
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Orders Management - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

// Handle order actions
$action = $_GET['action'] ?? '';
$order_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Update order status
if ($action === 'update_status' && $order_id && isset($_POST['status'])) {
    $new_status = $_POST['status'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        $message = "Order status updated to " . ucfirst($new_status) . " successfully.";
    } catch (Exception $e) {
        $error = "Error updating order status: " . $e->getMessage();
    }
}

// Update payment status
if ($action === 'update_payment' && $order_id && isset($_POST['payment_status'])) {
    $new_payment_status = $_POST['payment_status'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->execute([$new_payment_status, $order_id]);
        
        // If marking as paid and it's cash/bank transfer, update the payments table too
        if ($new_payment_status === 'paid') {
            $check_payment = $pdo->prepare("SELECT payment_id FROM payments WHERE order_id = ?");
            $check_payment->execute([$order_id]);
            
            if (!$check_payment->fetch()) {
                // Insert payment record for cash/bank transfers
                $order_stmt = $pdo->prepare("SELECT payment_method, total_amount FROM orders WHERE order_id = ?");
                $order_stmt->execute([$order_id]);
                $order = $order_stmt->fetch();
                
                $payment_stmt = $pdo->prepare("
                    INSERT INTO payments (order_id, payment_method, amount, status, payment_details)
                    VALUES (?, ?, ?, 'completed', ?)
                ");
                
                $payment_details = json_encode([
                    'method' => $order['payment_method'],
                    'marked_paid' => date('Y-m-d H:i:s')
                ]);
                
                $payment_stmt->execute([
                    $order_id, 
                    $order['payment_method'], 
                    $order['total_amount'], 
                    $payment_details
                ]);
            }
        }
        
        $message = "Payment status updated to " . ucfirst($new_payment_status) . " successfully.";
    } catch (Exception $e) {
        $error = "Error updating payment status: " . $e->getMessage();
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10; // Orders per page
$offset = ($page - 1) * $limit;

// Build orders query with filters
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($status)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($payment_status)) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = $count_stmt->fetch()['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders with customer info
$orders_sql = "
    SELECT o.*, u.full_name, u.email, u.phone,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE $where_clause
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
";

$orders_stmt = $pdo->prepare($orders_sql);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

// 🚨 FIXED: Get order statistics for cards - COUNT ALL ORDERS (including filtered ones)
$stats_where_conditions = ["1=1"];
$stats_params = [];

if (!empty($search)) {
    $stats_where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $stats_params[] = $search_term;
    $stats_params[] = $search_term;
    $stats_params[] = $search_term;
}

if (!empty($date_from)) {
    $stats_where_conditions[] = "DATE(o.created_at) >= ?";
    $stats_params[] = $date_from;
}

if (!empty($date_to)) {
    $stats_where_conditions[] = "DATE(o.created_at) <= ?";
    $stats_params[] = $date_to;
}

$stats_where_clause = implode(' AND ', $stats_where_conditions);

// Get statistics based on current filters (excluding status and payment_status filters for stats)
$stats_sql = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
        COALESCE(SUM(CASE WHEN o.status = 'confirmed' THEN 1 ELSE 0 END), 0) as confirmed_orders,
        COALESCE(SUM(CASE WHEN o.status = 'processing' THEN 1 ELSE 0 END), 0) as processing_orders,
        COALESCE(SUM(CASE WHEN o.status = 'shipped' THEN 1 ELSE 0 END), 0) as shipped_orders,
        COALESCE(SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
        COALESCE(SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders,
        -- FIXED REVENUE: Count ALL orders (except cancelled) for total revenue
        COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) as total_revenue,
        -- Additional revenue breakdown
        COALESCE(SUM(CASE WHEN o.payment_status = 'paid' AND o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) as paid_revenue,
        COALESCE(SUM(CASE WHEN o.payment_status = 'pending' AND o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) as pending_revenue,
        COALESCE(SUM(CASE WHEN o.status = 'cancelled' THEN o.total_amount ELSE 0 END), 0) as cancelled_revenue
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE $stats_where_clause
";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch();

// Calculate today's revenue (all non-cancelled orders with current filters)
$today_sql = "
    SELECT COALESCE(SUM(o.total_amount), 0) as today_revenue 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE DATE(o.created_at) = CURDATE() AND o.status != 'cancelled'
    AND $stats_where_clause
";
$today_stmt = $pdo->prepare($today_sql);
$today_stmt->execute($stats_params);
$today_revenue = $today_stmt->fetch()['today_revenue'];

// Calculate this month's revenue (all non-cancelled orders with current filters)
$month_sql = "
    SELECT COALESCE(SUM(o.total_amount), 0) as month_revenue 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE YEAR(o.created_at) = YEAR(CURDATE()) 
    AND MONTH(o.created_at) = MONTH(CURDATE())
    AND o.status != 'cancelled'
    AND $stats_where_clause
";
$month_stmt = $pdo->prepare($month_sql);
$month_stmt->execute($stats_params);
$month_revenue = $month_stmt->fetch()['month_revenue'];
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
        .orders-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 0;
            margin: -20px -20px 30px -20px;
        }
        .order-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #cce7ff; color: #004085; }
        .badge-processing { background: #d1ecf1; color: #0c5460; }
        .badge-shipped { background: #d4edda; color: #155724; }
        .badge-delivered { background: #28a745; color: white; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        
        .payment-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-paid { background: #d4edda; color: #155724; }
        .badge-pending-payment { background: #fff3cd; color: #856404; }
        .badge-failed { background: #f8d7da; color: #721c24; }
        .badge-refunded { background: #e2e3e5; color: #383d41; }
        
        .order-actions .btn {
            padding: 4px 8px;
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
        .stats-card.pending { border-left-color: #f39c12; }
        .stats-card.confirmed { border-left-color: #17a2b8; }
        .stats-card.processing { border-left-color: #6f42c1; }
        .stats-card.shipped { border-left-color: #ffc107; }
        .stats-card.delivered { border-left-color: #28a745; }
        .stats-card.revenue { border-left-color: #e74c3c; }
        .stats-card.today { border-left-color: #20c997; }
        .stats-card.month { border-left-color: #fd7e14; }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #7f8c8d;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .revenue-breakdown {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .revenue-item {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 3px 0;
        }
        
        .date-inputs .form-control {
            font-size: 0.9rem;
        }
        
        .filter-active {
            background-color: #e7f3ff !important;
            border-color: #1da1f2 !important;
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
                            <i class="fas fa-shopping-cart me-2"></i>Orders Management
                        </h3>
                        <p class="text-muted mb-0">Manage customer orders and tracking</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-success">
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

            <!-- Order Statistics -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card total">
                        <div class="stats-number text-primary"><?php echo $stats['total_orders']; ?></div>
                        <div class="stats-label">Total Orders</div>
                        <?php if ($search || $date_from || $date_to): ?>
                        <small class="text-muted mt-1">Filtered</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card pending">
                        <div class="stats-number text-warning"><?php echo $stats['pending_orders']; ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card confirmed">
                        <div class="stats-number text-info"><?php echo $stats['confirmed_orders']; ?></div>
                        <div class="stats-label">Confirmed</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card shipped">
                        <div class="stats-number" style="color: #ffc107;"><?php echo $stats['shipped_orders']; ?></div>
                        <div class="stats-label">Shipped</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card delivered">
                        <div class="stats-number text-success"><?php echo $stats['delivered_orders']; ?></div>
                        <div class="stats-label">Delivered</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6 mb-3">
                    <div class="stats-card revenue">
                        <div class="stats-number text-danger">Rs. <?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                        <div class="stats-label">Total Revenue</div>
                        <div class="revenue-breakdown">
                            <div class="revenue-item">
                                <span>Paid:</span>
                                <span class="text-success">Rs. <?php echo number_format($stats['paid_revenue'] ?? 0, 2); ?></span>
                            </div>
                            <div class="revenue-item">
                                <span>Pending:</span>
                                <span class="text-warning">Rs. <?php echo number_format($stats['pending_revenue'] ?? 0, 2); ?></span>
                            </div>
                            <div class="revenue-item">
                                <span>Cancelled:</span>
                                <span class="text-danger">Rs. <?php echo number_format($stats['cancelled_revenue'] ?? 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Revenue Stats -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stats-card today">
                        <div class="stats-number" style="color: #20c997;">Rs. <?php echo number_format($today_revenue, 2); ?></div>
                        <div class="stats-label">Today's Revenue</div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stats-card month">
                        <div class="stats-number" style="color: #fd7e14;">Rs. <?php echo number_format($month_revenue, 2); ?></div>
                        <div class="stats-label">This Month Revenue</div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-card">
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Order Filters</h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" class="form-control <?php echo $search ? 'filter-active' : ''; ?>" name="search" placeholder="Search orders..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select <?php echo $status ? 'filter-active' : ''; ?>" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select <?php echo $payment_status ? 'filter-active' : ''; ?>" name="payment_status">
                                <option value="">All Payment</option>
                                <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $payment_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="failed" <?php echo $payment_status == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $payment_status == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-2 date-inputs">
                            <input type="date" class="form-control <?php echo $date_from ? 'filter-active' : ''; ?>" name="date_from" placeholder="From Date"
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2 date-inputs">
                            <input type="date" class="form-control <?php echo $date_to ? 'filter-active' : ''; ?>" name="date_to" placeholder="To Date"
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <?php if ($search || $status || $payment_status || $date_from || $date_to): ?>
                    <div class="mt-3">
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                        <small class="text-muted ms-2">
                            Showing filtered results
                        </small>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="recent-table">
                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Orders (<?php echo $total_orders; ?>)
                    </h5>
                    <div>
                        <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                    No orders found
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong>#<?php echo $order['order_number']; ?></strong>
                                        <?php if ($order['notes']): ?>
                                            <br><small class="text-muted" title="<?php echo htmlspecialchars($order['notes']); ?>">
                                                <i class="fas fa-sticky-note"></i> Has notes
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></strong>
                                            <?php if ($order['email']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            <?php endif; ?>
                                            <?php if ($order['phone']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($order['phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $order['item_count']; ?> items</span>
                                    </td>
                                    <td>
                                        <strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php 
                                            $subtotal = $order['total_amount'] - $order['shipping_cost'] - $order['tax_amount'];
                                            echo 'Sub: Rs. ' . number_format($subtotal, 2); 
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="order-badge badge-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="payment-badge badge-<?php echo $order['payment_status']; ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></small>
                                    </td>
                                    <td>
                                        <div class="order-actions">
                                            <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['order_id']; ?>"
                                                    title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $order['order_id']; ?>"
                                                    title="Update Payment">
                                                <i class="fas fa-credit-card"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Status Update Modal -->
                                <div class="modal fade" id="statusModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Order Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="?action=update_status&id=<?php echo $order['order_id']; ?>">
                                                <div class="modal-body">
                                                    <p><strong>Order #<?php echo $order['order_number']; ?></strong></p>
                                                    <p>Customer: <?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></p>
                                                    <select class="form-select" name="status" required>
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Status Modal -->
                                <div class="modal fade" id="paymentModal<?php echo $order['order_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Payment Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="?action=update_payment&id=<?php echo $order['order_id']; ?>">
                                                <div class="modal-body">
                                                    <p><strong>Order #<?php echo $order['order_number']; ?></strong></p>
                                                    <p>Customer: <?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></p>
                                                    <p>Amount: <strong>Rs. <?php echo number_format($order['total_amount'], 2); ?></strong></p>
                                                    <p>Current Method: <strong><?php echo ucfirst($order['payment_method'] ?? 'N/A'); ?></strong></p>
                                                    <select class="form-select" name="payment_status" required>
                                                        <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                        <option value="refunded" <?php echo $order['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                                    </select>
                                                    <div class="form-text mt-2">
                                                        <i class="fas fa-info-circle"></i> Marking as "Paid" will create a payment record if it doesn't exist.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Payment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Orders pagination">
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
    function buildPaginationUrl(page) {
        const params = new URLSearchParams(window.location.search);
        params.set('page', page);
        return 'orders.php?' + params.toString();
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

<?php
// Helper function for pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'orders.php?' . http_build_query($params);
}
?>