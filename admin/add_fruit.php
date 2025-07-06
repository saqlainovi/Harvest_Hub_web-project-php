<?php
$page_title = "Add New Fruit";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get all sellers
$sellers_sql = "SELECT sp.seller_id, sp.farm_name, u.full_name 
                FROM seller_profiles sp 
                JOIN users u ON sp.user_id = u.user_id
                WHERE sp.is_verified = 1
                ORDER BY u.full_name";
$sellers_result = $conn->query($sellers_sql);
$sellers = [];

if ($sellers_result && $sellers_result->num_rows > 0) {
    while ($row = $sellers_result->fetch_assoc()) {
        $sellers[] = $row;
    }
}

// Get all categories
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $name = sanitize_input($_POST['name']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : 0;
    $description = sanitize_input($_POST['description']);
    $price_per_kg = floatval($_POST['price_per_kg']);
    $stock_quantity = floatval($_POST['stock_quantity']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    $errors = [];
    
    // Validate required fields
    if (empty($name)) {
        $errors[] = 'Fruit name is required.';
    }
    
    if ($category_id <= 0) {
        $errors[] = 'Please select a valid category.';
    }
    
    if ($seller_id <= 0) {
        $errors[] = 'Please select a valid seller.';
    }
    
    if ($price_per_kg <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    
    if ($stock_quantity < 0) {
        $errors[] = 'Stock quantity cannot be negative.';
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/fruits/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = 'Only JPG, JPEG, PNG and GIF files are allowed.';
        } else {
            $filename = uniqid('fruit_') . '.' . $file_extension;
            $upload_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                $image_path = 'uploads/fruits/' . $filename;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $insert_sql = "INSERT INTO fruits (name, category_id, seller_id, description, price_per_kg, stock_quantity, is_available, image, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('siisdisi', $name, $category_id, $seller_id, $description, $price_per_kg, $stock_quantity, $is_available, $image_path);
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Fruit added successfully!');
            redirect('fruits.php');
        } else {
            set_flash_message('error', 'Failed to add fruit: ' . $conn->error);
        }
    } else {
        // Display errors
        foreach ($errors as $error) {
            set_flash_message('error', $error);
        }
    }
}

include('../includes/header.php');
?>

<section class="add-fruit-section">
    <div class="container">
        <h2>Add New Fruit</h2>
        
        <div class="page-navigation">
            <a href="fruits.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Fruits List</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="fruit-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Fruit Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="seller_id">Seller *</label>
                        <select id="seller_id" name="seller_id" required>
                            <option value="">Select Seller</option>
                            <?php foreach ($sellers as $seller): ?>
                                <option value="<?php echo $seller['seller_id']; ?>" <?php echo (isset($_POST['seller_id']) && $_POST['seller_id'] == $seller['seller_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($seller['full_name'] . ' (' . $seller['farm_name'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_kg">Price per Kg (₹) *</label>
                        <input type="number" id="price_per_kg" name="price_per_kg" min="0.01" step="0.01" required value="<?php echo isset($_POST['price_per_kg']) ? htmlspecialchars($_POST['price_per_kg']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity (Kg) *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="0.1" required value="<?php echo isset($_POST['stock_quantity']) ? htmlspecialchars($_POST['stock_quantity']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Fruit Image</label>
                        <input type="file" id="image" name="image" accept="image/*">
                        <div class="form-help">Recommended size: 800x600 pixels. Max size: 2MB.</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_available" name="is_available" <?php echo (!isset($_POST['is_available']) || isset($_POST['is_available'])) ? 'checked' : ''; ?>>
                    <label for="is_available">Available for Purchase</label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Fruit</button>
                    <a href="fruits.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .add-fruit-section {
        padding: 60px 0;
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
    
    .form-container {
        background-color: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .fruit-form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .form-group {
        margin-bottom: 5px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .form-group textarea {
        resize: vertical;
    }
    
    .form-help {
        font-size: 0.8rem;
        color: #666;
        margin-top: 5px;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-group input {
        margin: 0;
    }
    
    .checkbox-group label {
        margin: 0;
    }
    
    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        font-size: 0.9rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 