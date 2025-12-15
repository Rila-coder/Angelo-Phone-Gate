<?php
// reset-password.php - Reset Password Page
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Reset Password - Angelo Phone Gate";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Check if user came from forgot-password process
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_time'])) {
    header('Location: forgot-password.php');
    exit;
}

// Check if reset session is expired (15 minutes)
if (time() - $_SESSION['reset_time'] > 900) { // 15 minutes
    unset($_SESSION['reset_email']);
    unset($_SESSION['reset_time']);
    header('Location: forgot-password.php?error=expired');
    exit;
}

$email = $_SESSION['reset_email'];

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $pdo = getDBConnection();
        
        try {
            // Update password in database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);
            
            // Clear reset session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_time']);
            
            $success = 'Password reset successfully! You can now login with your new password.';
            
            // Redirect to login page after 3 seconds
            header('Refresh: 3; URL=login.php');
            
        } catch (Exception $e) {
            $error = 'Error resetting password: ' . $e->getMessage();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .reset-container {
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .reset-header {
            background: linear-gradient(135deg, #1da1f2, #0b82c0);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .reset-body {
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
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .user-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
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

    <!-- Reset Password Content -->
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h3><i class="fas fa-key me-2"></i>Set New Password</h3>
                <p class="mb-0">Create your new password</p>
            </div>
            
            <div class="reset-body">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <div class="mt-2">
                        <small>Redirecting to login page...</small>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <div class="user-info">
                    <i class="fas fa-user-circle me-2"></i>
                    <strong>Resetting password for:</strong> <?php echo htmlspecialchars($email); ?>
                </div>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="Enter new password (min. 6 characters)" required
                                   onkeyup="checkPasswordStrength(this.value)">
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your new password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-reset">
                            <i class="fas fa-save me-2"></i>Reset Password
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <div class="text-center">
                    <p class="mb-0">
                        <a href="login.php" class="text-primary text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                        </a>
                    </p>
                </div>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <div class="text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>Your new password must be at least 6 characters long
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