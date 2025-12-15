<?php
// includes/apply_settings.php
function getStoreSettings() {
    global $pdo;
    $stmt = $pdo->query("SELECT key_name, value FROM settings");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

$settings = getStoreSettings();

// Apply dark mode if enabled
if (isset($settings['enable_dark_mode']) && $settings['enable_dark_mode'] == '1') {
    echo '<script>document.documentElement.classList.add("dark-mode");</script>';
}
?>