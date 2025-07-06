<?php
// Image Display Diagnostic and Repair Tool
require_once 'includes/config.php';

// Create img directory if it doesn't exist
$img_dir = __DIR__ . '/img';
if (!is_dir($img_dir)) {
    mkdir($img_dir, 0755, true);
}

// Function to check if path is absolute
function is_absolute_path($path) {
    return (strpos($path, '/') === 0 || preg_match('/^[A-Za-z]:\\\\/', $path));
}

// Function to create a test image
function create_test_image($name, $path) {
    // Create a colored rectangle image
    $img = imagecreatetruecolor(400, 300);
    
    // Generate a color based on the name
    $color_seed = crc32($name) % 240;
    $r = ($color_seed + 50) % 240 + 15;
    $g = ($color_seed + 100) % 240 + 15;
    $b = ($color_seed + 150) % 240 + 15;
    
    $bg_color = imagecolorallocate($img, $r, $g, $b);
    $text_color = imagecolorallocate($img, 255, 255, 255);
    
    // Fill the background
    imagefilledrectangle($img, 0, 0, 399, 299, $bg_color);
    
    // Add text
    $text = $name;
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_height = imagefontheight($font_size);
    
    imagestring($img, $font_size, 
               (400 - $text_width) / 2, 
               (300 - $text_height) / 2, 
               $text, $text_color);
    
    // Save the image
    imagejpeg($img, $path, 90);
    imagedestroy($img);
    
    return filesize($path) > 0;
}

// Start HTML output
echo '<!DOCTYPE html>
<html>
<head>
    <title>Image Display Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .section {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .error {
            color: #d9534f;
            background-color: #f2dede;
            border: 1px solid #ebccd1;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .success {
            color: #5cb85c;
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .warning {
            color: #f0ad4e;
            background-color: #fcf8e3;
            border: 1px solid #faebcc;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
            margin-top: 10px;
            margin-right: 5px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .red-btn {
            background-color: #d9534f;
        }
        .red-btn:hover {
            background-color: #c9302c;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .image-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .image-info {
            padding: 10px;
        }
        pre {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>Image Display Diagnostic and Repair</h1>';

// SECTION 1: Check site URL and image directory
echo '<div class="section">
    <h2>1. Environment Configuration</h2>';

// Check SITE_URL
echo '<div>
    <h3>Site URL Configuration</h3>';
echo '<p>Current SITE_URL: <code>' . htmlspecialchars(SITE_URL) . '</code></p>';

if (empty(SITE_URL)) {
    echo '<div class="error">SITE_URL is not defined or empty. This will cause image loading issues.</div>';
} else {
    echo '<div class="success">SITE_URL is defined.</div>';
}

// Check img directory
echo '<h3>Image Directory Status</h3>';

$img_exists = is_dir($img_dir);
$img_writable = is_writable($img_dir);

if ($img_exists) {
    echo '<div class="success">Image directory exists at: <code>' . htmlspecialchars($img_dir) . '</code></div>';
} else {
    echo '<div class="error">Image directory does not exist at: <code>' . htmlspecialchars($img_dir) . '</code></div>';
}

if ($img_writable) {
    echo '<div class="success">Image directory is writable.</div>';
} else {
    echo '<div class="error">Image directory is not writable. PHP cannot create or modify image files.</div>';
}

// Test image creation
$test_image = $img_dir . '/test_image.jpg';
$test_creation = create_test_image('Test Image', $test_image);

if ($test_creation) {
    echo '<div class="success">Test image created successfully at: <code>' . htmlspecialchars($test_image) . '</code></div>';
    echo '<div><img src="' . SITE_URL . '/img/test_image.jpg" alt="Test Image" style="max-width: 300px; margin-top: 10px;"></div>';
} else {
    echo '<div class="error">Failed to create test image at: <code>' . htmlspecialchars($test_image) . '</code></div>';
}

echo '</div>';

// SECTION 2: Check fruit image paths in database
echo '<div class="section">
    <h2>2. Database Image Path Analysis</h2>';

// Check fruit images
$fruit_result = $conn->query("SELECT fruit_id, name, image FROM fruits ORDER BY fruit_id");
$fruit_issues = 0;

if ($fruit_result && $fruit_result->num_rows > 0) {
    echo '<h3>Fruit Image Paths</h3>';
    echo '<table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Image Path</th>
            <th>Status</th>
        </tr>';
    
    while ($fruit = $fruit_result->fetch_assoc()) {
        $status = '';
        $img_path = $fruit['image'];
        $full_path = '';
        
        // Check if path is empty
        if (empty($img_path)) {
            $status = '<span class="error">Empty path</span>';
            $fruit_issues++;
        } else {
            // Determine the full server path to the image
            if (is_absolute_path($img_path)) {
                $full_path = $img_path;
            } else {
                $full_path = __DIR__ . '/' . $img_path;
            }
            
            // Check if file exists
            if (file_exists($full_path)) {
                $status = '<span class="success">File exists</span>';
            } else {
                $status = '<span class="error">File not found</span>';
                $fruit_issues++;
            }
        }
        
        echo '<tr>
            <td>' . $fruit['fruit_id'] . '</td>
            <td>' . htmlspecialchars($fruit['name']) . '</td>
            <td>' . htmlspecialchars($img_path) . '</td>
            <td>' . $status . '</td>
        </tr>';
    }
    
    echo '</table>';
    
    if ($fruit_issues > 0) {
        echo '<div class="warning">Found ' . $fruit_issues . ' image path issues in fruits table.</div>';
    } else {
        echo '<div class="success">All fruit image paths appear to be valid.</div>';
    }
} else {
    echo '<div class="warning">No fruits found in database or error querying fruits.</div>';
}

echo '</div>';

// SECTION 3: Fix image paths and create missing images
echo '<div class="section">
    <h2>3. Automatic Repair Options</h2>';

echo '<form method="post" action="">
    <button type="submit" name="fix_images" class="btn">Repair Image Paths & Create Missing Images</button>
    <button type="submit" name="reset_default" class="btn red-btn">Reset to Default Image Paths</button>
    <button type="submit" name="simple_fix" class="btn" style="background-color: #2196F3;">Quick Fix with Simple Images</button>
</form>';

// Process simple fix request - NEW OPTION
if (isset($_POST['simple_fix'])) {
    echo '<h3>Simple Fix Results</h3>';
    $fixed_count = 0;
    
    // Create images directory if it doesn't exist
    if (!is_dir(__DIR__ . '/img')) {
        mkdir(__DIR__ . '/img', 0755, true);
    }
    
    // Process fruits with a simplified approach
    $fruit_sql = "SELECT fruit_id, name, image FROM fruits";
    $fruit_result = $conn->query($fruit_sql);
    
    if ($fruit_result && $fruit_result->num_rows > 0) {
        echo '<h4>Fixed Fruit Images</h4>';
        echo '<div class="image-grid">';
        
        while ($fruit = $fruit_result->fetch_assoc()) {
            // Create a very simple filename based on the ID to avoid any path issues
            $simple_filename = "fruit_" . $fruit['fruit_id'] . ".jpg";
            $relative_path = "img/" . $simple_filename;
            $full_path = __DIR__ . '/' . $relative_path;
            
            // Create the image
            create_test_image($fruit['name'], $full_path);
            
            // Update the database with the simple path
            $update_sql = "UPDATE fruits SET image = ? WHERE fruit_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('si', $relative_path, $fruit['fruit_id']);
            
            if ($stmt->execute()) {
                $fixed_count++;
                
                echo '<div class="image-card">
                    <img src="' . SITE_URL . '/' . $relative_path . '" alt="' . htmlspecialchars($fruit['name']) . '">
                    <div class="image-info">
                        <strong>' . htmlspecialchars($fruit['name']) . '</strong>
                        <p>Path: ' . htmlspecialchars($relative_path) . '</p>
                    </div>
                </div>';
            }
            
            $stmt->close();
        }
        
        echo '</div>';
    }
    
    // Do the same for agricultural products if they exist
    $table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
    
    if ($table_check && $table_check->num_rows > 0) {
        $agri_sql = "SELECT product_id, name, image FROM agricultural_products";
        $agri_result = $conn->query($agri_sql);
        
        if ($agri_result && $agri_result->num_rows > 0) {
            echo '<h4>Fixed Agricultural Product Images</h4>';
            echo '<div class="image-grid">';
            
            while ($product = $agri_result->fetch_assoc()) {
                // Simple filename based on ID
                $simple_filename = "agri_" . $product['product_id'] . ".jpg";
                $relative_path = "img/" . $simple_filename;
                $full_path = __DIR__ . '/' . $relative_path;
                
                // Create the image
                create_test_image($product['name'], $full_path);
                
                // Update the database
                $update_sql = "UPDATE agricultural_products SET image = ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param('si', $relative_path, $product['product_id']);
                
                if ($stmt->execute()) {
                    $fixed_count++;
                    
                    echo '<div class="image-card">
                        <img src="' . SITE_URL . '/' . $relative_path . '" alt="' . htmlspecialchars($product['name']) . '">
                        <div class="image-info">
                            <strong>' . htmlspecialchars($product['name']) . '</strong>
                            <p>Path: ' . htmlspecialchars($relative_path) . '</p>
                        </div>
                    </div>';
                }
                
                $stmt->close();
            }
            
            echo '</div>';
        }
    }
    
    echo '<div class="success">Repaired ' . $fixed_count . ' image paths with a simplified approach. Using simple, numbered filenames to avoid path issues.</div>';
    echo '<div class="success">Return to the <a href="pages/fruits.php">Fruits page</a> to see if images are displaying correctly now.</div>';
}

// Process repair request
if (isset($_POST['fix_images'])) {
    echo '<h3>Repair Results</h3>';
    $fixed_count = 0;
    
    // Process fruits
    $fruit_sql = "SELECT fruit_id, name, image FROM fruits";
    $fruit_result = $conn->query($fruit_sql);
    
    if ($fruit_result && $fruit_result->num_rows > 0) {
        echo '<h4>Fixed Fruit Images</h4>';
        echo '<div class="image-grid">';
        
        while ($fruit = $fruit_result->fetch_assoc()) {
            // Create sanitized filename
            $filename = preg_replace('/[^a-zA-Z0-9]/', '_', $fruit['name']);
            $filename = strtolower($filename);
            
            // Define image paths
            $relative_path = "img/{$filename}.jpg";
            $full_path = __DIR__ . '/' . $relative_path;
            
            // Create the image
            create_test_image($fruit['name'], $full_path);
            
            // Update the database
            $update_sql = "UPDATE fruits SET image = ? WHERE fruit_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('si', $relative_path, $fruit['fruit_id']);
            
            if ($stmt->execute()) {
                $fixed_count++;
                
                echo '<div class="image-card">
                    <img src="' . SITE_URL . '/' . $relative_path . '" alt="' . htmlspecialchars($fruit['name']) . '">
                    <div class="image-info">
                        <strong>' . htmlspecialchars($fruit['name']) . '</strong>
                        <p>Path: ' . htmlspecialchars($relative_path) . '</p>
                    </div>
                </div>';
            }
            
            $stmt->close();
        }
        
        echo '</div>';
    }
    
    // Process agricultural products if they exist
    $table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
    
    if ($table_check && $table_check->num_rows > 0) {
        $agri_sql = "SELECT product_id, name, image FROM agricultural_products";
        $agri_result = $conn->query($agri_sql);
        
        if ($agri_result && $agri_result->num_rows > 0) {
            echo '<h4>Fixed Agricultural Product Images</h4>';
            echo '<div class="image-grid">';
            
            while ($product = $agri_result->fetch_assoc()) {
                // Create sanitized filename
                $filename = preg_replace('/[^a-zA-Z0-9]/', '_', $product['name']);
                $filename = strtolower($filename);
                
                // Define image paths
                $relative_path = "img/agri_{$filename}.jpg";
                $full_path = __DIR__ . '/' . $relative_path;
                
                // Create the image
                create_test_image($product['name'], $full_path);
                
                // Update the database
                $update_sql = "UPDATE agricultural_products SET image = ? WHERE product_id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param('si', $relative_path, $product['product_id']);
                
                if ($stmt->execute()) {
                    $fixed_count++;
                    
                    echo '<div class="image-card">
                        <img src="' . SITE_URL . '/' . $relative_path . '" alt="' . htmlspecialchars($product['name']) . '">
                        <div class="image-info">
                            <strong>' . htmlspecialchars($product['name']) . '</strong>
                            <p>Path: ' . htmlspecialchars($relative_path) . '</p>
                        </div>
                    </div>';
                }
                
                $stmt->close();
            }
            
            echo '</div>';
        }
    }
    
    echo '<div class="success">Repaired ' . $fixed_count . ' image paths and created associated images.</div>';
}

// Process reset request
if (isset($_POST['reset_default'])) {
    // Reset fruits to default
    $reset_fruits = $conn->query("UPDATE fruits SET image = CONCAT('img/', LOWER(REPLACE(name, ' ', '_')), '.jpg')");
    $fruit_count = $conn->affected_rows;
    
    // Reset agricultural products if they exist
    $agri_count = 0;
    $table_check = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
    
    if ($table_check && $table_check->num_rows > 0) {
        $reset_agri = $conn->query("UPDATE agricultural_products SET image = CONCAT('img/agri_', LOWER(REPLACE(name, ' ', '_')), '.jpg')");
        $agri_count = $conn->affected_rows;
    }
    
    echo '<h3>Reset Results</h3>';
    echo '<div class="success">Reset ' . $fruit_count . ' fruit images and ' . $agri_count . ' agricultural product images to default paths.</div>';
    echo '<div class="warning">You should now click the "Repair Image Paths & Create Missing Images" button to create the actual image files.</div>';
}

echo '</div>';

// SECTION 4: Debug Information
echo '<div class="section">
    <h2>4. Debug Information</h2>';

// Server info
echo '<h3>Server Environment</h3>';
echo '<pre>';
echo 'PHP Version: ' . phpversion() . "\n";
echo 'Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo 'Script Path: ' . __FILE__ . "\n";
echo 'GD Library: ' . (extension_loaded('gd') ? 'Installed' : 'Not Installed') . "\n";
echo '</pre>';

// Check image rendering in pages
echo '<h3>Template Image Rendering</h3>';
echo '<p>The following shows how images should be referenced in your templates:</p>';

echo '<pre>';
echo '&lt;?php if (!empty($fruit[\'image\'])): ?&gt;
    &lt;img src="&lt;?php echo SITE_URL . \'/\' . $fruit[\'image\']; ?&gt;" alt="&lt;?php echo htmlspecialchars($fruit[\'name\']); ?&gt;"&gt;
&lt;?php else: ?&gt;
    &lt;img src="&lt;?php echo SITE_URL; ?&gt;/images/placeholder.jpg" alt="&lt;?php echo htmlspecialchars($fruit[\'name\']); ?&gt;"&gt;
&lt;?php endif; ?&gt;';
echo '</pre>';

echo '</div>';

// Navigation buttons
echo '<div>
    <a href="index.php" class="btn">Return to Homepage</a>
    <a href="pages/fruits.php" class="btn">View Fruits Page</a>
    <a href="pages/agricultural_products.php" class="btn">View Agricultural Products</a>
</div>';

echo '</body>
</html>'; 