<?php
require_once '../includes/config.php';

// Get fruit ID
$fruit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($fruit_id <= 0) {
    set_flash_message('error', 'Invalid fruit ID.');
    redirect(SITE_URL . '/pages/fruits.php');
}

// Get fruit details
$sql = "SELECT f.*, c.name as category_name, s.farm_name, s.description as seller_description, s.location, 
        u.full_name as seller_name, AVG(r.rating) as avg_rating, COUNT(r.review_id) as review_count
        FROM fruits f
        JOIN seller_profiles s ON f.seller_id = s.seller_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
        WHERE f.fruit_id = $fruit_id
        GROUP BY f.fruit_id";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    set_flash_message('error', 'Fruit not found.');
    redirect(SITE_URL . '/pages/fruits.php');
}

$fruit = $result->fetch_assoc();

// Get harvest seasons
$harvest_sql = "SELECT * FROM harvest_seasons WHERE fruit_id = $fruit_id ORDER BY start_date";
$harvest_result = $conn->query($harvest_sql);
$harvest_seasons = [];

if ($harvest_result && $harvest_result->num_rows > 0) {
    while ($row = $harvest_result->fetch_assoc()) {
        $harvest_seasons[] = $row;
    }
}

// Get reviews
$reviews_sql = "SELECT r.*, u.full_name, u.profile_image
                FROM reviews r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.fruit_id = $fruit_id
                ORDER BY r.created_at DESC
                LIMIT 5";
$reviews_result = $conn->query($reviews_sql);
$reviews = [];

if ($reviews_result && $reviews_result->num_rows > 0) {
    while ($row = $reviews_result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Handle adding to cart
if (isset($_POST['add_to_cart']) && is_logged_in() && has_role('buyer')) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($quantity <= 0 || $quantity > $fruit['stock_quantity']) {
        $error = 'Invalid quantity.';
    } else {
        // In a real application, you would add to cart in the database or session
        // For now, we'll just show a success message
        set_flash_message('success', $quantity . ' kg of ' . $fruit['name'] . ' added to your cart!');
        redirect(SITE_URL . '/pages/fruit_details.php?id=' . $fruit_id);
    }
}

// Handle adding to favorites
if (isset($_POST['add_to_favorites']) && is_logged_in() && has_role('buyer')) {
    $user_id = $_SESSION['user_id'];
    
    // Check if already in favorites
    $check_sql = "SELECT * FROM favorites WHERE user_id = $user_id AND fruit_id = $fruit_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        set_flash_message('info', 'This fruit is already in your favorites.');
    } else {
        $insert_sql = "INSERT INTO favorites (user_id, fruit_id) VALUES ($user_id, $fruit_id)";
        if ($conn->query($insert_sql)) {
            set_flash_message('success', 'Added to your favorites!');
        } else {
            set_flash_message('error', 'Error adding to favorites: ' . $conn->error);
        }
    }
    
    redirect(SITE_URL . '/pages/fruit_details.php?id=' . $fruit_id);
}

$page_title = $fruit['name'];
?>

<?php include('../includes/header.php'); ?>

<section class="fruit-details">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php">Home</a> &gt;
            <a href="<?php echo SITE_URL; ?>/pages/fruits.php">Fruits</a> &gt;
            <span><?php echo htmlspecialchars($fruit['name']); ?></span>
        </div>
        
        <div class="fruit-main-content">            <div class="fruit-image">
                <?php if (!empty($fruit['image'])): 
                    // Check if the path already includes the base URL
                    if (strpos($fruit['image'], 'http') === 0) {
                        $image_url = $fruit['image'];
                    } else {
                        // Check if image exists in 'img' directory
                        if (file_exists(__DIR__ . '/../' . $fruit['image'])) {
                            $image_url = SITE_URL . '/' . $fruit['image'];
                        } else {
                            // Try alternative paths
                            $image_url = SITE_URL . '/img/' . basename($fruit['image']);
                        }
                    }
                ?>
                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>" onerror="this.src='<?php echo SITE_URL; ?>/img/placeholder.jpg'">
                <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/img/placeholder.jpg" alt="<?php echo htmlspecialchars($fruit['name']); ?>">
                <?php endif; ?>
                
                <?php if ($fruit['is_organic']): ?>
                    <span class="organic-badge">Organic</span>
                <?php endif; ?>
            </div>
            
            <div class="fruit-info">
                <h1><?php echo htmlspecialchars($fruit['name']); ?></h1>
                
                <div class="fruit-meta">
                    <div class="category">
                        Category: <span><?php echo htmlspecialchars($fruit['category_name']); ?></span>
                    </div>
                    
                    <div class="rating">
                        <div class="stars">
                            <?php 
                            $rating = !is_null($fruit['avg_rating']) ? round($fruit['avg_rating']) : 0;
                            for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $rating): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span>(<?php echo $fruit['review_count']; ?> reviews)</span>
                    </div>
                </div>
                
                <div class="price">
                    <h2><?php echo format_price($fruit['price_per_kg']); ?> <span>per kg</span></h2>
                </div>
                
                <div class="availability">
                    <?php if ($fruit['is_available'] && $fruit['stock_quantity'] > 0): ?>
                        <span class="in-stock">In Stock (<?php echo $fruit['stock_quantity']; ?> kg available)</span>
                    <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="description">
                    <h3>Description</h3>
                    <p><?php echo htmlspecialchars($fruit['description']); ?></p>
                </div>
                
                <?php if ($fruit['is_available'] && $fruit['stock_quantity'] > 0): ?>
                    <form action="add_to_cart.php" method="POST" class="cart-form">
                        <input type="hidden" name="product_id" value="<?php echo $fruit_id; ?>">
                        <input type="hidden" name="product_type" value="fruit">
                        <input type="hidden" name="return_to_product" value="1">
                        <div class="quantity-selector">
                            <label for="quantity">Quantity (kg):</label>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $fruit['stock_quantity']; ?>">
                                <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            
                            <button type="submit" formaction="add_to_cart.php?redirect_to_cart=1" class="btn buy-now-btn" name="redirect_to_cart" value="1">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="seller-info">
                    <h3>Seller Information</h3>
                    <p><strong>Farm/Orchard:</strong> <?php echo htmlspecialchars($fruit['farm_name']); ?></p>
                    <p><strong>Seller:</strong> <?php echo htmlspecialchars($fruit['seller_name']); ?></p>
                    <?php if (!empty($fruit['location'])): ?>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($fruit['location']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($fruit['seller_description'])): ?>
                        <p><strong>About:</strong> <?php echo htmlspecialchars($fruit['seller_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Harvest Seasons Section -->
        <?php if (!empty($harvest_seasons)): ?>
            <div class="harvest-seasons-section">
                <h2>Harvest Seasons</h2>
                <div class="harvest-seasons-grid">
                    <?php foreach ($harvest_seasons as $season): ?>
                        <div class="harvest-season-card">
                            <div class="season-period">
                                <h4>
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('M d', strtotime($season['start_date'])); ?> - 
                                    <?php echo date('M d', strtotime($season['end_date'])); ?>
                                </h4>
                            </div>
                            
                            <?php if (!empty($season['region'])): ?>
                                <div class="season-region">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($season['region']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($season['notes'])): ?>
                                <div class="season-notes">
                                    <p><?php echo htmlspecialchars($season['notes']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2>Customer Reviews</h2>
            
            <?php if (empty($reviews)): ?>
                <div class="no-reviews">
                    <p>No reviews yet. Be the first to review this fruit!</p>
                </div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-img">
                                        <?php if (!empty($review['profile_image'])): ?>
                                            <img src="<?php echo SITE_URL . '/' . $review['profile_image']; ?>" alt="<?php echo htmlspecialchars($review['full_name']); ?>">
                                        <?php else: ?>
                                            <div class="profile-placeholder">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="reviewer-details">
                                        <h4><?php echo htmlspecialchars($review['full_name']); ?></h4>
                                        <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
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
                            </div>
                            
                            <div class="review-content">
                                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (is_logged_in() && has_role('buyer')): ?>
                <div class="write-review">
                    <h3>Write a Review</h3>
                    <form action="submit_review.php" method="POST">
                        <input type="hidden" name="fruit_id" value="<?php echo $fruit_id; ?>">
                        
                        <div class="rating-select">
                            <label>Your Rating:</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5"><i class="far fa-star"></i></label>
                                
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4"><i class="far fa-star"></i></label>
                                
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3"><i class="far fa-star"></i></label>
                                
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2"><i class="far fa-star"></i></label>
                                
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1"><i class="far fa-star"></i></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Your Review:</label>
                            <textarea id="comment" name="comment" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn submit-review-btn">Submit Review</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .fruit-details {
        padding: 60px 0;
    }
    
    .breadcrumb {
        margin-bottom: 30px;
        color: #666;
    }
    
    .breadcrumb a {
        color: #4CAF50;
    }
    
    /* Main Content */
    .fruit-main-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 40px;
        margin-bottom: 60px;
    }
    
    /* Fruit Image */
    .fruit-image {
        position: relative;
        height: 400px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .fruit-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .organic-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #8BC34A;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
    }
    
    /* Fruit Info */
    .fruit-info h1 {
        margin-bottom: 15px;
        color: #333;
        font-size: 2.2rem;
    }
    
    .fruit-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .category {
        color: #666;
    }
    
    .category span {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .rating {
        display: flex;
        align-items: center;
    }
    
    .stars {
        color: #FFC107;
        margin-right: 10px;
    }
    
    .price {
        margin-bottom: 20px;
    }
    
    .price h2 {
        color: #4CAF50;
        font-size: 2rem;
        text-align: left;
    }
    
    .price h2 span {
        font-size: 1rem;
        color: #666;
        font-weight: normal;
    }
    
    .availability {
        margin-bottom: 20px;
    }
    
    .in-stock {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .out-of-stock {
        color: #F44336;
        font-weight: 500;
    }
    
    .description {
        margin-bottom: 30px;
    }
    
    .description h3 {
        margin-bottom: 10px;
        color: #333;
        font-size: 1.3rem;
    }
    
    .description p {
        color: #555;
        line-height: 1.6;
    }
    
    /* Cart Form */
    .cart-form {
        margin-bottom: 30px;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 10px;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .quantity-selector label {
        margin-right: 15px;
        font-weight: 500;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
    }
    
    .quantity-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #ddd;
        background: white;
        font-size: 16px;
        cursor: pointer;
    }
    
    .quantity-controls input {
        width: 60px;
        height: 30px;
        text-align: center;
        border: 1px solid #ddd;
        border-left: none;
        border-right: none;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
    }
    
    .add-to-cart-btn {
        background: #FF9800;
        color: white;
        flex: 1;
        border: none;
        padding: 12px 0;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    
    .add-to-cart-btn:hover {
        background: #F57C00;
    }
    
    .buy-now-btn {
        background: #4CAF50;
        color: white;
        flex: 1;
        border: none;
        padding: 12px 0;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    
    .buy-now-btn:hover {
        background: #388E3C;
    }
    
    .add-to-favorites-btn {
        background: white;
        color: #333;
        border: 1px solid #ddd;
        flex: 1;
        padding: 12px 0;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
    }
    
    .add-to-favorites-btn:hover {
        background: #f1f1f1;
        border-color: #ccc;
    }
    
    /* Seller Info */
    .seller-info {
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }
    
    .seller-info h3 {
        margin-bottom: 15px;
        color: #333;
        font-size: 1.3rem;
    }
    
    .seller-info p {
        margin-bottom: 10px;
        color: #555;
    }
    
    /* Harvest Seasons */
    .harvest-seasons-section {
        margin-bottom: 60px;
    }
    
    .harvest-seasons-section h2 {
        margin-bottom: 30px;
        color: #333;
    }
    
    .harvest-seasons-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .harvest-season-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    }
    
    .season-period {
        margin-bottom: 15px;
    }
    
    .season-period h4 {
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .season-period i, .season-region i {
        color: #4CAF50;
    }
    
    .season-region {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        color: #666;
    }
    
    .season-notes {
        color: #555;
        font-size: 0.9rem;
    }
    
    /* Reviews Section */
    .reviews-section {
        margin-bottom: 40px;
    }
    
    .reviews-section h2 {
        margin-bottom: 30px;
        color: #333;
    }
    
    .no-reviews {
        text-align: center;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 10px;
        color: #666;
    }
    
    .reviews-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .review-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .reviewer-info {
        display: flex;
        gap: 15px;
    }
    
    .reviewer-img {
        width: 50px;
        height: 50px;
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
        background: #f1f1f1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
    }
    
    .reviewer-details h4 {
        margin-bottom: 5px;
        color: #333;
    }
    
    .review-date {
        font-size: 0.8rem;
        color: #888;
    }
    
    .review-rating {
        color: #FFC107;
    }
    
    .review-content {
        color: #555;
        line-height: 1.6;
    }
    
    /* Write Review */
    .write-review {
        background: #f9f9f9;
        padding: 30px;
        border-radius: 10px;
    }
    
    .write-review h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    .rating-select {
        margin-bottom: 20px;
    }
    
    .rating-select label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
    }
    
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        gap: 5px;
    }
    
    .star-rating input {
        display: none;
    }
    
    .star-rating label {
        cursor: pointer;
        font-size: 1.5rem;
        color: #ddd;
    }
    
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input:checked ~ label {
        color: #FFC107;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
    }
    
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        resize: vertical;
    }
    
    .submit-review-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .submit-review-btn:hover {
        background: #388E3C;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .fruit-main-content {
            grid-template-columns: 1fr;
        }
        
        .fruit-image {
            height: 300px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<script>
    // Quantity selector
    function incrementQuantity() {
        const input = document.getElementById('quantity');
        const max = parseInt(input.getAttribute('max'));
        let value = parseInt(input.value);
        
        if (value < max) {
            input.value = value + 1;
        }
    }
    
    function decrementQuantity() {
        const input = document.getElementById('quantity');
        let value = parseInt(input.value);
        
        if (value > 1) {
            input.value = value - 1;
        }
    }
    
    // Star rating
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.star-rating label');
        
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                this.querySelector('i').classList.remove('far');
                this.querySelector('i').classList.add('fas');
                
                let prevSibling = this.previousElementSibling;
                while (prevSibling && prevSibling.tagName === 'LABEL') {
                    prevSibling.querySelector('i').classList.remove('far');
                    prevSibling.querySelector('i').classList.add('fas');
                    prevSibling = prevSibling.previousElementSibling;
                }
                
                let nextSibling = this.nextElementSibling;
                while (nextSibling && nextSibling.tagName === 'LABEL') {
                    nextSibling.querySelector('i').classList.remove('fas');
                    nextSibling.querySelector('i').classList.add('far');
                    nextSibling = nextSibling.nextElementSibling;
                }
            });
            
            star.addEventListener('mouseout', function() {
                const checkedInput = document.querySelector('.star-rating input:checked');
                
                if (!checkedInput) {
                    document.querySelectorAll('.star-rating i').forEach(icon => {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    });
                } else {
                    const checkedValue = parseInt(checkedInput.value);
                    
                    document.querySelectorAll('.star-rating label').forEach((label, index) => {
                        const icon = label.querySelector('i');
                        const starValue = 5 - index;
                        
                        if (starValue <= checkedValue) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                    });
                }
            });
        });
    });
</script>

<?php include('../includes/footer.php'); ?> 