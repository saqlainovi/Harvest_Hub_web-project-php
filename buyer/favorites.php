<?php
$page_title = "My Favorites";
require_once '../includes/config.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];

// Get all favorites
$favorites_sql = "SELECT f.*, c.name as category_name, s.farm_name, AVG(r.rating) as avg_rating
                 FROM favorites fav
                 JOIN fruits f ON fav.fruit_id = f.fruit_id
                 JOIN seller_profiles s ON f.seller_id = s.seller_id
                 LEFT JOIN categories c ON f.category_id = c.category_id
                 LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
                 WHERE fav.user_id = $user_id
                 GROUP BY f.fruit_id
                 ORDER BY fav.created_at DESC";
$favorites_result = $conn->query($favorites_sql);
$favorites = [];
if ($favorites_result && $favorites_result->num_rows > 0) {
    while ($row = $favorites_result->fetch_assoc()) {
        $favorites[] = $row;
    }
}

// Handle remove from favorites
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $fruit_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM favorites WHERE user_id = $user_id AND fruit_id = $fruit_id";
    
    if ($conn->query($delete_sql)) {
        set_flash_message('success', 'Item removed from favorites successfully.');
    } else {
        set_flash_message('error', 'Failed to remove item from favorites.');
    }
    redirect($_SERVER['PHP_SELF']);
}

include('../includes/header.php');
?>

<section class="favorites-section">
    <div class="container">
        <h2>My Favorites</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <?php if (empty($favorites)): ?>
            <div class="no-favorites">
                <div class="no-data-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>No Favorites Found</h3>
                <p>You haven't added any items to your favorites yet.</p>
                <a href="../pages/fruits.php" class="btn">Browse Fruits</a>
                <a href="../pages/agricultural_products.php" class="btn">Browse Agricultural Products</a>
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
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=remove&id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this item from favorites?');">Remove</a>
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
</section>

<style>
    .favorites-section {
        padding: 60px 0;
    }
    
    .favorites-section h2 {
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
    
    /* No Favorites */
    .no-favorites {
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
    
    .no-favorites h3 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .no-favorites p {
        margin-bottom: 20px;
        color: #666;
    }
    
    .no-favorites .btn {
        display: inline-block;
        margin: 0 5px;
        padding: 10px 20px;
        background: #4CAF50;
        color: white;
        border-radius: 5px;
        text-decoration: none;
    }
    
    /* Favorites Grid */
    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .favorite-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .favorite-img {
        height: 200px;
        overflow: hidden;
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
    }
    
    .favorite-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    
    .category {
        color: #666;
        font-size: 0.9rem;
    }
    
    .price {
        font-weight: 500;
        color: #4CAF50;
    }
    
    .favorite-rating {
        margin-bottom: 10px;
    }
    
    .favorite-rating i {
        color: #FFC107;
        font-size: 0.9rem;
    }
    
    .favorite-seller {
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .favorite-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8rem;
        border-radius: 3px;
        text-decoration: none;
        display: inline-block;
        background: #eee;
        color: #333;
    }
    
    .btn-primary {
        background: #4CAF50;
        color: white;
    }
    
    .btn-danger {
        background: #F44336;
        color: white;
    }
    
    .out-of-stock {
        font-size: 0.8rem;
        color: #F44336;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .favorites-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 