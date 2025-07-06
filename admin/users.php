<?php
$page_title = "Manage Users";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Handle user status toggle (activate/deactivate)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'activate') {
        $update_sql = "UPDATE users SET status = 'active' WHERE user_id = $user_id";
        $message = 'User activated successfully.';
    } elseif ($action === 'deactivate') {
        $update_sql = "UPDATE users SET status = 'inactive' WHERE user_id = $user_id";
        $message = 'User deactivated successfully.';
    } elseif ($action === 'delete') {
        $update_sql = "DELETE FROM users WHERE user_id = $user_id AND role != 'admin'";
        $message = 'User deleted successfully.';
    }
    
    if (isset($update_sql) && $conn->query($update_sql)) {
        set_flash_message('success', $message);
    } else {
        set_flash_message('error', 'Failed to update user status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filtering and pagination parameters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query
$users_sql = "SELECT * FROM users WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";

$params = [];

if (!empty($search)) {
    $search_term = "%$search%";
    $users_sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $count_sql .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($role_filter)) {
    $users_sql .= " AND role = ?";
    $count_sql .= " AND role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $users_sql .= " AND status = ?";
    $count_sql .= " AND status = ?";
    $params[] = $status_filter;
}

// Add sorting and pagination
$users_sql .= " ORDER BY created_at DESC LIMIT $offset, $per_page";

// Prepare and execute the statements
$users_stmt = $conn->prepare($users_sql);
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $users_stmt->bind_param($types, ...$params);
    $count_stmt->bind_param($types, ...$params);
}

$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = [];

while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_users = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

include('../includes/header.php');
?>

<section class="admin-users">
    <div class="container">
        <h2>Manage Users</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <!-- Filters & Search -->
        <div class="filters-section">
            <form action="" method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role">
                            <option value="">All Roles</option>
                            <option value="buyer" <?php echo ($role_filter === 'buyer') ? 'selected' : ''; ?>>Buyer</option>
                            <option value="seller" <?php echo ($role_filter === 'seller') ? 'selected' : ''; ?>>Seller</option>
                            <option value="admin" <?php echo ($role_filter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <?php if (empty($users)): ?>
            <div class="no-data">
                <p>No users found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="user_details.php?id=<?php echo $user['user_id']; ?>" class="btn-sm">Details</a>
                                        
                                        <?php if ($user['role'] !== 'admin' || $user['user_id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn-sm btn-warning" onclick="return confirm('Are you sure you want to deactivate this user?')">Deactivate</a>
                                            <?php else: ?>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=activate&id=<?php echo $user['user_id']; ?>" class="btn-sm btn-success">Activate</a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&id=<?php echo $user['user_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
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
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page - 1); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page + 1); ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
    .admin-users {
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
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        background-color: #4CAF50;
        color: white;
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    /* Users Table */
    .users-table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .users-table th, 
    .users-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .users-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .users-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .role-badge, 
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .role-admin {
        background-color: #E1F5FE;
        color: #0288D1;
    }
    
    .role-seller {
        background-color: #FFF8E1;
        color: #FFA000;
    }
    
    .role-buyer {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-active {
        background-color: #E8F5E9;
        color: #388E3C;
    }
    
    .status-inactive {
        background-color: #FFEBEE;
        color: #D32F2F;
    }
    
    .status-pending {
        background-color: #FFF8E1;
        color: #FFA000;
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
        background-color: #eee;
        color: #333;
        border: none;
        cursor: pointer;
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
        
        .action-buttons {
            flex-direction: column;
        }
        
        .users-table th, 
        .users-table td {
            padding: 10px;
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