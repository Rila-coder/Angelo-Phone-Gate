<?php
// logout.php - Logout Script
session_start();

// Save cart data temporarily (preserve cart for guest sessions)
$temp_cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : null;

// Destroy all session data
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start new session and restore cart for guest
session_start();
if ($temp_cart) {
    $_SESSION['cart'] = $temp_cart;
}

// Redirect to homepage
header('Location: index.php');
exit;
?>