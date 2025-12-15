<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check for messages from form processing
$success_message = '';
$error_message = '';
$form_data = [];

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Angelo Phone Gate</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
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
                <li class="nav-item"><a class="nav-link" href="products.php?category=5">Accessories</a></li>
                <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <li class="nav-item ms-3">
                    <a class="nav-link position-relative" href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                            <?php 
                            if(isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                                echo array_sum($_SESSION['cart']);
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                    </a>
                </li>
            </ul>
            <form class="d-flex ms-3 mt-2 mt-lg-0" role="search">
                <input class="form-control me-2" type="search" placeholder="Search...">
                <button class="btn btn-info" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </nav>

    <!-- Contact Section -->
    <div class="container my-5">
        <h1 class="text-center mb-5">Contact Us</h1>
        
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-md-6">
                <div class="contact-card shadow-sm">
                    <h4>Get in Touch</h4>
                    <p class="text-muted mb-4">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="includes/contact_process.php" method="POST" id="contactForm">
                        <div class="mb-3">
                            <label class="form-label">Your Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" 
                                   placeholder="Enter your name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>" 
                                   placeholder="Enter your phone number">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject *</label>
                            <select class="form-select" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="Product Inquiry" <?php echo (($form_data['subject'] ?? '') == 'Product Inquiry') ? 'selected' : ''; ?>>Product Inquiry</option>
                                <option value="Technical Support" <?php echo (($form_data['subject'] ?? '') == 'Technical Support') ? 'selected' : ''; ?>>Technical Support</option>
                                <option value="Warranty Claim" <?php echo (($form_data['subject'] ?? '') == 'Warranty Claim') ? 'selected' : ''; ?>>Warranty Claim</option>
                                <option value="General Question" <?php echo (($form_data['subject'] ?? '') == 'General Question') ? 'selected' : ''; ?>>General Question</option>
                                <option value="Other" <?php echo (($form_data['subject'] ?? '') == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea class="form-control" name="message" rows="5" 
                                      placeholder="Your message" required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <?php if (isLoggedIn()): ?>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                                    <div>
                                        <strong>Login Required</strong><br>
                                        Please <a href="login.php" class="alert-link">login</a> or 
                                        <a href="register.php" class="alert-link">create an account</a> to contact us.
                                    </div>
                                </div>
                            </div>
                            <a href="login.php" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Continue
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Store Info & Map -->
            <div class="col-md-6">
                <div class="contact-card shadow-sm">
                    <h4>Our Store</h4>
                    <div class="mb-4">
                        <p><i class="fas fa-map-marker-alt me-2 text-primary"></i><strong>Address:</strong> 
                           <?php echo getSetting('store_address') ?: '157, Main Street, Kuliyapitiya 60200, Sri Lanka'; ?></p>
                        <p><i class="fas fa-envelope me-2 text-primary"></i><strong>Email:</strong> 
                           <?php echo getSetting('store_email') ?: 'angelophonegate@gmail.com'; ?></p>
                        <p><i class="fas fa-phone me-2 text-primary"></i><strong>Phone:</strong> 
                           <?php echo getSetting('store_phone') ?: '+94 77 123 4567'; ?></p>
                        <p><i class="fas fa-clock me-2 text-primary"></i><strong>Business Hours:</strong> Mon-Sun: 9:00 AM - 8:00 PM</p>
                    </div>

                    <!-- Contact Methods -->
                    <div class="mb-4">
                        <h5>Other Ways to Reach Us</h5>
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="https://wa.me/94771234567" class="btn btn-outline-primary" target="_blank">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </a>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fab fa-facebook-messenger me-2"></i>Messenger
                            </a>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fab fa-viber me-2"></i>Viber
                            </a>
                        </div>
                    </div>

                    <!-- Google Map -->
                    <div>
                        <h5>Find Us on Map</h5>
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.752334227409!2d80.0376921!3d7.469042!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae2d9ba5a1091e7%3A0xca0ae00057f52147!2sAngelo%20Phone%20Gate!5e0!3m2!1sen!2slk!4v1693651541234!5m2!1sen!2slk"
                            width="100%" height="250" style="border:0; border-radius: 8px;" allowfullscreen="" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="contact-card shadow-sm">
                    <h4 class="text-center mb-4">Frequently Asked Questions</h4>
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We offer a 7-day return policy for all products. Items must be in original condition with all accessories and packaging. Please contact us for return authorization.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Do you offer warranty on products?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, we provide Angelo Warranty on all our products. The warranty period varies by product category. Please check the product page for specific warranty details.
                                </div>
                            </div>
                        </div>
                        
                        <!-- FAQ 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    How long does delivery take?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Delivery typically takes 2-3 business days within Sri Lanka. For remote areas, it may take up to 5 business days. We provide tracking information for all orders.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Do you offer international shipping?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Currently, we only ship within Sri Lanka. We're working on expanding our services to international customers in the near future.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 5 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    What payment methods do you accept?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    We accept cash on delivery, bank transfers, and credit/debit cards. All online payments are processed through secure payment gateways.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-container">
                <div class="footer-about">
                    <h2>Angelo Phone Gate</h2>
                    <p>Your trusted mobile store for the latest smartphones, best prices, and reliable customer support.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone me-2"></i> +94 71 123 4567</li>
                        <li><i class="fas fa-envelope me-2"></i> angelophones@gmail.com</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123, Colombo Road, Sri Lanka</li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="accessories.php">Accessories</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-products">
                    <h3>Recent Products</h3>
                    <ul>
                        <li>Oppo Reno 12F</li>
                        <li>Samsung Galaxy A15</li>
                        <li>iPhone 14 Pro Max</li>
                    </ul>
                </div>
                <div class="footer-social">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Angelo Phone Gate. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Contact Form Validation -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                let isValid = true;
                const inputs = contactForm.querySelectorAll('input[required], select[required], textarea[required]');
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });
                
                // Email validation
                const emailInput = contactForm.querySelector('input[type="email"]');
                if (emailInput && emailInput.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailInput.value)) {
                        isValid = false;
                        emailInput.classList.add('is-invalid');
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstInvalid = contactForm.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        }
    });
    </script>
</body>
</html>