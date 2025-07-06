<?php
$page_title = "Add New Fruit";
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

// Get categories for dropdown
$categories_sql = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = sanitize_input($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = sanitize_input($_POST['description']);
    $price_per_kg = (float)$_POST['price_per_kg'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Fruit name is required.';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a valid category.';
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
            // Create img directory if it doesn't exist
            $upload_dir = __DIR__ . '/../img/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate a unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'fruit_' . time() . '_' . mt_rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = 'img/' . $filename; // Store relative path
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // Insert into database if no errors
    if (empty($errors)) {
        $insert_sql = "INSERT INTO fruits (seller_id, category_id, name, description, price_per_kg, stock_quantity, is_organic, is_available, image, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('iissdiiss', $seller_id, $category_id, $name, $description, $price_per_kg, $stock_quantity, $is_organic, $is_available, $image_path);
        
        if ($stmt->execute()) {
            $fruit_id = $conn->insert_id;
            set_flash_message('success', 'Fruit added successfully!');
            redirect(SITE_URL . '/seller/dashboard.php');
        } else {
            $errors[] = 'Database error: ' . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="add-fruit-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Add New Fruit</span>
        </div>
        
        <h2>Add New Fruit</h2>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="fruit-form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="fruit-form">
                <div class="form-group">
                    <label for="name">Fruit Name*</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category*</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($category_id) && $category_id == $category['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
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
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo isset($stock_quantity) ? htmlspecialchars($stock_quantity) : ''; ?>" required>
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
                    <label for="image">Fruit Image</label>
                    <input type="file" id="image" name="image" accept="image/jpeg, image/png, image/jpg">
                    <p class="form-hint">Upload a clear image of your fruit. Max file size: 5MB. Supported formats: JPG, JPEG, PNG.</p>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo SITE_URL; ?>/seller/dashboard.php" class="btn cancel-btn">Cancel</a>
                    <button type="submit" class="btn submit-btn">Add Fruit</button>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .add-fruit-section {
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
    
    .error-message {
        background-color: #ffebee;
        color: #c62828;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .error-message ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .fruit-form-container {
        background-color: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .fruit-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
    }
    
    .form-group.half {
        width: 50%;
    }
    
    label {
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    
    input[type="text"],
    input[type="number"],
    select,
    textarea {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    textarea {
        resize: vertical;
    }
    
    .checkbox-group {
        flex-direction: row;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-group input {
        width: auto;
    }
    
    .form-hint {
        margin-top: 5px;
        font-size: 14px;
        color: #666;
    }
    
    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
    
    .cancel-btn {
        background-color: #f5f5f5;
        color: #333;
    }
    
    .submit-btn {
        background-color: #4CAF50;
        color: white;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group.half {
            width: 100%;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 