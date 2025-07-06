<?php
$page_title = "Admin Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get admin information
$user_id = $_SESSION['user_id'];
$admin_sql = "SELECT * FROM users WHERE user_id = $user_id";
$admin_result = $conn->query($admin_sql);

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
} else {
    set_flash_message('error', 'Admin profile not found.');
    redirect(SITE_URL . '/index.php');
}

// Get dashboard statistics
// Total users
$users_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'buyer' THEN 1 ELSE 0 END) as total_buyers,
                SUM(CASE WHEN role = 'seller' THEN 1 ELSE 0 END) as total_sellers
              FROM users";
$users_result = $conn->query($users_sql);
$users_stats = ($users_result && $users_result->num_rows > 0) ? $users_result->fetch_assoc() : ['total_users' => 0, 'total_buyers' => 0, 'total_sellers' => 0];

// Total fruits
$fruits_sql = "SELECT COUNT(*) as total_fruits FROM fruits";
$fruits_result = $conn->query($fruits_sql);
$total_fruits = ($fruits_result && $fruits_result->num_rows > 0) ? $fruits_result->fetch_assoc()['total_fruits'] : 0;

// Total orders
$orders_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_amount) as total_sales
               FROM orders";
$orders_result = $conn->query($orders_sql);
$orders_stats = ($orders_result && $orders_result->num_rows > 0) ? $orders_result->fetch_assoc() : ['total_orders' => 0, 'pending_orders' => 0, 'confirmed_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'cancelled_orders' => 0, 'total_sales' => 0];

// Get recent orders
$orders_sql = "SELECT o.*, u.full_name as buyer_name 
              FROM orders o
              JOIN users u ON o.buyer_id = u.user_id
              ORDER BY o.created_at DESC
              LIMIT 5";
$orders_result = $conn->query($orders_sql);
$recent_orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Get pending COD orders
$cod_orders_sql = "SELECT o.*, u.full_name as buyer_name 
                  FROM orders o
                  JOIN users u ON o.buyer_id = u.user_id
                  WHERE o.payment_method = 'cash_on_delivery' 
                  AND o.payment_status = 'pending'
                  ORDER BY o.created_at DESC
                  LIMIT 5";
$cod_orders_result = $conn->query($cod_orders_sql);
$pending_cod_orders = [];

if ($cod_orders_result && $cod_orders_result->num_rows > 0) {
    while ($row = $cod_orders_result->fetch_assoc()) {
        $pending_cod_orders[] = $row;
    }
}

// Get new seller registrations pending verification
$new_sellers_sql = "SELECT sp.*, u.full_name, u.email, u.created_at
                    FROM seller_profiles sp
                    JOIN users u ON sp.user_id = u.user_id
                    WHERE sp.is_verified = 0
                    ORDER BY u.created_at DESC LIMIT 5";
$new_sellers_result = $conn->query($new_sellers_sql);
$new_sellers = [];
if ($new_sellers_result && $new_sellers_result->num_rows > 0) {
    while ($row = $new_sellers_result->fetch_assoc()) {
        $new_sellers[] = $row;
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="admin-dashboard">
    <div class="container">
        <h2>Admin Dashboard</h2>
        <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($admin['full_name']); ?>!</p>
        
        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p><?php echo $users_stats['total_users']; ?></p>
                    <div class="stat-details">
                        <span>Buyers: <?php echo $users_stats['total_buyers']; ?></span>
                        <span>Sellers: <?php echo $users_stats['total_sellers']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-apple-alt"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Fruits</h3>
                    <p><?php echo $total_fruits; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <p><?php echo $orders_stats['total_orders']; ?></p>
                    <div class="stat-details">
                        <span>Pending: <?php echo $orders_stats['pending_orders']; ?></span>
                        <span>Delivered: <?php echo $orders_stats['delivered_orders']; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Sales</h3>
                    <p><?php echo format_price($orders_stats['total_sales'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Admin Quick Actions -->
        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="actions-grid">
                <a href="users.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h4>Manage Users</h4>
                </a>
                
                <a href="fruits.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <h4>Manage Fruits</h4>
                </a>
                
                <a href="orders.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>Manage Orders</h4>
                </a>
                
                <a href="categories.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <h4>Manage Categories</h4>
                </a>
                
                <a href="verify_sellers.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Verify Sellers</h4>
                </a>
                
                <a href="reports.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h4>View Reports</h4>
                </a>

                <a href="import_sample_data.php" class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4>Import Sample Data</h4>
                </a>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <a href="orders.php" class="view-all">View All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($recent_orders)): ?>
                        <p class="no-data">No recent orders found.</p>
                    <?php else: ?>
                        <div class="orders-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                            <td><?php echo format_price($order['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending COD Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Pending COD Payments</h3>
                    <a href="orders.php?payment_method=cash_on_delivery&payment_status=pending" class="view-all">View All</a>
                </div>
                <div class="card-content">
                    <?php if (empty($pending_cod_orders)): ?>
                        <p class="no-data">No pending COD payments.</p>
                    <?php else: ?>
                        <div class="orders-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Order Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_cod_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                            <td><?php echo format_price($order['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-sm">Manage</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- New Seller Registrations -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>New Seller Registrations</h3>
                    <a href="verify_sellers.php" class="view-all">View All</a>
                </div>
                
                <?php if (empty($new_sellers)): ?>
                    <div class="no-data">
                        <p>No new seller registrations pending verification.</p>
                    </div>
                <?php else: ?>
                    <div class="sellers-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Farm Name</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($new_sellers as $seller): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['farm_name']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                        <td><?php echo htmlspecialchars($seller['location'] ?? 'Not specified'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($seller['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="verify_seller.php?id=<?php echo $seller['seller_id']; ?>" class="btn-sm">Verify</a>
                                                <a href="seller_details.php?id=<?php echo $seller['seller_id']; ?>" class="btn-sm btn-secondary">Details</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
    .admin-dashboard {
        padding: 60px 0;
    }
    
    .welcome-message {
        margin-bottom: 30px;
        font-size: 1.1rem;
        color: #666;
    }
    
    /* Stats Overview */
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        background: #f1f8e9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }
    
    .stat-icon i {
        font-size: 1.8rem;
        color: #4CAF50;
    }
    
    .stat-info h3 {
        margin-bottom: 5px;
        color: #666;
        font-size: 0.9rem;
        font-weight: 500;
    }
    
    .stat-info p {
        font-size: 1.6rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }
    
    .stat-details {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.8rem;
        color: #666;
    }
    
    /* Quick Actions */
    .quick-actions {
        margin-bottom: 40px;
    }
    
    .quick-actions h3 {
        margin-bottom: 20px;
        font-size: 1.3rem;
        color: #333;
    }
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .action-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        background: #f1f8e9;
    }
    
    .action-icon {
        width: 50px;
        height: 50px;
        background: #f1f8e9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }
    
    .action-icon i {
        font-size: 1.5rem;
        color: #4CAF50;
    }
    
    .action-card h4 {
        color: #333;
        font-size: 1rem;
    }
    
    /* Dashboard Content */
    .dashboard-content {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .dashboard-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .section-header h3 {
        color: #333;
        font-size: 1.3rem;
    }
    
    .view-all {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    /* Tables */
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table th, table td {
        padding: 12px 15px;
        text-align: left;
    }
    
    table th {
        background: #f5f5f5;
        font-weight: 500;
    }
    
    table tr {
        border-bottom: 1px solid #eee;
    }
    
    table tr:last-child {
        border-bottom: none;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
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
    
    .btn-sm {
        display: inline-block;
        padding: 5px 10px;
        background: #4CAF50;
        color: white;
        border-radius: 3px;
        font-size: 0.8rem;
        text-align: center;
    }
    
    .btn-secondary {
        background: #2196F3;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .orders-table, .sellers-table {
            overflow-x: auto;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 