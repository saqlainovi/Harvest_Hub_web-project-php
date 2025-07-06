<?php
require_once '../includes/config.php';

// Get filters from URL
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Check if the agricultural_products table exists
$table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
$table_exists = $table_check && $table_check->num_rows > 0;

$products = [];

if ($table_exists) {
    // Base SQL query
    $sql = "SELECT p.*, s.farm_name as seller_name 
            FROM agricultural_products p
            JOIN seller_profiles s ON p.seller_id = s.seller_id
            WHERE p.is_available = 1";

    // Add filters if set
    if (!empty($category_filter)) {
        $sql .= " AND p.category = '$category_filter'";
    }

    if (!empty($search_query)) {
        $sql .= " AND (p.name LIKE '%$search_query%' OR p.description LIKE '%$search_query%')";
    }

    // Order by name
    $sql .= " ORDER BY p.name";

    // Get the products
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    // Get all categories for the filter
    $categories_sql = "SELECT DISTINCT category FROM agricultural_products ORDER BY category";
    $categories_result = $conn->query($categories_sql);
    $categories = [];

    if ($categories_result && $categories_result->num_rows > 0) {
        while ($row = $categories_result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    }
} else {
    // Table doesn't exist - no products available
    $categories = [];
}

$page_title = "Agricultural Products";
?>

<?php include('../includes/header.php'); ?>

<?php if (!$table_exists): ?>
    <div class="setup-required">
        <h2>Database Setup Required</h2>
        <p>The agricultural products database tables need to be set up before you can view products.</p>
        <p>Please run the setup script first:</p>
        <a href="<?php echo SITE_URL; ?>/simple_setup.php" class="btn setup-btn">Run Setup Script</a>
    </div>
    <style>
        .setup-required {
            background-color: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin: 30px 0;
            text-align: center;
        }
        .setup-btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .setup-btn:hover {
            background-color: #45a049;
        }
    </style>
<?php else: ?>
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Home</a> 
            <i class="fas fa-angle-right separator"></i>
            <span>Agricultural Products</span>
        </div>
        <h1 class="page-title">Agricultural Products</h1>
        
        <div class="filter-section">
            <form action="" method="GET" class="search-form">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
                
                <div class="filter-box">
                    <select name="category" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category; ?>" <?php echo ($category == $category_filter) ? 'selected' : ''; ?>>
                                <?php echo $category; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No agricultural products found. Please try a different search or browse all products.</p>
                <?php if (!empty($category_filter) || !empty($search_query)): ?>
                    <a href="agricultural_products.php" class="btn">View All Products</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">                        <div class="product-image">
                            <?php if (!empty($product['image'])): 
                                // Check if the path already includes the base URL
                                if (strpos($product['image'], 'http') === 0) {
                                    $image_url = $product['image'];
                                } else {
                                    // Check if image exists in expected path
                                    if (file_exists(__DIR__ . '/../images/agricultural/' . $product['image'])) {
                                        $image_url = SITE_URL . '/images/agricultural/' . $product['image'];
                                    } elseif (file_exists(__DIR__ . '/../' . $product['image'])) {
                                        // Try direct path
                                        $image_url = SITE_URL . '/' . $product['image'];
                                    } else {
                                        // Try alternative 'img' directory path
                                        $image_url = SITE_URL . '/img/' . basename($product['image']);
                                    }
                                }
                            ?>
                                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo SITE_URL; ?>/img/placeholder.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/img/placeholder.jpg" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-details">
                            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                            
                            <div class="product-meta">
                                <span class="category"><?php echo htmlspecialchars($product['category']); ?></span>
                                <span class="seller">By: <?php echo htmlspecialchars($product['seller_name']); ?></span>
                            </div>
                            
                            <div class="product-description">
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                            </div>
                            
                            <div class="product-price">
                                <span class="price"><?php echo format_price($product['price_per_kg']); ?></span>
                                <span class="unit">per kg</span>
                            </div>
                            
                            <div class="product-actions">
                                <a href="agricultural_product_details.php?id=<?php echo $product['product_id']; ?>" class="btn view-btn">View Details</a>
                                <?php if (is_logged_in() && has_role('buyer')): ?>
                                    <form action="add_to_cart.php" method="post" class="cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="product_type" value="agricultural">
                                        <div class="quantity-selector">
                                            <label>Qty (kg):</label>
                                            <div class="quantity-controls">
                                                <button type="button" class="quantity-btn" onclick="decrementQuantity(this)">-</button>
                                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="quantity-input">
                                                <button type="button" class="quantity-btn" onclick="incrementQuantity(this)">+</button>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn add-to-cart-btn"><i class="fas fa-cart-plus"></i> Add to Cart</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<style>
    .breadcrumb {
        margin-bottom: 20px;
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .breadcrumb a {
        color: #4CAF50;
        text-decoration: none;
        transition: color 0.2s ease;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .breadcrumb a:hover {
        color: #2E7D32;
        text-decoration: underline;
    }
    
    .breadcrumb span {
        color: #333;
        font-weight: 600;
    }
    
    .breadcrumb .separator {
        font-size: 12px;
        color: #aaa;
    }
    
    .breadcrumb .fa-home {
        margin-right: 4px;
    }

    .page-title {
        margin: 40px 0 30px;
        color: #333;
        text-align: center;
    }
    
    .filter-section {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
    }
    
    .search-form {
        display: flex;
        width: 100%;
        gap: 20px;
    }
    
    .search-box {
        flex: 1;
        display: flex;
    }
    
    .search-box input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px 0 0 4px;
        font-size: 16px;
    }
    
    .search-box button {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 0 20px;
        border-radius: 0 4px 4px 0;
        cursor: pointer;
    }
    
    .filter-box select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
        background-color: white;
        min-width: 200px;
    }
    
    .no-products {
        padding: 40px;
        text-align: center;
        background: #f9f9f9;
        border-radius: 8px;
        margin: 30px 0;
    }
    
    .no-products p {
        margin-bottom: 20px;
        color: #666;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 50px;
    }
    
    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    
    .product-image {
        height: 200px;
        overflow: hidden;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.05);
    }
    
    .product-details {
        padding: 20px;
    }
    
    .product-details h2 {
        margin: 0 0 10px;
        font-size: 1.3rem;
        color: #333;
    }
    
    .product-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 0.9rem;
    }
    
    .category {
        color: #4CAF50;
        font-weight: 500;
    }
    
    .seller {
        color: #666;
    }
    
    .product-description {
        margin-bottom: 15px;
        color: #555;
        font-size: 0.9rem;
        line-height: 1.5;
        max-height: 60px;
        overflow: hidden;
    }
    
    .product-price {
        margin-bottom: 15px;
    }
    
    .price {
        font-size: 1.3rem;
        font-weight: bold;
        color: #FF9800;
    }
    
    .unit {
        font-size: 0.9rem;
        color: #888;
        margin-left: 5px;
    }
    
    .product-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        text-align: center;
        text-decoration: none;
        transition: background 0.3s;
    }
    
    .view-btn {
        background: #f1f1f1;
        color: #333;
    }
    
    .view-btn:hover {
        background: #e0e0e0;
    }
    
    .cart-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.9rem;
    }
    
    .quantity-controls {
        display: flex;
        align-items: center;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .quantity-btn {
        width: 28px;
        height: 28px;
        background: #f1f1f1;
        border: none;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .quantity-btn:hover {
        background: #e0e0e0;
    }
    
    .quantity-input {
        width: 50px;
        height: 28px;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        font-size: 14px;
    }
    
    .add-to-cart-btn {
        background: #FF9800;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    
    .add-to-cart-btn:hover {
        background: #F57C00;
    }
    
    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
            gap: 10px;
        }
        
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        }
    }
</style>

<script>
    // Quantity selector functions
    function incrementQuantity(button) {
        const input = button.previousElementSibling;
        const max = parseInt(input.getAttribute('max')) || 100;
        let value = parseInt(input.value);
        
        if (value < max) {
            input.value = value + 1;
        }
    }
    
    function decrementQuantity(button) {
        const input = button.nextElementSibling;
        let value = parseInt(input.value);
        
        if (value > 1) {
            input.value = value - 1;
        }
    }
</script>

<?php include('../includes/footer.php'); ?> 