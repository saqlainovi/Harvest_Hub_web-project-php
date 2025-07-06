<?php
$page_title = "Verify Sellers";
require_once '../includes/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_flash_message('error', 'You must be logged in as an admin to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Handle seller verification
if (isset($_GET['action']) && isset($_GET['id'])) {
    $seller_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'verify') {
        $update_sql = "UPDATE seller_profiles SET is_verified = 1, verified_at = NOW() WHERE seller_id = $seller_id";
        $message = 'Seller verified successfully.';
    } elseif ($action === 'reject') {
        $update_sql = "UPDATE seller_profiles SET is_verified = 0, verified_at = NULL WHERE seller_id = $seller_id";
        $message = 'Seller verification rejected.';
    }
    
    if (isset($update_sql) && $conn->query($update_sql)) {
        set_flash_message('success', $message);
    } else {
        set_flash_message('error', 'Failed to update seller status: ' . $conn->error);
    }
    redirect($_SERVER['PHP_SELF']);
}

// Get filtering and pagination parameters
$verification_filter = isset($_GET['verification']) ? $_GET['verification'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query
$sellers_sql = "SELECT sp.*, u.full_name, u.email, u.created_at as registration_date
               FROM seller_profiles sp
               JOIN users u ON sp.user_id = u.user_id
               WHERE 1=1";
$count_sql = "SELECT COUNT(*) as total FROM seller_profiles sp JOIN users u ON sp.user_id = u.user_id WHERE 1=1";

$params = [];
$count_params = [];

if (!empty($search)) {
    $search_term = "%$search%";
    $sellers_sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR sp.farm_name LIKE ?)";
    $count_sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR sp.farm_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
    $count_params[] = $search_term;
}

if ($verification_filter !== '') {
    $is_verified = $verification_filter === 'verified' ? 1 : 0;
    $sellers_sql .= " AND sp.is_verified = ?";
    $count_sql .= " AND sp.is_verified = ?";
    $params[] = $is_verified;
    $count_params[] = $is_verified;
}

// Add sorting and pagination
$sellers_sql .= " ORDER BY u.created_at DESC LIMIT $offset, $per_page";

// Prepare and execute the statements
$sellers_stmt = $conn->prepare($sellers_sql);
$count_stmt = $conn->prepare($count_sql);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $sellers_stmt->bind_param($types, ...$params);
}

if (!empty($count_params)) {
    $count_types = str_repeat('s', count($count_params));
    $count_stmt->bind_param($count_types, ...$count_params);
}

$sellers_stmt->execute();
$sellers_result = $sellers_stmt->get_result();
$sellers = [];

while ($row = $sellers_result->fetch_assoc()) {
    $sellers[] = $row;
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_sellers = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_sellers / $per_page);

include('../includes/header.php');
?>

<section class="admin-verify-sellers">
    <div class="container">
        <h2>Verify Sellers</h2>
        
        <div class="page-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <!-- Filters & Search -->
        <div class="filters-section">
            <form action="" method="GET" class="filters-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="verification">Verification Status:</label>
                        <select name="verification" id="verification">
                            <option value="">All Sellers</option>
                            <option value="pending" <?php echo ($verification_filter === 'pending') ? 'selected' : ''; ?>>Pending Verification</option>
                            <option value="verified" <?php echo ($verification_filter === 'verified') ? 'selected' : ''; ?>>Verified</option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" placeholder="Search by name, email or farm name" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Sellers Table -->
        <?php if (empty($sellers)): ?>
            <div class="no-data">
                <p>No sellers found matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="sellers-table-container">
                <table class="sellers-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Farm Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sellers as $seller): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($seller['farm_name']); ?></td>
                                <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                <td><?php echo htmlspecialchars($seller['location'] ?? 'Not specified'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($seller['registration_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $seller['is_verified'] ? 'verified' : 'pending'; ?>">
                                        <?php echo $seller['is_verified'] ? 'Verified' : 'Pending'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if (!$seller['is_verified']): ?>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=verify&id=<?php echo $seller['seller_id']; ?>" class="btn-sm btn-success" onclick="return confirm('Are you sure you want to verify this seller?');">Verify</a>
                                        <?php else: ?>
                                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>?action=reject&id=<?php echo $seller['seller_id']; ?>" class="btn-sm btn-warning" onclick="return confirm('Are you sure you want to reject this seller?');">Un-verify</a>
                                        <?php endif; ?>
                                        <a href="seller_details.php?id=<?php echo $seller['seller_id']; ?>" class="btn-sm">Details</a>
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
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page - 1); ?>&verification=<?php echo $verification_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">&laquo; Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $i; ?>&verification=<?php echo $verification_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo ($page + 1); ?>&verification=<?php echo $verification_filter; ?>&search=<?php echo urlencode($search); ?>" class="page-link">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
    .admin-verify-sellers {
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
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-secondary {
        background-color: #f1f1f1;
        color: #333;
    }
    
    /* Sellers Table */
    .sellers-table-container {
        overflow-x: auto;
        margin-bottom: 20px;
    }
    
    .sellers-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .sellers-table th, 
    .sellers-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .sellers-table th {
        background-color: #f8f8f8;
        font-weight: 600;
        color: #333;
    }
    
    .sellers-table tr:hover {
        background-color: #f9f9f9;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-verified {
        background-color: #E8F5E9;
        color: #388E3C;
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
        display: inline-block;
        background-color: #eee;
        color: #333;
        border: none;
        cursor: pointer;
    }
    
    .btn-success {
        background-color: #4CAF50;
        color: white;
    }
    
    .btn-warning {
        background-color: #FFF3CD;
        color: #856404;
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