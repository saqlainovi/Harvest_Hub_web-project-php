<?php
$page_title = "Checkout";
require_once '../includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('warning', 'Please log in to proceed with checkout.');
    redirect(SITE_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['PHP_SELF']));
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    set_flash_message('warning', 'Your cart is empty. Please add some items before checkout.');
    redirect(SITE_URL . '/index.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);
$user = ($user_result && $user_result->num_rows > 0) ? $user_result->fetch_assoc() : null;

// Calculate cart totals
$cart_items = [];
$total_price = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    // Skip invalid items
    if (!is_array($item) || !isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) {
        continue;
    }
    
    $parts = explode('_', $key);
    $item_type = isset($parts[0]) ? $parts[0] : 'unknown';
    $item_id = isset($parts[1]) ? $parts[1] : 0;
    
    $subtotal = $item['price'] * $item['quantity'];
    $total_price += $subtotal;
    
    // Determine image path
    if ($item_type === 'agricultural') {
        $image_folder = 'agricultural';
    } else {
        $image_folder = 'fruits';
    }
    
    $display_path = !empty($item['image']) 
        ? SITE_URL . '/images/' . $image_folder . '/' . $item['image'] 
        : SITE_URL . '/images/placeholder.jpg';
    
    $cart_items[] = [
        'key' => $key,
        'id' => $item_id,
        'type' => $item_type,
        'name' => $item['name'],
        'price' => $item['price'],
        'quantity' => $item['quantity'],
        'subtotal' => $subtotal,
        'image' => $display_path,
        'seller' => $item['seller'] ?? 'Unknown Seller'
    ];
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $delivery_address = isset($_POST['delivery_address']) ? sanitize_input($_POST['delivery_address']) : '';
    $payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : '';
    
    $errors = [];
    
    if (empty($delivery_address)) {
        $errors[] = 'Delivery address is required.';
    }
    
    if (!in_array($payment_method, ['credit_card', 'bkash', 'cash_on_delivery'])) {
        $errors[] = 'Please select a valid payment method.';
    }
    
    // If no errors, proceed with order
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Create order
            $order_sql = "INSERT INTO orders (buyer_id, total_amount, delivery_address, payment_method, order_status) 
                         VALUES (?, ?, ?, ?, 'pending')";
            $order_stmt = $conn->prepare($order_sql);
            $order_stmt->bind_param("idss", $user_id, $total_price, $delivery_address, $payment_method);
            
            if ($order_stmt->execute()) {
                $order_id = $conn->insert_id;
                
                // Add order items
                foreach ($cart_items as $item) {
                    // Insert order item - use fruit_id instead of product_id and price_per_kg instead of price
                    $items_sql = "INSERT INTO order_items (order_id, fruit_id, quantity, price_per_kg, subtotal) 
                                VALUES (?, ?, ?, ?, ?)";
                    
                    $items_stmt = $conn->prepare($items_sql);
                    $items_stmt->bind_param("iiddd", $order_id, $item['id'], $item['quantity'], $item['price'], $item['subtotal']);
                    $items_stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Set success message
                set_flash_message('success', 'Your order has been placed successfully!');
                
                // Redirect to order confirmation
                redirect(SITE_URL . '/pages/order_confirmation.php?order_id=' . $order_id);
            } else {
                throw new Exception("Failed to create order.");
            }
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            set_flash_message('error', 'An error occurred: ' . $e->getMessage());
        }
    } else {
        // Set error messages
        foreach ($errors as $error) {
            set_flash_message('error', $error);
        }
    }
}

include('../includes/header.php');
?>

<section class="checkout-page">
    <div class="container">
        <h2>Checkout</h2>
        
        <?php display_flash_messages(); ?>
        
        <div class="checkout-grid">
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div class="item-info">
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?> <?php echo ($item['type'] === 'agricultural') ? 'kg' : ''; ?></p>
                                    <?php if(isset($item['seller'])): ?>
                                        <p class="seller">Seller: <?php echo htmlspecialchars($item['seller']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-price">
                                <?php echo format_price($item['subtotal']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="summary-total">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span><?php echo format_price($total_price); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping:</span>
                        <span>Free</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <span><?php echo format_price($total_price); ?></span>
                    </div>
                </div>
                
                <div class="back-to-cart">
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php">← Back to Cart</a>
                </div>
            </div>
            
            <!-- Checkout Form -->
            <div class="checkout-form">
                <h3>Shipping & Payment</h3>
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="form-section">
                        <h4>Contact Information</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Shipping Address</h4>
                        <div class="form-group">
                            <label for="delivery_address">Delivery Address</label>
                            <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4>Payment Method</h4>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="credit_card" name="payment_method" value="credit_card">
                                <label for="credit_card">
                                    <i class="fas fa-credit-card"></i>
                                    Credit Card
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="bkash" name="payment_method" value="bkash">
                                <label for="bkash">
                                    <i class="fas fa-mobile-alt"></i>
                                    bKash
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="cash_on_delivery" name="payment_method" value="cash_on_delivery" checked>
                                <label for="cash_on_delivery">
                                    <i class="fas fa-money-bill-wave"></i>
                                    Cash on Delivery
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Place Order</button>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
.checkout-page {
    padding: 60px 0;
}

.checkout-page h2 {
    margin-bottom: 30px;
    text-align: center;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.order-summary, .checkout-form {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
}

.order-summary h3, .checkout-form h3 {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.summary-items {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.item-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.item-info img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
    margin-right: 15px;
}

.item-info h4 {
    margin: 0 0 5px;
    font-size: 1rem;
}

.item-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.seller {
    font-size: 0.8rem;
    color: #888;
    font-style: italic;
}

.item-price {
    font-weight: bold;
    font-size: 1.1rem;
    color: #4CAF50;
}

.summary-total {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.grand-total {
    font-size: 1.2rem;
    font-weight: bold;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
}

.back-to-cart {
    margin-top: 20px;
    text-align: center;
}

.back-to-cart a {
    color: #666;
    text-decoration: none;
}

.back-to-cart a:hover {
    text-decoration: underline;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h4 {
    margin-bottom: 15px;
    color: #555;
    font-size: 1.1rem;
}

.form-row {
    display: flex;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
    width: 100%;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    resize: vertical;
}

.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.payment-method {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
}

.payment-method:hover {
    background: #f9f9f9;
}

.payment-method input[type="radio"] {
    margin-right: 15px;
}

.payment-method label {
    display: flex;
    align-items: center;
    cursor: pointer;
    width: 100%;
}

.payment-method i {
    margin-right: 10px;
    font-size: 1.1rem;
    color: #555;
}

.btn-primary {
    background: #4CAF50;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    width: 100%;
    transition: background 0.3s;
}

.btn-primary:hover {
    background: #45a049;
}

@media (max-width: 768px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>

<?php include('../includes/footer.php'); ?> 