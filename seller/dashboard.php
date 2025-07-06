<?php
$page_title = "Seller Dashboard";
require_once '../includes/config.php';

// Check if user is logged in and is a seller
if (!is_logged_in() || !has_role('seller')) {
    set_flash_message('error', 'You must be logged in as a seller to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get seller information
$user_id = $_SESSION['user_id'];
$seller_sql = "SELECT sp.*, u.full_name, u.email, u.username, u.profile_image 
              FROM seller_profiles sp 
              JOIN users u ON sp.user_id = u.user_id 
              WHERE sp.user_id = $user_id";
$seller_result = $conn->query($seller_sql);

if ($seller_result && $seller_result->num_rows > 0) {
    $seller = $seller_result->fetch_assoc();
} else {
    set_flash_message('error', 'Seller profile not found.');
    redirect(SITE_URL . '/index.php');
}

// Get seller statistics
// Total fruits
$fruits_sql = "SELECT COUNT(*) as total_fruits FROM fruits WHERE seller_id = {$seller['seller_id']}";
$fruits_result = $conn->query($fruits_sql);
$total_fruits = ($fruits_result && $fruits_result->num_rows > 0) ? $fruits_result->fetch_assoc()['total_fruits'] : 0;

// Total sales
$sales_sql = "SELECT COUNT(o.order_id) as total_orders, SUM(oi.subtotal) as total_sales
              FROM orders o
              JOIN order_items oi ON o.order_id = oi.order_id
              JOIN fruits f ON oi.fruit_id = f.fruit_id
              WHERE f.seller_id = {$seller['seller_id']}
              AND o.order_status != 'cancelled'";
$sales_result = $conn->query($sales_sql);
$sales = ($sales_result && $sales_result->num_rows > 0) ? $sales_result->fetch_assoc() : ['total_orders' => 0, 'total_sales' => 0];

// Average rating
$rating_sql = "SELECT AVG(r.rating) as avg_rating, COUNT(r.review_id) as total_reviews
               FROM reviews r
               JOIN fruits f ON r.fruit_id = f.fruit_id
               WHERE f.seller_id = {$seller['seller_id']}";
$rating_result = $conn->query($rating_sql);
$rating = ($rating_result && $rating_result->num_rows > 0) ? $rating_result->fetch_assoc() : ['avg_rating' => 0, 'total_reviews' => 0];

// Recent orders
$orders_sql = "SELECT o.order_id, o.created_at, o.total_amount, o.order_status, u.full_name as buyer_name,
                GROUP_CONCAT(f.name SEPARATOR ', ') as fruit_names
                FROM orders o
                JOIN order_items oi ON o.order_id = oi.order_id
                JOIN fruits f ON oi.fruit_id = f.fruit_id
                JOIN users u ON o.buyer_id = u.user_id
                WHERE f.seller_id = {$seller['seller_id']}
                GROUP BY o.order_id
                ORDER BY o.created_at DESC
                LIMIT 5";
$orders_result = $conn->query($orders_sql);
$recent_orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

// Get fruits by this seller
$fruits_list_sql = "SELECT f.*, c.name as category_name, AVG(r.rating) as avg_rating, COUNT(r.review_id) as review_count
                   FROM fruits f
                   LEFT JOIN categories c ON f.category_id = c.category_id
                   LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
                   WHERE f.seller_id = {$seller['seller_id']}
                   GROUP BY f.fruit_id
                   ORDER BY f.created_at DESC";
$fruits_list_result = $conn->query($fruits_list_sql);
$fruits_list = [];
if ($fruits_list_result && $fruits_list_result->num_rows > 0) {
    while ($row = $fruits_list_result->fetch_assoc()) {
        $fruits_list[] = $row;
    }
}

// Set extra CSS for this page
$extra_css = '
<style>
    .seller-dashboard {
        background: linear-gradient(135deg, #f8f9fa, #f1f8e9);
        padding: 60px 0;
    }
    
    .seller-dashboard h2 {
        font-size: 2.2rem;
        margin-bottom: 30px;
        color: #2E7D32;
        text-align: center;
        position: relative;
        padding-bottom: 15px;
    }
    
    .seller-dashboard h2:after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, #4CAF50, #8BC34A);
        border-radius: 2px;
    }
    
    .profile-section {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
        margin-bottom: 40px;
        background: linear-gradient(to right, #f1f8e9, #e8f5e9);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        overflow: hidden;
        padding: 0;
        position: relative;
    }
    
    .profile-section:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(to right, #4CAF50, #8BC34A);
        z-index: 1;
    }
    
    .profile-info {
        background-color: rgba(255, 255, 255, 0.95);
        border-radius: 16px;
        box-shadow: 0 12px 20px rgba(0,0,0,0.06);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        padding: 35px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .profile-img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        overflow: hidden;
        margin-bottom: 25px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        border: 5px solid white;
    }
    
    .profile-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    
    .dashboard-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 12px 24px rgba(0,0,0,0.06);
        border-top: 5px solid #4CAF50;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.06);
        border-left: 5px solid #4CAF50;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .section-header h3 {
        color: #2E7D32;
        font-size: 1.4rem;
        font-weight: 600;
        position: relative;
        padding-left: 18px;
    }
    
    .section-header h3::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: linear-gradient(to bottom, #4CAF50, #8BC34A);
        border-radius: 5px;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 22px;
        background: white;
        color: #333;
        font-weight: 500;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        text-decoration: none;
    }
    
    .action-btn:hover {
        background: linear-gradient(to right, #4CAF50, #66BB6A);
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        text-decoration: none;
    }
    
    .action-btn i {
        color: #4CAF50;
        font-size: 1.3rem;
    }
    
    .action-btn:hover i {
        color: white;
    }
    
    .status-badge {
        display: inline-block;
        padding: 8px 15px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        letter-spacing: 0.5px;
        text-transform: uppercase;
        text-align: center;
        min-width: 100px;
    }
    
    .status-active, .status-available {
        background: linear-gradient(45deg, #e8f5e9, #c8e6c9);
        color: #2e7d32;
    }
    
    .status-inactive, .status-unavailable {
        background: linear-gradient(45deg, #ffebee, #ffcdd2);
        color: #c62828;
    }
    
    .status-pending {
        background: linear-gradient(45deg, #fff8e1, #ffecb3);
        color: #ff8f00;
    }
    
    @media (max-width: 992px) {
        .profile-section {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .seller-dashboard {
            padding: 40px 0;
        }
    }
</style>';
?>

<?php include('../includes/header.php'); ?>

<section class="seller-dashboard">
    <div class="container">
        <h2>Seller Dashboard</h2>
        
        <div class="profile-section">
            <div class="profile-info">
                <div class="profile-img">
                    <?php if (!empty($seller['profile_image'])): ?>
                        <img src="<?php echo SITE_URL . '/' . $seller['profile_image']; ?>" alt="<?php echo htmlspecialchars($seller['full_name']); ?>">
                    <?php else: ?>
                        <div class="profile-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-details">
                    <h3><?php echo htmlspecialchars($seller['farm_name']); ?></h3>
                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($seller['full_name']); ?></p>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($seller['email']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo !empty($seller['location']) ? htmlspecialchars($seller['location']) : 'Location not specified'; ?></p>
                    <p><i class="fas fa-check-circle"></i> <?php echo $seller['is_verified'] ? 'Verified Seller' : 'Verification Pending'; ?></p>
                    <a href="profile.php" class="btn edit-profile-btn">Edit Profile</a>
                </div>
            </div>
            
            <div class="seller-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-apple-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Total Fruits</h4>
                        <p><?php echo $total_fruits; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Total Orders</h4>
                        <p><?php echo $sales['total_orders']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Total Sales</h4>
                        <p><?php echo format_price($sales['total_sales'] ?? 0); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Average Rating</h4>
                        <p><?php echo number_format($rating['avg_rating'] ?? 0, 1); ?>/5 (<?php echo $rating['total_reviews']; ?> reviews)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-actions">
            <a href="add_fruit.php" class="action-btn">
                <i class="fas fa-plus"></i> Add New Fruit
            </a>
            <a href="add_agricultural_product.php" class="action-btn">
                <i class="fas fa-seedling"></i> Add Agricultural Product
            </a>
            <a href="orders.php" class="action-btn">
                <i class="fas fa-shopping-cart"></i> Manage Orders
            </a>
            <a href="harvest_seasons.php" class="action-btn">
                <i class="fas fa-calendar-alt"></i> Manage Harvest Seasons
            </a>
            <a href="reviews.php" class="action-btn">
                <i class="fas fa-star"></i> View Reviews
            </a>
        </div>
        
        <div class="dashboard-content">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>Recent Orders</h3>
                    <a href="orders.php" class="view-all">View All</a>
                </div>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="no-data">
                        <p>No orders yet.</p>
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
                                        <td><?php echo htmlspecialchars($order['fruit_names']); ?></td>
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
            
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>My Fruits</h3>
                    <a href="fruits.php" class="view-all">View All</a>
                </div>
                
                <?php if (empty($fruits_list)): ?>
                    <div class="no-data">
                        <p>You haven't added any fruits yet. <a href="add_fruit.php">Add your first fruit</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="fruits-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fruits_list as $fruit): ?>
                                    <tr>
                                        <td class="fruit-img">
                                            <?php if (!empty($fruit['image']) && file_exists('../' . $fruit['image'])): ?>
                                                <img src="<?php echo SITE_URL . '/' . $fruit['image']; ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                                            <?php else: ?>
                                                <img src="<?php echo SITE_URL . '/img/fruit_placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($fruit['name']); ?></td>
                                        <td><?php echo htmlspecialchars($fruit['category_name']); ?></td>
                                        <td><?php echo format_price($fruit['price_per_kg']); ?>/kg</td>
                                        <td><?php echo $fruit['stock_quantity']; ?> kg</td>
                                        <td>
                                            <?php if ($fruit['review_count'] > 0): ?>
                                                <?php echo number_format($fruit['avg_rating'], 1); ?>/5 (<?php echo $fruit['review_count']; ?>)
                                            <?php else: ?>
                                                No ratings
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $fruit['is_available'] ? 'available' : 'unavailable'; ?>">
                                                <?php echo $fruit['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_fruit.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm edit-btn">Edit</a>
                                                <a href="delete_fruit.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm delete-btn" onclick="return confirm('Are you sure you want to delete this fruit?')">Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Agricultural Products Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3>My Agricultural Products</h3>
                    <a href="agricultural_products.php" class="view-all">View All</a>
                </div>
                
                <?php
                // Check if agricultural_products table exists
                $agri_table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
                $agri_table_exists = $agri_table_check && $agri_table_check->num_rows > 0;
                
                // Get agricultural products for this seller
                $agri_products = [];
                if ($agri_table_exists) {
                    $agri_sql = "SELECT * FROM agricultural_products WHERE seller_id = {$seller['seller_id']} ORDER BY created_at DESC LIMIT 5";
                    $agri_result = $conn->query($agri_sql);
                    
                    if ($agri_result && $agri_result->num_rows > 0) {
                        while ($row = $agri_result->fetch_assoc()) {
                            $agri_products[] = $row;
                        }
                    }
                }
                ?>
                
                <?php if (!$agri_table_exists): ?>
                    <div class="no-data">
                        <p>The agricultural products feature needs to be set up. <a href="add_agricultural_product.php">Add your first agricultural product</a>.</p>
                    </div>
                <?php elseif (empty($agri_products)): ?>
                    <div class="no-data">
                        <p>You haven't added any agricultural products yet. <a href="add_agricultural_product.php">Add your first agricultural product</a>.</p>
                    </div>
                <?php else: ?>
                    <div class="products-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Organic</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agri_products as $product): ?>
                                    <tr>
                                        <td class="product-img">
                                            <?php if (!empty($product['image']) && file_exists('../' . $product['image'])): ?>
                                                <img src="<?php echo SITE_URL . '/' . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <?php else: ?>
                                                <img src="<?php echo SITE_URL . '/images/agricultural/rice_placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td><?php echo format_price($product['price_per_kg']); ?>/kg</td>
                                        <td><?php echo $product['stock_quantity']; ?> kg</td>
                                        <td><?php echo $product['is_organic'] ? 'Yes' : 'No'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $product['is_available'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_agricultural_product.php?id=<?php echo $product['product_id']; ?>" class="btn-sm edit-btn">Edit</a>
                                                <a href="agricultural_products.php?action=delete&id=<?php echo $product['product_id']; ?>" class="btn-sm delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
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

<?php include('../includes/footer.php'); ?> 