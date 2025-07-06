<?php
$page_title = "Manage Orders";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Handle order status update
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $update_sql = "UPDATE orders SET order_status = '$status', updated_at = NOW() WHERE order_id = $order_id";
    
    if ($conn->query($update_sql)) {
        set_flash_message('success', "Order #$order_id status updated to " . ucfirst($status));
    } else {
        set_flash_message('error', 'Failed to update order status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF'] . (isset($_GET['page']) ? "?page={$_GET['page']}" : ""));
}

// Get filtering and pagination parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query
$orders_sql = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email
               FROM orders o
               JOIN users u ON o.buyer_id = u.user_id
               WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";

$params = [];
$count_params = [];

if (!empty($search)) {
    $search_term = "%$search%";
    $orders_sql .= " AND (o.order_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $count_sql .= " AND (o.order_id LIKE ? OR o.buyer_id IN (SELECT user_id FROM users WHERE full_name LIKE ? OR email LIKE ?))";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if (!empty($status_filter)) {
    $orders_sql .= " AND o.order_status = ?";
    $count_sql .= " AND o.order_status = ?";
    $params[] = $status_filter;
    $count_params[] = $status_filter;
}

if (!empty($payment_method)) {
    $orders_sql .= " AND o.payment_method = ?";
    $count_sql .= " AND o.payment_method = ?";
    $params[] = $payment_method;
    $count_params[] = $payment_method;
}

if (!empty($payment_status)) {
    $orders_sql .= " AND o.payment_status = ?";
    $count_sql .= " AND o.payment_status = ?";
    $params[] = $payment_status;
    $count_params[] = $payment_status;
}

if (!empty($date_filter)) {
    if ($date_filter === 'today') {
        $orders_sql .= " AND DATE(o.created_at) = CURDATE()";
        $count_sql .= " AND DATE(o.created_at) = CURDATE()";
    } elseif ($date_filter === 'week') {
        $orders_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $count_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($date_filter === 'month') {
        $orders_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $count_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    }
}

// Add sorting and pagination
$orders_sql .= " ORDER BY o.created_at DESC LIMIT $offset, $per_page";

// Prepare and execute the statements
$orders_stmt = $conn->prepare($orders_sql);
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $orders_stmt->bind_param($types, ...$params);
}

if (!empty($count_params)) {
    $count_types = str_repeat('s', count($count_params));
    $count_stmt->bind_param($count_types, ...$count_params);
}

$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = [];

while ($row = $orders_result->fetch_assoc()) {
    $orders[] = $row;
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

include('../includes/header.php');
?>

<section class="admin-orders">
    <div class="container">
        <h2>Manage Orders</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <!-- Filters & Search -->
        <div class="filters-section">
            <form action="" method="GET" class="filters-form">
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
                    
                    <div class="filter-group">
                        <label for="date">Date Range:</label>
                        <select name="date" id="date">
                            <option value="">All Time</option>
                            <option value="today" <?php echo ($date_filter === 'today') ? 'selected' : ''; ?>>Today</option>
                            <option value="week" <?php echo ($date_filter === 'week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="month" <?php echo ($date_filter === 'month') ? 'selected' : ''; ?>>This Month</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" placeholder="Search by order ID, customer name or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Orders Table -->
        <?php if (empty($orders)): ?>
            <div class="no-data">
                <p>No orders found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="orders-table-container">
                <table class="orders-table">
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
                        <?php foreach ($orders as $order): 
                            // Get order items
                            $items_sql = "SELECT COUNT(*) as item_count FROM order_items WHERE order_id = {$order['order_id']}";
                            $items_result = $conn->query($items_sql);
                            $item_count = ($items_result && $items_result->num_rows > 0) ? $items_result->fetch_assoc()['item_count'] : 0;
                        ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="customer-info">
                                        <div><?php echo htmlspecialchars($order['buyer_name']); ?></div>
                                        <div class="customer-email"><?php echo htmlspecialchars($order['buyer_email']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo $item_count; ?> item(s)</td>
                                <td><?php echo format_price($order['total_amount']); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">View Details</a>
                                        <button class="btn-sm btn-secondary" onclick="openStatusModal(<?php echo $order['order_id']; ?>, '<?php echo $order['order_status']; ?>')">Update Status</button>
                                    </div>
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
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page - 1); ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page + 1); ?>&status=<?php echo $status_filter; ?>&date=<?php echo $date_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Status Update Modal -->
        <div id="statusModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h3>Update Order Status</h3>
                
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <input type="hidden" id="modal_order_id" name="order_id" value="">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="form-group">
                        <label for="status">Order Status:</label>
                        <select name="status" id="modal_status" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary close-modal-btn">Cancel</button>
                        <button type="submit" class="btn">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
    .admin-orders {
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
    
    /* Filters */
    .filters-section {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .filters-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .search-group {
        flex: 2;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        background-color: #4CAF50;
        color: white;
        font-size: 0.9rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    /* Orders Table */
    .orders-table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .orders-table th, 
    .orders-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .orders-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .orders-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .customer-info {
        display: flex;
        flex-direction: column;
    }
    
    .customer-email {
        font-size: 0.8rem;
        color: #666;
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
    
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .btn-sm {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-block;
        background-color: #eee;
        color: #333;
        border: none;
        cursor: pointer;
    }
    
    .btn-secondary {
        background-color: #2196F3;
        color: white;
    }
    
    /* No Data */
    .no-data {
        text-align: center;
        padding: 30px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .no-data p {
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .page-link {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 5px;
        border-radius: 3px;
        background-color: white;
        color: #333;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .page-link.active {
        background-color: #4CAF50;
        color: white;
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
        position: relative;
        margin: 10% auto;
        padding: 30px;
        width: 400px;
        max-width: 90%;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }
    
    .close-modal {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        color: #777;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .modal-content {
            width: 90%;
            margin: 20% auto;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit the form when filters change
    const filterSelects = document.querySelectorAll('.filters-form select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.querySelector('.filters-form').submit();
        });
    });
    
    // Modal functionality
    const modal = document.getElementById('statusModal');
    const closeModalBtn = document.querySelector('.close-modal');
    const closeModalBtnAlt = document.querySelector('.close-modal-btn');
    
    // Close modal when clicking the X
    closeModalBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking the Cancel button
    closeModalBtnAlt.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

function openStatusModal(orderId, currentStatus) {
    const modal = document.getElementById('statusModal');
    const orderIdInput = document.getElementById('modal_order_id');
    const statusSelect = document.getElementById('modal_status');
    
    orderIdInput.value = orderId;
    statusSelect.value = currentStatus;
    
    modal.style.display = 'block';
}
</script>

<?php include('../includes/footer.php'); ?> 