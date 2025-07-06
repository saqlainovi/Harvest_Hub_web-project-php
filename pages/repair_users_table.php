<?php
// Database settings
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "fresh_harvest";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h1>Users Table Repair Tool</h1>";

// Check if the database exists
$dbExists = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
if ($dbExists->num_rows == 0) {
    echo "<p style='color:red'>Database '$db_name' does not exist!</p>";
    echo "<p>Attempting to create database...</p>";
    
    if ($conn->query("CREATE DATABASE IF NOT EXISTS $db_name")) {
        echo "<p style='color:green'>Database created successfully!</p>";
        $conn->select_db($db_name);
    } else {
        die("<p style='color:red'>Failed to create database: " . $conn->error . "</p>");
    }
}

// Check if users table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'users'");
if ($tableExists->num_rows == 0) {
    echo "<p>Users table does not exist. Creating new table...</p>";
    
    // Create the table with all required columns
    $create_sql = "CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NULL,
        full_name VARCHAR(100) NOT NULL,
        firebase_uid VARCHAR(128) NULL,
        role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL,
        login_provider VARCHAR(20) NULL
    )";
    
    if ($conn->query($create_sql)) {
        echo "<p style='color:green'>Users table created successfully!</p>";
    } else {
        echo "<p style='color:red'>Failed to create users table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Users table exists. Checking structure...</p>";
    
    // Check table structure and repair if needed
    $columns = [
        'user_id' => "ALTER TABLE users ADD COLUMN user_id INT AUTO_INCREMENT PRIMARY KEY",
        'username' => "ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL",
        'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NOT NULL",
        'password' => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL",
        'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL",
        'firebase_uid' => "ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128) NULL",
        'role' => "ALTER TABLE users ADD COLUMN role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer'",
        'created_at' => "ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'last_login' => "ALTER TABLE users ADD COLUMN last_login DATETIME NULL",
        'login_provider' => "ALTER TABLE users ADD COLUMN login_provider VARCHAR(20) NULL"
    ];
    
    // Get existing columns
    $result = $conn->query("DESCRIBE users");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add missing columns
    foreach ($columns as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            echo "<p>Adding missing column: $column</p>";
            if ($conn->query($sql)) {
                echo "<p style='color:green'>- Column added successfully</p>";
            } else {
                echo "<p style='color:red'>- Failed to add column: " . $conn->error . "</p>";
            }
        }
    }
    
    // Consider dropping and recreating the table if needed
    echo "<h2>Recreate Table Option</h2>";
    echo "<p>If you continue to have issues, you can choose to drop and recreate the users table (all data will be lost):</p>";
    
    if (isset($_GET['recreate']) && $_GET['recreate'] == 'yes') {
        // Drop and recreate the table
        $conn->query("DROP TABLE users");
        
        $create_sql = "CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NULL,
            full_name VARCHAR(100) NOT NULL,
            firebase_uid VARCHAR(128) NULL,
            role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL,
            login_provider VARCHAR(20) NULL
        )";
        
        if ($conn->query($create_sql)) {
            echo "<p style='color:green'>Users table recreated successfully!</p>";
        } else {
            echo "<p style='color:red'>Failed to recreate users table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p><a href='?recreate=yes' onclick=\"return confirm('Are you sure? All user data will be lost!');\">Recreate Users Table</a></p>";
    }
}

// Try direct insert without prepared statement
echo "<h2>Testing Direct Insert</h2>";

// Generate test data
$test_username = "testuser" . rand(1000, 9999);
$test_email = "test" . rand(1000, 9999) . "@example.com";
$test_name = "Test User";
$test_firebase_uid = "firebase_" . rand(1000000, 9999999);
$test_role = "buyer";

$direct_sql = "INSERT INTO users (username, email, full_name, firebase_uid, role) 
              VALUES ('$test_username', '$test_email', '$test_name', '$test_firebase_uid', '$test_role')";

echo "<p>Direct SQL query: " . htmlspecialchars($direct_sql) . "</p>";

if ($conn->query($direct_sql)) {
    echo "<p style='color:green'>Direct insert successful! ID: " . $conn->insert_id . "</p>";
    // Clean up
    $conn->query("DELETE FROM users WHERE email = '$test_email'");
} else {
    echo "<p style='color:red'>Direct insert failed: " . $conn->error . "</p>";
}

// Test prepared statement
echo "<h2>Testing Prepared Statement</h2>";

// Generate new test data
$test_username = "testuser" . rand(1000, 9999);
$test_email = "test" . rand(1000, 9999) . "@example.com";

$prepared_sql = "INSERT INTO users (username, email, full_name, firebase_uid, role) VALUES (?, ?, ?, ?, ?)";
echo "<p>Prepared statement: " . htmlspecialchars($prepared_sql) . "</p>";

$stmt = $conn->prepare($prepared_sql);
if (!$stmt) {
    echo "<p style='color:red'>Prepared statement failed: " . $conn->error . "</p>";
    echo "<p>Checking if the error is related to the table structure...</p>";
    
    // Get MySQL version and character set information
    $mysql_version = $conn->query("SELECT VERSION() as version")->fetch_assoc()['version'];
    echo "<p>MySQL Version: " . $mysql_version . "</p>";
    
    $charset = $conn->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch_assoc()['Value'];
    echo "<p>Database Character Set: " . $charset . "</p>";
    
    $collation = $conn->query("SHOW VARIABLES LIKE 'collation_database'")->fetch_assoc()['Value'];
    echo "<p>Database Collation: " . $collation . "</p>";
} else {
    echo "<p style='color:green'>Prepared statement created successfully!</p>";
    $stmt->bind_param("sssss", $test_username, $test_email, $test_name, $test_firebase_uid, $test_role);
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>Prepared insert successful! ID: " . $conn->insert_id . "</p>";
        // Clean up
        $conn->query("DELETE FROM users WHERE email = '$test_email'");
    } else {
        echo "<p style='color:red'>Prepared statement execution failed: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

// Show current table structure
echo "<h2>Current Table Structure</h2>";
$result = $conn->query("DESCRIBE users");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Close connection
$conn->close();
echo "<p><a href='firebase_test.html'>Test Firebase Authentication</a> | <a href='login.php'>Go to Login Page</a></p>";
?> 