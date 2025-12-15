<?php
// admin/settings.php - Store Settings Management
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect to login if not admin
if (!isset($_SESSION['user_id']) || (!isAdmin() && !isSuperAdmin())) {
    header('Location: login.php');
    exit;
}

$page_title = "Store Settings - Angelo Phone Gate Admin";

// Get database connection
$pdo = getDBConnection();

$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // General Settings
        if (isset($_POST['update_general'])) {
            $settings_to_update = [
                'store_name' => $_POST['store_name'] ?? '',
                'store_email' => $_POST['store_email'] ?? '',
                'store_phone' => $_POST['store_phone'] ?? '',
                'store_address' => $_POST['store_address'] ?? '',
                'store_currency' => $_POST['store_currency'] ?? 'LKR',
                'store_timezone' => $_POST['store_timezone'] ?? 'Asia/Colombo',
                'store_tagline' => $_POST['store_tagline'] ?? '',
                'store_description' => $_POST['store_description'] ?? ''
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'general');
            }
            $message = "General settings updated successfully!";
        }
        
        // Business Settings
        if (isset($_POST['update_business'])) {
            $settings_to_update = [
                'business_hours' => $_POST['business_hours'] ?? '',
                'tax_rate' => $_POST['tax_rate'] ?? '0',
                'shipping_cost' => $_POST['shipping_cost'] ?? '0',
                'free_shipping_min' => $_POST['free_shipping_min'] ?? '0',
                'return_policy_days' => $_POST['return_policy_days'] ?? '7',
                'warranty_period' => $_POST['warranty_period'] ?? '12',
                'low_stock_threshold' => $_POST['low_stock_threshold'] ?? '5'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'business');
            }
            $message = "Business settings updated successfully!";
        }
        
        // Payment Settings
        if (isset($_POST['update_payment'])) {
            $settings_to_update = [
                'payment_methods' => implode(',', $_POST['payment_methods'] ?? []),
                'bank_name' => $_POST['bank_name'] ?? '',
                'account_number' => $_POST['account_number'] ?? '',
                'account_holder' => $_POST['account_holder'] ?? '',
                'paypal_email' => $_POST['paypal_email'] ?? '',
                'stripe_publishable_key' => $_POST['stripe_publishable_key'] ?? '',
                'stripe_secret_key' => $_POST['stripe_secret_key'] ?? ''
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'payment');
            }
            $message = "Payment settings updated successfully!";
        }
        
        // Email Settings
        if (isset($_POST['update_email'])) {
            $settings_to_update = [
                'smtp_host' => $_POST['smtp_host'] ?? '',
                'smtp_port' => $_POST['smtp_port'] ?? '587',
                'smtp_username' => $_POST['smtp_username'] ?? '',
                'smtp_password' => $_POST['smtp_password'] ?? '',
                'email_from_name' => $_POST['email_from_name'] ?? '',
                'order_notifications' => isset($_POST['order_notifications']) ? '1' : '0',
                'customer_welcome_email' => isset($_POST['customer_welcome_email']) ? '1' : '0',
                'order_status_updates' => isset($_POST['order_status_updates']) ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'email');
            }
            $message = "Email settings updated successfully!";
        }
        
        // Appearance Settings
        if (isset($_POST['update_appearance'])) {
            $settings_to_update = [
                'theme_primary_color' => $_POST['theme_primary_color'] ?? '#1da1f2',
                'theme_secondary_color' => $_POST['theme_secondary_color'] ?? '#0b82c0',
                'theme_accent_color' => $_POST['theme_accent_color'] ?? '#ff6b00',
                'theme_font_family' => $_POST['theme_font_family'] ?? 'Inter',
                'theme_layout' => $_POST['theme_layout'] ?? 'default',
                'homepage_layout' => $_POST['homepage_layout'] ?? 'grid',
                'show_featured_products' => isset($_POST['show_featured_products']) ? '1' : '0',
                'show_best_sellers' => isset($_POST['show_best_sellers']) ? '1' : '0',
                'show_new_arrivals' => isset($_POST['show_new_arrivals']) ? '1' : '0',
                'products_per_page' => $_POST['products_per_page'] ?? '12',
                'enable_dark_mode' => isset($_POST['enable_dark_mode']) ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'appearance');
            }
            $message = "Appearance settings updated successfully!";
        }
        
        // Social Media Settings
        if (isset($_POST['update_social'])) {
            $settings_to_update = [
                'facebook_url' => $_POST['facebook_url'] ?? '',
                'instagram_url' => $_POST['instagram_url'] ?? '',
                'twitter_url' => $_POST['twitter_url'] ?? '',
                'youtube_url' => $_POST['youtube_url'] ?? '',
                'whatsapp_number' => $_POST['whatsapp_number'] ?? '',
                'linkedin_url' => $_POST['linkedin_url'] ?? '',
                'tiktok_url' => $_POST['tiktok_url'] ?? '',
                'enable_social_sharing' => isset($_POST['enable_social_sharing']) ? '1' : '0',
                'enable_social_login' => isset($_POST['enable_social_login']) ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'social');
            }
            $message = "Social media settings updated successfully!";
        }
        
        // SEO Settings
        if (isset($_POST['update_seo'])) {
            $settings_to_update = [
                'meta_title' => $_POST['meta_title'] ?? '',
                'meta_description' => $_POST['meta_description'] ?? '',
                'meta_keywords' => $_POST['meta_keywords'] ?? '',
                'google_analytics_id' => $_POST['google_analytics_id'] ?? '',
                'google_site_verification' => $_POST['google_site_verification'] ?? '',
                'enable_sitemap' => isset($_POST['enable_sitemap']) ? '1' : '0',
                'enable_structured_data' => isset($_POST['enable_structured_data']) ? '1' : '0',
                'canonical_url' => $_POST['canonical_url'] ?? ''
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'seo');
            }
            $message = "SEO settings updated successfully!";
        }
        
        // Security Settings
        if (isset($_POST['update_security'])) {
            $settings_to_update = [
                'enable_https' => isset($_POST['enable_https']) ? '1' : '0',
                'enable_captcha' => isset($_POST['enable_captcha']) ? '1' : '0',
                'captcha_site_key' => $_POST['captcha_site_key'] ?? '',
                'captcha_secret_key' => $_POST['captcha_secret_key'] ?? '',
                'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
                'session_timeout' => $_POST['session_timeout'] ?? '30',
                'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0',
                'block_suspicious_ips' => isset($_POST['block_suspicious_ips']) ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'security');
            }
            $message = "Security settings updated successfully!";
        }
        
        // Maintenance Settings
        if (isset($_POST['update_maintenance'])) {
            $settings_to_update = [
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'maintenance_message' => $_POST['maintenance_message'] ?? '',
                'enable_backups' => isset($_POST['enable_backups']) ? '1' : '0',
                'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
                'enable_error_logging' => isset($_POST['enable_error_logging']) ? '1' : '0',
                'enable_performance_monitoring' => isset($_POST['enable_performance_monitoring']) ? '1' : '0'
            ];
            
            foreach ($settings_to_update as $key => $value) {
                updateOrInsertSetting($pdo, $key, $value, 'maintenance');
            }
            $message = "Maintenance settings updated successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Helper function to update or insert settings
function updateOrInsertSetting($pdo, $key, $value, $group) {
    $check_stmt = $pdo->prepare("SELECT setting_id FROM settings WHERE key_name = ?");
    $check_stmt->execute([$key]);
    
    if ($check_stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE settings SET value = ?, updated_at = NOW() WHERE key_name = ?");
        $stmt->execute([$value, $key]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, value, type, group_name) VALUES (?, ?, 'string', ?)");
        $stmt->execute([$key, $value, $group]);
    }
}

// Get all settings
// Get settings from database
$settings_result = $pdo->query("SELECT key_name, value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
$db_settings = [];
foreach ($settings_result as $row) {
    $db_settings[$row['key_name']] = $row['value'];
}

// Make sure $default_settings exists and merge
if (!isset($default_settings) || !is_array($default_settings)) {
    // If $default_settings doesn't exist, use only database settings
    $settings = $db_settings;
} else {
    // Merge default settings with database settings
    $settings = array_merge($default_settings, $db_settings);
}

// Default values for settings that might not exist
$default_settings = [
    // General
    'store_name' => 'Angelo Phone Gate',
    'store_email' => 'contact@angelophonegate.com',
    'store_phone' => '+94 77 123 4567',
    'store_address' => '157, Main Street, Kuliyapitiya 60200, Sri Lanka',
    'store_currency' => 'LKR',
    'store_timezone' => 'Asia/Colombo',
    'store_tagline' => 'Trusted Genuine Forever',
    'store_description' => 'Your trusted mobile store for the latest smartphones, best prices, and reliable customer support.',
    
    // Business
    'business_hours' => 'Mon-Sun: 9:00 AM - 8:00 PM',
    'tax_rate' => '8',
    'shipping_cost' => '200',
    'free_shipping_min' => '5000',
    'return_policy_days' => '14',
    'warranty_period' => '12',
    'low_stock_threshold' => '5',
    
    // Payment
    'payment_methods' => 'cash,card,bank',
    'bank_name' => '',
    'account_number' => '',
    'account_holder' => '',
    'paypal_email' => '',
    'stripe_publishable_key' => '',
    'stripe_secret_key' => '',
    
    // Email
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'email_from_name' => 'Angelo Phone Gate',
    'order_notifications' => '1',
    'customer_welcome_email' => '1',
    'order_status_updates' => '1',
    
    // Appearance
    'theme_primary_color' => '#1da1f2',
    'theme_secondary_color' => '#0b82c0',
    'theme_accent_color' => '#ff6b00',
    'theme_font_family' => 'Inter',
    'theme_layout' => 'default',
    'homepage_layout' => 'grid',
    'show_featured_products' => '1',
    'show_best_sellers' => '1',
    'show_new_arrivals' => '1',
    'products_per_page' => '12',
    'enable_dark_mode' => '0',
    
    // Social Media
    'facebook_url' => '',
    'instagram_url' => '',
    'twitter_url' => '',
    'youtube_url' => '',
    'whatsapp_number' => '',
    'linkedin_url' => '',
    'tiktok_url' => '',
    'enable_social_sharing' => '1',
    'enable_social_login' => '0',
    
    // SEO
    'meta_title' => 'Angelo Phone Gate - Trusted Mobile Store',
    'meta_description' => 'Buy latest smartphones, accessories at best prices. Trusted genuine products with warranty.',
    'meta_keywords' => 'mobile phones, smartphones, accessories, electronics',
    'google_analytics_id' => '',
    'google_site_verification' => '',
    'enable_sitemap' => '1',
    'enable_structured_data' => '1',
    'canonical_url' => '',
    
    // Security
    'enable_https' => '0',
    'enable_captcha' => '0',
    'captcha_site_key' => '',
    'captcha_secret_key' => '',
    'max_login_attempts' => '5',
    'session_timeout' => '30',
    'enable_2fa' => '0',
    'block_suspicious_ips' => '0',
    
    // Maintenance
    'maintenance_mode' => '0',
    'maintenance_message' => 'Website is under maintenance. Please check back later.',
    'enable_backups' => '1',
    'backup_frequency' => 'daily',
    'enable_error_logging' => '1',
    'enable_performance_monitoring' => '1'
];

// Merge with actual settings
$settings = array_merge($default_settings, $settings);

// Timezone options
$timezones = [
    'Asia/Colombo' => 'Sri Lanka (Colombo)',
    'Asia/Kolkata' => 'India (Kolkata)',
    'Asia/Dubai' => 'UAE (Dubai)',
    'Asia/Singapore' => 'Singapore',
    'Europe/London' => 'UK (London)',
    'America/New_York' => 'USA (New York)'
];

// Currency options
$currencies = [
    'LKR' => 'Sri Lankan Rupee (LKR)',
    'USD' => 'US Dollar (USD)',
    'EUR' => 'Euro (EUR)',
    'GBP' => 'British Pound (GBP)',
    'AED' => 'UAE Dirham (AED)',
    'INR' => 'Indian Rupee (INR)'
];

// Font options
$fonts = [
    'Inter' => 'Inter (Modern)',
    'Roboto' => 'Roboto (Clean)',
    'Open Sans' => 'Open Sans (Friendly)',
    'Poppins' => 'Poppins (Elegant)',
    'Montserrat' => 'Montserrat (Bold)',
    'Lato' => 'Lato (Professional)',
    'Arial' => 'Arial (Standard)',
    'Helvetica' => 'Helvetica (Classic)'
];

// Layout options
$layouts = [
    'default' => 'Default Layout',
    'modern' => 'Modern Layout',
    'minimal' => 'Minimal Layout',
    'classic' => 'Classic Layout'
];

// Homepage layouts
$homepage_layouts = [
    'grid' => 'Grid Layout',
    'list' => 'List Layout',
    'masonry' => 'Masonry Layout',
    'carousel' => 'Carousel Layout'
];

// Backup frequencies
$backup_frequencies = [
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly'
];
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin-styles.css">
    
    <style>
        :root {
            --primary-color: <?php echo $settings['theme_primary_color']; ?>;
            --secondary-color: <?php echo $settings['theme_secondary_color']; ?>;
            --accent-color: <?php echo $settings['theme_accent_color']; ?>;
            --font-family: '<?php echo $settings['theme_font_family']; ?>', sans-serif;
        }
        
        body {
            font-family: var(--font-family);
        }
        
        .settings-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px 0;
            margin: -20px -20px 30px -20px;
        }
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary-color);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .settings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .settings-card.general { border-left-color: #3498db; }
        .settings-card.business { border-left-color: #2ecc71; }
        .settings-card.payment { border-left-color: #9b59b6; }
        .settings-card.email { border-left-color: #e74c3c; }
        .settings-card.appearance { border-left-color: #f39c12; }
        .settings-card.social { border-left-color: #1abc9c; }
        .settings-card.seo { border-left-color: #34495e; }
        .settings-card.security { border-left-color: #e74c3c; }
        .settings-card.maintenance { border-left-color: #7f8c8d; }
        
        .card-header {
            background: transparent;
            border-bottom: 2px solid #f8f9fa;
            padding: 0 0 15px 0;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .card-title i {
            margin-right: 10px;
            width: 25px;
        }
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
            display: inline-block;
        }
        .setting-description {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .payment-method-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method-option:hover {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        .payment-method-option.selected {
            border-color: var(--primary-color);
            background: #e3f2fd;
        }
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 10px;
            color: var(--primary-color);
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: var(--primary-color);
        }
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-block;
            margin-right: 10px;
            border: 2px solid #ddd;
            cursor: pointer;
        }
        .theme-preview {
            width: 100%;
            height: 80px;
            border-radius: 8px;
            margin-top: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: 2px solid #e9ecef;
        }
        .layout-preview {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .layout-preview:hover {
            border-color: var(--primary-color);
        }
        .layout-preview.selected {
            border-color: var(--primary-color);
            background: #f8f9fa;
        }
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-size: 1.2rem;
        }
        .facebook { background: #3b5998; }
        .instagram { background: #e4405f; }
        .twitter { background: #1da1f2; }
        .youtube { background: #cd201f; }
        .whatsapp { background: #25d366; }
        .linkedin { background: #0077b5; }
        .tiktok { background: #000000; }
        .badge-new {
            background: var(--accent-color);
            color: white;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }
        .preview-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
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
                            <i class="fas fa-cogs me-2"></i>Store Settings
                        </h3>
                        <p class="text-muted mb-0">Configure your store preferences and appearance</p>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">
                            <i class="fas fa-store me-1"></i>
                            <?php echo htmlspecialchars($settings['store_name']); ?>
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

            <!-- Settings Navigation Tabs -->
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-store me-2"></i>General
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab">
                                <i class="fas fa-briefcase me-2"></i>Business
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">
                                <i class="fas fa-credit-card me-2"></i>Payment
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope me-2"></i>Email
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appearance-tab" data-bs-toggle="tab" data-bs-target="#appearance" type="button" role="tab">
                                <i class="fas fa-palette me-2"></i>Appearance
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                                <i class="fas fa-share-alt me-2"></i>Social
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">
                                <i class="fas fa-search me-2"></i>SEO
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="maintenance-tab" data-bs-toggle="tab" data-bs-target="#maintenance" type="button" role="tab">
                                <i class="fas fa-tools me-2"></i>Maintenance
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="settingsTabsContent">
                
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="settings-card general">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-store"></i>General Store Settings
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Store Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Store Name *</label>
                                            <input type="text" class="form-control" name="store_name" 
                                                   value="<?php echo htmlspecialchars($settings['store_name']); ?>" required>
                                            <div class="setting-description">Your business/store name displayed throughout the site</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Store Tagline</label>
                                            <input type="text" class="form-control" name="store_tagline" 
                                                   value="<?php echo htmlspecialchars($settings['store_tagline']); ?>">
                                            <div class="setting-description">Short catchy phrase that describes your store</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Store Description</label>
                                            <textarea class="form-control" name="store_description" rows="3"><?php echo htmlspecialchars($settings['store_description']); ?></textarea>
                                            <div class="setting-description">Detailed description of your store for SEO and about pages</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Contact Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Store Email *</label>
                                            <input type="email" class="form-control" name="store_email" 
                                                   value="<?php echo htmlspecialchars($settings['store_email']); ?>" required>
                                            <div class="setting-description">Primary contact email for customers</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Store Phone *</label>
                                            <input type="text" class="form-control" name="store_phone" 
                                                   value="<?php echo htmlspecialchars($settings['store_phone']); ?>" required>
                                            <div class="setting-description">Customer service phone number</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Store Address</label>
                                            <textarea class="form-control" name="store_address" rows="3"><?php echo htmlspecialchars($settings['store_address']); ?></textarea>
                                            <div class="setting-description">Physical store location or office address</div>
                                        </div>
                                    </div>
                                    <div class="form-section">
                                        <h5 class="section-title">Regional Settings</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Currency</label>
                                            <select class="form-select" name="store_currency">
                                                <?php foreach ($currencies as $code => $name): ?>
                                                <option value="<?php echo $code; ?>" 
                                                    <?php echo $settings['store_currency'] == $code ? 'selected' : ''; ?>>
                                                    <?php echo $name; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="setting-description">Default currency for product prices</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Timezone</label>
                                            <select class="form-select" name="store_timezone">
                                                <?php foreach ($timezones as $tz => $label): ?>
                                                <option value="<?php echo $tz; ?>" 
                                                    <?php echo $settings['store_timezone'] == $tz ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="setting-description">Your local timezone for order timestamps</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_general" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Business Settings Tab -->
                <div class="tab-pane fade" id="business" role="tabpanel">
                    <div class="settings-card business">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-briefcase"></i>Business Operations
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Store Operations</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Business Hours</label>
                                            <input type="text" class="form-control" name="business_hours" 
                                                   value="<?php echo htmlspecialchars($settings['business_hours']); ?>">
                                            <div class="setting-description">e.g., Mon-Sun: 9:00 AM - 8:00 PM</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Return Policy (Days)</label>
                                            <input type="number" class="form-control" name="return_policy_days" 
                                                   value="<?php echo htmlspecialchars($settings['return_policy_days']); ?>" min="0" max="365">
                                            <div class="setting-description">Number of days customers can return products</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Warranty Period (Months)</label>
                                            <input type="number" class="form-control" name="warranty_period" 
                                                   value="<?php echo htmlspecialchars($settings['warranty_period']); ?>" min="0" max="60">
                                            <div class="setting-description">Default warranty period for products</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Pricing & Inventory</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Tax Rate (%)</label>
                                            <input type="number" class="form-control" name="tax_rate" 
                                                   value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" min="0" max="50" step="0.1">
                                            <div class="setting-description">Sales tax/VAT percentage applied to orders</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Standard Shipping Cost (Rs.)</label>
                                            <input type="number" class="form-control" name="shipping_cost" 
                                                   value="<?php echo htmlspecialchars($settings['shipping_cost']); ?>" min="0" step="50">
                                            <div class="setting-description">Default shipping charge for orders</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Free Shipping Minimum (Rs.)</label>
                                            <input type="number" class="form-control" name="free_shipping_min" 
                                                   value="<?php echo htmlspecialchars($settings['free_shipping_min']); ?>" min="0" step="100">
                                            <div class="setting-description">Order amount threshold for free shipping</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Low Stock Threshold</label>
                                            <input type="number" class="form-control" name="low_stock_threshold" 
                                                   value="<?php echo htmlspecialchars($settings['low_stock_threshold']); ?>" min="1" max="100">
                                            <div class="setting-description">Show low stock warning when quantity falls below this number</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_business" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Business Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Settings Tab -->
                <div class="tab-pane fade" id="payment" role="tabpanel">
                    <div class="settings-card payment">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-credit-card"></i>Payment Gateway Settings
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="form-section">
                                <h5 class="section-title">Accepted Payment Methods</h5>
                                <div class="row">
                                    <?php
                                    $payment_methods = explode(',', $settings['payment_methods']);
                                    $payment_options = [
                                        'cash' => ['icon' => 'fa-money-bill-wave', 'label' => 'Cash on Delivery'],
                                        'card' => ['icon' => 'fa-credit-card', 'label' => 'Credit/Debit Card'],
                                        'bank' => ['icon' => 'fa-university', 'label' => 'Bank Transfer'],
                                        'paypal' => ['icon' => 'fa-paypal', 'label' => 'PayPal']
                                    ];
                                    ?>
                                    <?php foreach ($payment_options as $method => $info): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="payment-method-option <?php echo in_array($method, $payment_methods) ? 'selected' : ''; ?>" 
                                             onclick="togglePaymentMethod('<?php echo $method; ?>')">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="payment_methods[]" 
                                                       value="<?php echo $method; ?>" id="pay_<?php echo $method; ?>"
                                                       <?php echo in_array($method, $payment_methods) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="pay_<?php echo $method; ?>">
                                                    <i class="fas <?php echo $info['icon']; ?> payment-icon"></i>
                                                    <?php echo $info['label']; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Bank Transfer Details</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" class="form-control" name="bank_name" 
                                                   value="<?php echo htmlspecialchars($settings['bank_name']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control" name="account_number" 
                                                   value="<?php echo htmlspecialchars($settings['account_number']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Account Holder Name</label>
                                            <input type="text" class="form-control" name="account_holder" 
                                                   value="<?php echo htmlspecialchars($settings['account_holder']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Online Payment Gateways</h5>
                                        <div class="mb-3">
                                            <label class="form-label">PayPal Email</label>
                                            <input type="email" class="form-control" name="paypal_email" 
                                                   value="<?php echo htmlspecialchars($settings['paypal_email']); ?>">
                                            <div class="setting-description">Email associated with your PayPal business account</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stripe Publishable Key</label>
                                            <input type="text" class="form-control" name="stripe_publishable_key" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_publishable_key']); ?>">
                                            <div class="setting-description">Your Stripe publishable API key</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Stripe Secret Key</label>
                                            <input type="password" class="form-control" name="stripe_secret_key" 
                                                   value="<?php echo htmlspecialchars($settings['stripe_secret_key']); ?>">
                                            <div class="setting-description">Your Stripe secret API key</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_payment" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Payment Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Email Settings Tab -->
                <div class="tab-pane fade" id="email" role="tabpanel">
                    <div class="settings-card email">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-envelope"></i>Email & Notification Settings
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">SMTP Configuration</h5>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="smtp_host" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                                            <div class="setting-description">e.g., smtp.gmail.com</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                                            <div class="setting-description">Typically 587 for TLS, 465 for SSL</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" name="smtp_username" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" name="smtp_password" 
                                                   value="<?php echo htmlspecialchars($settings['smtp_password']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Email Preferences</h5>
                                        <div class="mb-3">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="email_from_name" 
                                                   value="<?php echo htmlspecialchars($settings['email_from_name']); ?>">
                                            <div class="setting-description">Sender name for outgoing emails</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Order Notifications</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="order_notifications" value="1" 
                                                       <?php echo $settings['order_notifications'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Receive email notifications for new orders</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Customer Welcome Emails</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="customer_welcome_email" value="1" 
                                                       <?php echo $settings['customer_welcome_email'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Send welcome email to new customers</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Order Status Updates</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="order_status_updates" value="1" 
                                                       <?php echo $settings['order_status_updates'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Send email updates when order status changes</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_email" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Email Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Appearance Settings Tab -->
                <div class="tab-pane fade" id="appearance" role="tabpanel">
                    <div class="settings-card appearance">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-palette"></i>Theme & Appearance
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Color Scheme</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Primary Color</label>
                                            <div class="input-group">
                                                <span class="color-preview" style="background: <?php echo $settings['theme_primary_color']; ?>" 
                                                      onclick="document.getElementById('primaryColor').click()"></span>
                                                <input type="color" class="form-control form-control-color" id="primaryColor" 
                                                       name="theme_primary_color" value="<?php echo $settings['theme_primary_color']; ?>">
                                            </div>
                                            <div class="setting-description">Main brand color for buttons and highlights</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Secondary Color</label>
                                            <div class="input-group">
                                                <span class="color-preview" style="background: <?php echo $settings['theme_secondary_color']; ?>" 
                                                      onclick="document.getElementById('secondaryColor').click()"></span>
                                                <input type="color" class="form-control form-control-color" id="secondaryColor" 
                                                       name="theme_secondary_color" value="<?php echo $settings['theme_secondary_color']; ?>">
                                            </div>
                                            <div class="setting-description">Secondary color for backgrounds and accents</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Accent Color</label>
                                            <div class="input-group">
                                                <span class="color-preview" style="background: <?php echo $settings['theme_accent_color']; ?>" 
                                                      onclick="document.getElementById('accentColor').click()"></span>
                                                <input type="color" class="form-control form-control-color" id="accentColor" 
                                                       name="theme_accent_color" value="<?php echo $settings['theme_accent_color']; ?>">
                                            </div>
                                            <div class="setting-description">Color for special elements and call-to-actions</div>
                                        </div>
                                        <div class="theme-preview"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Typography</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Font Family</label>
                                            <select class="form-select" name="theme_font_family" onchange="updateFontPreview()">
                                                <?php foreach ($fonts as $font => $label): ?>
                                                <option value="<?php echo $font; ?>" 
                                                    <?php echo $settings['theme_font_family'] == $font ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="setting-description">Primary font for all text content</div>
                                        </div>
                                        <div class="preview-box">
                                            <h6>Font Preview:</h6>
                                            <p id="fontPreview" style="font-family: '<?php echo $settings['theme_font_family']; ?>', sans-serif;">
                                                The quick brown fox jumps over the lazy dog. 1234567890
                                            </p>
                                        </div>
                                    </div>
                                    <div class="form-section">
                                        <h5 class="section-title">Display Options</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Dark Mode</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_dark_mode" value="1" 
                                                       <?php echo $settings['enable_dark_mode'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Enable dark mode theme option for users</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Products Per Page</label>
                                            <input type="number" class="form-control" name="products_per_page" 
                                                   value="<?php echo htmlspecialchars($settings['products_per_page']); ?>" min="1" max="100">
                                            <div class="setting-description">Number of products to show per page in listings</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-section">
                                        <h5 class="section-title">Homepage Layout</h5>
                                        <div class="row">
                                            <?php foreach ($homepage_layouts as $layout => $label): ?>
                                            <div class="col-md-3 mb-3">
                                                <div class="layout-preview <?php echo $settings['homepage_layout'] == $layout ? 'selected' : ''; ?>" 
                                                     onclick="selectLayout('homepage', '<?php echo $layout; ?>')">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="homepage_layout" 
                                                               value="<?php echo $layout; ?>" id="home_<?php echo $layout; ?>"
                                                               <?php echo $settings['homepage_layout'] == $layout ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="home_<?php echo $layout; ?>">
                                                            <?php echo $label; ?>
                                                        </label>
                                                    </div>
                                                    <div class="mt-2 text-center">
                                                        <i class="fas fa-th-large fa-2x text-muted"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-section">
                                        <h5 class="section-title">Homepage Sections</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label d-block">Featured Products</label>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="show_featured_products" value="1" 
                                                           <?php echo $settings['show_featured_products'] == '1' ? 'checked' : ''; ?>>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                <span class="ms-2">Show featured products section</span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label d-block">Best Sellers</label>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="show_best_sellers" value="1" 
                                                           <?php echo $settings['show_best_sellers'] == '1' ? 'checked' : ''; ?>>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                <span class="ms-2">Show best-selling products section</span>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label d-block">New Arrivals</label>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="show_new_arrivals" value="1" 
                                                           <?php echo $settings['show_new_arrivals'] == '1' ? 'checked' : ''; ?>>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                <span class="ms-2">Show new arrivals section</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="submit" name="update_appearance" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Appearance Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Social Media Settings Tab -->
                <div class="tab-pane fade" id="social" role="tabpanel">
                    <div class="settings-card social">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-share-alt"></i>Social Media Integration
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Social Media Profiles</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Facebook URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon facebook">
                                                    <i class="fab fa-facebook-f"></i>
                                                </span>
                                                <input type="url" class="form-control" name="facebook_url" 
                                                       value="<?php echo htmlspecialchars($settings['facebook_url']); ?>" 
                                                       placeholder="https://facebook.com/yourpage">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Instagram URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon instagram">
                                                    <i class="fab fa-instagram"></i>
                                                </span>
                                                <input type="url" class="form-control" name="instagram_url" 
                                                       value="<?php echo htmlspecialchars($settings['instagram_url']); ?>" 
                                                       placeholder="https://instagram.com/yourprofile">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Twitter URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon twitter">
                                                    <i class="fab fa-twitter"></i>
                                                </span>
                                                <input type="url" class="form-control" name="twitter_url" 
                                                       value="<?php echo htmlspecialchars($settings['twitter_url']); ?>" 
                                                       placeholder="https://twitter.com/yourprofile">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">YouTube URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon youtube">
                                                    <i class="fab fa-youtube"></i>
                                                </span>
                                                <input type="url" class="form-control" name="youtube_url" 
                                                       value="<?php echo htmlspecialchars($settings['youtube_url']); ?>" 
                                                       placeholder="https://youtube.com/yourchannel">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Additional Platforms</h5>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">WhatsApp Number</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon whatsapp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </span>
                                                <input type="text" class="form-control" name="whatsapp_number" 
                                                       value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>" 
                                                       placeholder="+94123456789">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">LinkedIn URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon linkedin">
                                                    <i class="fab fa-linkedin-in"></i>
                                                </span>
                                                <input type="url" class="form-control" name="linkedin_url" 
                                                       value="<?php echo htmlspecialchars($settings['linkedin_url']); ?>" 
                                                       placeholder="https://linkedin.com/company/yourcompany">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">TikTok URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text social-icon tiktok">
                                                    <i class="fab fa-tiktok"></i>
                                                </span>
                                                <input type="url" class="form-control" name="tiktok_url" 
                                                       value="<?php echo htmlspecialchars($settings['tiktok_url']); ?>" 
                                                       placeholder="https://tiktok.com/@yourprofile">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5 class="section-title">Social Features</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Social Sharing</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_social_sharing" value="1" 
                                                       <?php echo $settings['enable_social_sharing'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Allow customers to share products on social media</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Social Login</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_social_login" value="1" 
                                                       <?php echo $settings['enable_social_login'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Allow login with social media accounts <span class="badge badge-new">New</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_social" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Social Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SEO Settings Tab -->
                <div class="tab-pane fade" id="seo" role="tabpanel">
                    <div class="settings-card seo">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-search"></i>SEO & Analytics
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Meta Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Meta Title</label>
                                            <input type="text" class="form-control" name="meta_title" 
                                                   value="<?php echo htmlspecialchars($settings['meta_title']); ?>">
                                            <div class="setting-description">Page title for search engines (50-60 characters recommended)</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" name="meta_description" rows="3"><?php echo htmlspecialchars($settings['meta_description']); ?></textarea>
                                            <div class="setting-description">Page description for search engines (150-160 characters recommended)</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Meta Keywords</label>
                                            <input type="text" class="form-control" name="meta_keywords" 
                                                   value="<?php echo htmlspecialchars($settings['meta_keywords']); ?>">
                                            <div class="setting-description">Comma-separated keywords for SEO</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Canonical URL</label>
                                            <input type="url" class="form-control" name="canonical_url" 
                                                   value="<?php echo htmlspecialchars($settings['canonical_url']); ?>">
                                            <div class="setting-description">Preferred URL for duplicate content</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Analytics & Verification</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Google Analytics ID</label>
                                            <input type="text" class="form-control" name="google_analytics_id" 
                                                   value="<?php echo htmlspecialchars($settings['google_analytics_id']); ?>">
                                            <div class="setting-description">Your Google Analytics tracking ID (e.g., G-XXXXXXXXXX)</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Google Site Verification</label>
                                            <input type="text" class="form-control" name="google_site_verification" 
                                                   value="<?php echo htmlspecialchars($settings['google_site_verification']); ?>">
                                            <div class="setting-description">Google Search Console verification code</div>
                                        </div>
                                    </div>
                                    <div class="form-section">
                                        <h5 class="section-title">SEO Features</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Auto Sitemap Generation</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_sitemap" value="1" 
                                                       <?php echo $settings['enable_sitemap'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Automatically generate XML sitemap for search engines</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Structured Data</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_structured_data" value="1" 
                                                       <?php echo $settings['enable_structured_data'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Add structured data markup for better search results <span class="badge badge-new">New</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_seo" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save SEO Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Settings Tab -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="settings-card security">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-shield-alt"></i>Security Settings
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Website Security</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Force HTTPS</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_https" value="1" 
                                                       <?php echo $settings['enable_https'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Redirect all traffic to HTTPS (requires SSL certificate)</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">CAPTCHA Protection</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_captcha" value="1" 
                                                       <?php echo $settings['enable_captcha'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Enable CAPTCHA on login and contact forms</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">CAPTCHA Site Key</label>
                                            <input type="text" class="form-control" name="captcha_site_key" 
                                                   value="<?php echo htmlspecialchars($settings['captcha_site_key']); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">CAPTCHA Secret Key</label>
                                            <input type="password" class="form-control" name="captcha_secret_key" 
                                                   value="<?php echo htmlspecialchars($settings['captcha_secret_key']); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Access Control</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Max Login Attempts</label>
                                            <input type="number" class="form-control" name="max_login_attempts" 
                                                   value="<?php echo htmlspecialchars($settings['max_login_attempts']); ?>" min="1" max="10">
                                            <div class="setting-description">Number of failed login attempts before temporary lockout</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Session Timeout (Minutes)</label>
                                            <input type="number" class="form-control" name="session_timeout" 
                                                   value="<?php echo htmlspecialchars($settings['session_timeout']); ?>" min="5" max="1440">
                                            <div class="setting-description">User session duration in minutes</div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Two-Factor Authentication</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_2fa" value="1" 
                                                       <?php echo $settings['enable_2fa'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Enable 2FA for admin accounts <span class="badge badge-new">New</span></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">IP Blocking</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="block_suspicious_ips" value="1" 
                                                       <?php echo $settings['block_suspicious_ips'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Automatically block suspicious IP addresses</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_security" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Security Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Maintenance Settings Tab -->
                <div class="tab-pane fade" id="maintenance" role="tabpanel">
                    <div class="settings-card maintenance">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-tools"></i>Maintenance & Backups
                            </h4>
                        </div>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">Maintenance Mode</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Enable Maintenance Mode</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="maintenance_mode" value="1" 
                                                       <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Show maintenance page to visitors (admins can still access)</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Maintenance Message</label>
                                            <textarea class="form-control" name="maintenance_message" rows="3"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                                            <div class="setting-description">Message to display during maintenance</div>
                                        </div>
                                    </div>
                                    <div class="form-section">
                                        <h5 class="section-title">Backup Settings</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Automatic Backups</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_backups" value="1" 
                                                       <?php echo $settings['enable_backups'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Automatically backup database and files</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Backup Frequency</label>
                                            <select class="form-select" name="backup_frequency">
                                                <?php foreach ($backup_frequencies as $freq => $label): ?>
                                                <option value="<?php echo $freq; ?>" 
                                                    <?php echo $settings['backup_frequency'] == $freq ? 'selected' : ''; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="setting-description">How often to create automatic backups</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-section">
                                        <h5 class="section-title">System Monitoring</h5>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Error Logging</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_error_logging" value="1" 
                                                       <?php echo $settings['enable_error_logging'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Log PHP errors and exceptions for debugging</span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label d-block">Performance Monitoring</label>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="enable_performance_monitoring" value="1" 
                                                       <?php echo $settings['enable_performance_monitoring'] == '1' ? 'checked' : ''; ?>>
                                                <span class="toggle-slider"></span>
                                            </label>
                                            <span class="ms-2">Monitor website performance and load times <span class="badge badge-new">New</span></span>
                                        </div>
                                    </div>
                                    <div class="form-section">
                                        <h5 class="section-title">Quick Actions</h5>
                                        <div class="d-grid gap-2">
                                            <button type="button" class="btn btn-outline-primary" onclick="createBackup()">
                                                <i class="fas fa-download me-2"></i>Create Manual Backup
                                            </button>
                                            <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                                                <i class="fas fa-broom me-2"></i>Clear System Cache
                                            </button>
                                            <button type="button" class="btn btn-outline-info" onclick="viewLogs()">
                                                <i class="fas fa-file-alt me-2"></i>View Error Logs
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_maintenance" class="btn btn-save">
                                    <i class="fas fa-save me-2"></i>Save Maintenance Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Mobile menu functionality
    function toggleMobileMenu() {
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.mobile-overlay');
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    }
    
    // Payment method selection
    function togglePaymentMethod(method) {
        const checkbox = document.getElementById('pay_' + method);
        const option = checkbox.closest('.payment-method-option');
        
        checkbox.checked = !checkbox.checked;
        if (checkbox.checked) {
            option.classList.add('selected');
        } else {
            option.classList.remove('selected');
        }
    }
    
    // Layout selection
    function selectLayout(type, layout) {
        document.querySelectorAll(`input[name="${type}_layout"]`).forEach(radio => {
            const preview = radio.closest('.layout-preview');
            if (radio.value === layout) {
                radio.checked = true;
                preview.classList.add('selected');
            } else {
                preview.classList.remove('selected');
            }
        });
    }
    
    // Font preview update
    function updateFontPreview() {
        const fontSelect = document.querySelector('select[name="theme_font_family"]');
        const fontPreview = document.getElementById('fontPreview');
        const selectedFont = fontSelect.value;
        fontPreview.style.fontFamily = `'${selectedFont}', sans-serif`;
    }
    
    // Color picker preview
    document.querySelectorAll('input[type="color"]').forEach(picker => {
        picker.addEventListener('input', function() {
            const preview = this.previousElementSibling;
            preview.style.backgroundColor = this.value;
            updateThemePreview();
        });
    });
    
    // Theme preview update
    function updateThemePreview() {
        const primaryColor = document.getElementById('primaryColor').value;
        const secondaryColor = document.getElementById('secondaryColor').value;
        const preview = document.querySelector('.theme-preview');
        preview.style.background = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;
        
        // Update CSS variables for real-time preview
        document.documentElement.style.setProperty('--primary-color', primaryColor);
        document.documentElement.style.setProperty('--secondary-color', secondaryColor);
    }
    
    // Initialize payment method visual states
    document.addEventListener('DOMContentLoaded', function() {
        // Payment methods
        document.querySelectorAll('input[name="payment_methods[]"]').forEach(checkbox => {
            const option = checkbox.closest('.payment-method-option');
            if (checkbox.checked) {
                option.classList.add('selected');
            }
        });
        
        // Layout selections
        document.querySelectorAll('.layout-preview').forEach(preview => {
            const radio = preview.querySelector('input[type="radio"]');
            if (radio.checked) {
                preview.classList.add('selected');
            }
        });
        
        // Initialize theme preview
        updateThemePreview();
    });
    
    // Maintenance actions
    function createBackup() {
        if (confirm('Create a manual backup of the database and files?')) {
            alert('Backup process started... This may take a few minutes.');
            // In a real implementation, you would call a PHP script via AJAX
        }
    }
    
    function clearCache() {
        if (confirm('Clear all system cache? This may temporarily slow down the site.')) {
            alert('Cache cleared successfully!');
            // In a real implementation, you would call a PHP script via AJAX
        }
    }
    
    function viewLogs() {
        alert('Opening error logs...');
        // In a real implementation, you would open a log viewer modal
    }
    
    // Tab persistence
    document.addEventListener('DOMContentLoaded', function() {
        // Remember active tab
        const activeTab = localStorage.getItem('activeSettingsTab');
        if (activeTab) {
            const tab = new bootstrap.Tab(document.getElementById(activeTab + '-tab'));
            tab.show();
        }
        
        // Save active tab on change
        document.querySelectorAll('#settingsTabs button').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (e) {
                const activeTab = e.target.getAttribute('id').replace('-tab', '');
                localStorage.setItem('activeSettingsTab', activeTab);
            });
        });
    });
    </script>
</body>
</html>