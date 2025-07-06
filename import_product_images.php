<?php
// Script to import product images and update the database
require_once 'includes/config.php';

// Define paths
$source_dir = __DIR__ . '/img/';
$target_dir = __DIR__ . '/images/fruits/';

// Create target directory if it doesn't exist
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0755, true);
    echo "<p>Created directory: images/fruits/</p>";
}

// Function to get file extension
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Function to check if file is an image
function is_image($filepath) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = get_file_extension($filepath);
    return in_array($extension, $allowed_extensions);
}

// Check if fruits table exists
$table_check = $conn->query("SHOW TABLES LIKE 'fruits'");
$fruits_table_exists = $table_check && $table_check->num_rows > 0;

if (!$fruits_table_exists) {
    // Create fruits table if it doesn't exist
    $create_fruits_table = "CREATE TABLE IF NOT EXISTS fruits (
        fruit_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price_per_kg DECIMAL(10,2) NOT NULL,
        stock_quantity INT DEFAULT 0,
        is_organic TINYINT(1) DEFAULT 0,
        is_available TINYINT(1) DEFAULT 1,
        seller_id INT NOT NULL,
        category_id INT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_fruits_table)) {
        echo "<p>Created fruits table successfully!</p>";
        $fruits_table_exists = true;
    } else {
        echo "<p>Error creating fruits table: " . $conn->error . "</p>";
    }
}

// Check if seller_profiles table exists
$table_check = $conn->query("SHOW TABLES LIKE 'seller_profiles'");
$seller_table_exists = $table_check && $table_check->num_rows > 0;

if (!$seller_table_exists) {
    // Create a simple seller_profiles table
    $create_seller_table = "CREATE TABLE IF NOT EXISTS seller_profiles (
        seller_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        farm_name VARCHAR(100) NOT NULL,
        description TEXT,
        location VARCHAR(100),
        is_verified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_seller_table)) {
        echo "<p>Created seller_profiles table successfully!</p>";
        $seller_table_exists = true;
        
        // Add a default seller
        $insert_seller = "INSERT INTO seller_profiles (user_id, farm_name, description, location, is_verified)
                          VALUES (1, 'Demo Farm', 'This is a demo farm for testing', 'Bangladesh', 1)";
        if ($conn->query($insert_seller)) {
            echo "<p>Added a default seller for testing.</p>";
        }
    } else {
        echo "<p>Error creating seller_profiles table: " . $conn->error . "</p>";
    }
}

// Check if categories table exists
$table_check = $conn->query("SHOW TABLES LIKE 'categories'");
$categories_table_exists = $table_check && $table_check->num_rows > 0;

if (!$categories_table_exists) {
    // Create a categories table
    $create_categories_table = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        description TEXT
    )";
    
    if ($conn->query($create_categories_table)) {
        echo "<p>Created categories table successfully!</p>";
        $categories_table_exists = true;
        
        // Add some default categories
        $default_categories = [
            ['name' => 'Tropical Fruits', 'description' => 'Fruits grown in tropical climates'],
            ['name' => 'Citrus Fruits', 'description' => 'Fruits high in citric acid'],
            ['name' => 'Berries', 'description' => 'Small, pulpy fruits'],
            ['name' => 'Melons', 'description' => 'Large, fleshy fruits with seeds in the middle'],
            ['name' => 'Stone Fruits', 'description' => 'Fruits with large seed/pit inside']
        ];
        
        foreach ($default_categories as $category) {
            $insert_category = "INSERT INTO categories (name, description) VALUES ('{$category['name']}', '{$category['description']}')";
            $conn->query($insert_category);
        }
        
        echo "<p>Added default categories.</p>";
    } else {
        echo "<p>Error creating categories table: " . $conn->error . "</p>";
    }
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Import Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background-color: #dff0d8; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .error { background-color: #f2dede; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .image-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
        .image-card { border: 1px solid #ddd; border-radius: 4px; padding: 10px; }
        .image-card img { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; }
        form { margin-top: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #45a049; }
    </style>
</head>
<body>
    <h1>Import Product Images</h1>';

// Handle form submission to add product with image
if (isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    $is_organic = isset($_POST['is_organic']) ? 1 : 0;
    $image = $conn->real_escape_string($_POST['image']);
    
    // Get the first seller
    $seller_id = 1;
    $seller_result = $conn->query("SELECT seller_id FROM seller_profiles LIMIT 1");
    if ($seller_result && $seller_result->num_rows > 0) {
        $seller = $seller_result->fetch_assoc();
        $seller_id = $seller['seller_id'];
    }
    
    // Insert the product
    $insert_sql = "INSERT INTO fruits (name, description, price_per_kg, stock_quantity, is_organic, 
                                       seller_id, category_id, image, is_available) 
                  VALUES ('$name', '$description', $price, $stock, $is_organic, 
                          $seller_id, $category_id, '$image', 1)";
    
    if ($conn->query($insert_sql)) {
        echo "<div class='success'>Product added successfully!</div>";
    } else {
        echo "<div class='error'>Error adding product: " . $conn->error . "</div>";
    }
}

// Scan for image files in source directory
$images = [];
if (is_dir($source_dir)) {
    $files = scandir($source_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($source_dir . $file) && is_image($source_dir . $file)) {
            // Copy file to target directory if it doesn't exist
            if (!file_exists($target_dir . $file)) {
                copy($source_dir . $file, $target_dir . $file);
                echo "<p>Copied {$file} to images/fruits/</p>";
            }
            $images[] = $file;
        }
    }
} else {
    echo "<div class='error'>Source directory not found: $source_dir</div>";
}

// Display image grid form
if (!empty($images)) {
    // Get categories for dropdown
    $categories = [];
    $categories_result = $conn->query("SELECT * FROM categories ORDER BY name");
    if ($categories_result) {
        while ($row = $categories_result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    echo "<h2>Available Images</h2>";
    echo "<p>Click on an image to add it as a new product:</p>";
    echo "<div class='image-grid'>";
    
    foreach ($images as $image) {
        echo "<div class='image-card'>";
        echo "<img src='images/fruits/{$image}' alt='{$image}'>";
        echo "<p>{$image}</p>";
        echo "<button onclick='selectImage(\"{$image}\")'>Select</button>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Add product form
    echo "<div id='product-form' style='display:none; margin-top: 30px;'>";
    echo "<h2>Add New Product</h2>";
    echo "<form method='post'>";
    echo "<input type='hidden' id='selected_image' name='image' value=''>";
    echo "<img id='preview-image' src='' alt='Selected Image' style='max-width: 200px; margin-bottom: 20px;'>";
    
    echo "<label for='name'>Fruit Name:</label>";
    echo "<input type='text' id='name' name='name' required>";
    
    echo "<label for='description'>Description:</label>";
    echo "<textarea id='description' name='description' rows='4'></textarea>";
    
    echo "<label for='price'>Price per KG (BDT):</label>";
    echo "<input type='number' id='price' name='price' step='0.01' min='0' required>";
    
    echo "<label for='stock'>Stock (KG):</label>";
    echo "<input type='number' id='stock' name='stock' min='0' required>";
    
    echo "<label for='category_id'>Category:</label>";
    echo "<select id='category_id' name='category_id' required>";
    foreach ($categories as $category) {
        echo "<option value='{$category['category_id']}'>{$category['name']}</option>";
    }
    echo "</select>";
    
    echo "<label for='is_organic'>";
    echo "<input type='checkbox' id='is_organic' name='is_organic' value='1' style='width:auto;'> Organic</label>";
    
    echo "<button type='submit' name='add_product'>Add Product</button>";
    echo "</form>";
    echo "</div>";
    
    // JavaScript for image selection
    echo "<script>
    function selectImage(imageName) {
        document.getElementById('selected_image').value = imageName;
        document.getElementById('preview-image').src = 'images/fruits/' + imageName;
        document.getElementById('product-form').style.display = 'block';
        document.getElementById('name').value = imageName.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
        window.scrollTo(0, document.getElementById('product-form').offsetTop);
    }
    </script>";
} else {
    echo "<p>No images found in source directory.</p>";
}

// Show existing products
echo "<h2>Existing Products</h2>";
$products_result = $conn->query("SELECT f.*, c.name as category_name 
                                FROM fruits f 
                                LEFT JOIN categories c ON f.category_id = c.category_id 
                                ORDER BY f.created_at DESC 
                                LIMIT 10");

if ($products_result && $products_result->num_rows > 0) {
    echo "<table border='1' cellpadding='5' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Organic</th></tr>";
    
    while ($product = $products_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$product['fruit_id']}</td>";
        echo "<td>";
        if (!empty($product['image']) && file_exists($target_dir . $product['image'])) {
            echo "<img src='images/fruits/{$product['image']}' style='width: 50px; height: 50px; object-fit: cover;'>";
        } else {
            echo "No image";
        }
        echo "</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['category_name']}</td>";
        echo "<td>BDT {$product['price_per_kg']}</td>";
        echo "<td>{$product['stock_quantity']} kg</td>";
        echo "<td>" . ($product['is_organic'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No products found in the database.</p>";
}

echo "</body></html>";
?> 