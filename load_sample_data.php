<?php
/**
 * This script helps users load sample data for the Fresh Harvest website
 * It should be run directly from the browser
 */

// Database configuration
$host = 'localhost';
$username = 'root';  // Default XAMPP username
$password = '';      // Default XAMPP password
$database = 'fresh_harvest';

// Connect to database
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    // Try connecting without database to create it
    try {
        $conn = new mysqli($host, $username, $password);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS $database";
        if ($conn->query($sql) === TRUE) {
            echo "Database created successfully<br>";
            // Select the database
            $conn->select_db($database);
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    } catch (Exception $innerEx) {
        die("Error: " . $innerEx->getMessage());
    }
}

// Initialize results arrays
$results = [
    'success' => [],
    'error' => []
];

// Function to run SQL file
function execute_sql_file($conn, $file_path, &$results) {
    if (!file_exists($file_path)) {
        $results['error'][] = "File not found: $file_path";
        return false;
    }
    
    // Read SQL file
    $sql = file_get_contents($file_path);
    
    // Split into separate queries
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            if ($conn->query($query)) {
                $results['success'][] = "Query executed successfully: " . substr($query, 0, 50) . "...";
            } else {
                $results['error'][] = "Error executing query: " . $conn->error . "\nQuery: " . substr($query, 0, 100) . "...";
            }
        } catch (Exception $e) {
            $results['error'][] = "Exception: " . $e->getMessage() . "\nQuery: " . substr($query, 0, 100) . "...";
        }
    }
    
    return true;
}

// Create images directory if it doesn't exist
$images_dirs = [
    'images/fruits',
    'images/uploads',
    'images/banners',
    'images/icons'
];

foreach ($images_dirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            $results['success'][] = "Created directory: $dir";
        } else {
            $results['error'][] = "Failed to create directory: $dir";
        }
    }
}

// Check and process the request
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'setup_database':
            // Run database setup script
            execute_sql_file($conn, 'database/db_setup.sql', $results);
            break;
            
        case 'import_sample_data':
            // Run sample data import
            execute_sql_file($conn, 'database/sample_data.sql', $results);
            break;
            
        case 'generate_images':
            // Generate placeholder images for fruits
            if (file_exists('download_placeholder_images.php')) {
                include('download_placeholder_images.php');
                $results['success'][] = "Attempted to download placeholder images";
            } else {
                $results['error'][] = "Image generation script not found";
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fresh Harvest Sample Data Loader</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        h2 {
            margin-top: 30px;
            color: #555;
        }
        
        .step {
            background-color: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        
        .step p {
            margin-bottom: 15px;
        }
        
        .btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #388E3C;
        }
        
        .results {
            margin-top: 30px;
            padding: 15px;
            border-radius: 5px;
        }
        
        .success {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        
        .error {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
        }
        
        ul {
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 5px;
        }
        
        .note {
            background-color: #fff8e1;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #FFC107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fresh Harvest Sample Data Loader</h1>
        <p>This tool helps you set up your Fresh Harvest website with sample data.</p>
        
        <div class="note">
            <strong>Note:</strong> Make sure you have XAMPP running with Apache and MySQL services started.
        </div>
        
        <h2>Setup Steps</h2>
        
        <div class="step">
            <h3>Step 1: Set Up Database</h3>
            <p>This will create the database structure (tables, relationships, etc.)</p>
            <form method="post" action="">
                <input type="hidden" name="action" value="setup_database">
                <button type="submit" class="btn">Run Database Setup</button>
            </form>
        </div>
        
        <div class="step">
            <h3>Step 2: Import Sample Data</h3>
            <p>This will add sample users, fruits, categories, and other data to your database.</p>
            <form method="post" action="">
                <input type="hidden" name="action" value="import_sample_data">
                <button type="submit" class="btn">Import Sample Data</button>
            </form>
        </div>
        
        <div class="step">
            <h3>Step 3: Generate Placeholder Images</h3>
            <p>This will create placeholder images for all fruits in the sample data.</p>
            <form method="post" action="">
                <input type="hidden" name="action" value="generate_images">
                <button type="submit" class="btn">Generate Placeholder Images</button>
            </form>
        </div>
        
        <div class="step">
            <h3>Step 4: Access Your Website</h3>
            <p>Once all steps are completed, you can access your website using the links below:</p>
            <ul>
                <li><a href="http://localhost/asraf%20idp2/index.php" target="_blank">Homepage</a></li>
                <li><a href="http://localhost/asraf%20idp2/pages/login.php" target="_blank">Login Page</a></li>
            </ul>
            <p><strong>Admin Login:</strong> Username: admin, Password: admin123</p>
            <p><strong>Seller Login:</strong> Username: sunvalleyfarm, Password: seller123</p>
        </div>
        
        <?php if (!empty($results['success']) || !empty($results['error'])): ?>
            <h2>Results</h2>
            
            <?php if (!empty($results['success'])): ?>
                <div class="results success">
                    <h3>Success:</h3>
                    <ul>
                        <?php foreach ($results['success'] as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results['error'])): ?>
                <div class="results error">
                    <h3>Errors:</h3>
                    <ul>
                        <?php foreach ($results['error'] as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 