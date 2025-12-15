<?php
// register.php - User Registration Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Register - Angelo Phone Gate";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $result = registerUser($email, $password, $full_name, $phone);
        
        if ($result === true) {
            $success = 'Registration successful! You can now login.';
            // Auto-login after registration
            if (loginUser($email, $password)) {
                header('Location: index.php');
                exit;
            }
        } else {
            $error = $result;
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
        .register-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            padding: 20px 0;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .register-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .register-body {
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
        .btn-register {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 161, 242, 0.4);
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
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

    <!-- Register Content -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h3><i class="fas fa-user-plus me-2"></i>Create Account</h3>
                <p class="mb-0">Join Angelo Phone Gate today</p>
            </div>
            
            <div class="register-body">
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
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>" 
                                   placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo $_POST['phone'] ?? ''; ?>" 
                                   placeholder="Enter your phone number">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password (min. 6 characters)" required
                                   onkeyup="checkPasswordStrength(this.value)">
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-register">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-primary text-decoration-none">Sign in here</a>
                        </p>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>Your information is secure and protected
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
    
    <script>
    function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('passwordStrength');
        let strength = 0;
        
        if (password.length >= 6) strength += 25;
        if (password.match(/[a-z]+/)) strength += 25;
        if (password.match(/[A-Z]+/)) strength += 25;
        if (password.match(/[0-9]+/)) strength += 25;
        
        if (strength === 0) {
            strengthBar.style.width = '0%';
            strengthBar.style.background = '#dc3545';
        } else if (strength <= 25) {
            strengthBar.style.width = '25%';
            strengthBar.style.background = '#dc3545';
        } else if (strength <= 50) {
            strengthBar.style.width = '50%';
            strengthBar.style.background = '#ffc107';
        } else if (strength <= 75) {
            strengthBar.style.width = '75%';
            strengthBar.style.background = '#17a2b8';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.style.background = '#28a745';
        }
    }
    </script>
</body>
</html>