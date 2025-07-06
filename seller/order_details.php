<?php
$page_title = "Order Details";
require_once '../includes/config.php';

// Check if user is logged in and is a seller
if (!is_logged_in() || !has_role('seller')) {
    set_flash_message('error', 'You must be logged in as a seller to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get seller information
$user_id = $_SESSION['user_id'];
$seller_sql = "SELECT * FROM seller_profiles WHERE user_id = $user_id";
$seller_result = $conn->query($seller_sql);

if ($seller_result && $seller_result->num_rows > 0) {
    $seller = $seller_result->fetch_assoc();
    $seller_id = $seller['seller_id'];
} else {
    set_flash_message('error', 'Seller profile not found.');
    redirect(SITE_URL . '/seller/dashboard.php');
}

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    set_flash_message('error', 'Invalid order ID.');
    redirect(SITE_URL . '/seller/orders.php');
}

// Check if the order contains products from this seller
$check_sql = "SELECT o.order_id 
             FROM orders o
             JOIN order_items oi ON o.order_id = oi.order_id
             JOIN fruits f ON oi.fruit_id = f.fruit_id
             WHERE f.seller_id = $seller_id AND o.order_id = $order_id
             LIMIT 1";
$check_result = $conn->query($check_sql);

if (!$check_result || $check_result->num_rows == 0) {
    set_flash_message('error', 'You are not authorized to view this order.');
    redirect(SITE_URL . '/seller/orders.php');
}

// Get order details
$order_sql = "SELECT o.*, u.full_name, u.email, u.phone, a.address_line1, a.address_line2, a.city, a.state, a.postal_code, a.country
              FROM orders o
              JOIN users u ON o.buyer_id = u.user_id
              LEFT JOIN addresses a ON o.shipping_address_id = a.address_id
              WHERE o.order_id = $order_id";
$order_result = $conn->query($order_sql);

if (!$order_result || $order_result->num_rows == 0) {
    set_flash_message('error', 'Order not found.');
    redirect(SITE_URL . '/seller/orders.php');
}

$order = $order_result->fetch_assoc();

// Get order items that belong to this seller
$items_sql = "SELECT oi.*, f.name, f.image, f.price_per_kg
              FROM order_items oi
              JOIN fruits f ON oi.fruit_id = f.fruit_id
              WHERE oi.order_id = $order_id AND f.seller_id = $seller_id";
$items_result = $conn->query($items_sql);
$items = [];
$seller_subtotal = 0;

if ($items_result && $items_result->num_rows > 0) {
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
        $seller_subtotal += $item['subtotal'];
    }
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['new_status'])) {
    $new_status = sanitize_input($_POST['new_status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $update_sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $new_status, $order_id);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Order status updated successfully.');
            // Update order status in local variable for display
            $order['order_status'] = $new_status;
        } else {
            set_flash_message('error', 'Failed to update order status: ' . $conn->error);
        }
        
        $stmt->close();
    } else {
        set_flash_message('error', 'Invalid order status.');
    }
}

// Handle payment confirmation for Cash on Delivery orders
if (isset($_POST['confirm_payment']) && $order['payment_method'] === 'cash_on_delivery') {
    $update_sql = "UPDATE orders SET payment_status = 'completed', updated_at = NOW() WHERE order_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('i', $order_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Payment marked as received successfully.');
        // Update payment status in local variable for display
        $order['payment_status'] = 'completed';
    } else {
        set_flash_message('error', 'Failed to update payment status: ' . $conn->error);
    }
    
    $stmt->close();
}
?>

<?php include('../includes/header.php'); ?>

<section class="order-details-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <a href="<?php echo SITE_URL; ?>/seller/orders.php">Manage Orders</a> &gt;
            <span>Order #<?php echo $order_id; ?></span>
        </div>
        
        <div class="order-header">
            <h2>Order #<?php echo $order_id; ?></h2>
            
            <div class="order-actions">
                <span class="order-date"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                
                <div class="status-container">
                    <span class="status-label">Status:</span>
                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                    
                    <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                        <button class="btn-sm update-status-btn">Update Status</button>
                    <?php endif; ?>
                </div>
                
                <div class="payment-container">
                    <span class="payment-label">Payment:</span>
                    <span class="payment-method"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                    <span class="status-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                    
                    <?php if ($order['payment_method'] === 'cash_on_delivery' && $order['payment_status'] === 'pending' && $order['order_status'] === 'delivered'): ?>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="confirm_payment" value="1">
                            <button type="submit" class="btn-sm btn-confirm-payment">Confirm Payment Received</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="order-content">
            <div class="order-details-container">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-details">
                        <div class="detail-row">
                            <span class="detail-label">Order Date:</span>
                            <span class="detail-value"><?php echo date('M d, Y, h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Payment Method:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['payment_method'] ?? 'Not specified'); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Payment Status:</span>
                            <span class="detail-value <?php echo $order['payment_status'] == 'paid' ? 'text-success' : 'text-warning'; ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'Not specified'); ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($order['notes'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Order Notes:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['notes']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="customer-info">
                    <h3>Customer Information</h3>
                    <div class="info-details">
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['full_name']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                        </div>
                        
                        <?php if (!empty($order['phone'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($order['address_line1'])): ?>
                            <div class="shipping-address">
                                <h4>Shipping Address</h4>
                                <address>
                                    <?php echo htmlspecialchars($order['address_line1']); ?><br>
                                    <?php if (!empty($order['address_line2'])): ?>
                                        <?php echo htmlspecialchars($order['address_line2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($order['city']); ?>, 
                                    <?php echo htmlspecialchars($order['state']); ?> 
                                    <?php echo htmlspecialchars($order['postal_code']); ?><br>
                                    <?php echo htmlspecialchars($order['country']); ?>
                                </address>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                <div class="items-note">
                    <p>Note: This view only shows items from your inventory in this order.</p>
                </div>
                
                <div class="items-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="product-cell">
                                        <div class="product-info">
                                            <div class="product-img">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?php echo SITE_URL . '/' . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <div class="image-placeholder"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo format_price($item['price_per_kg']); ?> per kg</td>
                                    <td><?php echo $item['quantity']; ?> kg</td>
                                    <td><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">Your Products Subtotal:</td>
                                <td class="amount"><?php echo format_price($seller_subtotal); ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right">Total Order Amount (All Sellers):</td>
                                <td class="amount total"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update Order Status</h3>
        <form action="" method="POST">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label for="new_status">Select New Status:</label>
                <select id="new_status" name="new_status" required>
                    <option value="">Select Status</option>
                    <option value="pending" <?php echo $order['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $order['order_status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $order['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $order['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $order['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn cancel-btn" id="cancel-status-update">Cancel</button>
                <button type="submit" class="btn submit-btn">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
    .order-details-section {
        padding: 60px 0;
    }
    
    .breadcrumb {
        margin-bottom: 30px;
        color: #666;
    }
    
    .breadcrumb a {
        color: #4CAF50;
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    h2 {
        color: #333;
        margin: 0;
    }
    
    .order-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }
    
    .order-date {
        color: #666;
    }
    
    .status-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .status-label {
        color: #666;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.85em;
        font-weight: 500;
    }
    
    .status-pending {
        background-color: #FFF9C4;
        color: #F57F17;
    }
    
    .status-processing {
        background-color: #E1F5FE;
        color: #0288D1;
    }
    
    .status-shipped {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-delivered {
        background-color: #D1C4E9;
        color: #512DA8;
    }
    
    .status-cancelled {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    .update-status-btn {
        display: inline-block;
        padding: 5px 10px;
        background-color: #FF9800;
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 0.9em;
        cursor: pointer;
    }
    
    .order-content {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .order-details-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .order-summary, .customer-info, .order-items {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
    }
    
    h4 {
        margin-top: 15px;
        margin-bottom: 10px;
        color: #333;
    }
    
    .summary-details, .info-details {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
    }
    
    .detail-label {
        font-weight: 500;
        color: #666;
    }
    
    .detail-value {
        color: #333;
    }
    
    .text-success {
        color: #4CAF50;
    }
    
    .text-warning {
        color: #FF9800;
    }
    
    .shipping-address {
        margin-top: 20px;
    }
    
    address {
        font-style: normal;
        line-height: 1.6;
        color: #333;
    }
    
    .items-note {
        margin-bottom: 15px;
        color: #666;
        font-style: italic;
    }
    
    .items-table {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    th {
        background-color: #f2f2f2;
        font-weight: 600;
    }
    
    .product-cell {
        width: 50%;
    }
    
    .product-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .product-img {
        width: 60px;
        height: 60px;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .product-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .image-placeholder {
        width: 100%;
        height: 100%;
        background-color: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
    }
    
    .product-name {
        font-weight: 500;
    }
    
    tfoot tr {
        font-weight: 500;
    }
    
    tfoot td {
        padding-top: 15px;
    }
    
    .text-right {
        text-align: right;
    }
    
    .amount {
        font-weight: 500;
    }
    
    .total {
        color: #4CAF50;
        font-size: 1.1em;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 25px;
        border-radius: 10px;
        width: 80%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: #333;
    }
    
    .modal h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
    }
    
    .modal .form-group {
        margin-bottom: 20px;
    }
    
    .modal label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .modal select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .modal .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
    
    .modal .cancel-btn {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .modal .submit-btn {
        background-color: #4CAF50;
        color: white;
    }
    
    @media (max-width: 992px) {
        .order-details-container {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .order-actions {
            align-items: flex-start;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('statusModal');
        const updateBtn = document.querySelector('.update-status-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancel-status-update');
        
        // Open modal
        updateBtn.addEventListener('click', function() {
            modal.style.display = 'block';
        });
        
        // Close modal methods
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>

<?php include('../includes/footer.php'); ?> 