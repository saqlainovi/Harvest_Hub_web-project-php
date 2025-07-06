<?php
// Import product images database script
require_once 'includes/config.php';

// Define the SQL file to import
$sql_file = 'database/product_images.sql';

// Function to execute multi-query SQL file
function execute_sql_file($conn, $file) {
    if (!file_exists($file)) {
        return "SQL file not found: $file";
    }
    
    $sql = file_get_contents($file);
    if (!$sql) {
        return "Could not read SQL file: $file";
    }
    
    // Split SQL by delimiter statements
    $delimiter = ';';
    $sql = str_replace('DELIMITER //', '', $sql);
    $sql = str_replace('DELIMITER ;', '', $sql);
    $sql = str_replace('//', ';', $sql);
    
    // Execute individual queries
    $queries = explode(';', $sql);
    $results = [];
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        if ($conn->query($query)) {
            $results[] = "Success: " . substr($query, 0, 50) . "...";
        } else {
            $results[] = "Error: " . $conn->error . " in query: " . substr($query, 0, 50) . "...";
        }
    }
    
    return $results;
}

// Create dummy image files if they don't exist
function create_dummy_images() {
    $img_dir = __DIR__ . '/img';
    if (!is_dir($img_dir)) {
        mkdir($img_dir, 0755, true);
    }
    
    $fruits = [
        'apple', 'orange', 'banana', 'mango', 'strawberry', 
        'grape', 'pineapple', 'watermelon', 'kiwi', 'peach',
        'plum', 'cherry', 'papaya', 'guava', 'lychee'
    ];
    
    $created = 0;
    foreach ($fruits as $fruit) {
        for ($i = 1; $i <= 2; $i++) {
            $filename = "{$img_dir}/{$fruit}{$i}.jpg";
            if (!file_exists($filename)) {
                // Create a colored rectangle image
                $img = imagecreatetruecolor(400, 300);
                
                // Generate a color based on the fruit name (for variety)
                $color_seed = crc32($fruit) % 240;
                $r = ($color_seed + 50) % 240 + 15;
                $g = ($color_seed + 100) % 240 + 15;
                $b = ($color_seed + 150) % 240 + 15;
                
                $bg_color = imagecolorallocate($img, $r, $g, $b);
                $text_color = imagecolorallocate($img, 255, 255, 255);
                
                // Fill the background
                imagefilledrectangle($img, 0, 0, 399, 299, $bg_color);
                
                // Add text
                $text = ucfirst($fruit) . " " . $i;
                $font_size = 5;
                $text_width = imagefontwidth($font_size) * strlen($text);
                $text_height = imagefontheight($font_size);
                
                imagestring($img, $font_size, 
                           (400 - $text_width) / 2, 
                           (300 - $text_height) / 2, 
                           $text, $text_color);
                
                // Save the image
                imagejpeg($img, $filename, 90);
                imagedestroy($img);
                $created++;
            }
        }
    }
    
    return $created;
}

// HTML output start
echo '<!DOCTYPE html>
<html>
<head>
    <title>Import Product Images Database</title>
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
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        pre {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
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
    <h1>Import Product Images Database</h1>';

// Create sample images
$created_images = create_dummy_images();
echo '<div class="info">';
echo '<h2>Image Generation</h2>';
echo $created_images > 0 
    ? "<p>Created {$created_images} sample images in the img/ directory.</p>"
    : "<p>No new images needed to be created. Using existing images.</p>";
echo '</div>';

// Import SQL file
echo '<div class="info">';
echo '<h2>Database Import</h2>';
$import_results = execute_sql_file($conn, $sql_file);
echo '<pre>';
if (is_array($import_results)) {
    foreach ($import_results as $result) {
        echo htmlspecialchars($result) . "\n";
    }
} else {
    echo htmlspecialchars($import_results);
}
echo '</pre>';
echo '</div>';

// Run the update procedures
echo '<div class="info">';
echo '<h2>Update Product Images</h2>';

// Try to call the stored procedure to update fruit images
$update_result = $conn->query("CALL update_fruit_images()");
if ($update_result) {
    $row = $update_result->fetch_assoc();
    echo '<p>' . $row['result'] . '</p>';
    $update_result->free();
} else {
    echo '<p class="error">Error updating fruit images: ' . $conn->error . '</p>';
}

// Try to call the stored procedure to update agricultural product images
$update_result = $conn->query("CALL update_agricultural_images()");
if ($update_result) {
    $row = $update_result->fetch_assoc();
    echo '<p>' . $row['result'] . '</p>';
    $update_result->free();
} else {
    echo '<p class="error">Error updating agricultural product images: ' . $conn->error . '</p>';
}
echo '</div>';

// View products with images
echo '<div class="info">';
echo '<h2>Products with Updated Images</h2>';

$fruits_result = $conn->query("SELECT fruit_id, name, image FROM fruits ORDER BY fruit_id LIMIT 10");
if ($fruits_result && $fruits_result->num_rows > 0) {
    echo '<table border="1" style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image Path</th>
        </tr>';
    
    while ($fruit = $fruits_result->fetch_assoc()) {
        echo '<tr>
            <td>' . $fruit['fruit_id'] . '</td>
            <td>' . htmlspecialchars($fruit['name']) . '</td>
            <td>' . htmlspecialchars($fruit['image']) . '</td>
        </tr>';
    }
    
    echo '</table>';
} else {
    echo '<p>No fruits found or error executing query: ' . $conn->error . '</p>';
}
echo '</div>';

echo '<div style="margin-top: 20px;">
    <a href="index.php" class="btn">Return to Homepage</a>
    <a href="pages/fruits.php" class="btn">View Fruits Page</a>
</div>';

echo '</body>
</html>'; 