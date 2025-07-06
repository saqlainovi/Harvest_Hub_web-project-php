<?php
$page_title = "Manage Fruits";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Handle fruit status toggle
if (isset($_GET['action']) && isset($_GET['id'])) {
    $fruit_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $update_sql = "UPDATE fruits SET is_available = 1 WHERE fruit_id = $fruit_id";
        $message = 'Fruit activated successfully.';
    } elseif ($action === 'deactivate') {
        $update_sql = "UPDATE fruits SET is_available = 0 WHERE fruit_id = $fruit_id";
        $message = 'Fruit deactivated successfully.';
    } elseif ($action === 'delete') {
        $update_sql = "DELETE FROM fruits WHERE fruit_id = $fruit_id";
        $message = 'Fruit deleted successfully.';
    }
    
    if (isset($update_sql) && $conn->query($update_sql)) {
        set_flash_message('success', $message);
    } else {
        set_flash_message('error', 'Failed to update fruit: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filtering and pagination parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$available_filter = isset($_GET['available']) ? $_GET['available'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query
$fruits_sql = "SELECT f.*, c.name as category_name, s.farm_name, u.full_name as seller_name 
               FROM fruits f 
               LEFT JOIN categories c ON f.category_id = c.category_id
               LEFT JOIN seller_profiles s ON f.seller_id = s.seller_id
               LEFT JOIN users u ON s.user_id = u.user_id
               WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM fruits f WHERE 1=1";

$params = [];
$count_params = [];

if (!empty($search)) {
    $search_term = "%$search%";
    $fruits_sql .= " AND (f.name LIKE ? OR f.description LIKE ?)";
    $count_sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if (!empty($category_filter)) {
    $fruits_sql .= " AND f.category_id = ?";
    $count_sql .= " AND category_id = ?";
    $params[] = $category_filter;
    $count_params[] = $category_filter;
}

if ($available_filter !== '') {
    $is_available = $available_filter === '1' ? 1 : 0;
    $fruits_sql .= " AND f.is_available = ?";
    $count_sql .= " AND is_available = ?";
    $params[] = $is_available;
    $count_params[] = $is_available;
}

// Add sorting and pagination
$fruits_sql .= " ORDER BY f.created_at DESC LIMIT $offset, $per_page";

// Prepare and execute the statements
$fruits_stmt = $conn->prepare($fruits_sql);
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $fruits_stmt->bind_param($types, ...$params);
}

if (!empty($count_params)) {
    $count_types = str_repeat('s', count($count_params));
    $count_stmt->bind_param($count_types, ...$count_params);
}

$fruits_stmt->execute();
$fruits_result = $fruits_stmt->get_result();
$fruits = [];

while ($row = $fruits_result->fetch_assoc()) {
    $fruits[] = $row;
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_fruits = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_fruits / $per_page);

// Get categories for filter
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

include('../includes/header.php');
?>

<section class="admin-fruits">
    <div class="container">
        <h2>Manage Fruits</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <!-- Filters & Search -->
        <div class="filters-section">
            <form action="" method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_filter == $category['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="available">Availability:</label>
                        <select name="available" id="available">
                            <option value="">All</option>
                            <option value="1" <?php echo ($available_filter === '1') ? 'selected' : ''; ?>>Available</option>
                            <option value="0" <?php echo ($available_filter === '0') ? 'selected' : ''; ?>>Not Available</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" placeholder="Search by name or description" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset</a>
                    <a href="add_fruit.php" class="btn btn-primary">Add New Fruit</a>
                </div>
            </form>
        </div>
        
        <!-- Fruits Table -->
        <?php if (empty($fruits)): ?>
            <div class="no-data">
                <p>No fruits found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="fruits-table-container">
                <table class="fruits-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fruits as $fruit): ?>
                            <tr>
                                <td><?php echo $fruit['fruit_id']; ?></td>
                                <td>
                                    <?php if (!empty($fruit['image'])): ?>
                                        <img src="<?php echo SITE_URL . '/' . $fruit['image']; ?>" alt="<?php echo htmlspecialchars($fruit['name']); ?>" class="fruit-thumbnail">
                                    <?php else: ?>
                                        <div class="no-image">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($fruit['name']); ?></td>
                                <td><?php echo htmlspecialchars($fruit['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo format_price($fruit['price_per_kg']); ?>/kg</td>
                                <td><?php echo $fruit['stock_quantity']; ?> kg</td>
                                <td><?php echo htmlspecialchars($fruit['seller_name'] ?? $fruit['farm_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $fruit['is_available'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $fruit['is_available'] ? 'Available' : 'Not Available'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="../pages/fruit_details.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm" target="_blank">View</a>
                                        <a href="edit_fruit.php?id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-secondary">Edit</a>
                                        
                                        <?php if ($fruit['is_available']): ?>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=deactivate&id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-warning" onclick="return confirm('Are you sure you want to mark this fruit as unavailable?')">Deactivate</a>
                                        <?php else: ?>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=activate&id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-success">Activate</a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=<?php echo $fruit['fruit_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this fruit? This action cannot be undone.')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page - 1); ?>&category=<?php echo $category_filter; ?>&available=<?php echo $available_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>&available=<?php echo $available_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page + 1); ?>&category=<?php echo $category_filter; ?>&available=<?php echo $available_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
    .admin-fruits {
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
    
    /* Filters */
    .filters-section {
        background-color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .filters-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    
    .search-group {
        flex: 2;
    }
    
    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .filter-group select, 
    .filter-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
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
    
    .btn-primary {
        background-color: #2196F3;
        color: white;
    }
    
    /* Fruits Table */
    .fruits-table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    .fruits-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .fruits-table th, 
    .fruits-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .fruits-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .fruits-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .fruit-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
    }
    
    .no-image {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f1f1f1;
        color: #999;
        font-size: 0.8rem;
        border-radius: 4px;
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
        flex-wrap: wrap;
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
    
    .btn-secondary {
        background-color: #2196F3;
        color: white;
    }
    
    .btn-warning {
        background-color: #FFF3CD;
        color: #856404;
    }
    
    .btn-danger {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    .btn-success {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    /* No Data */
    .no-data {
        text-align: center;
        padding: 30px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .no-data p {
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    
    .page-link {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 5px;
        border-radius: 3px;
        background-color: white;
        color: #333;
        text-decoration: none;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .page-link.active {
        background-color: #4CAF50;
        color: white;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-actions {
            flex-direction: column;
            align-items: stretch;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit the form when filters change
    const filterSelects = document.querySelectorAll('.filters-form select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.querySelector('.filters-form').submit();
        });
    });
});
</script>

<?php include('../includes/footer.php'); ?> 