<?php
// Script to fix product image paths
require_once 'includes/config.php';

// Function to create an image with the name of the product
function create_product_image($product_name, $number = 1) {
    $img_dir = __DIR__ . '/img';
    
    // Create img directory if it doesn't exist
    if (!is_dir($img_dir)) {
        mkdir($img_dir, 0755, true);
    }
    
    // Sanitize product name for filename
    $filename = preg_replace('/[^a-zA-Z0-9]/', '_', $product_name);
    $filename = strtolower($filename);
    
    // Create image path
    $image_path = "img/{$filename}{$number}.jpg";
    $full_path = "{$img_dir}/{$filename}{$number}.jpg";
    
    // Only create the image if it doesn't exist
    if (!file_exists($full_path)) {
        // Create a colored rectangle image
        $img = imagecreatetruecolor(400, 300);
        
        // Generate a color based on the product name
        $color_seed = crc32($product_name) % 240;
        $r = ($color_seed + 50) % 240 + 15;
        $g = ($color_seed + 100) % 240 + 15;
        $b = ($color_seed + 150) % 240 + 15;
        
        $bg_color = imagecolorallocate($img, $r, $g, $b);
        $text_color = imagecolorallocate($img, 255, 255, 255);
        
        // Fill the background
        imagefilledrectangle($img, 0, 0, 399, 299, $bg_color);
        
        // Add text
        $text = $product_name;
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        
        // If text is too long, truncate it
        if ($text_width > 380) {
            $text = substr($text, 0, 30) . "...";
            $text_width = imagefontwidth($font_size) * strlen($text);
        }
        
        imagestring($img, $font_size, 
                  (400 - $text_width) / 2, 
                  (300 - $text_height) / 2, 
                  $text, $text_color);
        
        // Save the image
        imagejpeg($img, $full_path, 90);
        imagedestroy($img);
    }
    
    return $image_path;
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Fix Product Images</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Fix Product Images</h1>';

// Get all fruits and create images for them
$fruits_updated = 0;
$fruit_sql = "SELECT fruit_id, name FROM fruits";
$fruit_result = $conn->query($fruit_sql);

if ($fruit_result && $fruit_result->num_rows > 0) {
    echo '<div class="info">
        <h2>Fixing Fruit Images</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>New Image Path</th>
            </tr>';
    
    while ($fruit = $fruit_result->fetch_assoc()) {
        $image_path = create_product_image($fruit['name']);
        
        // Update the fruit's image path in the database
        $update_sql = "UPDATE fruits SET image = ? WHERE fruit_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $image_path, $fruit['fruit_id']);
        
        if ($stmt->execute()) {
            $fruits_updated++;
            echo '<tr>
                <td>' . $fruit['fruit_id'] . '</td>
                <td>' . htmlspecialchars($fruit['name']) . '</td>
                <td>' . htmlspecialchars($image_path) . '</td>
            </tr>';
        }
        
        $stmt->close();
    }
    
    echo '</table>
    </div>';
}

// Check for agricultural_products table and update those too
$agri_updated = 0;
$table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");

if ($table_check && $table_check->num_rows > 0) {
    $agri_sql = "SELECT product_id, name FROM agricultural_products";
    $agri_result = $conn->query($agri_sql);
    
    if ($agri_result && $agri_result->num_rows > 0) {
        echo '<div class="info">
            <h2>Fixing Agricultural Product Images</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>New Image Path</th>
                </tr>';
        
        while ($product = $agri_result->fetch_assoc()) {
            $image_path = create_product_image($product['name']);
            
            // Update the product's image path in the database
            $update_sql = "UPDATE agricultural_products SET image = ? WHERE product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('si', $image_path, $product['product_id']);
            
            if ($stmt->execute()) {
                $agri_updated++;
                echo '<tr>
                    <td>' . $product['product_id'] . '</td>
                    <td>' . htmlspecialchars($product['name']) . '</td>
                    <td>' . htmlspecialchars($image_path) . '</td>
                </tr>';
            }
            
            $stmt->close();
        }
        
        echo '</table>
        </div>';
    }
}

// Show summary
echo '<div class="success">
    <h2>Summary</h2>
    <p>Updated ' . $fruits_updated . ' fruit images</p>
    <p>Updated ' . $agri_updated . ' agricultural product images</p>
</div>';

echo '<div>
    <a href="index.php" class="btn">Return to Homepage</a>
    <a href="pages/fruits.php" class="btn">View Fruits Page</a>
    <a href="pages/agricultural_products.php" class="btn">View Agricultural Products</a>
</div>';

echo '</body>
</html>'; 