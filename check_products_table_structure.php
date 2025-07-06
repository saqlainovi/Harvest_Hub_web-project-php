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

echo "<h1>Products Table Structure Check</h1>";

// Check if products table exists
$result = $conn->query("SHOW TABLES LIKE 'products'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>ERROR: The products table does not exist in the database.</p>";
    echo "<h2>Creating Products Table</h2>";
    
    // Create products table if it doesn't exist
    $create_sql = "CREATE TABLE products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        seller_id INT,
        category VARCHAR(100),
        image_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_sql) === TRUE) {
        echo "<p style='color: green;'>Products table created successfully.</p>";
    } else {
        echo "<p style='color: red;'>Error creating products table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>Products table exists.</p>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE products");
    
    if ($structure) {
        echo "<h2>Products Table Structure</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
            <th style='padding: 8px; text-align: left;'>Field</th>
            <th style='padding: 8px; text-align: left;'>Type</th>
            <th style='padding: 8px; text-align: left;'>Null</th>
            <th style='padding: 8px; text-align: left;'>Key</th>
            <th style='padding: 8px; text-align: left;'>Default</th>
            <th style='padding: 8px; text-align: left;'>Extra</th>
        </tr>";
        
        $has_image_field = false;
        
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $row['Field'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Type'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Null'] . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Key'] . "</td>";
            echo "<td style='padding: 8px;'>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td style='padding: 8px;'>" . $row['Extra'] . "</td>";
            echo "</tr>";
            
            if ($row['Field'] == 'image_path') {
                $has_image_field = true;
                
                // Check if the image_path field is too short
                if (strpos($row['Type'], 'varchar') !== false) {
                    preg_match('/varchar\((\d+)\)/', $row['Type'], $matches);
                    if (isset($matches[1]) && (int)$matches[1] < 255) {
                        echo "<tr style='background-color: #ffdddd;'>";
                        echo "<td colspan='6' style='padding: 8px; color: red;'>WARNING: image_path field length is " . $matches[1] . ", which may be too short for long paths. Consider increasing to VARCHAR(255).</td>";
                        echo "</tr>";
                    }
                }
            }
        }
        
        echo "</table>";
        
        if (!$has_image_field) {
            echo "<p style='color: red;'>ERROR: The products table is missing the image_path field.</p>";
            echo "<h2>Adding Image Path Field</h2>";
            
            // Add image_path field if it doesn't exist
            $alter_sql = "ALTER TABLE products ADD COLUMN image_path VARCHAR(255)";
            
            if ($conn->query($alter_sql) === TRUE) {
                echo "<p style='color: green;'>Added image_path field to products table.</p>";
            } else {
                echo "<p style='color: red;'>Error adding image_path field: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Error getting table structure: " . $conn->error . "</p>";
    }
    
    // Check for sample data
    $data_check = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $data_check->fetch_assoc();
    
    echo "<h2>Data Check</h2>";
    echo "<p>Number of products in database: <strong>" . $row['count'] . "</strong></p>";
    
    if ($row['count'] == 0) {
        echo "<p style='color: orange;'>The products table is empty. Consider adding sample data.</p>";
        
        echo "<h3>Add Sample Products</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='add_samples' value='yes'>";
        echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;'>Add Sample Products</button>";
        echo "</form>";
        
        // Add sample products if requested
        if (isset($_POST['add_samples']) && $_POST['add_samples'] == 'yes') {
            $sample_products = [
                ['Apple', 'Fresh red apples', 2.99, 100, 1, 'Fruits'],
                ['Banana', 'Yellow bananas', 1.99, 150, 1, 'Fruits'],
                ['Orange', 'Juicy oranges', 3.49, 80, 2, 'Fruits'],
                ['Grapes', 'Sweet grapes', 4.99, 60, 2, 'Fruits'],
                ['Strawberry', 'Delicious strawberries', 5.99, 40, 3, 'Fruits']
            ];
            
            $insert_count = 0;
            
            foreach ($sample_products as $product) {
                $insert_sql = "INSERT INTO products (name, description, price, stock_quantity, seller_id, category) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ssdiss", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5]);
                
                if ($stmt->execute()) {
                    $insert_count++;
                }
                
                $stmt->close();
            }
            
            echo "<p style='color: green;'>Added " . $insert_count . " sample products.</p>";
            echo "<p>Refresh the page to see the updated count.</p>";
        }
    }
    
    // Check image paths
    $image_check = $conn->query("SELECT COUNT(*) as count FROM products WHERE image_path IS NOT NULL AND image_path != ''");
    $row = $image_check->fetch_assoc();
    
    echo "<p>Products with images: <strong>" . $row['count'] . "</strong></p>";
    
    if ($row['count'] == 0) {
        echo "<p style='color: orange;'>No products have images assigned. You need to assign images to products.</p>";
    } else {
        // Sample of image paths
        $image_samples = $conn->query("SELECT product_id, name, image_path FROM products WHERE image_path IS NOT NULL AND image_path != '' LIMIT 5");
        
        if ($image_samples->num_rows > 0) {
            echo "<h3>Sample Image Paths</h3>";
            echo "<ul>";
            while ($row = $image_samples->fetch_assoc()) {
                echo "<li><strong>" . htmlspecialchars($row['name']) . "</strong>: " . htmlspecialchars($row['image_path']) . "</li>";
            }
            echo "</ul>";
        }
    }
}

// Check if img directory exists
echo "<h2>Image Directory Check</h2>";
$img_dir = 'img';

if (is_dir($img_dir)) {
    echo "<p style='color: green;'>Image directory exists.</p>";
    
    // Count image files
    $files = scandir($img_dir);
    $image_count = 0;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && !is_dir($img_dir . '/' . $file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $image_count++;
            }
        }
    }
    
    echo "<p>Number of image files in directory: <strong>" . $image_count . "</strong></p>";
    
    if ($image_count == 0) {
        echo "<p style='color: orange;'>No image files found in the img directory. You need to add image files.</p>";
    }
} else {
    echo "<p style='color: red;'>Image directory does not exist. You need to create a directory named 'img' in the root of your project.</p>";
    
    // Create button
    echo "<form method='post'>";
    echo "<input type='hidden' name='create_dir' value='yes'>";
    echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;'>Create img Directory</button>";
    echo "</form>";
    
    // Create directory if requested
    if (isset($_POST['create_dir']) && $_POST['create_dir'] == 'yes') {
        if (mkdir($img_dir, 0777, true)) {
            echo "<p style='color: green;'>Successfully created img directory.</p>";
        } else {
            echo "<p style='color: red;'>Failed to create img directory. Please check permissions.</p>";
        }
    }
}

// Suggest next steps
echo "<h2>Suggested Next Steps</h2>";
echo "<ol>";
echo "<li>Ensure the products table exists and has the correct structure</li>";
echo "<li>Make sure the image_path field is present and large enough (VARCHAR(255))</li>";
echo "<li>Check that you have products in the database</li>";
echo "<li>Verify that the img directory exists and contains image files</li>";
echo "<li>Make sure product records have valid image paths pointing to existing files</li>";
echo "<li>Use '/asraf idp2/img/filename.ext' format for image paths</li>";
echo "</ol>";

// Navigation
echo "<div style='margin-top: 30px;'>";
echo "<a href='update_product_images.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Update Image Paths</a>";
echo "<a href='check_products_images.php' style='padding: 10px 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Check Images</a>";
echo "<a href='create_test_images.php' style='padding: 10px 15px; background: #ff9800; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Create Test Images</a>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";

// Close connection
$conn->close();
?> 