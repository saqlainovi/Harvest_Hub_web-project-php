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

echo "<h1>Users Table Diagnostic Tool</h1>";
echo "<p>Connected to database successfully.</p>";

// Get full table structure
echo "<h2>Table Structure</h2>";
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

// Check existing users
echo "<h2>Existing Users</h2>";
$result = $conn->query("SELECT * FROM users LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    $first = true;
    
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in the database.</p>";
}

// Test insert with simple query
echo "<h2>Testing Insert Query</h2>";

// Generate test data
$test_username = "testuser" . rand(1000, 9999);
$test_email = "test" . rand(1000, 9999) . "@example.com";
$test_name = "Test User";
$test_firebase_uid = "firebase_" . rand(1000000, 9999999);
$test_role = "buyer";

echo "<p>Preparing to insert test user:</p>";
echo "<ul>";
echo "<li>Username: " . $test_username . "</li>";
echo "<li>Email: " . $test_email . "</li>";
echo "<li>Name: " . $test_name . "</li>";
echo "<li>Firebase UID: " . $test_firebase_uid . "</li>";
echo "<li>Role: " . $test_role . "</li>";
echo "</ul>";

// First try with all required fields
$insert_query = "INSERT INTO users (username, email, full_name, firebase_uid, role) 
               VALUES (?, ?, ?, ?, ?)";

echo "<p>Using query: <code>" . htmlspecialchars($insert_query) . "</code></p>";

$stmt = $conn->prepare($insert_query);

if (!$stmt) {
    echo "<p style='color:red'>Prepare failed: " . $conn->error . "</p>";
} else {
    echo "<p style='color:green'>Prepare successful!</p>";
    
    $stmt->bind_param("sssss", $test_username, $test_email, $test_name, $test_firebase_uid, $test_role);
    
    if ($stmt->execute()) {
        echo "<p style='color:green'>Insert successful! New user ID: " . $conn->insert_id . "</p>";
        
        // Clean up test data
        $conn->query("DELETE FROM users WHERE email = '" . $test_email . "'");
        echo "<p>Test user has been removed from database.</p>";
    } else {
        echo "<p style='color:red'>Execute failed: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
}

// Check MySQL version and settings
echo "<h2>MySQL Information</h2>";
$version = $conn->query("SELECT VERSION() as version")->fetch_assoc();
echo "<p>MySQL Version: " . $version['version'] . "</p>";

$variables = ["sql_mode", "character_set_database", "character_set_server"];
echo "<table border='1'>";
echo "<tr><th>Variable</th><th>Value</th></tr>";

foreach ($variables as $var) {
    $result = $conn->query("SHOW VARIABLES LIKE '$var'")->fetch_assoc();
    echo "<tr><td>" . $result['Variable_name'] . "</td><td>" . $result['Value'] . "</td></tr>";
}
echo "</table>";

// Close connection
$conn->close();
echo "<p><a href='fix_database.php'>Run Database Repair Tool</a> | <a href='firebase_test.html'>Test Firebase Authentication</a> | <a href='login.php'>Go to Login Page</a></p>";
?> 