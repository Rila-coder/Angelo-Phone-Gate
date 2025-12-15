<?php
// admin/login.php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$page_title = "Admin Login - Angelo Phone Gate";

// Redirect if already logged in as admin
if (isset($_SESSION['user_id']) && (isAdmin() || isSuperAdmin())) {
    header('Location: index.php');
    exit;
}

$error = '';

// Handle admin login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (loginUser($email, $password)) {
            // Check if user has admin privileges
            if (isAdmin() || isSuperAdmin()) {
                $success = 'Login successful! Redirecting to dashboard...';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Access denied. Admin privileges required.';
                // Logout regular users
                session_destroy();
                session_start();
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
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
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            padding: 20px;
        }
        .admin-login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            border: none;
        }
        .admin-login-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .admin-logo {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        .admin-login-body {
            padding: 40px 30px;
        }
        .btn-admin-login {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-admin-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(29, 161, 242, 0.4);
        }
        .admin-features {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        .feature-item i {
            color: #1da1f2;
            margin-right: 10px;
            width: 20px;
        }
    </style>
</head>
<body>
    <!-- Admin Login Content -->
    <div class="admin-login-container">
        <div class="admin-login-card">
            <div class="admin-login-header">
                <div class="admin-logo">
                    <i class="fas fa-cog"></i>
                </div>
                <h3><i class="fas fa-shield-alt me-2"></i>Admin Access</h3>
                <p class="mb-0">Angelo Phone Gate Control Panel</p>
            </div>
            
            <div class="admin-login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user-shield"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                   placeholder="Enter admin email" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-admin-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Access Admin Panel
                        </button>
                    </div>
                </form>
                
                <div class="admin-features">
                    <h6 class="mb-3"><i class="fas fa-tachometer-alt me-2"></i>Admin Features:</h6>
                    <div class="feature-item">
                        <i class="fas fa-box"></i>Product Management
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-shopping-cart"></i>Order Processing
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-users"></i>Customer Management
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-chart-bar"></i>Sales Analytics
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Back to Store
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>