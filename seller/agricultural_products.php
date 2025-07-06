<?php
$page_title = "Manage Agricultural Products";
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

// Check if the table exists
$table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
$table_exists = $table_check && $table_check->num_rows > 0;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $product_id = (int)$_GET['id'];
    
    // Verify product belongs to seller
    $check_sql = "SELECT product_id FROM agricultural_products WHERE product_id = $product_id AND seller_id = $seller_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result && $check_result->num_rows > 0) {
        if ($action === 'delete') {
            // Delete the product
            $delete_sql = "DELETE FROM agricultural_products WHERE product_id = $product_id";
            if ($conn->query($delete_sql)) {
                set_flash_message('success', 'Product deleted successfully.');
            } else {
                set_flash_message('error', 'Failed to delete product: ' . $conn->error);
            }
        } elseif ($action === 'toggle') {
            // Toggle availability
            $toggle_sql = "UPDATE agricultural_products SET is_available = NOT is_available WHERE product_id = $product_id";
            if ($conn->query($toggle_sql)) {
                set_flash_message('success', 'Product availability updated.');
            } else {
                set_flash_message('error', 'Failed to update product: ' . $conn->error);
            }
        }
    } else {
        set_flash_message('error', 'You are not authorized to modify this product.');
    }
    
    // Redirect to remove the action from URL
    redirect(SITE_URL . '/seller/agricultural_products.php');
}

// Get agricultural products for this seller
$products = [];
if ($table_exists) {
    $products_sql = "SELECT * FROM agricultural_products WHERE seller_id = $seller_id ORDER BY created_at DESC";
    $products_result = $conn->query($products_sql);
    
    if ($products_result && $products_result->num_rows > 0) {
        while ($row = $products_result->fetch_assoc()) {
            $products[] = $row;
        }
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="products-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Manage Agricultural Products</span>
        </div>
        
        <div class="section-header">
            <h2>Manage Agricultural Products</h2>
            <a href="<?php echo SITE_URL; ?>/seller/add_agricultural_product.php" class="btn add-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>
        
        <?php if (!$table_exists): ?>
            <div class="error-message">
                <p>The agricultural products table does not exist in the database. Please contact the administrator.</p>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="no-data">
                <p>You haven't added any agricultural products yet.</p>
                <a href="<?php echo SITE_URL; ?>/seller/add_agricultural_product.php" class="btn">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Organic</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
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
                                <td class="actions">
                                    <a href="edit_agricultural_product.php?id=<?php echo $product['product_id']; ?>" class="btn-sm edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="agricultural_products.php?action=toggle&id=<?php echo $product['product_id']; ?>" class="btn-sm toggle-btn">
                                        <?php if ($product['is_available']): ?>
                                            <i class="fas fa-eye-slash"></i> Hide
                                        <?php else: ?>
                                            <i class="fas fa-eye"></i> Show
                                        <?php endif; ?>
                                    </a>
                                    <a href="agricultural_products.php?action=delete&id=<?php echo $product['product_id']; ?>" class="btn-sm delete-btn" onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include('../includes/footer.php'); ?> 