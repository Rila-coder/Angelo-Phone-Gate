<?php
// admin/index.php - Admin Dashboard WITH CHARTS
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Dashboard - Angelo Phone Gate Admin";

// Get dashboard statistics
$pdo = getDBConnection();

// Total Products
$total_products = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch()['count'];

// Total Orders
$total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];

// Total Customers
$total_customers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch()['count'];

// Total Revenue
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE payment_status = 'paid'")->fetch()['total'];

// Recent Orders (last 5)
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Low Stock Products
$low_stock_products = $pdo->query("
    SELECT * FROM products 
    WHERE stock_quantity <= min_stock AND status = 'active' 
    ORDER BY stock_quantity ASC 
    LIMIT 5
")->fetchAll();

// Recent Customers
$recent_customers = $pdo->query("
    SELECT u.*, c.total_orders 
    FROM users u 
    LEFT JOIN customers c ON u.user_id = c.user_id 
    WHERE u.role = 'customer' 
    ORDER BY u.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get sales data for the chart (last 6 months)
$sales_data = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COALESCE(SUM(total_amount), 0) as revenue,
        COUNT(*) as orders
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    AND payment_status = 'paid'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

// Prepare data for JavaScript
$chart_months = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($sales_data as $data) {
    $chart_months[] = date('M Y', strtotime($data['month'] . '-01'));
    $chart_revenue[] = (float) $data['revenue'];
    $chart_orders[] = (int) $data['orders'];
}

// If no sales data, create empty arrays to prevent JavaScript errors
if (empty($chart_months)) {
    $chart_months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    $chart_revenue = [0, 0, 0, 0, 0, 0];
    $chart_orders = [0, 0, 0, 0, 0, 0];
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
    <link rel="stylesheet" href="css/admin-styles.css">

    <style>
        /* .admin-container {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .admin-sidebar {
            background: #2c3e50;
            color: white;
            min-height: 100vh;
            padding: 0;
            position: fixed;
            width: 250px;
        }

        .admin-content {
            margin-left: 250px;
            padding: 20px;
        }

        .admin-header {
            background: white;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            margin: -20px -20px 20px -20px;
        }

        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
            background: #1a252f;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: white;
            background: #34495e;
            border-left-color: #3498db;
        }

        .sidebar-menu .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.products {
            border-left-color: #e74c3c;
        }

        .stat-card.orders {
            border-left-color: #f39c12;
        }

        .stat-card.customers {
            border-left-color: #9b59b6;
        }

        .stat-card.revenue {
            border-left-color: #27ae60;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-icon {
            font-size: 2rem;
            opacity: 0.7;
            margin-bottom: 15px;
        }

        .recent-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-processing {
            background: #cce7ff;
            color: #004085;
        }

        .badge-shipped {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-delivered {
            background: #d4edda;
            color: #155724;
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        /* === RESPONSIVE DESIGN === */
        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                left: -250px;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .admin-sidebar.mobile-open {
                left: 0;
            }

            .admin-content {
                margin-left: 0;
                padding: 15px;
            }

            .mobile-menu-btn {
                display: block !important;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: #3498db;
                border: none;
                color: white;
                border-radius: 5px;
                padding: 10px 15px;
                font-size: 1.2rem;
            }

            .admin-header {
                margin: 0;
                padding: 60px 15px 15px 15px;
            }

            .stat-card {
                padding: 15px;
                margin-bottom: 15px;
            }

            .stat-number {
                font-size: 2rem;
            }

            .stat-icon {
                font-size: 1.5rem;
            }

            .chart-container {
                padding: 15px;
                margin-bottom: 15px;
            }

            .recent-table {
                margin-bottom: 15px;
            }

            .table-responsive {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .admin-content {
                padding: 10px;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .mobile-menu-btn {
                top: 10px;
                left: 10px;
                padding: 8px 12px;
            }
        }

        /* Mobile Menu Button (hidden on desktop) */
        .mobile-menu-btn {
            display: none;
        }

        /* Overlay for mobile menu */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .mobile-overlay.active {
            display: block;
        } */
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Header -->
            <!-- Header -->
            <div class="admin-header">
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </h3>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo ucfirst($_SESSION['role']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Overlay -->
            <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card products">
                        <div class="stat-icon text-danger">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-number text-danger"><?php echo $total_products; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card orders">
                        <div class="stat-icon text-warning">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-number text-warning"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card customers">
                        <div class="stat-icon text-purple">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number text-purple"><?php echo $total_customers; ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card revenue">
                        <div class="stat-icon text-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-number text-success">Rs. <?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
            </div>

            <!-- Sales Chart Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="chart-container">
                        <h5 class="mb-4">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Sales Analytics (Last 6 Months)
                        </h5>
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Low Stock -->
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-8 mb-4">
                    <div class="recent-table">
                        <div class="p-3 border-bottom bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Orders
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                                No orders yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><strong>#<?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo htmlspecialchars($order['full_name'] ?? 'Guest'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge badge-<?php echo $order['status']; ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Low Stock & Recent Customers -->
                <div class="col-lg-4">
                    <!-- Low Stock Alert -->
                    <div class="recent-table mb-4">
                        <div class="p-3 border-bottom bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php if (empty($low_stock_products)): ?>
                                <div class="list-group-item text-center text-muted py-3">
                                    <i class="fas fa-check-circle text-success mb-2"></i><br>
                                    All products in stock
                                </div>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="text-muted">SKU: <?php echo $product['sku']; ?></small>
                                            </div>
                                            <span class="badge bg-danger"><?php echo $product['stock_quantity']; ?> left</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Customers -->
                    <div class="recent-table">
                        <div class="p-3 border-bottom bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-plus me-2"></i>Recent Customers
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php if (empty($recent_customers)): ?>
                                <div class="list-group-item text-center text-muted py-3">
                                    <i class="fas fa-users fa-2x mb-2"></i><br>
                                    No customers yet
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_customers as $customer): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($customer['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo $customer['email']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="recent-table">
                        <div class="p-3 border-bottom bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="p-3">
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                                    <a href="products.php?action=add" class="btn btn-primary w-100 py-3">
                                        <i class="fas fa-plus me-2"></i>Add Product
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="orders.php" class="btn btn-success w-100 py-3">
                                        <i class="fas fa-eye me-2"></i>View Orders
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="customers.php" class="btn btn-info w-100 py-3">
                                        <i class="fas fa-users me-2"></i>Manage Customers
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="settings.php" class="btn btn-warning w-100 py-3">
                                        <i class="fas fa-cogs me-2"></i>Settings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Sales Trend Chart
        const salesChart = new Chart(
            document.getElementById('salesChart'),
            {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_months); ?>,
                    datasets: [
                        {
                            label: 'Revenue (Rs)',
                            data: <?php echo json_encode($chart_revenue); ?>,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Orders',
                            data: <?php echo json_encode($chart_orders); ?>,
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue (Rs)'
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Number of Orders'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Sales Performance Trend'
                        }
                    }
                }
            }
        );

        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }

        // Close menu when clicking on menu items (mobile)
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
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            }
        });

        // Auto refresh dashboard every 30 seconds
        setTimeout(function () {
            window.location.reload();
        }, 30000);
    </script>
</body>

</html>