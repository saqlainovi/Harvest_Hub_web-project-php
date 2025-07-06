<?php
// Turn off error display for HTML
ini_set('display_errors', 0);
error_reporting(0);

// Set content type
header('Content-Type: text/html');

echo "<h1>Database Fix Utility</h1>";

require_once 'includes/config.php';

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    echo "<p class='error'>Database connection failed: " . ($conn ? $conn->connect_error : "Not connected") . "</p>";
    
    echo "<h2>Database Connection Settings</h2>";
    echo "<p>Please check the database connection settings in <code>includes/db_connect.php</code>:</p>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>";
    echo htmlspecialchars(file_get_contents('includes/db_connect.php'));
    echo "</pre>";
    
    exit;
}

echo "<p class='success'>Connected to database successfully!</p>";

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    if (!tableExists($conn, $table)) return false;
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to generate SQL for a table if it doesn't exist
function generateTableSQL($table) {
    switch ($table) {
        case 'users':
            return "CREATE TABLE users (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NULL,
                firebase_uid VARCHAR(128) NULL,
                login_provider ENUM('email', 'google', 'facebook', 'traditional') DEFAULT 'traditional',
                full_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                address TEXT,
                role ENUM('admin', 'seller', 'buyer') NOT NULL,
                profile_image VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX firebase_uid_idx (firebase_uid)
            );";
            
        case 'seller_profiles':
            return "CREATE TABLE seller_profiles (
                seller_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                farm_name VARCHAR(100) NOT NULL,
                description TEXT,
                location VARCHAR(100),
                is_verified BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            );";
            
        case 'buyers':
            return "CREATE TABLE buyers (
                buyer_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            );";
            
        default:
            return "";
    }
}

// Function to generate SQL for a column if it doesn't exist
function generateColumnSQL($table, $column) {
    if ($table === 'users') {
        switch ($column) {
            case 'firebase_uid':
                return "ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128) NULL AFTER password, ADD UNIQUE INDEX firebase_uid_idx (firebase_uid);";
                
            case 'login_provider':
                return "ALTER TABLE users ADD COLUMN login_provider ENUM('email', 'google', 'facebook', 'traditional') DEFAULT 'traditional' AFTER firebase_uid;";
                
            case 'last_login':
                return "ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER created_at;";
                
            default:
                return "";
        }
    }
    return "";
}

// Generate SQL script based on missing tables/columns
$sql_script = "";

// Check important tables
$tables = ['users', 'seller_profiles', 'buyers'];
echo "<h2>Checking Tables</h2>";
echo "<ul>";

foreach ($tables as $table) {
    if (tableExists($conn, $table)) {
        echo "<li style='color:green'>✅ Table '$table' exists</li>";
    } else {
        echo "<li style='color:red'>❌ Table '$table' does NOT exist</li>";
        $sql_script .= generateTableSQL($table) . "\n\n";
    }
}
echo "</ul>";

// Check Firebase columns in users table
if (tableExists($conn, 'users')) {
    echo "<h2>Checking Firebase Columns in Users Table</h2>";
    echo "<ul>";
    
    $columns = ['firebase_uid', 'login_provider', 'last_login'];
    foreach ($columns as $column) {
        if (columnExists($conn, 'users', $column)) {
            echo "<li style='color:green'>✅ Column '$column' exists</li>";
        } else {
            echo "<li style='color:red'>❌ Column '$column' does NOT exist</li>";
            $sql_script .= generateColumnSQL('users', $column) . "\n";
        }
    }
    
    // Check if password is nullable
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
    if ($result && $result->num_rows > 0) {
        $column = $result->fetch_assoc();
        if (strpos($column['Type'], 'NOT NULL') === false && strpos($column['Null'], 'YES') !== false) {
            echo "<li style='color:green'>✅ Password column is nullable (required for Firebase)</li>";
        } else {
            echo "<li style='color:red'>❌ Password column is NOT nullable</li>";
            $sql_script .= "ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;\n";
        }
    }
    
    echo "</ul>";
}

// Output SQL script if needed
if (!empty($sql_script)) {
    echo "<h2>SQL Script to Fix Issues</h2>";
    echo "<p>Run the following SQL in your database to fix the issues:</p>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>";
    echo htmlspecialchars($sql_script);
    echo "</pre>";
    
    echo "<h3>Or Automatically Execute SQL</h3>";
    
    if (isset($_POST['execute_sql'])) {
        echo "<div style='background: #f0f8ff; padding: 15px; border: 1px solid #add8e6; margin: 15px 0;'>";
        echo "<h3>Executing SQL...</h3>";
        
        // Split the SQL script into individual statements
        $statements = explode(';', $sql_script);
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            if ($conn->query($statement)) {
                echo "<p style='color:green'>✅ SQL executed successfully: " . htmlspecialchars(substr($statement, 0, 50)) . "...</p>";
                $success_count++;
            } else {
                echo "<p style='color:red'>❌ Error executing SQL: " . $conn->error . "</p>";
                echo "<p>Statement: " . htmlspecialchars($statement) . "</p>";
                $error_count++;
            }
        }
        
        echo "<p>Summary: $success_count statements executed successfully, $error_count errors.</p>";
        echo "</div>";
        
        // Refresh page to show updated status
        echo "<script>setTimeout(function() { window.location.href = 'fix_database.php'; }, 5000);</script>";
        echo "<p>The page will refresh in 5 seconds to show updated status...</p>";
    } else {
        echo "<form method='post'>";
        echo "<p style='color:red'><strong>Warning:</strong> This will modify your database structure. Make sure you have a backup before proceeding.</p>";
        echo "<input type='submit' name='execute_sql' value='Execute SQL to Fix Issues' style='padding: 10px; background-color: #007bff; color: white; border: none; cursor: pointer;'>";
        echo "</form>";
    }
} else {
    echo "<h2>No Issues Found</h2>";
    echo "<p style='color:green'>All required tables and columns exist in your database!</p>";
}

// Additional test for firebase_auth.php
echo "<h2>Testing firebase_auth.php</h2>";
echo "<p>Try running <a href='ajax_test.html'>AJAX Test</a> to debug the issues.</p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .success { color: green; }
    .error { color: red; }
    h1, h2, h3 { color: #333; }
    h2 { border-bottom: 1px solid #eee; padding-bottom: 10px; }
</style> 