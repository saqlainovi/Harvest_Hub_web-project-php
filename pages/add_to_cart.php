<?php
require_once '../includes/config.php';

// TEMPORARY: Skip login check for demonstration
/*
// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to add items to cart.');
    redirect(SITE_URL . '/login.php');
}
*/

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Debug: Log the incoming POST request
if (DEBUG_MODE) {
    log_error("Add to Cart Request", $_POST);
}

// Get product details from form with proper validation
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$product_type = isset($_POST['product_type']) ? htmlspecialchars($_POST['product_type']) : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate inputs
if ($product_id <= 0) {
    set_flash_message('error', 'Invalid product ID');
    redirect(SITE_URL . '/index.php');
    exit;
}

if (empty($product_type) || !in_array($product_type, ['fruit', 'agricultural'])) {
    set_flash_message('error', 'Invalid product type');
    redirect(SITE_URL . '/index.php');
    exit;
}

if ($quantity <= 0 || $quantity > 99) {
    set_flash_message('error', 'Invalid quantity. Please select between 1 and 99.');
    redirect(SITE_URL . '/index.php');
    exit;
}

// Check if the product exists and is available
if ($product_type === 'agricultural') {
    // For agricultural products
    $sql = "SELECT ap.*, s.farm_name 
            FROM agricultural_products ap
            JOIN seller_profiles s ON ap.seller_id = s.seller_id
            WHERE ap.product_id = ? AND ap.is_available = 1";
    $product_page = SITE_URL . '/pages/agricultural_product_details.php?id=' . $product_id;
} else {
    // For fruits
    $sql = "SELECT f.*, s.farm_name 
            FROM fruits f
            JOIN seller_profiles s ON f.seller_id = s.seller_id
            WHERE f.fruit_id = ? AND f.is_available = 1";
    $product_page = SITE_URL . '/pages/fruit_details.php?id=' . $product_id;
}

// Prepare and execute query with proper error handling
try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows == 0) {
        set_flash_message('error', 'Product not found or unavailable.');
        redirect(SITE_URL . '/index.php');
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Check if quantity is valid
    if ($quantity > $product['stock_quantity']) {
        set_flash_message('error', 'Not enough stock available. Only ' . $product['stock_quantity'] . ' available.');
        redirect($product_page);
        exit;
    }
    
    // Generate a unique cart item key
    $cart_key = $product_type . '_' . $product_id;
    
    // Check if product is already in cart
    if (isset($_SESSION['cart'][$cart_key])) {
        // Update quantity (prevent exceeding stock)
        $new_quantity = min($product['stock_quantity'], $_SESSION['cart'][$cart_key]['quantity'] + $quantity);
        $_SESSION['cart'][$cart_key]['quantity'] = $new_quantity;
        
        $message = 'Updated quantity. Cart now has ' . $new_quantity . ' ' . 
                   (($product_type === 'agricultural') ? 'kg of ' : '') . 
                   $product['name'] . '.';
    } else {
        // Add new product to cart
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'product_type' => $product_type,
            'name' => $product['name'],
            'price' => $product['price_per_kg'],
            'quantity' => $quantity,
            'image' => $product['image'] ?? 'placeholder.jpg',
            'seller' => $product['farm_name'] ?? 'Unknown Seller',
            'stock' => $product['stock_quantity']
        ];
        
        $message = 'Added ' . $quantity . ' ' . 
                   (($product_type === 'agricultural') ? 'kg of ' : '') . 
                   $product['name'] . ' to your cart!';
    }
    
    // Update cart totals
    update_cart_totals();
    
    // Set success message
    set_flash_message('success', $message);
    
    // Redirect based on preference
    if (isset($_POST['redirect_to_cart']) && $_POST['redirect_to_cart'] == 1) {
        redirect(SITE_URL . '/pages/cart.php');
    } else if (isset($_POST['return_to_product']) && $_POST['return_to_product'] == 1) {
        redirect($product_page);
    } else {
        // Default: go back to product listing
        if ($product_type === 'agricultural') {
            redirect(SITE_URL . '/pages/agricultural_products.php');
        } else {
            redirect(SITE_URL . '/pages/fruits.php');
        }
    }
    
} catch (Exception $e) {
    // Log error and inform user
    if (DEBUG_MODE) {
        log_error("Cart Error", $e->getMessage());
    }
    
    set_flash_message('error', 'An error occurred while adding to cart. Please try again.');
    redirect($product_page);
    exit;
}
?> 