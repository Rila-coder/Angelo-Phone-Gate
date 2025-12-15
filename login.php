<?php
// login.php - User Login Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Login - Angelo Phone Gate";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (loginUser($email, $password)) {
            $success = 'Login successful! Redirecting...';
            
            // Redirect based on user role
            if (isAdmin() || isSuperAdmin()) {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
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
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .login-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #1da1f2;
            box-shadow: 0 0 0 3px rgba(29, 161, 242, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 161, 242, 0.4);
        }
        .forgot-password-link {
            color: #1da1f2;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .forgot-password-link:hover {
            text-decoration: underline;
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
            
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Content -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h3><i class="fas fa-sign-in-alt me-2"></i>Welcome Back</h3>
                <p class="mb-0">Sign in to your account</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                        <div class="text-end mt-2">
                            <a href="forgot-password.php" class="forgot-password-link">
                                <i class="fas fa-key me-1"></i>Forgot Password?
                            </a>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? 
                            <a href="register.php" class="text-primary text-decoration-none">Sign up here</a>
                        </p>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>Your login is secure and encrypted
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 Angelo Phone Gate. All rights reserved.</p>
        </div>
    </footer>

    <!-- Custom JS -->
    <script src="js/script.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>