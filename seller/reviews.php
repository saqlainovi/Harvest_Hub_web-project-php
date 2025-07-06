<?php
$page_title = "Product Reviews";
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

// Get filter parameters
$fruit_id = isset($_GET['fruit_id']) ? (int)$_GET['fruit_id'] : 0;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Build the query with filters
$filter_conditions = "AND f.seller_id = $seller_id";

if ($fruit_id > 0) {
    $filter_conditions .= " AND r.fruit_id = $fruit_id";
}

if ($rating > 0) {
    $filter_conditions .= " AND r.rating = $rating";
}

// Get reviews
$reviews_sql = "SELECT r.*, f.name AS fruit_name, u.full_name AS reviewer_name, u.profile_image
                FROM reviews r
                JOIN fruits f ON r.fruit_id = f.fruit_id
                JOIN users u ON r.user_id = u.user_id
                WHERE 1=1 $filter_conditions
                ORDER BY r.created_at DESC
                LIMIT $offset, $items_per_page";
$reviews_result = $conn->query($reviews_sql);
$reviews = [];

if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total
              FROM reviews r
              JOIN fruits f ON r.fruit_id = f.fruit_id
              WHERE 1=1 $filter_conditions";
$count_result = $conn->query($count_sql);
$total_reviews = ($count_result && $count_result->num_rows > 0) ? $count_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_reviews / $items_per_page);

// Get all fruits by this seller for filter dropdown
$fruits_sql = "SELECT fruit_id, name FROM fruits WHERE seller_id = $seller_id ORDER BY name ASC";
$fruits_result = $conn->query($fruits_sql);
$fruits = [];

if ($fruits_result && $fruits_result->num_rows > 0) {
    while ($row = $fruits_result->fetch_assoc()) {
        $fruits[] = $row;
    }
}

// Calculate average rating for this seller
$avg_rating_sql = "SELECT AVG(r.rating) as avg_rating, COUNT(*) as total_reviews
                  FROM reviews r
                  JOIN fruits f ON r.fruit_id = f.fruit_id
                  WHERE f.seller_id = $seller_id";
$avg_rating_result = $conn->query($avg_rating_sql);
$rating_stats = ($avg_rating_result && $avg_rating_result->num_rows > 0) ? $avg_rating_result->fetch_assoc() : ['avg_rating' => 0, 'total_reviews' => 0];

// Get rating distribution
$rating_dist_sql = "SELECT r.rating, COUNT(*) as count
                   FROM reviews r
                   JOIN fruits f ON r.fruit_id = f.fruit_id
                   WHERE f.seller_id = $seller_id
                   GROUP BY r.rating
                   ORDER BY r.rating DESC";
$rating_dist_result = $conn->query($rating_dist_sql);
$rating_distribution = [];

if ($rating_dist_result && $rating_dist_result->num_rows > 0) {
    while ($row = $rating_dist_result->fetch_assoc()) {
        $rating_distribution[$row['rating']] = $row['count'];
    }
}

// Fill in missing ratings
for ($i = 5; $i >= 1; $i--) {
    if (!isset($rating_distribution[$i])) {
        $rating_distribution[$i] = 0;
    }
}

// Sort by rating descending
krsort($rating_distribution);
?>

<?php include('../includes/header.php'); ?>

<section class="reviews-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Product Reviews</span>
        </div>
        
        <h2>Product Reviews</h2>
        
        <div class="reviews-overview">
            <div class="rating-summary">
                <div class="avg-rating">
                    <h3>Average Rating</h3>
                    <div class="rating-value"><?php echo number_format($rating_stats['avg_rating'], 1); ?></div>
                    <div class="rating-stars">
                        <?php
                        $avg = round($rating_stats['avg_rating']);
                        for ($i = 1; $i <= 5; $i++):
                            if ($i <= $avg):
                        ?>
                            <i class="fas fa-star"></i>
                        <?php else: ?>
                            <i class="far fa-star"></i>
                        <?php endif; endfor; ?>
                    </div>
                    <div class="total-reviews"><?php echo $rating_stats['total_reviews']; ?> reviews</div>
                </div>
                
                <div class="rating-bars">
                    <?php foreach ($rating_distribution as $stars => $count):
                        // Calculate percentage for the bar width
                        $percentage = ($rating_stats['total_reviews'] > 0) ? ($count / $rating_stats['total_reviews']) * 100 : 0;
                    ?>
                        <div class="rating-bar-row">
                            <div class="stars-label"><?php echo $stars; ?> <i class="fas fa-star"></i></div>
                            <div class="rating-bar-container">
                                <div class="rating-bar" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="count-label"><?php echo $count; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="reviews-filter">
                <h3>Filter Reviews</h3>
                <form action="" method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="fruit_id">By Product</label>
                        <select id="fruit_id" name="fruit_id" onchange="this.form.submit()">
                            <option value="0">All Products</option>
                            <?php foreach ($fruits as $fruit): ?>
                                <option value="<?php echo $fruit['fruit_id']; ?>" <?php echo ($fruit_id == $fruit['fruit_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fruit['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating">By Rating</label>
                        <select id="rating" name="rating" onchange="this.form.submit()">
                            <option value="0">All Ratings</option>
                            <option value="5" <?php echo ($rating == 5) ? 'selected' : ''; ?>>5 Stars</option>
                            <option value="4" <?php echo ($rating == 4) ? 'selected' : ''; ?>>4 Stars</option>
                            <option value="3" <?php echo ($rating == 3) ? 'selected' : ''; ?>>3 Stars</option>
                            <option value="2" <?php echo ($rating == 2) ? 'selected' : ''; ?>>2 Stars</option>
                            <option value="1" <?php echo ($rating == 1) ? 'selected' : ''; ?>>1 Star</option>
                        </select>
                    </div>
                    
                    <?php if ($fruit_id > 0 || $rating > 0): ?>
                        <div class="form-group">
                            <a href="<?php echo SITE_URL; ?>/seller/reviews.php" class="btn reset-btn">Reset Filters</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="reviews-list">
            <h3>
                <?php if ($fruit_id > 0): ?>
                    <?php
                    $fruit_name = '';
                    foreach ($fruits as $f) {
                        if ($f['fruit_id'] == $fruit_id) {
                            $fruit_name = $f['name'];
                            break;
                        }
                    }
                    ?>
                    Reviews for <?php echo htmlspecialchars($fruit_name); ?>
                <?php elseif ($rating > 0): ?>
                    <?php echo $rating; ?>-Star Reviews
                <?php else: ?>
                    All Reviews
                <?php endif; ?>
            </h3>
            
            <?php if (empty($reviews)): ?>
                <div class="no-reviews">
                    <p>No reviews found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-img">
                                        <?php if (!empty($review['profile_image'])): ?>
                                            <img src="<?php echo SITE_URL . '/' . $review['profile_image']; ?>" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>">
                                        <?php else: ?>
                                            <div class="profile-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="reviewer-details">
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                        <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-name">For: <?php echo htmlspecialchars($review['fruit_name']); ?></div>
                                </div>
                            </div>
                            
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            
                            <div class="review-content">
                                <?php echo htmlspecialchars($review['comment']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $fruit_id > 0 ? '&fruit_id=' . $fruit_id : ''; ?><?php echo $rating > 0 ? '&rating=' . $rating : ''; ?>" class="pagination-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $fruit_id > 0 ? '&fruit_id=' . $fruit_id : ''; ?><?php echo $rating > 0 ? '&rating=' . $rating : ''; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $fruit_id > 0 ? '&fruit_id=' . $fruit_id : ''; ?><?php echo $rating > 0 ? '&rating=' . $rating : ''; ?>" class="pagination-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .reviews-section {
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
    
    .reviews-overview {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .rating-summary {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        gap: 30px;
    }
    
    .avg-rating {
        display: flex;
        flex-direction: column;
        align-items: center;
        border-right: 1px solid #eee;
        padding-right: 30px;
        flex: 0 0 160px;
    }
    
    .avg-rating h3 {
        margin-bottom: 15px;
        font-size: 1.1rem;
    }
    
    .rating-value {
        font-size: 3rem;
        font-weight: 700;
        color: #4CAF50;
        line-height: 1;
        margin-bottom: 5px;
    }
    
    .rating-stars {
        color: #FFC107;
        font-size: 1.2rem;
        margin-bottom: 5px;
    }
    
    .total-reviews {
        color: #666;
        font-size: 0.9rem;
    }
    
    .rating-bars {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 10px;
    }
    
    .rating-bar-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .stars-label {
        flex: 0 0 60px;
        color: #666;
        font-size: 0.9rem;
        text-align: right;
    }
    
    .rating-bar-container {
        flex: 1;
        height: 12px;
        background-color: #f1f1f1;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .rating-bar {
        height: 100%;
        background-color: #FFC107;
        border-radius: 6px;
    }
    
    .count-label {
        flex: 0 0 30px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .reviews-filter {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .reviews-filter h3 {
        margin-bottom: 20px;
        font-size: 1.1rem;
    }
    
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-group label {
        color: #555;
        font-weight: 500;
    }
    
    .form-group select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .reset-btn {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .reviews-list {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }
    
    .reviews-list h3 {
        margin-bottom: 25px;
        font-size: 1.2rem;
        color: #333;
    }
    
    .no-reviews {
        text-align: center;
        padding: 30px;
        color: #666;
        background-color: #f9f9f9;
        border-radius: 8px;
    }
    
    .reviews-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .review-card {
        padding: 20px;
        border: 1px solid #eee;
        border-radius: 8px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .reviewer-info {
        display: flex;
        gap: 10px;
    }
    
    .reviewer-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
    }
    
    .reviewer-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f1f1f1;
        color: #999;
    }
    
    .reviewer-details {
        display: flex;
        flex-direction: column;
    }
    
    .reviewer-name {
        font-weight: 500;
        color: #333;
    }
    
    .review-date {
        font-size: 0.8rem;
        color: #888;
    }
    
    .product-info {
        text-align: right;
    }
    
    .product-name {
        font-size: 0.9rem;
        color: #666;
    }
    
    .review-rating {
        margin-bottom: 10px;
        color: #FFC107;
    }
    
    .review-content {
        color: #555;
        line-height: 1.5;
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
    
    @media (max-width: 992px) {
        .reviews-overview {
            grid-template-columns: 1fr;
        }
        
        .rating-summary {
            flex-direction: column;
        }
        
        .avg-rating {
            border-right: none;
            border-bottom: 1px solid #eee;
            padding-right: 0;
            padding-bottom: 20px;
            margin-bottom: 20px;
            flex: none;
        }
    }
    
    @media (max-width: 768px) {
        .reviews-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 