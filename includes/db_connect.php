<?php
// Enhanced Database Connection with Fallback Options
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Primary connection credentials
$db_options = [
    // Try these connection options in order until one works
    [
        'host' => 'localhost',
        'user' => 'harvest_user',
        'pass' => 'harvest_pass',
        'name' => 'fresh_harvest'
    ],
    [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'name' => 'fresh_harvest'
    ],
    [
        'host' => 'localhost',
        'user' => 'root',
        'pass' => 'root',
        'name' => 'fresh_harvest'
    ],
    [
        'host' => '127.0.0.1',
        'user' => 'root',
        'pass' => '',
        'name' => 'fresh_harvest'
    ]
];

// Try each connection option
$conn = null;
$conn_error = '';

foreach ($db_options as $option) {
    try {
        $conn = @new mysqli(
            $option['host'], 
            $option['user'], 
            $option['pass'], 
            $option['name']
        );
        
        if (!$conn->connect_error) {
            // Connection successful - break out of the loop
            break;
        } else {
            $conn_error = $conn->connect_error;
            $conn = null;
        }
    } catch (Exception $e) {
        $conn_error = $e->getMessage();
        $conn = null;
    }
}

// Check final connection
if (!$conn) {
    // Log error instead of outputting HTML for API endpoints
    error_log("All database connection attempts failed. Last error: " . $conn_error);
    
    // If this is a JSON API endpoint, return proper JSON error
    if (strpos($_SERVER["SCRIPT_NAME"], "/pages/firebase_auth.php") !== false ||
        strpos($_SERVER["SCRIPT_NAME"], "/pages/firebase_register.php") !== false) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false, 
            "message" => "Database connection failed"
        ]);
        exit;
    }
    
    // Show error page for HTML pages
    echo '<div style="margin: 30px auto; max-width: 800px; font-family: Arial, sans-serif;">
        <h1 style="color: #a94442;">Database Connection Error</h1>
        <p>We\'re having trouble connecting to the database. Here are some things you can try:</p>
        <ol>
            <li>Make sure MySQL is running in Laragon</li>
            <li>Run the <a href="/asraf idp2/db_create_user.php">Database User Setup Tool</a> to create the necessary user</li>
            <li>Check that the "fresh_harvest" database exists</li>
        </ol>
        <p>Error details: ' . htmlspecialchars($conn_error) . '</p>
    </div>';
    exit;
}

// Set character set
$conn->set_charset("utf8mb4");
?> 