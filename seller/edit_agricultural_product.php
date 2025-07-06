<?php
$page_title = "Edit Agricultural Product";
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

// Check if product ID is specified
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'No product specified.');
    redirect(SITE_URL . '/seller/agricultural_products.php');
}

$product_id = (int)$_GET['id'];

// Check if the table exists
$table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
$table_exists = $table_check && $table_check->num_rows > 0;

if (!$table_exists) {
    set_flash_message('error', 'Agricultural products table does not exist.');
    redirect(SITE_URL . '/seller/dashboard.php');
}

// Get the product details
$product_sql = "SELECT * FROM agricultural_products WHERE product_id = $product_id AND seller_id = $seller_id";
$product_result = $conn->query($product_sql);

if (!$product_result || $product_result->num_rows === 0) {
    set_flash_message('error', 'Product not found or you do not have permission to edit it.');
    redirect(SITE_URL . '/seller/agricultural_products.php');
}

$product = $product_result->fetch_assoc();

// Get available agricultural product categories
$categories = [
    'Rice',
    'Wheat',
    'Corn',
    'Beans',
    'Lentils',
    'Seeds',
    'Nuts',
    'Vegetables',
    'Grains',
    'Spices',
    'Other'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = sanitize_input($_POST['name']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $price_per_kg = (float)$_POST['price_per_kg'];
    $stock_quantity = (float)$_POST['stock_quantity'];
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Product name is required.';
    }
    
    if (empty($category)) {
        $errors[] = 'Please select a category.';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required.';
    }
    
    if ($price_per_kg <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    
    if ($stock_quantity < 0) {
        $errors[] = 'Stock quantity cannot be negative.';
    }
    
    // Process image upload if a new image is provided
    $image_path = $product['image']; // Keep existing image by default
    
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed.';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'Image size should be less than 5MB.';
        } else {
            // Create images/agricultural directory if it doesn't exist
            $upload_dir = __DIR__ . '/../images/agricultural/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate a unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'agri_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // If successful, update the image path
                $image_path = 'images/agricultural/' . $filename;
                
                // Delete the old image if it exists
                if (!empty($product['image']) && file_exists(__DIR__ . '/../' . $product['image'])) {
                    unlink(__DIR__ . '/../' . $product['image']);
                }
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // Update database if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE agricultural_products SET 
                      name = ?, 
                      category = ?, 
                      description = ?, 
                      price_per_kg = ?, 
                      stock_quantity = ?, 
                      is_organic = ?, 
                      is_available = ?, 
                      image = ?,
                      updated_at = NOW()
                      WHERE product_id = ? AND seller_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sssddissis', $name, $category, $description, $price_per_kg, $stock_quantity, 
                          $is_organic, $is_available, $image_path, $product_id, $seller_id);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Agricultural product updated successfully!');
            redirect(SITE_URL . '/seller/agricultural_products.php');
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="edit-product-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <a href="<?php echo SITE_URL; ?>/seller/agricultural_products.php">Agricultural Products</a> &gt;
            <span>Edit Product</span>
        </div>
        
        <h2>Edit Agricultural Product</h2>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="product-form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="product-form">
                <div class="form-group">
                    <label for="name">Product Name*</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : htmlspecialchars($product['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category*</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($category) ? $category == $cat : $product['category'] == $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="price_per_kg">Price per kg (<?php echo CURRENCY_SYMBOL; ?>)*</label>
                        <input type="number" id="price_per_kg" name="price_per_kg" step="0.01" min="0.01" value="<?php echo isset($price_per_kg) ? htmlspecialchars($price_per_kg) : htmlspecialchars($product['price_per_kg']); ?>" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="stock_quantity">Stock Quantity (kg)*</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" step="0.01" min="0" value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : htmlspecialchars($product['stock_quantity']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_organic" name="is_organic" value="1" <?php echo (isset($is_organic) ? $is_organic : $product['is_organic']) ? 'checked' : ''; ?>>
                        <label for="is_organic">Organic</label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_available" name="is_available" value="1" <?php echo (isset($is_available) ? $is_available : $product['is_available']) ? 'checked' : ''; ?>>
                        <label for="is_available">Available for Sale</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <?php if (!empty($product['image']) && file_exists('../' . $product['image'])): ?>
                        <div class="current-image">
                            <img src="<?php echo SITE_URL . '/' . $product['image']; ?>" alt="Current product image">
                            <p>Current image</p>
                        </div>
                    <?php else: ?>
                        <div class="current-image">
                            <img src="<?php echo SITE_URL; ?>/images/agricultural/rice_placeholder.jpg" alt="Default image">
                            <p>No current image</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/jpg">
                    <p class="form-hint">Upload a new image to replace the current one. Leave empty to keep the current image. Max file size: 5MB. Supported formats: JPG, JPEG, PNG.</p>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo SITE_URL; ?>/seller/agricultural_products.php" class="btn cancel-btn">Cancel</a>
                    <button type="submit" class="btn submit-btn">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include('../includes/footer.php'); ?> 