<?php
$page_title = "Seller Details";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Check if seller ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid seller ID.');
    redirect('verify_sellers.php');
}

$seller_id = intval($_GET['id']);

// Handle seller verification
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'verify') {
        $update_sql = "UPDATE seller_profiles SET is_verified = 1, verified_at = NOW() WHERE seller_id = $seller_id";
        $message = 'Seller verified successfully.';
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE seller_profiles SET is_verified = 0, verified_at = NULL WHERE seller_id = $seller_id";
        $message = 'Seller verification rejected.';
    }
    
    if (isset($update_sql) && $conn->query($update_sql)) {
        set_flash_message('success', $message);
    } else {
        set_flash_message('error', 'Failed to update seller status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF'] . "?id=$seller_id");
}

// Get seller details
$seller_sql = "SELECT sp.*, u.full_name, u.email, u.phone, u.created_at as registration_date, u.status as user_status
               FROM seller_profiles sp
               JOIN users u ON sp.user_id = u.user_id
               WHERE sp.seller_id = $seller_id";
$seller_result = $conn->query($seller_sql);

if (!$seller_result || $seller_result->num_rows === 0) {
    set_flash_message('error', 'Seller not found.');
    redirect('verify_sellers.php');
}

$seller = $seller_result->fetch_assoc();

// Get seller's products
$products_sql = "SELECT f.*, c.name as category_name
                FROM fruits f
                LEFT JOIN categories c ON f.category_id = c.category_id
                WHERE f.seller_id = $seller_id
                ORDER BY f.created_at DESC
                LIMIT 10";
$products_result = $conn->query($products_sql);
$products = [];

if ($products_result && $products_result->num_rows > 0) {
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Get seller's orders
$orders_sql = "SELECT o.order_id, o.created_at, o.total_amount, o.order_status, 
                COUNT(oi.order_item_id) as item_count
               FROM orders o
               JOIN order_items oi ON o.order_id = oi.order_id
               JOIN fruits f ON oi.fruit_id = f.fruit_id
               WHERE f.seller_id = $seller_id
               GROUP BY o.order_id
               ORDER BY o.created_at DESC
               LIMIT 10";
$orders_result = $conn->query($orders_sql);
$orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

include('../includes/header.php');
?>

<section class="seller-details-section">
    <div class="container">
        <h2>Seller Details</h2>
        
        <div class="page-navigation">
            <a href="verify_sellers.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Sellers List</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="seller-details-container">
            <div class="seller-details-grid">
                <!-- Seller Info Card -->
                <div class="seller-info card">
                    <h3>Seller Information</h3>
                    <div class="profile-header">
                        <?php if (!empty($seller['profile_image'])): ?>
                            <img src="<?php echo SITE_URL . '/' . $seller['profile_image']; ?>" alt="<?php echo htmlspecialchars($seller['farm_name']); ?>" class="profile-image">
                        <?php else: ?>
                            <div class="profile-image-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-title">
                            <h4><?php echo htmlspecialchars($seller['farm_name']); ?></h4>
                            <div class="seller-status">
                                <span class="status-badge status-<?php echo $seller['is_verified'] ? 'verified' : 'pending'; ?>">
                                    <?php echo $seller['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                                </span>
                                
                                <span class="status-badge status-<?php echo $seller['user_status']; ?>">
                                    Account: <?php echo ucfirst($seller['user_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="seller-details">
                        <div class="info-row">
                            <span class="info-label">Owner Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($seller['full_name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($seller['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($seller['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Location:</span>
                            <span class="info-value"><?php echo htmlspecialchars($seller['location'] ?? 'Not specified'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registered On:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($seller['registration_date'])); ?></span>
                        </div>
                        <?php if ($seller['is_verified']): ?>
                            <div class="info-row">
                                <span class="info-label">Verified On:</span>
                                <span class="info-value"><?php echo date('M d, Y', strtotime($seller['verified_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="seller-description">
                        <h4>About Farm</h4>
                        <?php if (!empty($seller['description'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($seller['description'])); ?></p>
                        <?php else: ?>
                            <p class="no-data">No description provided.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="seller-actions">
                        <?php if (!$seller['is_verified']): ?>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $seller_id; ?>&action=verify" class="btn btn-success" onclick="return confirm('Are you sure you want to verify this seller?');">Verify Seller</a>
                        <?php else: ?>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $seller_id; ?>&action=reject" class="btn btn-warning" onclick="return confirm('Are you sure you want to reject this seller?');">Un-verify Seller</a>
                        <?php endif; ?>
                        <a href="user_details.php?id=<?php echo $seller['user_id']; ?>" class="btn btn-secondary">View User Account</a>
                    </div>
                </div>
                
                <!-- Seller Stats Card -->
                <div class="seller-stats card">
                    <h3>Seller Statistics</h3>
                    
                    <?php
                    // Get total products
                    $products_count_sql = "SELECT COUNT(*) as total FROM fruits WHERE seller_id = $seller_id";
                    $products_count_result = $conn->query($products_count_sql);
                    $products_count = $products_count_result ? $products_count_result->fetch_assoc()['total'] : 0;
                    
                    // Get active products
                    $active_products_sql = "SELECT COUNT(*) as total FROM fruits WHERE seller_id = $seller_id AND is_available = 1";
                    $active_products_result = $conn->query($active_products_sql);
                    $active_products = $active_products_result ? $active_products_result->fetch_assoc()['total'] : 0;
                    
                    // Get total orders
                    $total_orders_sql = "SELECT COUNT(DISTINCT o.order_id) as total
                                        FROM orders o
                                        JOIN order_items oi ON o.order_id = oi.order_id
                                        JOIN fruits f ON oi.fruit_id = f.fruit_id
                                        WHERE f.seller_id = $seller_id";
                    $total_orders_result = $conn->query($total_orders_sql);
                    $total_orders = $total_orders_result ? $total_orders_result->fetch_assoc()['total'] : 0;
                    
                    // Get total sales
                    $total_sales_sql = "SELECT SUM(oi.price_per_kg * oi.quantity) as total
                                        FROM order_items oi
                                        JOIN fruits f ON oi.fruit_id = f.fruit_id
                                        WHERE f.seller_id = $seller_id";
                    $total_sales_result = $conn->query($total_sales_sql);
                    $total_sales = $total_sales_result ? $total_sales_result->fetch_assoc()['total'] : 0;
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $products_count; ?></div>
                            <div class="stat-label">Total Products</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $active_products; ?></div>
                            <div class="stat-label">Active Products</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_orders; ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo format_price($total_sales); ?></div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    </div>
                    
                    <div class="verification-documents">
                        <h4>Verification Documents</h4>
                        <?php if (!empty($seller['verification_document'])): ?>
                            <div class="document-link">
                                <a href="<?php echo SITE_URL . '/' . $seller['verification_document']; ?>" target="_blank" class="btn btn-sm">
                                    <i class="fas fa-file-alt"></i> View Document
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No verification documents uploaded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="seller-products card">
                <h3>Recent Products</h3>
                <?php if (empty($products)): ?>
                    <div class="no-data">This seller has not added any products yet.</div>
                <?php else: ?>
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="<?php echo SITE_URL . '/' . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                            <?php else: ?>
                                                <div class="no-image"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td><?php echo format_price($product['price_per_kg']); ?>/kg</td>
                                        <td><?php echo $product['stock_quantity']; ?> kg</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $product['is_available'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $product['is_available'] ? 'Available' : 'Not Available'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="../pages/fruit_details.php?id=<?php echo $product['fruit_id']; ?>" class="btn-sm" target="_blank">View</a>
                                                <a href="edit_fruit.php?id=<?php echo $product['fruit_id']; ?>" class="btn-sm btn-secondary">Edit</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($products_count > 10): ?>
                        <div class="view-all-link">
                            <a href="fruits.php?seller_id=<?php echo $seller_id; ?>">View All Products</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Orders Section -->
            <div class="seller-orders card">
                <h3>Recent Orders</h3>
                <?php if (empty($orders)): ?>
                    <div class="no-data">This seller has not received any orders yet.</div>
                <?php else: ?>
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo $order['item_count']; ?> item(s)</td>
                                        <td><?php echo format_price($order['total_amount']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_orders > 10): ?>
                        <div class="view-all-link">
                            <a href="orders.php?seller_id=<?php echo $seller_id; ?>">View All Orders</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .seller-details-section {
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
    
    .seller-details-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .seller-details-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
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
    
    /* Profile Header */
    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .profile-image,
    .profile-image-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-right: 20px;
        object-fit: cover;
    }
    
    .profile-image-placeholder {
        background-color: #f1f1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 2rem;
    }
    
    .profile-title h4 {
        margin: 0 0 10px 0;
        font-size: 1.4rem;
        color: #333;
    }
    
    .seller-status {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    /* Info Rows */
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
    
    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-verified {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-pending {
        background-color: #FFF8E1;
        color: #FFA000;
    }
    
    .status-active {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-inactive {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    /* Seller Description */
    .seller-description {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .seller-description p {
        color: #555;
        line-height: 1.5;
    }
    
    /* Seller Actions */
    .seller-actions {
        margin-top: 25px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 0.9rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-success {
        background-color: #4CAF50;
        color: white;
    }
    
    .btn-warning {
        background-color: #FFC107;
        color: #333;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    .btn-sm {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-block;
        background-color: #eee;
        color: #333;
    }
    
    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background-color: #f8f8f8;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 600;
        color: #4CAF50;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    /* Verification Documents */
    .verification-documents {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .document-link {
        margin-top: 10px;
    }
    
    /* Tables */
    .products-table-container,
    .orders-table-container {
        overflow-x: auto;
    }
    
    .products-table,
    .orders-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .products-table th,
    .products-table td,
    .orders-table th,
    .orders-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .products-table th,
    .orders-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .product-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .no-image {
        width: 50px;
        height: 50px;
        background-color: #f1f1f1;
        border-radius: 4px;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .view-all-link {
        margin-top: 15px;
        text-align: right;
    }
    
    .view-all-link a {
        color: #4CAF50;
        text-decoration: none;
        font-weight: 500;
    }
    
    .no-data {
        padding: 15px 0;
        text-align: center;
        color: #666;
    }
    
    @media (max-width: 992px) {
        .seller-details-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .seller-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 