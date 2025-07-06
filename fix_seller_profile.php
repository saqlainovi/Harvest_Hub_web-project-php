<?php
// Include configuration
require_once 'includes/config.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Fresh Harvest Seller Profile and Products Fix</h1>";

// Step 1: Check user login
if (!isset($_SESSION['user_id'])) {
    die("<p>You must be logged in to use this tool. <a href='pages/login.php'>Log in here</a></p>");
}

$user_id = $_SESSION['user_id'];
echo "<p>Logged in user ID: $user_id</p>";

// Get user info
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);

if (!$user_result || $user_result->num_rows === 0) {
    die("<p>User not found in database.</p>");
}

$user = $user_result->fetch_assoc();
echo "<p>User: {$user['username']} (Role: {$user['role']})</p>";

// Display actions
echo "<form method='post' action=''>";
echo "<h2>Available Actions:</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<button type='submit' name='action' value='check'>Check Current Status</button>";
echo "<button type='submit' name='action' value='fix_seller_profile'>Fix Seller Profile</button>";
echo "<button type='submit' name='action' value='drop_table'>Drop & Recreate Agricultural Products Table</button>";
echo "</div>";
echo "</form>";

// Process actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'check') {
        checkStatus($conn, $user_id);
    } elseif ($action === 'fix_seller_profile') {
        fixSellerProfile($conn, $user_id, $user);
    } elseif ($action === 'drop_table') {
        dropAndRecreateTable($conn);
    }
}

// Function to check current status
function checkStatus($conn, $user_id) {
    echo "<h3>Current Status:</h3>";
    
    // Check seller profile
    $seller_sql = "SELECT * FROM seller_profiles WHERE user_id = $user_id";
    $seller_result = $conn->query($seller_sql);
    
    if (!$seller_result) {
        echo "<p>Error querying seller profiles: " . $conn->error . "</p>";
    } elseif ($seller_result->num_rows === 0) {
        echo "<p style='color: red;'>No seller profile found for your user ID.</p>";
    } else {
        $seller = $seller_result->fetch_assoc();
        echo "<p style='color: green;'>Seller profile found:</p>";
        echo "<pre>";
        print_r($seller);
        echo "</pre>";
    }
    
    // Check agricultural_products table
    $table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
    if ($table_check->num_rows === 0) {
        echo "<p style='color: red;'>Agricultural products table does not exist!</p>";
    } else {
        echo "<p style='color: green;'>Agricultural products table exists.</p>";
        
        // Check columns
        $columns = $conn->query("DESCRIBE agricultural_products");
        echo "<p>Columns in agricultural_products table:</p>";
        echo "<ul>";
        $has_is_organic = false;
        $has_is_available = false;
        
        while ($column = $columns->fetch_assoc()) {
            echo "<li>{$column['Field']} ({$column['Type']})</li>";
            if ($column['Field'] === 'is_organic') {
                $has_is_organic = true;
            }
            if ($column['Field'] === 'is_available') {
                $has_is_available = true;
            }
        }
        echo "</ul>";
        
        if (!$has_is_organic || !$has_is_available) {
            echo "<p style='color: orange;'>Missing columns in agricultural_products table. Recommend dropping and recreating table.</p>";
        }
    }
}

// Function to fix seller profile
function fixSellerProfile($conn, $user_id, $user) {
    echo "<h3>Fixing Seller Profile:</h3>";
    
    // Check if seller profile exists
    $check_sql = "SELECT * FROM seller_profiles WHERE user_id = $user_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo "<p>Seller profile already exists.</p>";
        return;
    }
    
    // Create seller profile with default values
    $farm_name = $user['full_name'] . "'s Farm";
    $description = "Welcome to my farm! I sell fresh, healthy produce.";
    $location = "Local Area";
    $is_verified = 0;
    
    // Use user_id as seller_id for simplicity
    $insert_sql = "INSERT INTO seller_profiles (seller_id, user_id, farm_name, description, location, is_verified) 
                  VALUES ($user_id, $user_id, '$farm_name', '$description', '$location', $is_verified)";
    
    if ($conn->query($insert_sql)) {
        echo "<p style='color: green;'>Success! Created new seller profile with default values.</p>";
        echo "<p>Farm Name: $farm_name</p>";
        echo "<p>You can edit your profile in the seller dashboard.</p>";
    } else {
        echo "<p style='color: red;'>Error creating seller profile: " . $conn->error . "</p>";
    }
}

// Function to drop and recreate agricultural_products table
function dropAndRecreateTable($conn) {
    echo "<h3>Dropping and Recreating Agricultural Products Table:</h3>";
    
    // Drop table if exists
    $drop_sql = "DROP TABLE IF EXISTS agricultural_products";
    if (!$conn->query($drop_sql)) {
        echo "<p style='color: red;'>Error dropping table: " . $conn->error . "</p>";
        return;
    }
    
    echo "<p>Successfully dropped table.</p>";
    
    // Create new table
    $create_sql = "CREATE TABLE agricultural_products (
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
    
    if ($conn->query($create_sql)) {
        echo "<p style='color: green;'>Successfully created new agricultural_products table with all required columns.</p>";
    } else {
        echo "<p style='color: red;'>Error creating table: " . $conn->error . "</p>";
    }
}

// Link to seller pages
echo "<div style='margin-top: 30px;'>";
echo "<p><a href='seller/dashboard.php'>Go to Seller Dashboard</a></p>";
echo "<p><a href='seller/add_agri_product.php'>Add Agricultural Product</a></p>";
echo "<p><a href='seller/add_fruit.php'>Add Fruit</a></p>";
echo "</div>";
?> 