<?php
// Include configuration
require_once 'includes/config.php';

// Check if we have a database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

echo "<h2>Diagnostic Information</h2>";

// Check if seller_profiles table exists
$table_check_sql = "SHOW TABLES LIKE 'seller_profiles'";
$table_exists = $conn->query($table_check_sql)->num_rows > 0;

if (!$table_exists) {
    echo "<div style='color: red; font-weight: bold;'>seller_profiles table does not exist!</div>";
} else {
    echo "<div style='color: green;'>seller_profiles table exists.</div>";
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: red; font-weight: bold;'>No user is logged in.</div>";
    echo "<div>Please <a href='pages/login.php'>log in</a> first.</div>";
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
echo "<div>User ID: $user_id</div>";

// Check user roles
echo "<div>User roles: ";
if (isset($_SESSION['user_role'])) {
    echo $_SESSION['user_role'];
} else {
    echo "None";
}
echo "</div>";

// Check if the logged in user has a seller profile
$seller_sql = "SELECT * FROM seller_profiles WHERE user_id = $user_id";
$seller_result = $conn->query($seller_sql);

if (!$seller_result) {
    echo "<div style='color: red; font-weight: bold;'>Error executing seller profile query: " . $conn->error . "</div>";
} else {
    if ($seller_result->num_rows > 0) {
        $seller = $seller_result->fetch_assoc();
        echo "<div style='color: green;'>Seller profile found:</div>";
        echo "<pre>";
        print_r($seller);
        echo "</pre>";
    } else {
        echo "<div style='color: red; font-weight: bold;'>No seller profile found for user ID $user_id.</div>";
        
        // Check if they have a 'seller' role
        if ($_SESSION['user_role'] === 'seller') {
            echo "<div>User has seller role but no seller profile. Needs to create a seller profile.</div>";
        } else {
            echo "<div>User does not have seller role. Cannot add products.</div>";
        }
    }
}

// Check agricultural_products table
$agri_check_sql = "SHOW TABLES LIKE 'agricultural_products'";
$agri_exists = $conn->query($agri_check_sql)->num_rows > 0;

if (!$agri_exists) {
    echo "<div style='color: red; font-weight: bold;'>agricultural_products table does not exist!</div>";
} else {
    echo "<div style='color: green;'>agricultural_products table exists.</div>";
    
    // Show the structure
    echo "<h3>agricultural_products table structure:</h3>";
    $result = $conn->query("DESCRIBE agricultural_products");
    if ($result) {
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? "NULL") . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Link to fix the profile issue or other problems
echo "<div style='margin-top: 20px;'>";
echo "<a href='seller/dashboard.php'>Go to Seller Dashboard</a> | ";
echo "<a href='create_agricultural_table.php'>Recreate Agricultural Products Table</a>";
echo "</div>";
?> 