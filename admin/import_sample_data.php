<?php
$page_title = "Import Sample Data";
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    set_flash_message('error', 'You do not have permission to access this page.');
    redirect(SITE_URL . '/index.php');
}

// Initialize status message
$status_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_data'])) {
        try {
            // Read the SQL file
            $sql_file = '../database/sample_data.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("Sample data SQL file not found.");
            }
            
            $sql_contents = file_get_contents($sql_file);
            
            // Split into separate queries
            $queries = explode(';', $sql_contents);
            
            // Execute each query
            $success_count = 0;
            $error_count = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query)) continue;
                
                try {
                    $result = $conn->query($query);
                    if ($result) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                } catch (Exception $e) {
                    $error_count++;
                }
            }
            
            $status_message = "Import completed: $success_count queries successful, $error_count errors.";
            set_flash_message('success', $status_message);
            
        } catch (Exception $e) {
            $status_message = "Error importing data: " . $e->getMessage();
            set_flash_message('error', $status_message);
        }
    }
    
    if (isset($_POST['generate_images'])) {
        // Redirect to the script that generates or downloads placeholder images
        redirect(SITE_URL . '/download_placeholder_images.php');
    }
}

// Include header
require_once '../includes/header.php';
?>

<div class="container">
    <div class="admin-content">
        <h1>Import Sample Data</h1>
        
        <?php display_flash_messages(); ?>
        
        <div class="admin-card">
            <h2>Sample Data Import</h2>
            <p>This tool will import sample data into your database. The sample data includes:</p>
            <ul>
                <li>Sample users (admin, sellers, buyers)</li>
                <li>Fruit categories</li>
                <li>Sample fruits (25+ fruits with details)</li>
                <li>Harvest seasons</li>
                <li>Sample reviews</li>
            </ul>
            
            <div class="alert alert-warning">
                <strong>Warning:</strong> This action will add sample data to your database. 
                Some data may be skipped if it already exists (users, categories).
            </div>
            
            <form method="post" action="">
                <div class="form-group">
                    <button type="submit" name="import_data" class="btn">Import Sample Data</button>
                </div>
            </form>
        </div>
        
        <div class="admin-card">
            <h2>Generate Placeholder Images</h2>
            <p>This tool will download placeholder images for all fruits in the sample data.</p>
            
            <form method="post" action="">
                <div class="form-group">
                    <button type="submit" name="generate_images" class="btn">Generate Placeholder Images</button>
                </div>
            </form>
        </div>
        
        <div class="admin-actions">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 