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

echo "<h1>Database Health Check</h1>";

// Function to display table info
function displayTableInfo($conn, $table) {
    echo "<h2>{$table} Table Check</h2>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>ERROR: The {$table} table does not exist in the database.</p>";
        return false;
    }
    
    echo "<p style='color: green;'>{$table} table exists.</p>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE {$table}");
    
    if ($structure) {
        echo "<h3>Table Structure</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
            <th style='padding: 8px; text-align: left;'>Field</th>
            <th style='padding: 8px; text-align: left;'>Type</th>
            <th style='padding: 8px; text-align: left;'>Null</th>
            <th style='padding: 8px; text-align: left;'>Key</th>
            <th style='padding: 8px; text-align: left;'>Default</th>
            <th style='padding: 8px; text-align: left;'>Extra</th>
        </tr>";
        
        $fields = [];
        
        while ($row = $structure->fetch_assoc()) {
            $fields[] = $row['Field'];
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $row['Field'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Type'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Null'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Key'] . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        return $fields;
    } else {
        echo "<p style='color: red;'>Error getting table structure: " . $conn->error . "</p>";
        return false;
    }
}

// Function to check for data issues
function checkTableData($conn, $table, $fields) {
    // Count records
    $count_result = $conn->query("SELECT COUNT(*) as count FROM {$table}");
    $count = $count_result->fetch_assoc()['count'];
    
    echo "<h3>Data Check</h3>";
    echo "<p>Number of records in {$table}: <strong>{$count}</strong></p>";
    
    if ($count == 0) {
        echo "<p style='color: orange;'>The {$table} table is empty. This may cause issues if your application expects data.</p>";
        return;
    }
    
    // Check for NULL values in important fields
    foreach ($fields as $field) {
        // Skip fields that can be NULL
        if (in_array($field, ['description', 'image_path', 'updated_at'])) {
            continue;
        }
        
        $null_check = $conn->query("SELECT COUNT(*) as count FROM {$table} WHERE {$field} IS NULL");
        $null_count = $null_check->fetch_assoc()['count'];
        
        if ($null_count > 0) {
            echo "<p style='color: orange;'>WARNING: Found {$null_count} records with NULL values in {$field} field.</p>";
        }
    }
    
    // Specific checks for products table
    if ($table == 'products' && in_array('image_path', $fields)) {
        // Check for products with missing images
        $missing_images = $conn->query("SELECT COUNT(*) as count FROM products WHERE image_path IS NULL OR image_path = ''");
        $missing_count = $missing_images->fetch_assoc()['count'];
        
        if ($missing_count > 0) {
            echo "<p style='color: orange;'>WARNING: Found {$missing_count} products with missing image paths.</p>";
        }
        
        // Check image path format
        $path_check = $conn->query("SELECT product_id, name, image_path FROM products WHERE image_path IS NOT NULL AND image_path != '' LIMIT 5");
        
        if ($path_check->num_rows > 0) {
            echo "<h4>Sample Image Paths</h4>";
            echo "<ul>";
            
            while ($row = $path_check->fetch_assoc()) {
                $path = $row['image_path'];
                $path_status = '';
                
                // Check if path starts with /asraf idp2/img/
                if (strpos($path, '/asraf idp2/img/') !== 0) {
                    $path_status = " <span style='color: red;'>(Incorrect format - should start with /asraf idp2/img/)</span>";
                }
                
                echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong>: " . htmlspecialchars($path) . $path_status . "</li>";
            }
            
            echo "</ul>";
            
            // Count products with incorrect path format
            $incorrect_format = $conn->query("SELECT COUNT(*) as count FROM products WHERE image_path IS NOT NULL AND image_path != '' AND image_path NOT LIKE '/asraf idp2/img/%'");
            $incorrect_count = $incorrect_format->fetch_assoc()['count'];
            
            if ($incorrect_count > 0) {
                echo "<p style='color: red;'>ERROR: Found {$incorrect_count} products with incorrect image path format. Paths should start with '/asraf idp2/img/'.</p>";
                
                // Add button to fix paths
                echo "<form method='post'>";
                echo "<input type='hidden' name='fix_paths' value='yes'>";
                echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;'>Fix Image Paths</button>";
                echo "</form>";
                
                // Fix paths if requested
                if (isset($_POST['fix_paths']) && $_POST['fix_paths'] == 'yes') {
                    $fix_result = $conn->query("SELECT product_id, image_path FROM products WHERE image_path IS NOT NULL AND image_path != '' AND image_path NOT LIKE '/asraf idp2/img/%'");
                    
                    $fixed_count = 0;
                    
                    while ($row = $fix_result->fetch_assoc()) {
                        $productId = $row['product_id'];
                        $currentPath = $row['image_path'];
                        $filename = basename($currentPath);
                        $newPath = "/asraf idp2/img/{$filename}";
                        
                        $stmt = $conn->prepare("UPDATE products SET image_path = ? WHERE product_id = ?");
                        $stmt->bind_param("si", $newPath, $productId);
                        
                        if ($stmt->execute()) {
                            $fixed_count++;
                        }
                        
                        $stmt->close();
                    }
                    
                    echo "<p style='color: green;'>Fixed {$fixed_count} image paths. Please refresh to see the changes.</p>";
                }
            }
        }
    }
}

// Check img directory
echo "<h2>Image Directory Check</h2>";
$img_dir = 'img';

if (is_dir($img_dir)) {
    echo "<p style='color: green;'>Image directory exists.</p>";
    
    // Count image files
    $files = scandir($img_dir);
    $image_count = 0;
    $image_files = [];
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($img_dir . '/' . $file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $image_count++;
                $image_files[] = $file;
            }
        }
    }
    
    echo "<p>Number of image files in directory: <strong>{$image_count}</strong></p>";
    
    if ($image_count == 0) {
        echo "<p style='color: orange;'>WARNING: No image files found in the img directory. You need to add image files.</p>";
    } else {
        // Display sample images
        echo "<h3>Sample Images</h3>";
        echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
        
        $displayed = 0;
        foreach ($image_files as $file) {
            if ($displayed >= 5) {
                break;
            }
            
            echo "<div style='text-align: center; margin: 5px; border: 1px solid #ccc; padding: 5px;'>";
            echo "<img src='/asraf idp2/img/{$file}' style='max-width: 100px; max-height: 100px;'><br>";
            echo "<small>{$file}</small>";
            echo "</div>";
            
            $displayed++;
        }
        
        if ($image_count > 5) {
            echo "<div style='margin: 5px; padding: 5px;'>...and " . ($image_count - 5) . " more</div>";
        }
        
        echo "</div>";
    }
} else {
    echo "<p style='color: red;'>ERROR: Image directory does not exist. You need to create a directory named 'img' in the root of your project.</p>";
}

// Check products table
$product_fields = displayTableInfo($conn, 'products');

if ($product_fields) {
    checkTableData($conn, 'products', $product_fields);
}

// Check permissions
echo "<h2>File Permission Check</h2>";
$permission_issues = false;

// Check img directory permissions
if (is_dir($img_dir)) {
    $perms = substr(sprintf('%o', fileperms($img_dir)), -4);
    echo "<p>img directory permissions: {$perms}</p>";
    
    if (!is_writable($img_dir)) {
        echo "<p style='color: red;'>ERROR: The img directory is not writable. Please set proper permissions.</p>";
        $permission_issues = true;
    }
}

if (!$permission_issues) {
    echo "<p style='color: green;'>No permission issues detected.</p>";
}

// Server information
echo "<h2>Server Information</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</li>";
echo "<li><strong>Script Path:</strong> " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "</li>";
echo "</ul>";

// Conclusion and recommendations
echo "<h2>Conclusion</h2>";
echo "<p>Based on the checks, here are the recommended actions:</p>";
echo "<ol>";

if (!is_dir($img_dir)) {
    echo "<li style='color: red;'>Create the 'img' directory in the root of your project.</li>";
} else if ($image_count == 0) {
    echo "<li style='color: red;'>Add image files to the 'img' directory.</li>";
}

if ($product_fields && !in_array('image_path', $product_fields)) {
    echo "<li style='color: red;'>Add 'image_path' column to the products table.</li>";
} else if (isset($incorrect_count) && $incorrect_count > 0) {
    echo "<li style='color: red;'>Fix the image paths in your database to use the format '/asraf idp2/img/filename.ext'.</li>";
} else if (isset($missing_count) && $missing_count > 0) {
    echo "<li style='color: orange;'>Assign images to products that currently don't have images.</li>";
}

echo "</ol>";

// Navigation
echo "<div style='margin-top: 30px;'>";
echo "<a href='check_products_table_structure.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Check Products Table</a>";
echo "<a href='download_fruit_images.php' style='padding: 10px 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Download Fruit Images</a>";
echo "<a href='update_product_images.php' style='padding: 10px 15px; background: #ff9800; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Update Image Paths</a>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";

// Close connection
$conn->close();
?> 