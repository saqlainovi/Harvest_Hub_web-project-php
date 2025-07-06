<?php
// Database connection settings
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "fresh_harvest";

// Path to SQL file
$sql_file = __DIR__ . '/database/db_setup.sql';

// Output buffer for clean response
ob_start();
echo "<pre>";

try {
    echo "Attempting to connect to MySQL...\n";
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to MySQL successfully!\n";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
    
    if ($result->num_rows == 0) {
        echo "Database '$db_name' does not exist. Creating it...\n";
        
        // Create the database
        if ($conn->query("CREATE DATABASE $db_name")) {
            echo "Database created successfully!\n";
        } else {
            echo "Error creating database: " . $conn->error . "\n";
            exit;
        }
    } else {
        echo "Database '$db_name' already exists.\n";
    }
    
    // Select the database
    $conn->select_db($db_name);
    
    // Check if SQL file exists
    if (!file_exists($sql_file)) {
        echo "SQL file not found at: $sql_file\n";
        echo "Looking for SQL file in these locations:\n";
        
        $possible_paths = [
            __DIR__ . '/database/db_setup.sql',
            __DIR__ . '/db_setup.sql',
            dirname(__DIR__) . '/database/db_setup.sql',
            dirname(__DIR__) . '/db_setup.sql'
        ];
        
        foreach ($possible_paths as $path) {
            echo "- $path: " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
            if (file_exists($path)) {
                $sql_file = $path;
                echo "Found SQL file at: $sql_file\n";
                break;
            }
        }
        
        if (!file_exists($sql_file)) {
            echo "Could not find SQL file in any location.\n";
            exit;
        }
    }
    
    echo "Reading SQL file: $sql_file\n";
    $sql = file_get_contents($sql_file);
    
    if (!$sql) {
        echo "Failed to read SQL file or file is empty.\n";
        exit;
    }
    
    echo "Executing SQL script...\n";
    
    // Split SQL by semicolon to get individual queries
    $queries = explode(';', $sql);
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if ($conn->query($query)) {
            $success_count++;
        } else {
            $error_count++;
            echo "Error executing query: " . $conn->error . "\n";
            echo "Query: " . substr($query, 0, 150) . "...\n\n";
        }
    }
    
    echo "Import completed with $success_count successful queries and $error_count errors.\n";
    
    echo "Database setup complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
ob_end_flush();
?> 