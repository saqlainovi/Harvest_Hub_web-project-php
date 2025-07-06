<?php
require_once 'includes/config.php';

echo "<h2>Database Tables Check</h2>";

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Check users table
if (tableExists($conn, 'users')) {
    echo "✅ users table exists.<br>";
} else {
    echo "❌ users table does NOT exist.<br>";
    echo "Please run the SQL from database/db_setup.sql to create the database schema.<br>";
}

// Check firebase columns
$sql = "SHOW COLUMNS FROM users LIKE 'firebase_uid'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "✅ firebase_uid column exists in users table.<br>";
} else {
    echo "❌ firebase_uid column does NOT exist in users table.<br>";
    echo "Please run the SQL from database/firebase_update.sql:<br>";
    echo "<pre>" . file_get_contents('database/firebase_update.sql') . "</pre>";
}

// Check buyers table
if (tableExists($conn, 'buyers')) {
    echo "✅ buyers table exists.<br>";
} else {
    echo "❌ buyers table does NOT exist. Creating it now...<br>";
    $conn->query("CREATE TABLE IF NOT EXISTS buyers (
        buyer_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    echo "✅ buyers table created.<br>";
}

// Check seller_profiles table
if (tableExists($conn, 'seller_profiles')) {
    echo "✅ seller_profiles table exists.<br>";
} else {
    echo "❌ seller_profiles table does NOT exist. Creating it now...<br>";
    $conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
        seller_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        farm_name VARCHAR(100) NOT NULL,
        description TEXT,
        location VARCHAR(100),
        is_verified BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    echo "✅ seller_profiles table created.<br>";
}

// Create a logs directory if it doesn't exist
$logs_dir = __DIR__ . '/logs';
if (!file_exists($logs_dir)) {
    mkdir($logs_dir, 0755, true);
    echo "✅ Created logs directory.<br>";
} else {
    echo "✅ logs directory exists.<br>";
}

// Check permissions on logs directory
if (is_writable($logs_dir)) {
    echo "✅ logs directory is writable.<br>";
} else {
    echo "❌ logs directory is NOT writable. Please set write permissions.<br>";
}

echo "<h3>Next Steps</h3>";
echo "<p>1. Make sure you have run the database setup SQL scripts.</p>";
echo "<p>2. Check the logs directory for error messages if login fails.</p>";
echo "<p>3. Try logging in with Google again and check for errors in logs/error.log</p>";
?> 