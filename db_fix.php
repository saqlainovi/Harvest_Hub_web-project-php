<?php
// Enhanced MySQL Connection Troubleshooter & Fixer
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Fixer</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }
        h1, h2 { color: #2E7D32; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #dff0d8; color: #3c763d; padding: 10px; border-radius: 5px; }
        .warning { background-color: #fcf8e3; color: #8a6d3b; padding: 10px; border-radius: 5px; }
        .error { background-color: #f2dede; color: #a94442; padding: 10px; border-radius: 5px; }
        .info { background-color: #d9edf7; color: #31708f; padding: 10px; border-radius: 5px; }
        pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { 
            display: inline-block; padding: 8px 15px; background-color: #4CAF50; color: white; 
            border: none; border-radius: 4px; cursor: pointer; text-decoration: none; margin: 5px 0;
        }
        .btn:hover { background-color: #3e8e41; }
    </style>
</head>
<body>
    <h1>MySQL Database Connection Fixer</h1>
    
    <div class="section">
        <h2>System Information</h2>
        <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
        <p>Script Path: <?php echo __FILE__; ?></p>
    </div>
    
<?php
// Function to check MySQL service
function check_mysql_service() {
    try {
        $fp = @fsockopen('localhost', 3306, $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }
        fclose($fp);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to test connection with a password
function test_mysql_connection($password) {
    try {
        $conn = @new mysqli('localhost', 'root', $password);
        if ($conn->connect_error) {
            return ['success' => false, 'error' => $conn->connect_error];
        }
        return ['success' => true, 'conn' => $conn];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Check if MySQL service is running
if (!check_mysql_service()) {
    echo '<div class="error">
        <h3>MySQL Service Not Running</h3>
        <p>The MySQL service doesn\'t seem to be running on port 3306.</p>
        <p>Please start the MySQL service in Laragon before continuing:</p>
        <ol>
            <li>Right-click on the Laragon icon in the system tray</li>
            <li>Select "MySQL" → "Start"</li>
            <li>Or restart all services with "Start All"</li>
        </ol>
        <p><a href="db_fix.php" class="btn">Refresh After Starting MySQL</a></p>
    </div>';
} else {
    echo '<div class="success"><h3>MySQL Service Check</h3><p>MySQL service is running on port 3306.</p></div>';
    
    // Common MySQL passwords to try
    $passwords = [
        "" => "Empty password",
        "root" => "Root",
        "password" => "Password",
        "laragon" => "Laragon",
        "admin" => "Admin",
    ];
    
    echo '<div class="section">
        <h2>Testing MySQL Connection</h2>
        <table>
            <tr>
                <th>Password</th>
                <th>Description</th>
                <th>Result</th>
            </tr>';
    
    $working_password = null;
    $working_connection = null;
    
    foreach ($passwords as $password => $description) {
        $result = test_mysql_connection($password);
        if ($result['success']) {
            echo "<tr style='background-color: #dff0d8;'>
                <td>\"$password\"</td>
                <td>$description</td>
                <td>SUCCESS</td>
            </tr>";
            $working_password = $password;
            $working_connection = $result['conn'];
            break;
        } else {
            echo "<tr style='background-color: #f2dede;'>
                <td>\"$password\"</td>
                <td>$description</td>
                <td>FAILED: {$result['error']}</td>
            </tr>";
        }
    }
    
    echo '</table></div>';
    
    if ($working_connection) {
        echo '<div class="success">
            <h3>MySQL Connection Successful</h3>
            <p>Successfully connected to MySQL with password: "' . $working_password . '"</p>
        </div>';
        
        // Check if the database exists
        $db_exists = false;
        $result = $working_connection->query("SHOW DATABASES LIKE 'fresh_harvest'");
        if ($result && $result->num_rows > 0) {
            $db_exists = true;
            echo '<div class="success"><p>Database "fresh_harvest" exists.</p></div>';
        } else {
            echo '<div class="warning"><p>Database "fresh_harvest" does not exist.</p></div>';
        }
        
        // Actions based on database existence
        if (!$db_exists) {
            if (isset($_GET['create_db'])) {
                // Create the database
                if ($working_connection->query("CREATE DATABASE fresh_harvest")) {
                    echo '<div class="success"><p>Successfully created database "fresh_harvest".</p></div>';
                    $db_exists = true;
                } else {
                    echo '<div class="error"><p>Failed to create database: ' . $working_connection->error . '</p></div>';
                }
            } else {
                echo '<div class="info">
                    <p>Would you like to create the database?</p>
                    <a href="db_fix.php?create_db=1" class="btn">Create Database</a>
                </div>';
            }
        }
        
        // If database exists, check tables
        if ($db_exists) {
            $working_connection->select_db('fresh_harvest');
            $tables_result = $working_connection->query("SHOW TABLES");
            
            if ($tables_result) {
                $tables = [];
                while ($row = $tables_result->fetch_row()) {
                    $tables[] = $row[0];
                }
                
                if (count($tables) > 0) {
                    echo '<div class="success">
                        <h3>Tables Found</h3>
                        <p>Found ' . count($tables) . ' tables in database "fresh_harvest":</p>
                        <ul>';
                    foreach ($tables as $table) {
                        echo "<li>$table</li>";
                    }
                    echo '</ul></div>';
                    
                    // Check if users table exists and has records
                    if (in_array('users', $tables)) {
                        $users_result = $working_connection->query("SELECT COUNT(*) as count FROM users");
                        if ($users_result) {
                            $count = $users_result->fetch_assoc()['count'];
                            echo '<div class="info"><p>Users table contains ' . $count . ' records.</p></div>';
                        }
                    }
                } else {
                    echo '<div class="warning">
                        <h3>No Tables Found</h3>
                        <p>The database "fresh_harvest" exists but contains no tables.</p>
                        <p>Would you like to create basic tables?</p>
                        <a href="db_fix.php?create_tables=1" class="btn">Create Basic Tables</a>
                    </div>';
                }
            }
        }
        
        // Update database connection files
        if ($working_password !== "root") {
            echo '<div class="section">
                <h2>Update Configuration Files</h2>
                <p>The working password "' . $working_password . '" is different from what is currently in your configuration files.</p>
                <p>You need to update the following files with the correct password:</p>
                <ul>
                    <li><strong>includes/db_connect.php</strong> - Line 5 ($db_pass variable)</li>
                    <li><strong>create_database.php</strong> - Line 5 ($db_pass variable)</li>
                    <li><strong>import_database.php</strong> - Line 5 ($db_pass variable)</li>
                    <li><strong>test.php</strong> - Line 14 (mysqli constructor)</li>
                </ul>
                <p>Do you want to automatically update these files?</p>
                <a href="db_fix.php?update_config=' . urlencode($working_password) . '" class="btn">Update Configuration Files</a>
            </div>';
        }
        
        // If user requested to update config files
        if (isset($_GET['update_config'])) {
            $new_password = $_GET['update_config'];
            $files_to_update = [
                'includes/db_connect.php' => [
                    'pattern' => '/\$db_pass\s*=\s*"[^"]*"\s*;/i',
                    'replacement' => '$db_pass = "' . $new_password . '"; // Updated by db_fix.php'
                ],
                'create_database.php' => [
                    'pattern' => '/\$db_pass\s*=\s*"[^"]*"\s*;/i',
                    'replacement' => '$db_pass = "' . $new_password . '"; // Updated by db_fix.php'
                ],
                'import_database.php' => [
                    'pattern' => '/\$db_pass\s*=\s*"[^"]*"\s*;/i',
                    'replacement' => '$db_pass = "' . $new_password . '"; // Updated by db_fix.php'
                ],
                'test.php' => [
                    'pattern' => '/new mysqli\("localhost", "root", "[^"]*"/i',
                    'replacement' => 'new mysqli("localhost", "root", "' . $new_password . '"'
                ]
            ];
            
            echo '<div class="section"><h3>Configuration Update Results</h3><ul>';
            
            foreach ($files_to_update as $file => $update_info) {
                if (file_exists($file)) {
                    $content = file_get_contents($file);
                    if ($content !== false) {
                        $new_content = preg_replace($update_info['pattern'], $update_info['replacement'], $content);
                        if ($new_content !== $content) {
                            if (file_put_contents($file, $new_content) !== false) {
                                echo "<li class='success'>Successfully updated $file</li>";
                            } else {
                                echo "<li class='error'>Failed to write to $file</li>";
                            }
                        } else {
                            echo "<li class='warning'>No changes needed for $file</li>";
                        }
                    } else {
                        echo "<li class='error'>Could not read $file</li>";
                    }
                } else {
                    echo "<li class='error'>File not found: $file</li>";
                }
            }
            
            echo '</ul></div>';
        }
        
        // Create basic tables if requested
        if (isset($_GET['create_tables']) && $db_exists) {
            $working_connection->select_db('fresh_harvest');
            
            $tables_sql = [
                "users" => "CREATE TABLE users (
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
                )"
            ];
            
            echo '<div class="section"><h3>Table Creation Results</h3><ul>';
            
            foreach ($tables_sql as $table => $sql) {
                if ($working_connection->query($sql)) {
                    echo "<li class='success'>Successfully created table: $table</li>";
                } else {
                    echo "<li class='error'>Failed to create table $table: " . $working_connection->error . "</li>";
                }
            }
            
            echo '</ul></div>';
        }
        
        echo '<div class="section">
            <h2>Next Steps</h2>
            <p>Now that your database connection is working, you should be able to access your website.</p>
            <p><a href="index.php" class="btn">Go to Homepage</a></p>
        </div>';
    } else {
        echo '<div class="error">
            <h3>Could Not Connect to MySQL</h3>
            <p>None of the common passwords worked. You might need to:</p>
            <ol>
                <li>Check Laragon\'s MySQL configuration</li>
                <li>Reset the MySQL root password</li>
                <li>Add a custom password to the list and try again</li>
            </ol>
        </div>';
        
        echo '<div class="section">
            <h2>MySQL Password Reset Instructions</h2>
            <p>To reset your MySQL root password in Laragon:</p>
            <ol>
                <li>Stop MySQL in Laragon (right-click → MySQL → Stop)</li>
                <li>Open a terminal from Laragon (right-click → Terminal)</li>
                <li>Run the following commands:</li>
            </ol>
            <pre>
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
mysqld --console --skip-grant-tables --shared-memory
            </pre>
            <p>In a new terminal window, run:</p>
            <pre>
cd C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
mysql -u root
            </pre>
            <p>Then in the MySQL prompt, run these commands:</p>
            <pre>
use mysql;
UPDATE user SET authentication_string='' WHERE User='root';
flush privileges;
quit
            </pre>
            <p>Now you can stop the mysqld process and start MySQL normally in Laragon.</p>
            <p>Then try connecting with an empty password.</p>
        </div>';
    }
}
?>

</body>
</html> 