<?php
// Script to create a new MySQL user for your application
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create MySQL User</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background-color: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .error { background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .info { background-color: #d9edf7; border: 1px solid #bce8f1; color: #31708f; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 6px 12px; margin-bottom: 0; font-size: 14px; font-weight: 400; line-height: 1.42857143; text-align: center; white-space: nowrap; vertical-align: middle; cursor: pointer; background-image: none; border: 1px solid transparent; border-radius: 4px; text-decoration: none; color: #fff; background-color: #337ab7; border-color: #2e6da4; }
        .btn:hover { background-color: #286090; border-color: #204d74; }
    </style>
</head>
<body>
    <h1>Create MySQL User for Harvest Hub</h1>
    
<?php
// Define new user credentials
$new_username = 'harvest_user';
$new_password = 'harvest_pass';
$database_name = 'fresh_harvest';

// Function to test various connection methods
function try_connections() {
    // Common passwords to try
    $passwords = ["", "root", "password", "laragon", "admin"];
    $successful_conn = null;
    
    echo "<div class='info'><h3>Testing MySQL Connections</h3>";
    echo "<p>Trying different connection methods...</p>";
    
    // Try socket connection first
    foreach ($passwords as $password) {
        try {
            $conn = @new mysqli('localhost', 'root', $password);
            if (!$conn->connect_error) {
                echo "<p class='success'>Connected using password: \"$password\"</p>";
                return $conn;
            }
        } catch (Exception $e) {
            // Continue to next method
        }
    }
    
    // Try using mysqladmin to test connection
    echo "<p>Could not connect using standard methods. Your MySQL server might be using a different authentication method.</p>";
    
    return null;
}

// Main process
$conn = try_connections();

if (!$conn) {
    echo "<div class='error'>
        <h3>Could Not Connect to MySQL</h3>
        <p>We couldn't connect to MySQL using common credentials. Here are alternative steps:</p>
        <ol>
            <li><strong>Manually Create User:</strong></li>
            <li>Open Laragon (right-click the icon in system tray)</li>
            <li>Select 'Database' to open the database manager</li>
            <li>Create a user with these credentials:
                <ul>
                    <li>Username: <strong>$new_username</strong></li>
                    <li>Password: <strong>$new_password</strong></li>
                    <li>Make sure to grant all privileges on <strong>$database_name</strong></li>
                </ul>
            </li>
            <li>Or use phpMyAdmin if available</li>
        </ol>
        <p>Once you've created the user manually, click below to update your config files:</p>
        <a href='?update_config=1' class='btn'>Update Configuration Files</a>
    </div>";
} else {
    // Check for the database
    $db_exists = false;
    $result = $conn->query("SHOW DATABASES LIKE '$database_name'");
    if ($result && $result->num_rows > 0) {
        $db_exists = true;
        echo "<div class='success'>Database '$database_name' exists.</div>";
    } else {
        echo "<div class='info'>Database '$database_name' doesn't exist. Creating it...</div>";
        if ($conn->query("CREATE DATABASE $database_name")) {
            echo "<div class='success'>Successfully created database '$database_name'.</div>";
            $db_exists = true;
        } else {
            echo "<div class='error'>Failed to create database: " . $conn->error . "</div>";
        }
    }
    
    if ($db_exists) {
        // Try to create the user
        $user_exists = false;
        $result = $conn->query("SELECT user FROM mysql.user WHERE user = '$new_username'");
        if ($result && $result->num_rows > 0) {
            $user_exists = true;
            echo "<div class='info'>User '$new_username' already exists.</div>";
        } else {
            echo "<div class='info'>Creating new user '$new_username'...</div>";
            
            // The syntax for creating users varies by MySQL version
            $success = false;
            
            // Try MySQL 8.0+ syntax first
            $create_user_sql = "CREATE USER '$new_username'@'localhost' IDENTIFIED BY '$new_password'";
            if ($conn->query($create_user_sql)) {
                $grant_sql = "GRANT ALL PRIVILEGES ON $database_name.* TO '$new_username'@'localhost'";
                if ($conn->query($grant_sql)) {
                    $conn->query("FLUSH PRIVILEGES");
                    echo "<div class='success'>Successfully created user and granted privileges.</div>";
                    $success = true;
                } else {
                    echo "<div class='error'>Failed to grant privileges: " . $conn->error . "</div>";
                }
            } else {
                // Try MySQL 5.7 and earlier syntax
                $alt_create_sql = "GRANT ALL PRIVILEGES ON $database_name.* TO '$new_username'@'localhost' IDENTIFIED BY '$new_password'";
                if ($conn->query($alt_create_sql)) {
                    $conn->query("FLUSH PRIVILEGES");
                    echo "<div class='success'>Successfully created user with older MySQL syntax.</div>";
                    $success = true;
                } else {
                    echo "<div class='error'>Failed to create user: " . $conn->error . "</div>";
                }
            }
            
            $user_exists = $success;
        }
        
        if ($user_exists) {
            echo "<div class='success'>
                <h3>MySQL User Ready</h3>
                <p>The MySQL user <strong>$new_username</strong> with password <strong>$new_password</strong> is set up and ready to use.</p>
                <p><a href='?update_config=1' class='btn'>Update Configuration Files</a></p>
            </div>";
        }
    }
}

// Update configuration files if requested
if (isset($_GET['update_config'])) {
    echo "<div class='info'><h3>Updating Configuration Files</h3>";
    
    $files_to_update = [
        'includes/db_connect.php' => [
            'search' => [
                '/\$db_user\s*=\s*"[^"]*"/i',
                '/\$db_pass\s*=\s*"[^"]*"/i'
            ],
            'replace' => [
                '$db_user = "' . $new_username . '"; // Updated by db_create_user.php',
                '$db_pass = "' . $new_password . '"; // Updated by db_create_user.php'
            ]
        ],
        'create_database.php' => [
            'search' => [
                '/\$db_user\s*=\s*"[^"]*"/i',
                '/\$db_pass\s*=\s*"[^"]*"/i'
            ],
            'replace' => [
                '$db_user = "' . $new_username . '"; // Updated by db_create_user.php',
                '$db_pass = "' . $new_password . '"; // Updated by db_create_user.php'
            ]
        ],
        'import_database.php' => [
            'search' => [
                '/\$db_user\s*=\s*"[^"]*"/i',
                '/\$db_pass\s*=\s*"[^"]*"/i'
            ],
            'replace' => [
                '$db_user = "' . $new_username . '"; // Updated by db_create_user.php',
                '$db_pass = "' . $new_password . '"; // Updated by db_create_user.php'
            ]
        ],
        'test.php' => [
            'search' => [
                '/new mysqli\("localhost", "[^"]*", "[^"]*"/i',
            ],
            'replace' => [
                'new mysqli("localhost", "' . $new_username . '", "' . $new_password . '"',
            ]
        ]
    ];
    
    foreach ($files_to_update as $file => $update_info) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if ($content !== false) {
                for ($i = 0; $i < count($update_info['search']); $i++) {
                    $content = preg_replace($update_info['search'][$i], $update_info['replace'][$i], $content, -1, $count);
                    if ($count > 0) {
                        file_put_contents($file, $content);
                        echo "<p class='success'>Updated $file with new credentials.</p>";
                    }
                }
            } else {
                echo "<p class='error'>Could not read $file.</p>";
            }
        } else {
            echo "<p class='error'>File not found: $file</p>";
        }
    }
    
    echo "</div>
    <div class='success'>
        <h3>Configuration Updated</h3>
        <p>Your database configuration has been updated to use:</p>
        <ul>
            <li>Username: <strong>$new_username</strong></li>
            <li>Password: <strong>$new_password</strong></li>
            <li>Database: <strong>$database_name</strong></li>
        </ul>
        <p>Try accessing your website now:</p>
        <p><a href='index.php' class='btn'>Go to Website</a></p>
    </div>";
}
?>

</body>
</html> 