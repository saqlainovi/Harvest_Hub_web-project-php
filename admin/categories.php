<?php
$page_title = "Manage Categories";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Handle category form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category']) || isset($_POST['edit_category'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate input
        $error = false;
        if (empty($name)) {
            set_flash_message('error', 'Category name is required.');
            $error = true;
        }
        
        if (!$error) {
            if (isset($_POST['add_category'])) {
                // Check if category already exists
                $check_sql = "SELECT * FROM categories WHERE name = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("s", $name);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    set_flash_message('error', 'A category with this name already exists.');
                } else {
                    // Add new category
                    $insert_sql = "INSERT INTO categories (name, description, is_active, created_at) VALUES (?, ?, ?, NOW())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bind_param("ssi", $name, $description, $is_active);
                    
                    if ($insert_stmt->execute()) {
                        set_flash_message('success', 'Category added successfully!');
                    } else {
                        set_flash_message('error', 'Failed to add category: ' . $conn->error);
                    }
                }
            } elseif (isset($_POST['edit_category']) && isset($_POST['category_id'])) {
                $category_id = intval($_POST['category_id']);
                
                // Check if another category with the same name exists
                $check_sql = "SELECT * FROM categories WHERE name = ? AND category_id != ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("si", $name, $category_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    set_flash_message('error', 'Another category with this name already exists.');
                } else {
                    // Update category
                    $update_sql = "UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE category_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("ssii", $name, $description, $is_active, $category_id);
                    
                    if ($update_stmt->execute()) {
                        set_flash_message('success', 'Category updated successfully!');
                    } else {
                        set_flash_message('error', 'Failed to update category: ' . $conn->error);
                    }
                }
            }
        }
    } elseif (isset($_POST['delete_category']) && isset($_POST['category_id'])) {
        $category_id = intval($_POST['category_id']);
        
        // Check if category is being used by any products
        $check_sql = "SELECT COUNT(*) as count FROM fruits WHERE category_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $product_count = $check_result->fetch_assoc()['count'];
        
        if ($product_count > 0) {
            set_flash_message('error', "Cannot delete this category because it's currently being used by $product_count products.");
        } else {
            // Delete category
            $delete_sql = "DELETE FROM categories WHERE category_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                set_flash_message('success', 'Category deleted successfully!');
            } else {
                set_flash_message('error', 'Failed to delete category: ' . $conn->error);
            }
        }
    }
}

// Get all categories
$categories_sql = "SELECT c.*, COUNT(f.fruit_id) as product_count 
                   FROM categories c 
                   LEFT JOIN fruits f ON c.category_id = f.category_id 
                   GROUP BY c.category_id 
                   ORDER BY c.name";
$categories_result = $conn->query($categories_sql);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle edit mode
$edit_mode = false;
$category_to_edit = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    
    foreach ($categories as $category) {
        if ($category['category_id'] === $category_id) {
            $edit_mode = true;
            $category_to_edit = $category;
            break;
        }
    }
}

include('../includes/header.php');
?>

<section class="admin-categories">
    <div class="container">
        <h2>Manage Categories</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="categories-layout">
            <!-- Category Form -->
            <div class="category-form-container">
                <div class="card">
                    <h3><?php echo $edit_mode ? 'Edit Category' : 'Add New Category'; ?></h3>
                    
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="category-form">
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="category_id" value="<?php echo $category_to_edit['category_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo $edit_mode ? htmlspecialchars($category_to_edit['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"><?php echo $edit_mode ? htmlspecialchars($category_to_edit['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" <?php echo (!$edit_mode || $category_to_edit['is_active']) ? 'checked' : ''; ?>>
                            <label for="is_active">Active</label>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_mode): ?>
                                <button type="submit" name="edit_category" class="btn">Update Category</button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_category" class="btn">Add Category</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Categories List -->
            <div class="categories-list-container">
                <div class="card">
                    <h3>Categories List</h3>
                    
                    <?php if (empty($categories)): ?>
                        <div class="no-data">
                            <p>No categories found. Add your first category!</p>
                        </div>
                    <?php else: ?>
                        <div class="categories-table-container">
                            <table class="categories-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td>
                                                <?php if (!empty($category['description'])): ?>
                                                    <div class="description-cell"><?php echo htmlspecialchars($category['description']); ?></div>
                                                <?php else: ?>
                                                    <em>No description</em>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $category['product_count']; ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                                    <?php echo $category['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=edit&id=<?php echo $category['category_id']; ?>" class="btn-sm btn-secondary">Edit</a>
                                                    
                                                    <?php if ($category['product_count'] == 0): ?>
                                                        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return confirm('Are you sure you want to delete this category?');" style="display: inline;">
                                                            <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                            <button type="submit" name="delete_category" class="btn-sm btn-danger">Delete</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn-sm btn-danger" disabled title="Cannot delete category that has products assigned to it">Delete</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .admin-categories {
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
    
    .categories-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
    }
    
    .card {
        background-color: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .card h3 {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #333;
    }
    
    /* Category Form */
    .category-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .form-group input[type="text"],
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
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
        background-color: #4CAF50;
        color: white;
        font-size: 0.9rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    /* Categories Table */
    .categories-table-container {
        overflow-x: auto;
    }
    
    .categories-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .categories-table th, 
    .categories-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .categories-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .categories-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .description-cell {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-active {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-inactive {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    .action-buttons {
        display: flex;
        gap: 5px;
    }
    
    .btn-sm {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-block;
        background-color: #eee;
        color: #333;
        border: none;
        cursor: pointer;
    }
    
    .btn-sm:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .btn-secondary {
        background-color: #2196F3;
        color: white;
    }
    
    .btn-danger {
        background-color: #F44336;
        color: white;
    }
    
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    @media (max-width: 992px) {
        .categories-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 