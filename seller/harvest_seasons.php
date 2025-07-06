<?php
$page_title = "Manage Harvest Seasons";
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

// Get all fruits by this seller for dropdown
$fruits_sql = "SELECT fruit_id, name FROM fruits WHERE seller_id = $seller_id ORDER BY name ASC";
$fruits_result = $conn->query($fruits_sql);
$fruits = [];

if ($fruits_result && $fruits_result->num_rows > 0) {
    while ($row = $fruits_result->fetch_assoc()) {
        $fruits[] = $row;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new harvest season
    if (isset($_POST['add_season'])) {
        $fruit_id = (int)$_POST['fruit_id'];
        $start_date = sanitize_input($_POST['start_date']);
        $end_date = sanitize_input($_POST['end_date']);
        $region = sanitize_input($_POST['region']);
        $notes = sanitize_input($_POST['notes']);
        
        // Validate input
        $errors = [];
        
        if ($fruit_id <= 0) {
            $errors[] = 'Please select a valid fruit.';
        }
        
        if (empty($start_date)) {
            $errors[] = 'Start date is required.';
        }
        
        if (empty($end_date)) {
            $errors[] = 'End date is required.';
        }
        
        if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
            $errors[] = 'End date cannot be earlier than start date.';
        }
        
        // Verify fruit belongs to seller
        if ($fruit_id > 0) {
            $check_sql = "SELECT fruit_id FROM fruits WHERE fruit_id = $fruit_id AND seller_id = $seller_id";
            $check_result = $conn->query($check_sql);
            
            if (!$check_result || $check_result->num_rows == 0) {
                $errors[] = 'You are not authorized to add harvest seasons for this fruit.';
            }
        }
        
        // Insert if no errors
        if (empty($errors)) {
            $insert_sql = "INSERT INTO harvest_seasons (fruit_id, start_date, end_date, region, notes) 
                          VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param('issss', $fruit_id, $start_date, $end_date, $region, $notes);
            
            if ($stmt->execute()) {
                set_flash_message('success', 'Harvest season added successfully.');
                redirect(SITE_URL . '/seller/harvest_seasons.php');
            } else {
                $errors[] = 'Database error: ' . $conn->error;
            }
            
            $stmt->close();
        }
    }
    
    // Delete harvest season
    if (isset($_POST['delete_season'])) {
        $season_id = (int)$_POST['season_id'];
        
        // Verify season belongs to seller's fruit
        $check_sql = "SELECT hs.season_id 
                     FROM harvest_seasons hs
                     JOIN fruits f ON hs.fruit_id = f.fruit_id
                     WHERE hs.season_id = $season_id AND f.seller_id = $seller_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $delete_sql = "DELETE FROM harvest_seasons WHERE season_id = $season_id";
            
            if ($conn->query($delete_sql)) {
                set_flash_message('success', 'Harvest season deleted successfully.');
            } else {
                set_flash_message('error', 'Failed to delete harvest season: ' . $conn->error);
            }
        } else {
            set_flash_message('error', 'You are not authorized to delete this harvest season.');
        }
        
        redirect(SITE_URL . '/seller/harvest_seasons.php');
    }
}

// Get harvest seasons for all fruits by this seller
$seasons_sql = "SELECT hs.*, f.name as fruit_name
               FROM harvest_seasons hs
               JOIN fruits f ON hs.fruit_id = f.fruit_id
               WHERE f.seller_id = $seller_id
               ORDER BY hs.start_date DESC";
$seasons_result = $conn->query($seasons_sql);
$seasons = [];

if ($seasons_result && $seasons_result->num_rows > 0) {
    while ($row = $seasons_result->fetch_assoc()) {
        $seasons[] = $row;
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="harvest-seasons-section">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a> &gt;
            <span>Manage Harvest Seasons</span>
        </div>
        
        <h2>Manage Harvest Seasons</h2>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="seasons-content">
            <div class="add-season-form">
                <h3>Add New Harvest Season</h3>
                
                <?php if (empty($fruits)): ?>
                    <div class="no-fruits-message">
                        <p>You need to add fruits before you can add harvest seasons.</p>
                        <a href="<?php echo SITE_URL; ?>/seller/add_fruit.php" class="btn">Add New Fruit</a>
                    </div>
                <?php else: ?>
                    <form action="" method="POST" class="season-form">
                        <input type="hidden" name="add_season" value="1">
                        
                        <div class="form-group">
                            <label for="fruit_id">Select Fruit*</label>
                            <select id="fruit_id" name="fruit_id" required>
                                <option value="">Select Fruit</option>
                                <?php foreach ($fruits as $fruit): ?>
                                    <option value="<?php echo $fruit['fruit_id']; ?>" <?php echo (isset($_POST['fruit_id']) && $_POST['fruit_id'] == $fruit['fruit_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($fruit['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label for="start_date">Start Date*</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group half">
                                <label for="end_date">End Date*</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="region">Region</label>
                            <input type="text" id="region" name="region" value="<?php echo isset($_POST['region']) ? htmlspecialchars($_POST['region']) : ''; ?>" placeholder="e.g., North Region, Southern Valley">
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Any additional information about the harvest season"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn submit-btn">Add Harvest Season</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="seasons-list">
                <h3>Your Harvest Seasons</h3>
                
                <?php if (empty($seasons)): ?>
                    <div class="no-seasons">
                        <p>You haven't added any harvest seasons yet.</p>
                    </div>
                <?php else: ?>
                    <div class="seasons-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fruit</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Region</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($seasons as $season): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($season['fruit_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($season['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($season['end_date'])); ?></td>
                                        <td><?php echo !empty($season['region']) ? htmlspecialchars($season['region']) : '-'; ?></td>
                                        <td><?php echo !empty($season['notes']) ? htmlspecialchars($season['notes']) : '-'; ?></td>
                                        <td>
                                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this harvest season?');" style="display: inline;">
                                                <input type="hidden" name="delete_season" value="1">
                                                <input type="hidden" name="season_id" value="<?php echo $season['season_id']; ?>">
                                                <button type="submit" class="btn-sm delete-btn">Delete</button>
                                            </form>
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
</section>

<style>
    .harvest-seasons-section {
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
    
    .seasons-content {
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 30px;
    }
    
    h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    .add-season-form {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .no-fruits-message {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .no-fruits-message .btn {
        margin-top: 15px;
    }
    
    .season-form {
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
        gap: 15px;
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
    input[type="date"],
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
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 10px;
    }
    
    .submit-btn {
        background-color: #4CAF50;
        color: white;
    }
    
    .seasons-list {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .no-seasons {
        text-align: center;
        padding: 20px;
        color: #666;
    }
    
    .seasons-table {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    th {
        background-color: #f2f2f2;
        font-weight: 600;
    }
    
    tr:hover {
        background-color: #f9f9f9;
    }
    
    .btn-sm {
        display: inline-block;
        padding: 5px 10px;
        background-color: #4CAF50;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        border: none;
        cursor: pointer;
    }
    
    .delete-btn {
        background-color: #F44336;
    }
    
    .btn-sm:hover {
        opacity: 0.9;
    }
    
    @media (max-width: 992px) {
        .seasons-content {
            grid-template-columns: 1fr;
        }
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