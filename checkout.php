<?php
// checkout.php - Complete Checkout System
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = "Checkout - Angelo Phone Gate";

// Redirect to cart if cart is empty
$cart_items = getCartItems();
$cart_total = getCartTotal();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

// Get user data
$pdo = getDBConnection();
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch();

// Handle order placement
$order_success = false;
$order_data = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $pdo->beginTransaction();

        // Get form data
        $shipping_name = trim($_POST['shipping_name']);
        $shipping_phone = trim($_POST['shipping_phone']);
        $shipping_address = trim($_POST['shipping_address']);
        $shipping_city = trim($_POST['shipping_city']);
        $payment_method = $_POST['payment_method'];
        
        // Card details if payment method is card
        $card_number = '';
        $card_holder = '';
        $card_expiry = '';
        $card_cvv = '';
        
        if ($payment_method === 'card') {
            $card_number = str_replace(' ', '', $_POST['card_number'] ?? '');
            $card_holder = trim($_POST['card_holder'] ?? '');
            $card_expiry = trim($_POST['card_expiry'] ?? '');
            $card_cvv = trim($_POST['card_cvv'] ?? '');
            
            // Basic card validation
            if (empty($card_number) || empty($card_holder) || empty($card_expiry) || empty($card_cvv)) {
                throw new Exception("Please fill all card details.");
            }
            
            if (strlen($card_number) < 16) {
                throw new Exception("Please enter a valid card number.");
            }
        }

        // Calculate totals
        $subtotal = $cart_total;
        $shipping_cost = 200.00;
        $tax_rate = 0.08;
        $tax_amount = $subtotal * $tax_rate;
        $total_amount = $subtotal + $shipping_cost + $tax_amount;

        // Generate order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());

        // Shipping address as JSON
        $shipping_address_data = json_encode([
            'name' => $shipping_name,
            'phone' => $shipping_phone,
            'address' => $shipping_address,
            'city' => $shipping_city
        ]);

        // Insert order
        $order_stmt = $pdo->prepare("
            INSERT INTO orders (order_number, user_id, total_amount, subtotal, tax_amount, shipping_cost, 
                              payment_method, shipping_address, status, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");
        $order_stmt->execute([
            $order_number,
            $_SESSION['user_id'],
            $total_amount,
            $subtotal,
            $tax_amount,
            $shipping_cost,
            $payment_method,
            $shipping_address_data
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $order_item_stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($cart_items as $item) {
            $order_item_stmt->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $item['total_price']
            ]);

            // Update product stock
            $update_stock_stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE product_id = ?
            ");
            $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // Insert payment record if card payment
        if ($payment_method === 'card') {
            $payment_stmt = $pdo->prepare("
                INSERT INTO payments (order_id, payment_method, amount, status, payment_details)
                VALUES (?, 'credit_card', ?, 'completed', ?)
            ");
            
            $payment_details = json_encode([
                'card_last4' => substr($card_number, -4),
                'card_holder' => $card_holder,
                'card_expiry' => $card_expiry
            ]);
            
            $payment_stmt->execute([$order_id, $total_amount, $payment_details]);
            
            // Update order payment status
            $update_order_stmt = $pdo->prepare("
                UPDATE orders SET payment_status = 'paid' WHERE order_id = ?
            ");
            $update_order_stmt->execute([$order_id]);
        }

        $pdo->commit();

        // Store order data for success page
        $order_data = [
            'order_number' => $order_number,
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'shipping_address' => [
                'name' => $shipping_name,
                'phone' => $shipping_phone,
                'address' => $shipping_address,
                'city' => $shipping_city
            ],
            'items' => $cart_items
        ];

        // Clear cart
        unset($_SESSION['cart']);

        $order_success = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error placing order: " . $e->getMessage();
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
        .checkout-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #1da1f2;
            background: #f8f9fa;
        }
        .payment-method.selected {
            border-color: #1da1f2;
            background: #e3f2fd;
        }
        .card-details {
            display: none;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
        }
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #1da1f2;
        }
        .success-section {
            text-align: center;
            padding: 60px 30px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
        }
        .printable-bill {
            background: white;
            color: #333;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .bill-header {
            border-bottom: 3px solid #1da1f2;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .bill-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        @media print {
            .no-print { display: none !important; }
            .printable-bill { 
                box-shadow: none !important;
                margin: 0 !important;
                padding: 20px !important;
            }
            body { background: white !important; }
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
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="profile.php#order-history"><i class="fas fa-shopping-bag me-2"></i>My Orders</a></li>
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

    <!-- Checkout Content -->
    <div class="container py-5">
        <?php if ($order_success && $order_data): ?>
        
        <!-- SUCCESS SECTION -->
        <div class="success-section">
            <i class="fas fa-check-circle fa-5x mb-4"></i>
            <h1 class="mb-3">Order Placed Successfully!</h1>
            <p class="lead mb-4">Thank you for your purchase. Your order has been confirmed.</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="printable-bill">
                        <!-- Printable Bill -->
                        <div class="bill-header text-center">
                            <img src="images/logo.jfif" alt="Logo" style="height: 50px;" class="mb-3">
                            <h2>Angelo Phone Gate</h2>
                            <p class="mb-0">Trusted Genuine Forever</p>
                            <small>123, Colombo Road, Sri Lanka</small>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-6">
                                <strong>Order Number:</strong><br>
                                <?php echo $order_data['order_number']; ?>
                            </div>
                            <div class="col-6 text-end">
                                <strong>Date:</strong><br>
                                <?php echo date('F j, Y'); ?>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Shipping Address</h5>
                            <p class="mb-1"><?php echo htmlspecialchars($order_data['shipping_address']['name']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($order_data['shipping_address']['address']); ?></p>
                            <p class="mb-1"><?php echo htmlspecialchars($order_data['shipping_address']['city']); ?></p>
                            <p class="mb-0">Phone: <?php echo htmlspecialchars($order_data['shipping_address']['phone']); ?></p>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Order Items</h5>
                            <?php foreach ($order_data['items'] as $item): ?>
                            <div class="bill-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                    <small>Qty: <?php echo $item['quantity']; ?> × Rs. <?php echo number_format($item['price'], 2); ?></small>
                                </div>
                                <strong>Rs. <?php echo number_format($item['total_price'], 2); ?></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-end">
                            <div class="order-summary-item">
                                <span>Subtotal:</span>
                                <span>Rs. <?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="order-summary-item">
                                <span>Shipping:</span>
                                <span>Rs. 200.00</span>
                            </div>
                            <div class="order-summary-item">
                                <span>Tax (8%):</span>
                                <span>Rs. <?php echo number_format($cart_total * 0.08, 2); ?></span>
                            </div>
                            <div class="order-summary-item order-total">
                                <span>Total:</span>
                                <span>Rs. <?php echo number_format($order_data['total_amount'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-4 border-top text-center">
                            <p class="mb-2"><strong>Payment Method:</strong> <?php echo ucfirst($order_data['payment_method']); ?></p>
                            <p class="mb-0 text-muted">Thank you for shopping with us!</p>
                        </div>
                    </div>
                    
                    <div class="mt-4 no-print">
                        <button onclick="window.print()" class="btn btn-primary me-3">
                            <i class="fas fa-print me-2"></i>Print Bill
                        </button>
                        <a href="products.php" class="btn btn-success">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        
        <!-- CHECKOUT FORM -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h3 class="mb-4"><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h3>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="shipping_name" 
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="shipping_phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea class="form-control" name="shipping_address" rows="3" required 
                                      placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City *</label>
                            <input type="text" class="form-control" name="shipping_city" value="Kuliyapitiya" required>
                        </div>
                </div>
                
                <!-- Payment Method -->
                <div class="checkout-section">
                    <h3 class="mb-4"><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('cash')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="cash" id="cash" required>
                            <label class="form-check-label" for="cash">
                                <i class="fas fa-money-bill-wave me-2 fa-lg text-success"></i>
                                <strong>Cash on Delivery</strong>
                                <small class="d-block text-muted">Pay when you receive your order</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('card')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="card" id="card">
                            <label class="form-check-label" for="card">
                                <i class="fas fa-credit-card me-2 fa-lg text-primary"></i>
                                <strong>Credit/Debit Card</strong>
                                <small class="d-block text-muted">Pay securely with your card</small>
                            </label>
                        </div>
                    </div>
                    
                    <div id="cardDetails" class="card-details">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Card Number *</label>
                                <input type="text" class="form-control" name="card_number" 
                                       placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Card Holder Name *</label>
                                <input type="text" class="form-control" name="card_holder" 
                                       placeholder="John Doe">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date *</label>
                                <input type="text" class="form-control" name="card_expiry" 
                                       placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CVV *</label>
                                <input type="text" class="form-control" name="card_cvv" 
                                       placeholder="123" maxlength="3">
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('bank')">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="bank" id="bank">
                            <label class="form-check-label" for="bank">
                                <i class="fas fa-university me-2 fa-lg text-info"></i>
                                <strong>Bank Transfer</strong>
                                <small class="d-block text-muted">Transfer money to our bank account</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="checkout-section">
                    <h3 class="mb-4"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="order-summary-item">
                        <div>
                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            <small class="d-block text-muted">Qty: <?php echo $item['quantity']; ?></small>
                        </div>
                        <span>Rs. <?php echo number_format($item['total_price'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    
                    <div class="order-summary-item">
                        <span>Subtotal:</span>
                        <span>Rs. <?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <div class="order-summary-item">
                        <span>Shipping:</span>
                        <span>Rs. 200.00</span>
                    </div>
                    <div class="order-summary-item">
                        <span>Tax (8%):</span>
                        <span>Rs. <?php echo number_format($cart_total * 0.08, 2); ?></span>
                    </div>
                    <div class="order-summary-item order-total">
                        <span>Total:</span>
                        <span>Rs. <?php echo number_format($cart_total + 200 + ($cart_total * 0.08), 2); ?></span>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" name="place_order" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-lock me-2"></i>Place Order
                        </button>
                    </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-lock me-1"></i>Your payment information is secure and encrypted
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5 mt-5">
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
                        <li><a href="cart.php" class="text-light">Cart</a></li>
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
    
    <!-- Checkout JavaScript -->
    <script>
    function selectPaymentMethod(method) {
        // Update radio button
        document.getElementById(method).checked = true;
        
        // Remove selected class from all
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add selected class to clicked one
        event.currentTarget.classList.add('selected');
        
        // Show/hide card details
        const cardDetails = document.getElementById('cardDetails');
        if (method === 'card') {
            cardDetails.style.display = 'block';
            // Make card fields required
            document.querySelectorAll('#cardDetails input').forEach(input => {
                input.required = true;
            });
        } else {
            cardDetails.style.display = 'none';
            // Remove required from card fields
            document.querySelectorAll('#cardDetails input').forEach(input => {
                input.required = false;
            });
        }
    }
    
    // Format card number
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumberInput = document.querySelector('input[name="card_number"]');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let matches = value.match(/\d{4,16}/g);
                let match = matches && matches[0] || '';
                let parts = [];
                
                for (let i = 0; i < match.length; i += 4) {
                    parts.push(match.substring(i, i + 4));
                }
                
                if (parts.length) {
                    e.target.value = parts.join(' ');
                } else {
                    e.target.value = value;
                }
            });
        }
        
        // Format expiry date
        const expiryInput = document.querySelector('input[name="card_expiry"]');
        if (expiryInput) {
            expiryInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
            });
        }
        
        // Only numbers for CVV
        const cvvInput = document.querySelector('input[name="card_cvv"]');
        if (cvvInput) {
            cvvInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
        }
        
        // Form validation
        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedPayment) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            if (selectedPayment.value === 'card') {
                const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
                if (cardNumber.length !== 16) {
                    e.preventDefault();
                    alert('Please enter a valid 16-digit card number.');
                    return false;
                }
            }
        });
    });
    </script>
</body>
</html>