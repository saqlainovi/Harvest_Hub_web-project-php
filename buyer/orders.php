<?php
$page_title = "My Orders";
require_once '../includes/config.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];

// Get filters from URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query
$orders_sql = "SELECT o.*, 
              COUNT(*) as item_count,
              GROUP_CONCAT(DISTINCT f.name SEPARATOR ', ') as product_names
              FROM orders o
              LEFT JOIN order_items oi ON o.order_id = oi.order_id
              LEFT JOIN fruits f ON oi.fruit_id = f.fruit_id
              WHERE o.buyer_id = $user_id";

// Add filters
if (!empty($status_filter)) {
    $orders_sql .= " AND o.order_status = '$status_filter'";
}

if (!empty($date_filter)) {
    if ($date_filter === 'today') {
        $orders_sql .= " AND DATE(o.created_at) = CURDATE()";
    } elseif ($date_filter === 'week') {
        $orders_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    } elseif ($date_filter === 'month') {
        $orders_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($date_filter === 'year') {
        $orders_sql .= " AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
    }
}

// Group by order and add sorting
$orders_sql .= " GROUP BY o.order_id";

if ($sort_by === 'newest') {
    $orders_sql .= " ORDER BY o.created_at DESC";
} elseif ($sort_by === 'oldest') {
    $orders_sql .= " ORDER BY o.created_at ASC";
} elseif ($sort_by === 'price_high') {
    $orders_sql .= " ORDER BY o.total_amount DESC";
} elseif ($sort_by === 'price_low') {
    $orders_sql .= " ORDER BY o.total_amount ASC";
}

// Execute query
$orders_result = $conn->query($orders_sql);

// Get all orders
$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get order statuses for filter
$status_sql = "SELECT DISTINCT order_status FROM orders WHERE buyer_id = $user_id ORDER BY order_status";
$status_result = $conn->query($status_sql);
$statuses = [];

if ($status_result && $status_result->num_rows > 0) {
    while ($row = $status_result->fetch_assoc()) {
        $statuses[] = $row['order_status'];
    }
}

include('../includes/header.php');
?>

<section class="orders-page">
    <div class="container">
        <h2>My Orders</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <!-- Filters & Sorting -->
        <div class="filters-section">
            <form action="" method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($status === $status_filter) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date">Time Period:</label>
                    <select name="date" id="date" onchange="this.form.submit()">
                        <option value="">All Time</option>
                        <option value="today" <?php echo ($date_filter === 'today') ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo ($date_filter === 'week') ? 'selected' : ''; ?>>Last Week</option>
                        <option value="month" <?php echo ($date_filter === 'month') ? 'selected' : ''; ?>>Last Month</option>
                        <option value="year" <?php echo ($date_filter === 'year') ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo ($sort_by === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo ($sort_by === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="price_high" <?php echo ($sort_by === 'price_high') ? 'selected' : ''; ?>>Price (High to Low)</option>
                        <option value="price_low" <?php echo ($sort_by === 'price_low') ? 'selected' : ''; ?>>Price (Low to High)</option>
                    </select>
                </div>
                
                <?php if (!empty($status_filter) || !empty($date_filter) || $sort_by !== 'newest'): ?>
                    <div class="filter-group">
                        <a href="orders.php" class="reset-filters">Reset Filters</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <div class="no-data-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet that match your filters.</p>
                <?php if (!empty($status_filter) || !empty($date_filter)): ?>
                    <a href="orders.php" class="btn">View All Orders</a>
                <?php else: ?>
                    <a href="../pages/fruits.php" class="btn">Start Shopping</a>
                    <a href="../pages/agricultural_products.php" class="btn">Browse Agricultural Products</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-id">
                                <h3>Order #<?php echo $order['order_id']; ?></h3>
                                <span class="order-date"><?php echo date('F d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            
                            <div class="order-status">
                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-details">
                            <div class="order-info">
                                <div class="info-group">
                                    <span class="info-label">Items:</span>
                                    <span class="info-value"><?php echo $order['item_count']; ?> (<?php echo htmlspecialchars($order['product_names']); ?>)</span>
                                </div>
                                
                                <div class="info-group">
                                    <span class="info-label">Payment Method:</span>
                                    <span class="info-value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
                                </div>
                                
                                <div class="info-group">
                                    <span class="info-label">Payment Status:</span>
                                    <span class="info-value status-badge payment-status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                
                                <div class="info-group">
                                    <span class="info-label">Total Amount:</span>
                                    <span class="info-value price"><?php echo format_price($order['total_amount']); ?></span>
                                </div>
                            </div>
                            
                            <div class="order-actions">
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn">View Details</a>
                                
                                <?php if ($order['order_status'] === 'pending'): ?>
                                    <form method="post" action="cancel_order.php" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" class="btn btn-danger">Cancel Order</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($order['order_status'] === 'delivered'): ?>
                                    <a href="write_review.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">Write Review</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .orders-page {
        padding: 60px 0;
    }
    
    .orders-page h2 {
        margin-bottom: 30px;
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
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .filters-form {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: flex-end;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .filter-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        background-color: white;
    }
    
    .reset-filters {
        display: inline-block;
        padding: 10px 20px;
        color: #666;
        text-decoration: none;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f5f5f5;
        text-align: center;
    }
    
    /* No Orders */
    .no-orders {
        background: white;
        border-radius: 10px;
        padding: 50px 20px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .no-data-icon {
        width: 80px;
        height: 80px;
        background: #f1f8e9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    .no-data-icon i {
        font-size: 2.5rem;
        color: #4CAF50;
    }
    
    .no-orders h3 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .no-orders p {
        margin-bottom: 20px;
        color: #666;
    }
    
    .no-orders .btn {
        display: inline-block;
        margin: 0 5px;
    }
    
    /* Orders List */
    .orders-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .order-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        background: #f5f5f5;
        border-bottom: 1px solid #eee;
    }
    
    .order-id h3 {
        margin-bottom: 5px;
        color: #333;
        font-size: 1.1rem;
    }
    
    .order-date {
        font-size: 0.9rem;
        color: #666;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-pending {
        background: #FFF3CD;
        color: #856404;
    }
    
    .status-confirmed {
        background: #CCE5FF;
        color: #004085;
    }
    
    .status-shipped {
        background: #D1ECF1;
        color: #0C5460;
    }
    
    .status-delivered {
        background: #D4EDDA;
        color: #155724;
    }
    
    .status-cancelled {
        background: #F8D7DA;
        color: #721C24;
    }
    
    .order-details {
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 20px;
    }
    
    .order-info {
        flex: 1;
        min-width: 300px;
    }
    
    .info-group {
        margin-bottom: 10px;
    }
    
    .info-label {
        font-weight: 500;
        display: inline-block;
        width: 140px;
        color: #555;
    }
    
    .info-value {
        color: #333;
    }
    
    .price {
        font-weight: 500;
        color: #4CAF50;
    }
    
    .order-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-start;
    }
    
    .btn {
        display: inline-block;
        padding: 8px 15px;
        background: #4CAF50;
        color: white;
        border-radius: 5px;
        font-size: 0.9rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    
    .btn-danger {
        background: #F44336;
    }
    
    .btn-secondary {
        background: #2196F3;
    }
    
    @media (max-width: 768px) {
        .filters-form {
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .order-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .order-status {
            align-self: flex-start;
        }
        
        .order-details {
            flex-direction: column;
        }
        
        .order-actions {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 