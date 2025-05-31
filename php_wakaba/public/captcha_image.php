<?php

// public/captcha_image.php

// Define WAKABA_BASE_DIR if not already set (e.g., if this script is accessed directly)
if (!defined('WAKABA_BASE_DIR')) {
    define('WAKABA_BASE_DIR', dirname(__DIR__));
}

require_once WAKABA_BASE_DIR . '/config/config.php';
require_once WAKABA_BASE_DIR . '/src/Database.php'; // Needed for Captcha::storeCaptcha
require_once WAKABA_BASE_DIR . '/src/utils.php';    // For get_user_ip
require_once WAKABA_BASE_DIR . '/src/Captcha.php';

// CAPTCHA Image Generation Script

// Ensure built-in CAPTCHA is enabled
if (!defined('ENABLE_CAPTCHA') || (ENABLE_CAPTCHA !== 'builtin' && ENABLE_CAPTCHA !== 'captcha')) {
    header("HTTP/1.1 404 Not Found");
    echo "CAPTCHA not enabled or misconfigured.";
    exit;
}

// Database connection (required for storing CAPTCHA text)
$db = null;
try {
    $db_options = $GLOBALS['config_global']['DB_OPTIONS'] ?? [];
    $db = new Database(DB_DSN, DB_USER, DB_PASS, $db_options);
} catch (PDOException $e) {
    // Log error appropriately in a real application
    header("HTTP/1.1 500 Internal Server Error");
    echo "Database connection error for CAPTCHA.";
    error_log("CAPTCHA DB Error: " . $e->getMessage());
    exit;
}

// Get IP and parent (if any)
$user_ip = get_user_ip(); // From utils.php
$parent_id_str = $_GET['parent'] ?? null;
$parent_id = null;
if ($parent_id_str && is_numeric($parent_id_str) && intval($parent_id_str) > 0) {
    $parent_id = intval($parent_id_str);
}
$page_key = Captcha::getCaptchaPageKey($parent_id);

// Generate CAPTCHA text
$captcha_text = Captcha::generateCaptchaText(defined('CAPTCHA_LENGTH') ? CAPTCHA_LENGTH : 6);

// Store CAPTCHA text in database
if (!Captcha::storeCaptcha($db, $user_ip, $page_key, $captcha_text)) {
    // Log error appropriately
    header("HTTP/1.1 500 Internal Server Error");
    echo "Failed to store CAPTCHA data.";
    error_log("CAPTCHA store error for IP: {$user_ip}, Key: {$page_key}");
    exit;
}

// Define font path - use the dummy font for now
$font_path = WAKABA_BASE_DIR . '/resources/fonts/' . (defined('CAPTCHA_TTF_FONT') ? CAPTCHA_TTF_FONT : 'dummy_font.ttf');
if (!file_exists($font_path)){
    // If dummy_font.ttf (or configured one) doesn't exist, pass null to use GD built-in font
    $font_path = null;
    // error_log("CAPTCHA font not found: " . $font_path . ". Using GD built-in font.");
}


// Create and output the image
Captcha::createCaptchaImage($captcha_text, $font_path);

exit;
?>
