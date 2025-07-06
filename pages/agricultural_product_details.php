<?php
require_once '../includes/config.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    set_flash_message('error', 'Invalid product ID.');
    redirect(SITE_URL . '/pages/agricultural_products.php');
}

// Get product details
$sql = "SELECT p.*, s.farm_name, s.description as seller_description, s.location, 
        u.full_name as seller_name
        FROM agricultural_products p
        JOIN seller_profiles s ON p.seller_id = s.seller_id
        JOIN users u ON s.user_id = u.user_id
        WHERE p.product_id = $product_id";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    set_flash_message('error', 'Product not found.');
    redirect(SITE_URL . '/pages/agricultural_products.php');
}

$product = $result->fetch_assoc();

// Handle adding to cart
if (isset($_POST['add_to_cart']) && is_logged_in() && has_role('buyer')) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($quantity <= 0 || $quantity > $product['stock_quantity']) {
        $error = 'Invalid quantity.';
    } else {
        // In a real application, you would add to cart in the database or session
        // For now, we'll just show a success message
        set_flash_message('success', $quantity . ' kg of ' . $product['name'] . ' added to your cart!');
        redirect(SITE_URL . '/pages/agricultural_product_details.php?id=' . $product_id);
    }
}

// Handle buy now
if (isset($_POST['buy_now']) && is_logged_in() && has_role('buyer')) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    
    if ($quantity <= 0 || $quantity > $product['stock_quantity']) {
        $error = 'Invalid quantity.';
    } else {
        // In a real application, you would add to cart and redirect to checkout
        // For now, we'll redirect to a simulated checkout page
        $_SESSION['temp_checkout'] = [
            'product_id' => $product_id,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'price' => $product['price_per_kg'],
            'seller' => $product['seller_name'],
            'product_type' => 'agricultural'
        ];
        redirect(SITE_URL . '/pages/checkout.php');
    }
}

$page_title = $product['name'];
?>

<?php include('../includes/header.php'); ?>

<section class="product-details">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Home</a> 
            <i class="fas fa-angle-right separator"></i>
            <a href="<?php echo SITE_URL; ?>/pages/agricultural_products.php">Agricultural Products</a> 
            <i class="fas fa-angle-right separator"></i>
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        
        <div class="product-main-content">
            <div class="product-image">                <?php if (!empty($product['image'])): 
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
            
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <div class="category">
                        Category: <span><?php echo htmlspecialchars($product['category']); ?></span>
                    </div>
                </div>
                
                <div class="price">
                    <h2><?php echo format_price($product['price_per_kg']); ?> <span>per kg</span></h2>
                </div>
                
                <div class="availability">
                    <?php if ($product['is_available'] && $product['stock_quantity'] > 0): ?>
                        <span class="in-stock">In Stock (<?php echo $product['stock_quantity']; ?> kg available)</span>
                    <?php else: ?>
                        <span class="out-of-stock">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="description">
                    <h3>Description</h3>
                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                </div>
                
                <?php if ($product['is_available'] && $product['stock_quantity'] > 0 && is_logged_in() && has_role('buyer')): ?>
                    <form action="" method="POST" class="cart-form">
                        <div class="quantity-selector">
                            <label for="quantity">Quantity (kg):</label>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="decrementQuantity()">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="quantity-btn" onclick="incrementQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="add_to_cart" class="btn add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button type="submit" name="buy_now" class="btn buy-now-btn">
                                <i class="fas fa-bolt"></i> Buy Now
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                
                <div class="seller-info">
                    <h3>Seller Information</h3>
                    <p><strong>Farm/Orchard:</strong> <?php echo htmlspecialchars($product['farm_name']); ?></p>
                    <p><strong>Seller:</strong> <?php echo htmlspecialchars($product['seller_name']); ?></p>
                    <?php if (!empty($product['location'])): ?>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($product['location']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($product['seller_description'])): ?>
                        <p><strong>About:</strong> <?php echo htmlspecialchars($product['seller_description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Products Section -->
        <?php
        // Get related products (same category)
        $related_sql = "SELECT * FROM agricultural_products 
                      WHERE category = '{$product['category']}' 
                      AND product_id != $product_id
                      AND is_available = 1
                      LIMIT 4";
        $related_result = $conn->query($related_sql);
        $related_products = [];
        
        if ($related_result && $related_result->num_rows > 0) {
            while ($row = $related_result->fetch_assoc()) {
                $related_products[] = $row;
            }
        }
        
        if (!empty($related_products)):
        ?>
        <div class="related-products">
            <h2>Related Products</h2>
            
            <div class="products-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="product-card">                        <div class="product-image">
                            <?php if (!empty($related['image'])): 
                                // Check if the path already includes the base URL
                                if (strpos($related['image'], 'http') === 0) {
                                    $image_url = $related['image'];
                                } else {
                                    // Check if image exists in expected path
                                    if (file_exists(__DIR__ . '/../images/agricultural/' . $related['image'])) {
                                        $image_url = SITE_URL . '/images/agricultural/' . $related['image'];
                                    } elseif (file_exists(__DIR__ . '/../' . $related['image'])) {
                                        // Try direct path
                                        $image_url = SITE_URL . '/' . $related['image'];
                                    } else {
                                        // Try alternative 'img' directory path
                                        $image_url = SITE_URL . '/img/' . basename($related['image']);
                                    }
                                }
                            ?>
                                <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.src='<?php echo SITE_URL; ?>/img/placeholder.jpg'">
                            <?php else: ?>
                                <img src="<?php echo SITE_URL; ?>/img/placeholder.jpg" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                            
                            <div class="product-price">
                                <?php echo format_price($related['price_per_kg']); ?> <span>per kg</span>
                            </div>
                            
                            <a href="agricultural_product_details.php?id=<?php echo $related['product_id']; ?>" class="btn view-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
    .product-details {
        padding: 60px 0;
    }
    
    .breadcrumb {
        margin-bottom: 30px;
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
    
    /* Main Content */
    .product-main-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 40px;
        margin-bottom: 60px;
    }
    
    /* Product Image */
    .product-image {
        height: 400px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Product Info */
    .product-info h1 {
        margin-bottom: 15px;
        color: #333;
        font-size: 2.2rem;
    }
    
    .product-meta {
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
    
    .price {
        margin-bottom: 20px;
    }
    
    .price h2 {
        color: #4CAF50;
        font-size: 2rem;
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
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .quantity-btn {
        width: 40px;
        height: 40px;
        background: #f1f1f1;
        border: none;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
    }
    
    .quantity-btn:hover {
        background: #e0e0e0;
    }
    
    .quantity-controls input {
        width: 60px;
        height: 40px;
        text-align: center;
        border: none;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
        font-size: 16px;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
    }
    
    .add-to-cart-btn, .buy-now-btn {
        flex: 1;
        color: white;
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
    
    .add-to-cart-btn {
        background: #FF9800;
    }
    
    .add-to-cart-btn:hover {
        background: #F57C00;
    }
    
    .buy-now-btn {
        background: #4CAF50;
    }
    
    .buy-now-btn:hover {
        background: #388E3C;
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
    
    /* Related Products */
    .related-products {
        margin-top: 60px;
    }
    
    .related-products h2 {
        margin-bottom: 30px;
        color: #333;
    }
    
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }
    
    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
    }
    
    .product-card .product-image {
        height: 160px;
    }
    
    .product-card .product-details {
        padding: 15px;
    }
    
    .product-card h3 {
        margin: 0 0 10px;
        font-size: 1.1rem;
        color: #333;
    }
    
    .product-card .product-price {
        margin-bottom: 15px;
        font-weight: bold;
        color: #4CAF50;
    }
    
    .product-card .view-btn {
        display: block;
        width: 100%;
        padding: 8px 0;
        background: #f1f1f1;
        color: #333;
        text-align: center;
        border-radius: 4px;
        text-decoration: none;
        transition: background 0.3s;
    }
    
    .product-card .view-btn:hover {
        background: #e0e0e0;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .product-main-content {
            grid-template-columns: 1fr;
        }
        
        .product-image {
            height: 300px;
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
</script>

<?php include('../includes/footer.php'); ?> 