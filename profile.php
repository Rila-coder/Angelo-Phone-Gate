<?php
// profile.php - User Profile Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "My Profile - Angelo Phone Gate";

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Full name is required.';
    } else {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$full_name, $phone, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['full_name'] = $full_name;
            
            $success = 'Profile updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        $pdo = getDBConnection();
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                
                $success = 'Password changed successfully!';
            } else {
                $error = 'Current password is incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Error changing password: ' . $e->getMessage();
        }
    }
}

// Get user data
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user orders
$orders_stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.order_item_id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.order_id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.order_id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$orders_stmt->execute([$_SESSION['user_id']]);
$recent_orders = $orders_stmt->fetchAll();
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
        .profile-container {
            background: #f8f9fa;
            min-height: 80vh;
            padding: 30px 0;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2.5rem;
        }
        .profile-body {
            padding: 30px;
        }
        .nav-pills .nav-link {
            border-radius: 8px;
            margin-bottom: 5px;
            padding: 12px 20px;
            color: #333;
            transition: all 0.3s ease;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background: #f8f9fa;
        }
        .order-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
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
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="profile.php #order-history"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Profile Content -->
    <div class="profile-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <!-- Profile Sidebar -->
                    <div class="profile-card">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                            <small>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></small>
                        </div>
                        
                        <div class="profile-body">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#personal-info" data-bs-toggle="tab">
                                        <i class="fas fa-user me-2"></i>Personal Information
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#change-password" data-bs-toggle="tab">
                                        <i class="fas fa-lock me-2"></i>Change Password
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#order-history" data-bs-toggle="tab">
                                        <i class="fas fa-shopping-bag me-2"></i>Order History
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-8">
                    <!-- Profile Content -->
                    <div class="tab-content">
                        <!-- Personal Information Tab -->
                        <div class="tab-pane fade show active" id="personal-info">
                            <div class="profile-card">
                                <div class="profile-body">
                                    <h4 class="mb-4"><i class="fas fa-user me-2"></i>Personal Information</h4>
                                    
                                    <?php if ($error && isset($_POST['update_profile'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success && isset($_POST['update_profile'])): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <input type="hidden" name="update_profile" value="1">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="full_name" class="form-label">Full Name</label>
                                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" 
                                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                                <small class="text-muted">Email cannot be changed</small>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Account Type</label>
                                                <input type="text" class="form-control" 
                                                       value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" disabled>
                                            </div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="change-password">
                            <div class="profile-card">
                                <div class="profile-body">
                                    <h4 class="mb-4"><i class="fas fa-lock me-2"></i>Change Password</h4>
                                    
                                    <?php if ($error && isset($_POST['change_password'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success && isset($_POST['change_password'])): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <input type="hidden" name="change_password" value="1">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                                   placeholder="Minimum 6 characters" required>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-key me-2"></i>Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order History Tab -->
                        <div class="tab-pane fade" id="order-history">
                            <div class="profile-card">
                                <div class="profile-body">
                                    <h4 class="mb-4"><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h4>
                                    
                                    <?php if (empty($recent_orders)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                        <h5>No orders yet</h5>
                                        <p class="text-muted">You haven't placed any orders yet.</p>
                                        <a href="products.php" class="btn btn-primary">Start Shopping</a>
                                    </div>
                                    <?php else: ?>
                                    
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['order_number']; ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td><?php echo $order['item_count']; ?> items</td>
                                                    <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?php echo $order['status'] === 'delivered' ? 'bg-success' : 
                                                                  ($order['status'] === 'processing' ? 'bg-warning' : 'bg-info'); ?> order-badge">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">View</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="text-center mt-3">
                                        <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                                    </div>
                                    
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
    
    <script>
    // Activate tab based on URL hash
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        if (hash) {
            const trigger = document.querySelector(`a[href="${hash}"]`);
            if (trigger) {
                new bootstrap.Tab(trigger).show();
            }
        }
    });
    </script>
</body>
</html>