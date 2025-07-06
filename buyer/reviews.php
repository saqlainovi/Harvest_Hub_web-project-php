<?php
$page_title = "My Reviews";
require_once '../includes/config.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];

// Get all reviews
$reviews_sql = "SELECT r.*, 
               f.name as product_name, 
               f.image as product_image,
               'fruit' as product_type,
               f.fruit_id as product_id
               FROM reviews r
               JOIN fruits f ON r.fruit_id = f.fruit_id
               WHERE r.user_id = $user_id               
               ORDER BY created_at DESC";

$reviews_result = $conn->query($reviews_sql);
$reviews = [];
if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Handle delete review
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $delete_sql = "DELETE FROM reviews WHERE review_id = $review_id AND user_id = $user_id";
    
    if ($conn->query($delete_sql)) {
        set_flash_message('success', 'Review deleted successfully.');
    } else {
        set_flash_message('error', 'Failed to delete review.');
    }
    redirect($_SERVER['PHP_SELF']);
}

include('../includes/header.php');
?>

<section class="reviews-section">
    <div class="container">
        <h2>My Reviews</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <?php if (empty($reviews)): ?>
            <div class="no-reviews">
                <div class="no-data-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>No Reviews Found</h3>
                <p>You haven't written any reviews yet.</p>
                <a href="../pages/fruits.php" class="btn">Browse Fruits</a>
                <a href="../pages/agricultural_products.php" class="btn">Browse Agricultural Products</a>
            </div>
        <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-img">
                                <?php if (!empty($review['product_image'])): ?>
                                    <img src="<?php echo SITE_URL . '/' . $review['product_image']; ?>" alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                <?php else: ?>
                                    <img src="<?php echo SITE_URL; ?>/images/placeholder.jpg" alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="review-title">
                                <h4><?php echo htmlspecialchars($review['product_name']); ?></h4>
                                <span class="product-type">Fruit</span>
                                <div class="review-date">
                                    <i class="far fa-calendar-alt"></i> <?php echo date('F d, Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="review-content">
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="review-text">
                                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        </div>
                        
                        <div class="review-actions">
                            <a href="../pages/fruit_details.php?id=<?php echo $review['product_id']; ?>" class="btn-sm">View Product</a>
                            <a href="edit_review.php?id=<?php echo $review['review_id']; ?>" class="btn-sm btn-secondary">Edit Review</a>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=<?php echo $review['review_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .reviews-section {
        padding: 60px 0;
    }
    
    .reviews-section h2 {
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
    
    /* No Reviews */
    .no-reviews {
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
    
    .no-reviews h3 {
        margin-bottom: 10px;
        color: #333;
    }
    
    .no-reviews p {
        margin-bottom: 20px;
        color: #666;
    }
    
    .no-reviews .btn {
        display: inline-block;
        margin: 0 5px;
        padding: 10px 20px;
        background: #4CAF50;
        color: white;
        border-radius: 5px;
        text-decoration: none;
    }
    
    /* Reviews List */
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .review-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .review-header {
        display: flex;
        margin-bottom: 15px;
    }
    
    .review-img {
        width: 80px;
        height: 80px;
        border-radius: 5px;
        overflow: hidden;
        margin-right: 15px;
    }
    
    .review-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .review-title h4 {
        margin-bottom: 5px;
        color: #333;
    }
    
    .product-type {
        display: inline-block;
        padding: 2px 8px;
        background: #f1f8e9;
        color: #4CAF50;
        border-radius: 3px;
        font-size: 0.8rem;
        margin-bottom: 5px;
    }
    
    .review-date {
        font-size: 0.8rem;
        color: #666;
    }
    
    .review-date i {
        margin-right: 3px;
    }
    
    .review-content {
        margin-bottom: 15px;
    }
    
    .review-rating {
        margin-bottom: 10px;
    }
    
    .review-rating i {
        color: #FFC107;
    }
    
    .review-text p {
        color: #555;
        line-height: 1.5;
    }
    
    .review-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
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
    
    .btn-secondary {
        background: #2196F3;
        color: white;
    }
    
    .btn-danger {
        background: #F44336;
        color: white;
    }
    
    @media (max-width: 768px) {
        .review-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .review-img {
            margin-right: 0;
            margin-bottom: 10px;
        }
        
        .review-actions {
            justify-content: center;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 