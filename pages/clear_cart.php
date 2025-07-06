<?php
require_once '../includes/config.php';

// Clear the cart in session
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Cart cleared successfully'
]);
?> 