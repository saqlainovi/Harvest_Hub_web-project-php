<?php
$page_title = "Order Details";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid order ID.');
    redirect('orders.php');
}

$order_id = intval($_GET['id']);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && isset($_POST['status'])) {
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE orders SET order_status = '$status', updated_at = NOW() WHERE order_id = $order_id";
    
    if ($conn->query($update_sql)) {
        set_flash_message('success', "Order #$order_id status updated to " . ucfirst($status));
    } else {
        set_flash_message('error', 'Failed to update order status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF'] . "?id=$order_id");
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment_status']) && isset($_POST['payment_status'])) {
    $payment_status = $conn->real_escape_string($_POST['payment_status']);
    
    $update_sql = "UPDATE orders SET payment_status = '$payment_status', updated_at = NOW() WHERE order_id = $order_id";
    
    if ($conn->query($update_sql)) {
        set_flash_message('success', "Order #$order_id payment status updated to " . ucfirst($payment_status));
    } else {
        set_flash_message('error', 'Failed to update payment status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF'] . "?id=$order_id");
}

// Get order details
$order_sql = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email, u.phone as buyer_phone
              FROM orders o
              JOIN users u ON o.buyer_id = u.user_id
              WHERE o.order_id = $order_id";
$order_result = $conn->query($order_sql);

if (!$order_result || $order_result->num_rows === 0) {
    set_flash_message('error', 'Order not found.');
    redirect('orders.php');
}

$order = $order_result->fetch_assoc();

// Get order items
$items_sql = "SELECT oi.*, f.name as fruit_name, f.image
              FROM order_items oi
              JOIN fruits f ON oi.fruit_id = f.fruit_id
              WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_sql);
$order_items = [];

if ($items_result && $items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $order_items[] = $row;
    }
}

include('../includes/header.php');
?>

<section class="order-details-section">
    <div class="container">
        <h2>Order #<?php echo $order_id; ?> Details</h2>
        
        <div class="page-navigation">
            <a href="orders.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Orders List</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="order-details-container">
            <div class="order-details-grid">
                <!-- Order Summary Card -->
                <div class="order-summary card">
                    <h3>Order Summary</h3>
                    <div class="order-info">
                        <div class="info-row">
                            <span class="info-label">Order Number:</span>
                            <span class="info-value">#<?php echo $order_id; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date Placed:</span>
                            <span class="info-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo ucfirst($order['order_status']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <span class="info-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Status:</span>
                            <span class="info-value status-badge payment-status-<?php echo strtolower($order['payment_status']); ?>"><?php echo ucfirst($order['payment_status']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Subtotal:</span>
                            <span class="info-value"><?php echo format_price($order['subtotal_amount']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Delivery Fee:</span>
                            <span class="info-value"><?php echo format_price($order['delivery_fee']); ?></span>
                        </div>
                        <div class="info-row total-row">
                            <span class="info-label">Total Amount:</span>
                            <span class="info-value"><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Order Status Update -->
                    <div class="status-update card">
                        <h3>Update Order Status</h3>
                        <div class="status-form">
                            <form method="post">
                                <div class="form-group">
                                    <label for="status">Order Status:</label>
                                    <select name="status" id="status" class="form-control">
                                        <option value="pending" <?php echo ($order['order_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo ($order['order_status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="shipped" <?php echo ($order['order_status'] === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo ($order['order_status'] === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo ($order['order_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <input type="hidden" name="update_status" value="1">
                                <button type="submit" class="btn btn-primary">Update Status</button>
                            </form>
                        </div>
                    </div>

                    <!-- Payment Status Update -->
                    <?php if ($order['payment_method'] === 'cash_on_delivery'): ?>
                    <div class="payment-status-update card">
                        <h3>Update Payment Status</h3>
                        <div class="status-form">
                            <form method="post">
                                <div class="form-group">
                                    <label for="payment_status">Payment Status:</label>
                                    <select name="payment_status" id="payment_status" class="form-control">
                                        <option value="pending" <?php echo ($order['payment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo ($order['payment_status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="failed" <?php echo ($order['payment_status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </div>
                                <input type="hidden" name="update_payment_status" value="1">
                                <button type="submit" class="btn btn-primary">Update Payment Status</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Customer Info Card -->
                <div class="customer-info card">
                    <h3>Customer Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['buyer_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['buyer_phone'] ?? 'Not provided'); ?></span>
                    </div>
                    
                    <h4 class="address-title">Shipping Address</h4>
                    <div class="address-info">
                        <?php if (!empty($order['shipping_address'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        <?php else: ?>
                            <p>No shipping address provided.</p>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="address-title">Billing Address</h4>
                    <div class="address-info">
                        <?php if (!empty($order['billing_address'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($order['billing_address'])); ?></p>
                        <?php else: ?>
                            <p>Same as shipping address</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="order-items card">
                <h3>Order Items</h3>
                <?php if (empty($order_items)): ?>
                    <div class="no-items">No items found for this order.</div>
                <?php else: ?>
                    <div class="items-table-container">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td class="item-cell">
                                            <div class="item-info">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="<?php echo SITE_URL . '/' . $item['image']; ?>" alt="<?php echo htmlspecialchars($item['fruit_name']); ?>" class="item-thumbnail">
                                                <?php else: ?>
                                                    <div class="no-image"></div>
                                                <?php endif; ?>
                                                <span class="item-name"><?php echo htmlspecialchars($item['fruit_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo format_price($item['price_per_kg']); ?>/kg</td>
                                        <td><?php echo $item['quantity']; ?> kg</td>
                                        <td><?php echo format_price($item['price_per_kg'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="subtotal-row">
                                    <td colspan="3">Subtotal</td>
                                    <td><?php echo format_price($order['subtotal_amount']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3">Delivery Fee</td>
                                    <td><?php echo format_price($order['delivery_fee']); ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3">Total</td>
                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($order['notes'])): ?>
                <div class="order-notes card">
                    <h3>Order Notes</h3>
                    <div class="notes-content">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .order-details-section {
        padding: 60px 0;
    }
    
    .page-navigation {
        margin-bottom: 20px;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        color: #4CAF50;
        font-weight: 500;
    }
    
    .back-link i {
        margin-right: 5px;
    }
    
    .order-details-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .order-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
    
    .card {
        background-color: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .card h3 {
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
        color: #333;
        font-size: 1.3rem;
    }
    
    .card h4 {
        margin-top: 20px;
        margin-bottom: 10px;
        color: #555;
        font-size: 1.1rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .info-label {
        color: #666;
        font-weight: 500;
    }
    
    .info-value {
        color: #333;
        font-weight: 600;
    }
    
    .total-row {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px dashed #ddd;
        font-size: 1.1rem;
    }
    
    .total-row .info-label,
    .total-row .info-value {
        color: #2E7D32;
        font-weight: 600;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-pending {
        background-color: #FFF3CD;
        color: #856404;
    }
    
    .status-confirmed {
        background-color: #D1ECF1;
        color: #0C5460;
    }
    
    .status-shipped {
        background-color: #CCE5FF;
        color: #004085;
    }
    
    .status-delivered {
        background-color: #D4EDDA;
        color: #155724;
    }
    
    .status-cancelled {
        background-color: #F8D7DA;
        color: #721C24;
    }
    
    .address-title {
        margin-top: 20px;
    }
    
    .address-info p {
        margin-top: 5px;
        color: #333;
    }
    
    .status-update-form {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }
    
    /* Items Table */
    .items-table-container {
        overflow-x: auto;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .items-table th, 
    .items-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .items-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .items-table tfoot tr {
        background-color: #f9f9f9;
    }
    
    .items-table tfoot td {
        padding: 15px;
        font-weight: 600;
    }
    
    .item-cell {
        min-width: 200px;
    }
    
    .item-info {
        display: flex;
        align-items: center;
    }
    
    .item-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 10px;
    }
    
    .no-image {
        width: 50px;
        height: 50px;
        background-color: #f1f1f1;
        border-radius: 4px;
        margin-right: 10px;
    }
    
    .item-name {
        font-weight: 500;
    }
    
    .no-items {
        padding: 20px 0;
        text-align: center;
        color: #666;
    }
    
    .order-notes {
        padding: 25px;
    }
    
    .notes-content {
        color: #555;
        line-height: 1.5;
    }
    
    @media (max-width: 992px) {
        .order-details-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 