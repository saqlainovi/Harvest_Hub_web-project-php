<?php
/**
 * Cart Management Functions
 */

// Initialize cart
function initialize_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Calculate cart totals
    update_cart_totals();
}

// Add item to cart - Legacy version for fruit products
function add_to_cart($fruit_id, $quantity = 1) {
    global $conn;
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Get fruit details
    $fruit_sql = "SELECT f.*, s.farm_name 
                 FROM fruits f 
                 JOIN seller_profiles s ON f.seller_id = s.seller_id 
                 WHERE f.fruit_id = ?";
    
    $stmt = $conn->prepare($fruit_sql);
    $stmt->bind_param("i", $fruit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $fruit = $result->fetch_assoc();
        
        $cart_key = 'fruit_' . $fruit_id;
        
        // Check if already in cart
        if (isset($_SESSION['cart'][$cart_key])) {
            // Update quantity
            $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
        } else {
            // Add new item
            $_SESSION['cart'][$cart_key] = [
                'product_id' => $fruit_id,
                'product_type' => 'fruit',
                'name' => $fruit['name'],
                'price' => $fruit['price_per_kg'],
                'image' => $fruit['image'],
                'seller' => $fruit['farm_name'],
                'quantity' => $quantity
            ];
        }
        
        // Update cart totals
        update_cart_totals();
        
        return [
            'success' => true,
            'message' => $quantity . ' ' . $fruit['name'] . ' added to cart.'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Fruit not found.'
    ];
}

// Update cart item quantity
function update_cart_item($item_id, $quantity, $item_type) {
    $cart_key = $item_type . '_' . $item_id;
    
    // Check if cart and item exist
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$cart_key])) {
        return [
            'success' => false,
            'message' => 'Item not found in cart.'
        ];
    }
    
    if ($quantity <= 0) {
        // Remove item if quantity is 0 or negative
        return remove_from_cart($item_id, $item_type);
    } else {
        // Update quantity
        $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
        
        // Update cart totals
        update_cart_totals();
        
        return [
            'success' => true,
            'message' => 'Cart updated.'
        ];
    }
}

// Remove item from cart
function remove_from_cart($item_id, $item_type) {
    $cart_key = $item_type . '_' . $item_id;
    
    // Check if cart and item exist
    if (!isset($_SESSION['cart']) || !isset($_SESSION['cart'][$cart_key])) {
        return [
            'success' => false,
            'message' => 'Item not found in cart.'
        ];
    }
    
    // Store item name for message
    $item_name = $_SESSION['cart'][$cart_key]['name'];
    
    // Remove item
    unset($_SESSION['cart'][$cart_key]);
    
    // Update cart totals
    update_cart_totals();
    
    return [
        'success' => true,
        'message' => $item_name . ' removed from cart.'
    ];
}

// Clear cart
function clear_cart() {
    $_SESSION['cart'] = [];
    $_SESSION['cart_count'] = 0;
}

// Update cart totals
function update_cart_totals() {
    $total_quantity = 0;
    $total_price = 0;
    
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            // Skip non-array items and missing properties
            if (!is_array($item) || !isset($item['quantity']) || !isset($item['price'])) {
                continue;
            }
            
            $item_quantity = intval($item['quantity']);
            $item_price = floatval($item['price']);
            
            $total_quantity += $item_quantity;
            $total_price += $item_quantity * $item_price;
        }
    }
    
    // Set cart count for header display
    $_SESSION['cart_count'] = $total_quantity;
}

// Get cart contents
function get_cart_contents() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart = [
        'items' => [],
        'total_quantity' => 0,
        'total_price' => 0
    ];
    
    $total_quantity = 0;
    $total_price = 0;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        // Skip this item if it's not an array
        if (!is_array($item)) {
            continue;
        }
        
        // Handle various cart item formats
        $parts = explode('_', $key);
        $item_type = isset($parts[0]) ? $parts[0] : 'unknown';
        $item_id = isset($parts[1]) ? $parts[1] : 0;
        
        // Make sure price and quantity exist
        if (!isset($item['price']) || !isset($item['quantity'])) {
            continue;
        }
        
        $price = floatval($item['price']);
        $quantity = intval($item['quantity']);
        
        $subtotal = $price * $quantity;
        $total_quantity += $quantity;
        $total_price += $subtotal;
        
        $cart_item = $item;
        $cart_item['item_id'] = $item_id; 
        $cart_item['item_type'] = $item_type;
        $cart_item['subtotal'] = $subtotal;
        
        $cart['items'][$key] = $cart_item;
    }
    
    $cart['total_quantity'] = $total_quantity;
    $cart['total_price'] = $total_price;
    
    // Store cart count in session for header display
    $_SESSION['cart_count'] = $total_quantity;
    
    return $cart;
}

// Check if cart is empty
function is_cart_empty() {
    if (!isset($_SESSION['cart'])) {
        return true;
    }
    
    // Filter out non-array items
    $valid_items = array_filter($_SESSION['cart'], function($item) {
        return is_array($item) && isset($item['quantity']) && isset($item['price']);
    });
    
    return empty($valid_items);
}
?> 