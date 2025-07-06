<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'Harvest Hub');
define('SITE_URL', 'http://localhost/asraf idp2');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/asraf idp2/images/uploads/');
define('UPLOAD_URL', SITE_URL . '/images/uploads/');
define('CURRENCY_SYMBOL', '$');

// Debug mode (set to false in production)
define('DEBUG_MODE', true);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Error log function
function log_error($message, $data = null) {
    if (DEBUG_MODE) {
        $log_file = __DIR__ . '/../logs/error.log';
        $log_dir = dirname($log_file);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] {$message}";
        
        if ($data !== null) {
            $log_message .= "\nData: " . print_r($data, true);
        }
        
        $log_message .= "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Include database connection
require_once 'db_connect.php';

// Include functions
require_once 'functions.php';
require_once 'cart_functions.php';
?> 