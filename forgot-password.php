<?php
// forgot-password.php - Forgot Password Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Forgot Password - Angelo Phone Gate";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $pdo = getDBConnection();
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Store email in session for verification in reset-password.php
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_time'] = time();
            
            $success = "Password reset instructions have been sent to your email.<br><br>
                       <strong>Note:</strong> For security reasons, please contact the administrator to reset your password, or use the temporary password reset feature below.";
        } else {
            $error = 'No account found with that email address.';
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
        .forgot-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
        }
        .forgot-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .forgot-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .forgot-body {
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
        .btn-reset {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 161, 242, 0.4);
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
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
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Forgot Password Content -->
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <h3><i class="fas fa-key me-2"></i>Reset Password</h3>
                <p class="mb-0">Enter your email to reset your password</p>
            </div>
            
            <div class="forgot-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
                
                <div class="info-box">
                    <h6><i class="fas fa-info-circle me-2"></i>Quick Reset Option</h6>
                    <p class="mb-2">You can proceed to reset your password immediately:</p>
                    <a href="reset-password.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>Reset Password Now
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                   placeholder="Enter your registered email" required>
                        </div>
                        <div class="form-text">
                            We'll verify your email and help you reset your password.
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-reset">
                            <i class="fas fa-paper-plane me-2"></i>Verify Email
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center">
                    <p class="mb-0">
                        <a href="login.php" class="text-primary text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </p>
                </div>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>Your security is our priority
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>