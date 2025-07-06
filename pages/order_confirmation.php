<?php
$page_title = "Order Confirmation";
require_once '../includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_flash_message('warning', 'Please log in to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    set_flash_message('error', 'Invalid order ID.');
    redirect(SITE_URL . '/index.php');
}

$order_id = (int) $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Get order details
$order_sql = "SELECT o.*, u.full_name, u.email, COALESCE(u.phone, 'N/A') as phone 
              FROM orders o
              JOIN users u ON o.buyer_id = u.user_id
              WHERE o.order_id = $order_id AND o.buyer_id = $user_id";
$order_result = $conn->query($order_sql);

// Check if order exists and belongs to current user
if (!$order_result || $order_result->num_rows === 0) {
    set_flash_message('error', 'Order not found or you are not authorized to view it.');
    redirect(SITE_URL . '/index.php');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_sql = "SELECT oi.*, f.name, f.image, sp.farm_name
             FROM order_items oi
             JOIN fruits f ON oi.fruit_id = f.fruit_id
             JOIN seller_profiles sp ON f.seller_id = sp.seller_id
             WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_sql);
$order_items = [];

if ($items_result && $items_result->num_rows > 0) {
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
}

include('../includes/header.php');
?>

<section class="order-confirmation">
    <div class="container">
        <div class="confirmation-card">
            <div class="confirmation-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thank You for Your Order!</h2>
                <p>Your order has been placed successfully and is now being processed.</p>
            </div>

            <div class="order-number">
                <h3>Order #<?php echo $order_id; ?></h3>
                <p class="order-date"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></p>
            </div>
            
            <div class="order-summary">
                <div class="summary-section">
                    <div class="order-progress">
                        <div class="progress-step <?php echo $order['order_status'] === 'pending' || $order['order_status'] === 'confirmed' || $order['order_status'] === 'shipped' || $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                            <span class="step-label">Order Placed</span>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step <?php echo $order['order_status'] === 'confirmed' || $order['order_status'] === 'shipped' || $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                            <span class="step-label">Processing</span>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step <?php echo $order['order_status'] === 'shipped' || $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                            <span class="step-label">Shipped</span>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step <?php echo $order['order_status'] === 'delivered' ? 'active' : ''; ?>">
                            <span class="step-label">Delivered</span>
                        </div>
                    </div>
                </div>
                
                <div class="order-details-grid">
                    <div class="summary-section">
                        <h4><i class="fas fa-map-marker-alt"></i> Delivery Information</h4>
                        <div class="details-content">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['full_name'] ?? ''); ?></p>
                            <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['delivery_address'] ?? '')); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'] ?? ''); ?></p>
                        </div>
                    </div>
                    
                    <div class="summary-section">
                        <h4><i class="fas fa-credit-card"></i> Payment Information</h4>
                        <div class="details-content">
                            <p><strong>Method:</strong> <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></p>
                            <p><strong>Status:</strong> <span class="payment-badge payment-status-<?php echo strtolower($order['payment_status']); ?>"><?php echo ucfirst($order['payment_status']); ?></span></p>
                            <p><strong>Total Amount:</strong> <span class="total-price"><?php echo format_price($order['total_amount']); ?></span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="order-items-section">
                <h4><i class="fas fa-shopping-basket"></i> Order Items</h4>
                <div class="order-items">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo !empty($item['image']) ? SITE_URL . '/images/fruits/' . $item['image'] : SITE_URL . '/images/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>">
                            </div>
                            <div class="item-details">
                                <h5><?php echo htmlspecialchars($item['name'] ?? ''); ?></h5>
                                <p class="item-seller">Seller: <?php echo htmlspecialchars($item['farm_name'] ?? ''); ?></p>
                                <p class="item-price"><?php echo format_price($item['price_per_kg']); ?> per kg × <?php echo $item['quantity']; ?> kg</p>
                            </div>
                            <div class="item-subtotal">
                                <?php echo format_price($item['subtotal']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="order-total">
                        <span>Total:</span>
                        <span class="total-amount"><?php echo format_price($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="confirmation-actions">
                <a href="<?php echo SITE_URL; ?>/pages/fruits.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-basket"></i> Continue Shopping
                </a>
                <?php if (has_role('buyer')): ?>
                    <a href="<?php echo SITE_URL; ?>/buyer/orders.php" class="btn btn-primary">
                        <i class="fas fa-list-alt"></i> View All Orders
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.order-confirmation {
    padding: 60px 0;
    background-color: #f9f9f9;
}

.confirmation-card {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    max-width: 900px;
    margin: 0 auto;
}

.confirmation-header {
    background: linear-gradient(to right, #4CAF50, #2E7D32);
    padding: 40px 20px;
    text-align: center;
    color: white;
}

.success-icon {
    width: 80px;
    height: 80px;
    background-color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.success-icon i {
    font-size: 40px;
    color: #4CAF50;
}

.confirmation-header h2 {
    margin-bottom: 10px;
    color: white;
}

.confirmation-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.order-number {
    padding: 20px;
    background-color: #f8f8f8;
    text-align: center;
    border-bottom: 1px solid #eee;
}

.order-number h3 {
    margin-bottom: 5px;
    color: #333;
    font-size: 1.3rem;
}

.order-date {
    color: #666;
}

.order-summary {
    padding: 30px;
}

.summary-section {
    margin-bottom: 30px;
}

.summary-section h4 {
    margin-bottom: 15px;
    color: #333;
    display: flex;
    align-items: center;
    font-size: 1.2rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.summary-section h4 i {
    margin-right: 10px;
    color: #4CAF50;
}

/* Order Progress Bar */
.order-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 50px 0 30px;
    position: relative;
}

.order-progress::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background-color: #e0e0e0;
}

.progress-step {
    position: relative;
    text-align: center;
    color: #999;
    width: 25%;
}

.progress-step.active {
    color: #4CAF50;
    font-weight: 600;
}

.progress-step::before {
    content: "";
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #e0e0e0;
    border: 2px solid #fff;
    z-index: 1;
}

.progress-step.active::before {
    background-color: #4CAF50;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.3);
}

.progress-line {
    display: none;
}

.step-label {
    display: block;
    margin-top: 15px;
    font-size: 0.9rem;
}

.order-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-top: 30px;
}

.details-content {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

.details-content p {
    margin-bottom: 10px;
}

.payment-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.payment-status-pending {
    background-color: #FFF3CD;
    color: #856404;
}

.payment-status-completed {
    background-color: #D4EDDA;
    color: #155724;
}

.payment-status-failed {
    background-color: #F8D7DA;
    color: #721C24;
}

.total-price {
    font-weight: bold;
    color: #4CAF50;
    font-size: 1.1rem;
}

.order-items-section {
    padding: 30px;
    border-top: 1px solid #eee;
}

.order-items {
    margin-top: 20px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.item-image {
    width: 80px;
    height: 80px;
    overflow: hidden;
    border-radius: 8px;
    margin-right: 15px;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex-grow: 1;
}

.item-details h5 {
    margin-bottom: 5px;
}

.item-seller {
    font-size: 0.9rem;
    color: #666;
}

.item-price {
    font-size: 0.9rem;
    margin-top: 5px;
}

.item-subtotal {
    font-weight: bold;
    color: #4CAF50;
}

.order-total {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 20px 15px;
    font-weight: bold;
}

.total-amount {
    margin-left: 15px;
    font-size: 1.2rem;
    color: #4CAF50;
}

.confirmation-actions {
    display: flex;
    justify-content: center;
    padding: 30px;
    gap: 20px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 5px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
}

.btn i {
    font-size: 0.9rem;
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background-color: #3d8b40;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #333;
}

.btn-secondary:hover {
    background-color: #e0e0e0;
}

@media (max-width: 768px) {
    .order-confirmation {
        padding: 30px 0;
    }
    
    .order-details-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .step-label {
        font-size: 0.75rem;
    }
    
    .order-item {
        flex-wrap: wrap;
    }
    
    .item-image {
        width: 60px;
        height: 60px;
    }
    
    .item-subtotal {
        width: 100%;
        margin-top: 10px;
        text-align: right;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include('../includes/footer.php'); ?> 