<?php
$page_title = "Manage Orders";
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

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize_input($_POST['new_status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        // Check if this order belongs to the seller
        $check_sql = "SELECT o.order_id 
                     FROM orders o
                     JOIN order_items oi ON o.order_id = oi.order_id
                     JOIN fruits f ON oi.fruit_id = f.fruit_id
                     WHERE f.seller_id = $seller_id AND o.order_id = $order_id
                     LIMIT 1";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            // Update the status
            $update_sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('si', $new_status, $order_id);
            
            if ($stmt->execute()) {
                set_flash_message('success', 'Order status updated successfully.');
            } else {
                set_flash_message('error', 'Failed to update order status: ' . $conn->error);
            }
            
            $stmt->close();
        } else {
            set_flash_message('error', 'You are not authorized to update this order.');
        }
    } else {
        set_flash_message('error', 'Invalid order status.');
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/seller/orders.php');
}

// Get orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Get status filter from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

// Build status query
$status_query = "";
if (!empty($status_filter)) {
    $status_query .= " AND o.order_status = '$status_filter'";
}

// Add payment filter
if (!empty($payment_method)) {
    $status_query .= " AND o.payment_method = '$payment_method'";
}

// Add payment status filter
if (!empty($payment_status)) {
    $status_query .= " AND o.payment_status = '$payment_status'";
}

// Query to get orders for this seller
$orders_sql = "SELECT o.order_id, o.buyer_id, o.created_at, o.total_amount, o.order_status, 
               o.payment_method, o.payment_status,
               u.full_name as buyer_name, u.email as buyer_email,
               COUNT(DISTINCT oi.order_item_id) as item_count,
               GROUP_CONCAT(DISTINCT f.name SEPARATOR ', ') as fruit_names
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               JOIN fruits f ON oi.fruit_id = f.fruit_id
               JOIN users u ON o.buyer_id = u.user_id
               WHERE f.seller_id = $seller_id
               $status_query
               GROUP BY o.order_id
               ORDER BY o.created_at DESC
               LIMIT $offset, $items_per_page";

$orders_result = $conn->query($orders_sql);
$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT o.order_id) as total
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              JOIN fruits f ON oi.fruit_id = f.fruit_id
              WHERE f.seller_id = $seller_id
              $status_query";
$count_result = $conn->query($count_sql);
$total_orders = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_orders / $items_per_page);
?>

<?php include('../includes/header.php'); ?>

<section class="orders-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Manage Orders</span>
        </div>
        
        <h2>Manage Orders</h2>
        
        <div class="filter-section">
            <form action="" method="get" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="status">Order Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="shipped" <?php echo ($status_filter === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo ($status_filter === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="payment_method">Payment Method:</label>
                        <select name="payment_method" id="payment_method">
                            <option value="">All Methods</option>
                            <option value="credit_card" <?php echo ($payment_method === 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                            <option value="bkash" <?php echo ($payment_method === 'bkash') ? 'selected' : ''; ?>>bKash</option>
                            <option value="cash_on_delivery" <?php echo ($payment_method === 'cash_on_delivery') ? 'selected' : ''; ?>>Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="payment_status">Payment Status:</label>
                        <select name="payment_status" id="payment_status">
                            <option value="">All Payment Statuses</option>
                            <option value="pending" <?php echo ($payment_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo ($payment_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo ($payment_status === 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    
                    <div class="filter-action">
                        <button type="submit" class="btn-filter">Apply Filters</button>
                        <a href="orders.php" class="btn-clear">Clear</a>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="no-data">
                <p>No orders found. <?php echo !empty($status_filter) ? 'Try selecting a different status filter.' : ''; ?></p>
            </div>
        <?php else: ?>
            <div class="orders-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                                    <div class="email"><?php echo htmlspecialchars($order['buyer_email']); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['fruit_names']); ?></div>
                                    <div class="item-count"><?php echo $order['item_count']; ?> item(s)</div>
                                </td>
                                <td><?php echo format_price($order['total_amount']); ?></td>
                                <td>
                                    <div><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></div>
                                    <span class="status-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">View</a>
                                    
                                    <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                                        <button class="btn-sm update-status-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                            Update Status
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="pagination-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="pagination-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Update Status Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update Order Status</h3>
        <form action="" method="POST" id="update-status-form">
            <input type="hidden" name="order_id" id="modal-order-id">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label for="new_status">Select New Status:</label>
                <select id="new_status" name="new_status" required>
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
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
    .orders-section {
        padding: 60px 0;
    }
    
    .breadcrumb {
        margin-bottom: 30px;
        color: #666;
    }
    
    .breadcrumb a {
        color: #4CAF50;
    }
    
    h2 {
        margin-bottom: 30px;
        color: #333;
    }
    
    .filter-section {
        margin-bottom: 30px;
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
    }
    
    .filter-form {
        display: flex;
        align-items: center;
    }
    
    .filter-form .filter-row {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .filter-form .filter-group {
        display: flex;
        align-items: center;
    }
    
    .filter-form label {
        margin-right: 10px;
        font-weight: 500;
    }
    
    .filter-form select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .filter-form .filter-action {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .filter-form .btn-filter {
        padding: 8px 12px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .filter-form .btn-clear {
        padding: 8px 12px;
        background-color: #f5f5f5;
        color: #333;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    
    .no-data {
        background: #f9f9f9;
        padding: 30px;
        text-align: center;
        border-radius: 8px;
        color: #666;
    }
    
    .orders-table {
        margin-bottom: 30px;
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
    
    tr:hover {
        background-color: #f9f9f9;
    }
    
    .email, .item-count {
        font-size: 0.9em;
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
    
    .actions {
        display: flex;
        gap: 5px;
    }
    
    .btn-sm {
        display: inline-block;
        padding: 5px 10px;
        background-color: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        border: none;
        cursor: pointer;
    }
    
    .update-status-btn {
        background-color: #FF9800;
    }
    
    .btn-sm:hover {
        opacity: 0.9;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }
    
    .pagination-link {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 5px;
        background-color: #f5f5f5;
        color: #333;
        text-decoration: none;
        border-radius: 4px;
    }
    
    .pagination-link.active {
        background-color: #4CAF50;
        color: white;
    }
    
    .pagination-link:hover:not(.active) {
        background-color: #e0e0e0;
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
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .filter-form .filter-row {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .filter-form .filter-group {
            margin-bottom: 10px;
            width: 100%;
        }
        
        .filter-form select {
            width: 100%;
        }
        
        th, td {
            padding: 10px;
        }
        
        .actions {
            flex-direction: column;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('statusModal');
        const updateButtons = document.querySelectorAll('.update-status-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancel-status-update');
        const modalOrderId = document.getElementById('modal-order-id');
        
        // Open modal and set order ID
        updateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                modalOrderId.value = orderId;
                modal.style.display = 'block';
            });
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