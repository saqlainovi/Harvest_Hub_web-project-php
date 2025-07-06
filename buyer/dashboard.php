<?php
$page_title = "Buyer Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get buyer information
$user_id = $_SESSION['user_id'];
$buyer_sql = "SELECT * FROM users WHERE user_id = $user_id";
$buyer_result = $conn->query($buyer_sql);

if ($buyer_result && $buyer_result->num_rows > 0) {
    $buyer = $buyer_result->fetch_assoc();
} else {
    set_flash_message('error', 'Buyer profile not found.');
    redirect(SITE_URL . '/index.php');
}

// Get buyer statistics
// Total orders
$orders_sql = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total_amount) as total_spent
              FROM orders
              WHERE buyer_id = $user_id";
$orders_result = $conn->query($orders_sql);
$orders_stats = ($orders_result && $orders_result->num_rows > 0) ? $orders_result->fetch_assoc() : ['total_orders' => 0, 'pending_orders' => 0, 'confirmed_orders' => 0, 'shipped_orders' => 0, 'delivered_orders' => 0, 'cancelled_orders' => 0, 'total_spent' => 0];

// Recent orders
$recent_orders_sql = "SELECT o.*, 
                      GROUP_CONCAT(CONCAT(oi.quantity, ' kg of ', f.name) SEPARATOR ', ') as items
                      FROM orders o
                      JOIN order_items oi ON o.order_id = oi.order_id
                      JOIN fruits f ON oi.fruit_id = f.fruit_id
                      WHERE o.buyer_id = $user_id
                      GROUP BY o.order_id
                      ORDER BY o.created_at DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);
$recent_orders = [];
if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
    while ($row = $recent_orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Favorite fruits
$favorites_sql = "SELECT f.*, c.name as category_name, s.farm_name, AVG(r.rating) as avg_rating
                  FROM favorites fav
                  JOIN fruits f ON fav.fruit_id = f.fruit_id
                  JOIN seller_profiles s ON f.seller_id = s.seller_id
                  LEFT JOIN categories c ON f.category_id = c.category_id
                  LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
                  WHERE fav.user_id = $user_id
                  GROUP BY f.fruit_id
                  ORDER BY fav.created_at DESC LIMIT 4";
$favorites_result = $conn->query($favorites_sql);
$favorites = [];
if ($favorites_result && $favorites_result->num_rows > 0) {
    while ($row = $favorites_result->fetch_assoc()) {
        $favorites[] = $row;
    }
}

// Recent reviews
$reviews_sql = "SELECT r.*, f.name as fruit_name, f.image as fruit_image
                FROM reviews r
                JOIN fruits f ON r.fruit_id = f.fruit_id
                WHERE r.user_id = $user_id
                ORDER BY r.created_at DESC LIMIT 3";
$reviews_result = $conn->query($reviews_sql);
$reviews = [];
if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="buyer-dashboard">
    <div class="container">
        <h2>My Dashboard</h2>
        <p class="welcome-message">Welcome back, <?php echo htmlspecialchars($buyer['full_name']); ?>!</p>
        
        <!-- Dashboard Stats -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Orders</h3>
                    <p><?php echo $orders_stats['total_orders']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Orders</h3>
                    <p><?php echo $orders_stats['pending_orders'] + $orders_stats['confirmed_orders'] + $orders_stats['shipped_orders']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Completed Orders</h3>
                    <p><?php echo $orders_stats['delivered_orders']; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Spent</h3>
                    <p><?php echo format_price($orders_stats['total_spent'] ?? 0); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Actions -->
        <div class="dashboard-actions">
            <a href="profile.php" class="action-btn">
                <i class="fas fa-user"></i> Edit Profile
            </a>
            <a href="../pages/fruits.php" class="action-btn">
                <i class="fas fa-apple-alt"></i> Shop Fruits
            </a>
            <a href="orders.php" class="action-btn">
                <i class="fas fa-list"></i> View All Orders
            </a>
            <a href="favorites.php" class="action-btn">
                <i class="fas fa-heart"></i> My Favorites
            </a>
            <a href="reviews.php" class="action-btn">
                <i class="fas fa-star"></i> My Reviews
            </a>
        </div>
        
        <!-- Recent Orders -->
        <div class="dashboard-section">
            <div class="section-header">
                <h3>Recent Orders</h3>
                <a href="orders.php" class="view-all">View All</a>
            </div>
            
            <?php if (empty($recent_orders)): ?>
                <div class="no-data">
                    <p>You haven't placed any orders yet. <a href="../pages/fruits.php">Start shopping</a>!</p>
                </div>
            <?php else: ?>
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['items']); ?></td>
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
        
        <!-- Favorite Fruits -->
        <div class="dashboard-section">
            <div class="section-header">
                <h3>My Favorite Fruits</h3>
                <a href="favorites.php" class="view-all">View All</a>
            </div>
            
            <?php if (empty($favorites)): ?>
                <div class="no-data">
                    <p>You don't have any favorite fruits yet. Browse the <a href="../pages/fruits.php">fruit catalog</a> and add some!</p>
                </div>
            <?php else: ?>
                <div class="favorites-grid">
                    <?php foreach ($favorites as $fruit): ?>
                        <div class="favorite-card">
                            <div class="favorite-img">
                                <?php if (!empty($fruit['image'])): ?>
                                    <img src="<?php echo SITE_URL . '/' . $fruit['image']; ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo SITE_URL; ?>/images/placeholder.jpg" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="favorite-info">
                                <h4><?php echo htmlspecialchars($fruit['name']); ?></h4>
                                
                                <div class="favorite-meta">
                                    <span class="category"><?php echo htmlspecialchars($fruit['category_name']); ?></span>
                                    <span class="price"><?php echo format_price($fruit['price_per_kg']); ?>/kg</span>
                                </div>
                                
                                <div class="favorite-rating">
                                    <?php 
                                    $rating = round($fruit['avg_rating']);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $rating): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="favorite-seller">
                                    <span>Seller: <?php echo htmlspecialchars($fruit['farm_name']); ?></span>
                                </div>
                                
                                <div class="favorite-actions">
                                    <a href="../pages/fruit_details.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm">View Details</a>
                                    <?php if ($fruit['is_available'] && $fruit['stock_quantity'] > 0): ?>
                                        <a href="../pages/cart.php?action=add&id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-primary">Add to Cart</a>
                                    <?php else: ?>
                                        <span class="out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Reviews -->
        <div class="dashboard-section">
            <div class="section-header">
                <h3>My Recent Reviews</h3>
                <a href="reviews.php" class="view-all">View All</a>
            </div>
            
            <?php if (empty($reviews)): ?>
                <div class="no-data">
                    <p>You haven't written any reviews yet.</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-img">
                                <?php if (!empty($review['fruit_image'])): ?>
                                    <img src="<?php echo SITE_URL . '/' . $review['fruit_image']; ?>" alt="<?php echo htmlspecialchars($review['fruit_name']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo SITE_URL; ?>/images/placeholder.jpg" alt="<?php echo htmlspecialchars($review['fruit_name']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-content">
                                <h4><?php echo htmlspecialchars($review['fruit_name']); ?></h4>
                                
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                
                                <div class="review-text">
                                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                                
                                <div class="review-actions">
                                    <a href="../pages/fruit_details.php?id=<?php echo $review['fruit_id']; ?>" class="btn-sm">View Fruit</a>
                                    <a href="edit_review.php?id=<?php echo $review['review_id']; ?>" class="btn-sm btn-secondary">Edit Review</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .buyer-dashboard {
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
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
    }
    
    /* Dashboard Actions */
    .dashboard-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 40px;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        background: white;
        color: #333;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }
    
    .action-btn:hover {
        background: #4CAF50;
        color: white;
    }
    
    .action-btn i {
        font-size: 1.2rem;
    }
    
    /* Dashboard Sections */
    .dashboard-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
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
    
    /* Orders Table */
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
    
    /* Favorites Grid */
    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .favorite-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s;
    }
    
    .favorite-card:hover {
        transform: translateY(-5px);
    }
    
    .favorite-img {
        height: 150px;
    }
    
    .favorite-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .favorite-info {
        padding: 15px;
    }
    
    .favorite-info h4 {
        margin-bottom: 10px;
        color: #333;
        font-size: 1.1rem;
    }
    
    .favorite-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.9rem;
    }
    
    .category {
        color: #666;
    }
    
    .price {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .favorite-rating {
        margin-bottom: 10px;
        color: #FFC107;
    }
    
    .favorite-seller {
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .favorite-actions {
        display: flex;
        gap: 10px;
    }
    
    .out-of-stock {
        color: #F44336;
        font-size: 0.9rem;
        display: inline-block;
        padding: 5px 0;
    }
    
    /* Reviews */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .review-card {
        display: flex;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .review-card:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .review-img {
        width: 80px;
        height: 80px;
        border-radius: 5px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .review-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .review-content {
        flex: 1;
    }
    
    .review-content h4 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .review-rating {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        color: #FFC107;
    }
    
    .review-date {
        margin-left: 10px;
        font-size: 0.8rem;
        color: #888;
    }
    
    .review-text {
        margin-bottom: 15px;
        color: #555;
    }
    
    .review-actions {
        display: flex;
        gap: 10px;
    }
    
    /* Buttons */
    .btn-sm {
        display: inline-block;
        padding: 5px 10px;
        background: #4CAF50;
        color: white;
        border-radius: 3px;
        font-size: 0.8rem;
        text-align: center;
    }
    
    .btn-primary {
        background: #FF9800;
    }
    
    .btn-secondary {
        background: #2196F3;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .orders-table {
            overflow-x: auto;
        }
        
        .dashboard-actions {
            justify-content: center;
        }
        
        .review-card {
            flex-direction: column;
        }
        
        .review-img {
            width: 100%;
            height: 150px;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 