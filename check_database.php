<?php
// Database settings
$db_host = "localhost";
$db_user = "root";
$db_pass = "root";
$db_name = "fresh_harvest";

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to database successfully.<br>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    die("The 'users' table does not exist. Please create it first.");
}

echo "Users table exists.<br>";

// Get table structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Users Table Structure:</h3>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

$required_columns = ['user_id', 'username', 'email', 'full_name', 'firebase_uid', 'role', 'created_at', 'login_provider'];
$missing_columns = $required_columns;

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
    
    // Remove from missing_columns if found
    if (in_array($row['Field'], $missing_columns)) {
        $missing_columns = array_diff($missing_columns, [$row['Field']]);
    }
}
echo "</table>";

// Report missing columns
if (!empty($missing_columns)) {
    echo "<h3>Missing columns:</h3>";
    echo "<ul>";
    foreach ($missing_columns as $column) {
        echo "<li>" . $column . "</li>";
    }
    echo "</ul>";
    
    // Try to fix the table structure
    echo "<h3>Attempting to fix table structure:</h3>";
    
    // Create the missing columns
    $alter_queries = [];
    
    // Define the SQL for each missing column
    foreach ($missing_columns as $column) {
        switch ($column) {
            case 'user_id':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN user_id INT AUTO_INCREMENT PRIMARY KEY";
                break;
            case 'username':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE NOT NULL";
                break;
            case 'email':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE NOT NULL";
                break;
            case 'full_name':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL";
                break;
            case 'firebase_uid':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN firebase_uid VARCHAR(128) NULL";
                break;
            case 'role':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer'";
                break;
            case 'created_at':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
                break;
            case 'login_provider':
                $alter_queries[] = "ALTER TABLE users ADD COLUMN login_provider VARCHAR(20) NULL";
                break;
        }
    }
    
    // Execute the alter queries
    foreach ($alter_queries as $query) {
        if ($conn->query($query) === TRUE) {
            echo "Added column successfully: " . $query . "<br>";
        } else {
            echo "Error adding column: " . $conn->error . "<br>";
        }
    }
}

// Close connection
$conn->close();
?> 