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

echo "<h1>Products Table Structure</h1>";

// Check if products table exists
$result = $conn->query("SHOW TABLES LIKE 'products'");
if ($result->num_rows == 0) {
    echo "<p>Products table doesn't exist. Creating it now...</p>";
    
    // Create products table
    $sql = "CREATE TABLE products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        stock_quantity INT NOT NULL DEFAULT 0,
        category_id INT,
        seller_id INT,
        image_path VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<p>Products table created successfully.</p>";
    } else {
        echo "<p>Error creating products table: " . $conn->error . "</p>";
    }
}

// Display products table structure
$result = $conn->query("DESCRIBE products");
echo "<h2>Table Structure:</h2>";
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

// Check for existing products
$result = $conn->query("SELECT * FROM products LIMIT 10");
echo "<h2>Existing Products:</h2>";

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr>";
    $first = $result->fetch_assoc();
    foreach ($first as $key => $value) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    // Reset pointer
    $result->data_seek(0);
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            if ($value === NULL) {
                echo "<td><em>NULL</em></td>";
            } else {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

// Scan the images directory
echo "<h2>Available Images:</h2>";
$imgDir = "../../img"; // Path relative to this script
$imgWebDir = "/asraf idp2/img"; // Web path to images

if (is_dir($imgDir)) {
    $images = scandir($imgDir);
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    foreach ($images as $image) {
        if ($image != "." && $image != ".." && !is_dir($imgDir . "/" . $image)) {
            $imgPath = $imgWebDir . "/" . $image;
            echo "<div style='text-align: center; margin: 10px; border: 1px solid #ccc; padding: 10px;'>";
            echo "<img src='" . $imgPath . "' style='max-width: 150px; max-height: 150px;'><br>";
            echo "<span>" . $image . "</span>";
            echo "</div>";
        }
    }
    echo "</div>";
} else {
    echo "<p>Image directory not found or accessible: $imgDir</p>";
}

// Form to add images to products
echo "<h2>Add Images to Products</h2>";
echo "<form method='post' action='link_product_images.php'>";
echo "<p>Select a product and assign an image:</p>";

// Get all products for dropdown
$products = $conn->query("SELECT product_id, name FROM products ORDER BY name");
if ($products && $products->num_rows > 0) {
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='product'>Select Product:</label><br>";
    echo "<select name='product_id' id='product' required>";
    echo "<option value=''>-- Select a Product --</option>";
    while ($product = $products->fetch_assoc()) {
        echo "<option value='" . $product['product_id'] . "'>" . htmlspecialchars($product['name']) . "</option>";
    }
    echo "</select>";
    echo "</div>";
    
    // Images for dropdown
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='image'>Select Image:</label><br>";
    echo "<select name='image_path' id='image' required>";
    echo "<option value=''>-- Select an Image --</option>";
    if (is_dir($imgDir)) {
        foreach ($images as $image) {
            if ($image != "." && $image != ".." && !is_dir($imgDir . "/" . $image)) {
                $imgPath = $imgWebDir . "/" . $image;
                echo "<option value='" . $imgPath . "'>" . $image . "</option>";
            }
        }
    }
    echo "</select>";
    echo "</div>";
    
    echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;'>Link Image to Product</button>";
} else {
    echo "<p>No products available. Please add products first.</p>";
}
echo "</form>";

// Form to add multiple demo products
echo "<h2>Add Demo Products with Images</h2>";
echo "<form method='post' action='add_demo_products.php'>";
echo "<p>This will create sample fruit products and assign random images to them:</p>";
echo "<button type='submit' style='padding: 10px 15px; background: #2196F3; color: white; border: none; cursor: pointer;'>Create Demo Products</button>";
echo "</form>";

// Close connection
$conn->close();
?> 