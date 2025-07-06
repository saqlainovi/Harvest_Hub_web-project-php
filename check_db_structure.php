<?php
require_once 'includes/config.php';

// Check if Firebase columns exist in the users table
$sql = "SHOW COLUMNS FROM users LIKE 'firebase_uid'";
$result = $conn->query($sql);

echo "<h2>Database Structure Check</h2>";

if ($result && $result->num_rows > 0) {
    echo "✅ firebase_uid column exists in users table.<br>";
} else {
    echo "❌ firebase_uid column does NOT exist in users table.<br>";
    echo "Please run the SQL from database/firebase_update.sql:<br>";
    echo "<pre>" . file_get_contents('database/firebase_update.sql') . "</pre>";
}

// Check if login_provider column exists
$sql = "SHOW COLUMNS FROM users LIKE 'login_provider'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "✅ login_provider column exists in users table.<br>";
} else {
    echo "❌ login_provider column does NOT exist in users table.<br>";
}

// Check if password is nullable
$sql = "SHOW COLUMNS FROM users LIKE 'password'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $column = $result->fetch_assoc();
    if (strpos($column['Type'], 'NOT NULL') === false) {
        echo "✅ password column is nullable as required.<br>";
    } else {
        echo "❌ password column is NOT nullable (it should be nullable for Firebase users).<br>";
    }
}
?> 