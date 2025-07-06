<?php
$page_title = "Fruit Catalog";
require_once '../includes/config.php';

// Get filter parameters
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$organic = isset($_GET['organic']) ? (int)$_GET['organic'] : 0;
$season = isset($_GET['season']) ? (int)$_GET['season'] : 0;

// Build the query
$sql = "SELECT f.*, c.name as category_name, s.farm_name, u.full_name as seller_name, AVG(r.rating) as avg_rating 
        FROM fruits f
        JOIN seller_profiles s ON f.seller_id = s.seller_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN categories c ON f.category_id = c.category_id
        LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
        WHERE f.is_available = 1";

// Add filters
if ($category > 0) {
    $sql .= " AND f.category_id = $category";
}

if (!empty($search)) {
    $sql .= " AND (f.name LIKE '%$search%' OR f.description LIKE '%$search%')";
}

if ($organic == 1) {
    $sql .= " AND f.is_organic = 1";
}

if ($season > 0) {
    $current_date = date('Y-m-d');
    $sql .= " AND f.fruit_id IN (SELECT fruit_id FROM harvest_seasons WHERE start_date <= '$current_date' AND end_date >= '$current_date')";
}

$sql .= " GROUP BY f.fruit_id ORDER BY f.name ASC";

// Execute query
$result = $conn->query($sql);
$fruits = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fruits[] = $row;
    }
}

// Get all categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="fruits-section">
    <div class="container">
        <h2>Fruit Catalog</h2>
        
        <!-- Filter and Search -->
        <div class="filters">
            <form action="" method="GET" class="filter-form">
                <div class="search-bar">
                    <input type="text" name="search" placeholder="Search fruits..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filter-options">
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo ($category == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="season">Season:</label>
                        <select name="season" id="season">
                            <option value="0">All Seasons</option>
                            <option value="1" <?php echo ($season == 1) ? 'selected' : ''; ?>>Currently In Season</option>
                        </select>
                    </div>
                    
                    <div class="filter-group checkbox">
                        <input type="checkbox" id="organic" name="organic" value="1" <?php echo ($organic == 1) ? 'checked' : ''; ?>>
                        <label for="organic">Organic Only</label>
                    </div>
                    
                    <button type="submit" class="btn filter-btn">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <!-- Fruits Grid -->
        <div class="fruits-grid">
            <?php if (empty($fruits)): ?>
                <div class="no-results">
                    <p>No fruits found matching your criteria. Try adjusting your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($fruits as $fruit): ?>
                    <div class="fruit-card">                        <div class="fruit-image">
                            <?php 
                            // Update image path handling to fix the 404 errors
                            if (!empty($fruit['image'])): 
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
                            <h3><a href="fruit_details.php?id=<?php echo $fruit['fruit_id']; ?>"><?php echo htmlspecialchars($fruit['name']); ?></a></h3>
                            
                            <div class="category">
                                <span><?php echo htmlspecialchars($fruit['category_name']); ?></span>
                            </div>
                            
                            <div class="rating">
                                <?php 
                                $rating = round($fruit['avg_rating'] ?? 0);
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fas fa-star"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                                <span>(<?php echo $rating; ?>/5)</span>
                            </div>
                            
                            <div class="seller">
                                <span>Seller: <?php echo htmlspecialchars($fruit['farm_name']); ?></span>
                            </div>
                            
                            <div class="price">
                                <span><?php echo format_price($fruit['price_per_kg']); ?> per kg</span>
                            </div>
                            
                            <div class="stock">
                                <?php if ($fruit['stock_quantity'] > 0): ?>
                                    <span class="in-stock">In Stock (<?php echo $fruit['stock_quantity']; ?> kg available)</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="actions">
                                <a href="fruit_details.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn view-btn">View Details</a>
                                
                                <?php if ($fruit['stock_quantity'] > 0): ?>
                                    <form method="post" action="add_to_cart.php" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $fruit['fruit_id']; ?>">
                                        <input type="hidden" name="product_type" value="fruit">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="btn add-cart-btn">Add to Cart</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    .fruits-section {
        padding: 60px 0;
    }
    
    /* Filters */
    .filters {
        margin-bottom: 40px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .search-bar {
        display: flex;
        margin-bottom: 20px;
    }
    
    .search-bar input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px 0 0 5px;
        font-size: 16px;
    }
    
    .search-bar button {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 0 20px;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
    }
    
    .filter-options {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
    }
    
    .filter-group label {
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .filter-group select,
    .filter-group input[type="text"] {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .filter-group.checkbox {
        flex-direction: row;
        align-items: center;
        gap: 5px;
    }
    
    .filter-btn {
        margin-left: auto;
        height: 40px;
        padding: 0 20px;
    }
    
    /* Fruits Grid */
    .fruits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
    }
    
    .fruit-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }
    
    .fruit-card:hover {
        transform: translateY(-10px);
    }
    
    .fruit-image {
        position: relative;
        height: 200px;
    }
    
    .fruit-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .organic-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #8BC34A;
        color: white;
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .fruit-info {
        padding: 20px;
    }
    
    .fruit-info h3 {
        margin-bottom: 10px;
        font-size: 1.4rem;
    }
    
    .fruit-info h3 a {
        color: #2E7D32;
    }
    
    .category {
        margin-bottom: 10px;
        font-size: 14px;
        color: #666;
    }
    
    .rating {
        margin-bottom: 10px;
        color: #FFC107;
    }
    
    .rating span {
        color: #666;
        margin-left: 5px;
    }
    
    .seller {
        margin-bottom: 10px;
        font-size: 14px;
        color: #666;
    }
    
    .price {
        margin-bottom: 10px;
        font-weight: bold;
        font-size: 18px;
        color: #2E7D32;
    }
    
    .stock {
        margin-bottom: 15px;
        font-size: 14px;
    }
    
    .in-stock {
        color: #4CAF50;
    }
    
    .out-of-stock {
        color: #F44336;
    }
    
    .actions {
        display: flex;
        gap: 10px;
    }
    
    .view-btn, .add-cart-btn {
        flex: 1;
        text-align: center;
        padding: 8px 0;
        font-size: 14px;
    }
    
    .add-cart-btn {
        background: #FF9800;
    }
    
    .add-cart-btn:hover {
        background: #F57C00;
    }
    
    .no-results {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 10px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .filter-options {
            flex-direction: column;
            align-items: stretch;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-btn {
            width: 100%;
            margin-left: 0;
            margin-top: 10px;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 