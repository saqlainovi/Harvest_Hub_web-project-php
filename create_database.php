<?php
// Database connection settings
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";

try {
    echo "Attempting to connect to MySQL... \n";
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to MySQL successfully! \n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE 'fresh_harvest'");
    
    if ($result->num_rows > 0) {
        echo "Database 'fresh_harvest' already exists. \n";
    } else {
        echo "Database 'fresh_harvest' does not exist. Creating it... \n";
        
        // Create the database
        if ($conn->query("CREATE DATABASE fresh_harvest")) {
            echo "Database created successfully! \n";
        } else {
            echo "Error creating database: " . $conn->error . "\n";
        }
    }
    
    // Select the database
    $conn->select_db('fresh_harvest');
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($result->num_rows > 0) {
        echo "Table 'users' already exists. \n";
    } else {
        echo "Table 'users' does not exist. Creating basic tables... \n";
        
        // Create basic tables
        $users_sql = "CREATE TABLE users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255),
            full_name VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            address TEXT,
            role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer',
            firebase_uid VARCHAR(100),
            login_provider VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
        )";
        
        if ($conn->query($users_sql)) {
            echo "Users table created successfully! \n";
        } else {
            echo "Error creating users table: " . $conn->error . "\n";
        }
    }
    
    echo "Done! \n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 