<?php
$page_title = "Add Agricultural Product";
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

// Check if agricultural_products table exists and has correct structure
$table_check_sql = "SHOW TABLES LIKE 'agricultural_products'";
$table_exists = $conn->query($table_check_sql)->num_rows > 0;

// If table doesn't exist, create it
if (!$table_exists) {
    $create_table_sql = "CREATE TABLE agricultural_products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        description TEXT,
        price_per_kg DECIMAL(10, 2) NOT NULL,
        stock_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
        is_organic TINYINT(1) NOT NULL DEFAULT 0,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES seller_profiles(seller_id) ON DELETE CASCADE
    )";
    
    if (!$conn->query($create_table_sql)) {
        // If table creation fails, try a simpler version without the problematic columns
        $create_simple_table_sql = "CREATE TABLE agricultural_products (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            seller_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            description TEXT,
            price_per_kg DECIMAL(10, 2) NOT NULL,
            stock_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
            image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (seller_id) REFERENCES seller_profiles(seller_id) ON DELETE CASCADE
        )";
        
        if (!$conn->query($create_simple_table_sql)) {
            set_flash_message('error', 'Failed to create agricultural products table: ' . $conn->error);
            redirect(SITE_URL . '/seller/dashboard.php');
        }
    }
} else {
    // Table exists, check if it has the required columns
    $column_check = $conn->query("SHOW COLUMNS FROM agricultural_products LIKE 'is_organic'");
    if ($column_check->num_rows == 0) {
        // Add missing columns
        $add_column_sql = "ALTER TABLE agricultural_products ADD is_organic TINYINT(1) NOT NULL DEFAULT 0";
        $conn->query($add_column_sql);
    }
    
    $column_check = $conn->query("SHOW COLUMNS FROM agricultural_products LIKE 'is_available'");
    if ($column_check->num_rows == 0) {
        // Add missing columns
        $add_column_sql = "ALTER TABLE agricultural_products ADD is_available TINYINT(1) NOT NULL DEFAULT 1";
        $conn->query($add_column_sql);
    }
}

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
    
    // Process image upload if no errors
    $image_path = '';
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
                $image_path = 'images/agricultural/' . $filename; // Store relative path
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // Insert into database if no errors
    if (empty($errors)) {
        // Let's check if the table exists and has the required columns
        $table_check = $conn->query("DESCRIBE agricultural_products");
        $has_is_organic = false;
        $has_is_available = false;
        
        if ($table_check) {
            while ($column = $table_check->fetch_assoc()) {
                if ($column['Field'] === 'is_organic') {
                    $has_is_organic = true;
                }
                if ($column['Field'] === 'is_available') {
                    $has_is_available = true;
                }
            }
        }
        
        // Build the query based on available columns
        if ($has_is_organic && $has_is_available) {
            $insert_sql = "INSERT INTO agricultural_products (seller_id, name, category, description, price_per_kg, stock_quantity, is_organic, is_available, image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('isssddiss', $seller_id, $name, $category, $description, $price_per_kg, $stock_quantity, $is_organic, $is_available, $image_path);
        } else {
            // Fallback query without is_organic and is_available
            $insert_sql = "INSERT INTO agricultural_products (seller_id, name, category, description, price_per_kg, stock_quantity, image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('isssdds', $seller_id, $name, $category, $description, $price_per_kg, $stock_quantity, $image_path);
        }
        
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            set_flash_message('success', 'Agricultural product added successfully!');
            redirect(SITE_URL . '/seller/dashboard.php');
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="add-product-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Add Agricultural Product</span>
        </div>
        
        <h2>Add Agricultural Product</h2>
        
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
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category*</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo (isset($category) && $category == $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" rows="5" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="price_per_kg">Price per kg (<?php echo CURRENCY_SYMBOL; ?>)*</label>
                        <input type="number" id="price_per_kg" name="price_per_kg" step="0.01" min="0.01" value="<?php echo isset($price_per_kg) ? htmlspecialchars($price_per_kg) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="stock_quantity">Stock Quantity (kg)*</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" step="0.01" min="0" value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_organic" name="is_organic" value="1" <?php echo (isset($is_organic) && $is_organic) ? 'checked' : ''; ?>>
                        <label for="is_organic">Organic</label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_available" name="is_available" value="1" <?php echo (!isset($is_available) || $is_available) ? 'checked' : ''; ?>>
                        <label for="is_available">Available for Sale</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/jpg">
                    <p class="form-hint">Upload a clear image of your product. Max file size: 5MB. Supported formats: JPG, JPEG, PNG.</p>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo SITE_URL; ?>/seller/dashboard.php" class="btn cancel-btn">Cancel</a>
                    <button type="submit" class="btn submit-btn">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include('../includes/footer.php'); ?> 