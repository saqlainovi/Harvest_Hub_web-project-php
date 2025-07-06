<?php
$page_title = "User Details";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'Invalid user ID.');
    redirect('users.php');
}

$user_id = intval($_GET['id']);

// Handle user status update
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $update_sql = "UPDATE users SET status = 'active' WHERE user_id = $user_id";
        $message = 'User activated successfully.';
    } elseif ($action === 'deactivate') {
        $update_sql = "UPDATE users SET status = 'inactive' WHERE user_id = $user_id";
        $message = 'User deactivated successfully.';
    } elseif ($action === 'delete') {
        // Check if user is not an admin
        $check_sql = "SELECT role FROM users WHERE user_id = $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $user_role = $check_result->fetch_assoc()['role'];
            
            if ($user_role === 'admin') {
                set_flash_message('error', 'Cannot delete admin users.');
                redirect($_SERVER['PHP_SELF'] . "?id=$user_id");
            }
        }
        
        $update_sql = "DELETE FROM users WHERE user_id = $user_id AND role != 'admin'";
        $message = 'User deleted successfully.';
        
        if ($conn->query($update_sql)) {
            set_flash_message('success', $message);
            redirect('users.php');
        }
    }
    
    if (isset($update_sql) && $conn->query($update_sql)) {
        set_flash_message('success', $message);
    } else {
        set_flash_message('error', 'Failed to update user status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF'] . "?id=$user_id");
}

// Get user details
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);

if (!$user_result || $user_result->num_rows === 0) {
    set_flash_message('error', 'User not found.');
    redirect('users.php');
}

$user = $user_result->fetch_assoc();

// Get seller profile if user is a seller
$seller_profile = null;
if ($user['role'] === 'seller') {
    $seller_sql = "SELECT * FROM seller_profiles WHERE user_id = $user_id";
    $seller_result = $conn->query($seller_sql);
    
    if ($seller_result && $seller_result->num_rows > 0) {
        $seller_profile = $seller_result->fetch_assoc();
    }
}

// Get user's orders if user is a buyer
$orders = [];
if ($user['role'] === 'buyer') {
    $orders_sql = "SELECT * FROM orders WHERE buyer_id = $user_id ORDER BY created_at DESC LIMIT 10";
    $orders_result = $conn->query($orders_sql);
    
    if ($orders_result && $orders_result->num_rows > 0) {
        while ($row = $orders_result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}

// Get user's reviews
$reviews_sql = "SELECT r.*, f.name as fruit_name 
               FROM fruit_reviews r 
               JOIN fruits f ON r.fruit_id = f.fruit_id 
               WHERE r.user_id = $user_id 
               ORDER BY r.created_at DESC 
               LIMIT 5";
$reviews_result = $conn->query($reviews_sql);
$reviews = [];

if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

include('../includes/header.php');
?>

<section class="user-details-section">
    <div class="container">
        <h2>User Details</h2>
        
        <div class="page-navigation">
            <a href="users.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Users List</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="user-details-container">
            <div class="user-details-grid">
                <!-- User Info Card -->
                <div class="user-info card">
                    <h3>User Information</h3>
                    <div class="profile-header">
                        <div class="profile-image-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        
                        <div class="profile-title">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <div class="user-badges">
                                <span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span>
                                <span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-details">
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Registered On:</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Last Login:</span>
                            <span class="info-value">
                                <?php echo !empty($user['last_login']) ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'seller' && $seller_profile): ?>
                        <div class="seller-info">
                            <h4>Seller Information</h4>
                            <div class="info-row">
                                <span class="info-label">Farm Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($seller_profile['farm_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($seller_profile['location'] ?? 'Not specified'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Verification Status:</span>
                                <span class="info-value">
                                    <span class="status-badge status-<?php echo $seller_profile['is_verified'] ? 'verified' : 'pending'; ?>">
                                        <?php echo $seller_profile['is_verified'] ? 'Verified' : 'Pending Verification'; ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="seller-actions">
                                <a href="seller_details.php?id=<?php echo $seller_profile['seller_id']; ?>" class="btn btn-secondary">View Seller Profile</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-actions">
                        <?php if ($user['role'] !== 'admin' || $user['user_id'] != $_SESSION['user_id']): ?>
                            <?php if ($user['status'] === 'active'): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $user_id; ?>&action=deactivate" class="btn btn-warning" onclick="return confirm('Are you sure you want to deactivate this user?');">Deactivate User</a>
                            <?php else: ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $user_id; ?>&action=activate" class="btn btn-success">Activate User</a>
                            <?php endif; ?>
                            
                            <?php if ($user['role'] !== 'admin'): ?>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $user_id; ?>&action=delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- User Activity Card -->
                <div class="user-activity card">
                    <h3>User Activity</h3>
                    
                    <?php
                    // Get order count
                    $orders_count_sql = "SELECT COUNT(*) as total FROM orders WHERE buyer_id = $user_id";
                    $orders_count_result = $conn->query($orders_count_sql);
                    $orders_count = $orders_count_result ? $orders_count_result->fetch_assoc()['total'] : 0;
                    
                    // Get reviews count
                    $reviews_count_sql = "SELECT COUNT(*) as total FROM fruit_reviews WHERE user_id = $user_id";
                    $reviews_count_result = $conn->query($reviews_count_sql);
                    $reviews_count = $reviews_count_result ? $reviews_count_result->fetch_assoc()['total'] : 0;
                    
                    // Get favorites count
                    $favorites_count_sql = "SELECT COUNT(*) as total FROM favorites WHERE user_id = $user_id";
                    $favorites_count_result = $conn->query($favorites_count_sql);
                    $favorites_count = $favorites_count_result ? $favorites_count_result->fetch_assoc()['total'] : 0;
                    
                    // Get total spent (if buyer)
                    $total_spent = 0;
                    if ($user['role'] === 'buyer') {
                        $spent_sql = "SELECT SUM(total_amount) as total FROM orders WHERE buyer_id = $user_id AND order_status != 'cancelled'";
                        $spent_result = $conn->query($spent_sql);
                        $total_spent = $spent_result ? $spent_result->fetch_assoc()['total'] : 0;
                    }
                    ?>
                    
                    <div class="stats-grid">
                        <?php if ($user['role'] === 'buyer'): ?>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $orders_count; ?></div>
                                <div class="stat-label">Orders</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo format_price($total_spent); ?></div>
                                <div class="stat-label">Total Spent</div>
                            </div>
                        <?php endif; ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $reviews_count; ?></div>
                            <div class="stat-label">Reviews</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $favorites_count; ?></div>
                            <div class="stat-label">Favorites</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($user['role'] === 'buyer' && !empty($orders)): ?>
                <!-- Recent Orders -->
                <div class="user-orders card">
                    <h3>Recent Orders</h3>
                    <div class="orders-table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
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
                    
                    <?php if ($orders_count > 10): ?>
                        <div class="view-all-link">
                            <a href="orders.php?buyer_id=<?php echo $user_id; ?>">View All Orders</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($reviews)): ?>
                <!-- Recent Reviews -->
                <div class="user-reviews card">
                    <h3>Recent Reviews</h3>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-product"><?php echo htmlspecialchars($review['fruit_name']); ?></div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo ($i <= $review['rating']) ? ' active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-content">
                                    <?php echo htmlspecialchars($review['review_text']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($reviews_count > 5): ?>
                        <div class="view-all-link">
                            <a href="#">View All Reviews</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .user-details-section {
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
    
    .user-details-container {
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    
    .user-details-grid {
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
    
    .profile-image-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin-right: 20px;
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
    
    .user-badges {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    /* Badges */
    .role-badge, 
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .role-admin {
        background-color: #E1F5FE;
        color: #0288D1;
    }
    
    .role-seller {
        background-color: #FFF8E1;
        color: #FFA000;
    }
    
    .role-buyer {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-active {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-inactive {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    .status-pending {
        background-color: #FFF8E1;
        color: #FFA000;
    }
    
    .status-verified {
        background-color: #E8F5E9;
        color: #388E3C;
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
    
    /* Seller Info */
    .seller-info {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }
    
    .seller-actions {
        margin-top: 15px;
    }
    
    /* User Actions */
    .user-actions {
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
    
    .btn-danger {
        background-color: #F44336;
        color: white;
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
    
    /* Orders Table */
    .orders-table-container {
        overflow-x: auto;
    }
    
    .orders-table {
        width: 100%;
        border-collapse: collapse;
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
    
    /* Reviews */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .review-item {
        padding: 15px;
        border-radius: 5px;
        background-color: #f9f9f9;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .review-product {
        font-weight: 600;
        color: #333;
    }
    
    .review-rating {
        color: #FFC107;
    }
    
    .review-rating .fa-star.active {
        color: #FFC107;
    }
    
    .review-rating .fa-star:not(.active) {
        color: #ddd;
    }
    
    .review-date {
        font-size: 0.8rem;
        color: #666;
    }
    
    .review-content {
        color: #555;
        line-height: 1.5;
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
    
    @media (max-width: 992px) {
        .user-details-grid {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .user-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 