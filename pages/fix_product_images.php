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

echo "<h1>Product Image Path Diagnostics</h1>";

// Path configurations
$possible_paths = [
    "/asraf idp2/img/", // Current path set in the scripts
    "/img/",            // Relative to domain root
    "../img/",          // Relative path
    "/../img/",         // Another relative path format
    "/fresh_harvest/img/", // If using a subfolder for the project
    "../../img/"        // Deep relative path
];

// If a specific path was provided for testing
if (isset($_GET['test_path'])) {
    $test_path = $_GET['test_path'];
    if (!in_array($test_path, $possible_paths)) {
        $possible_paths[] = $test_path;
    }
}

// Check if fix was requested
$path_to_use = "";
if (isset($_GET['fix']) && $_GET['fix'] == "yes" && isset($_GET['path'])) {
    $path_to_use = urldecode($_GET['path']);
    echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>
        Updating all product images to use path prefix: <strong>$path_to_use</strong>
    </div>";
}

// Get all products with images
$result = $conn->query("SELECT product_id, name, image_path FROM products WHERE image_path IS NOT NULL");

if (!$result || $result->num_rows == 0) {
    echo "<p>No products with images found in the database.</p>";
} else {
    echo "<h2>Current Product Images</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
        <th style='padding: 8px; text-align: left;'>ID</th>
        <th style='padding: 8px; text-align: left;'>Name</th>
        <th style='padding: 8px; text-align: left;'>Current Image Path</th>
        <th style='padding: 8px; text-align: left;'>Image Preview</th>
    </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $row['product_id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['image_path']) . "</td>";
        echo "<td style='padding: 8px;'>";
        
        // Try to display the image
        echo "<img src='" . htmlspecialchars($row['image_path']) . "' style='max-width: 100px; max-height: 100px;'>";
        echo "</td>";
        echo "</tr>";
        
        // If we're fixing paths, update this product
        if (!empty($path_to_use)) {
            // Extract just the filename from the current path
            $file_name = basename($row['image_path']);
            $new_path = $path_to_use . $file_name;
            
            // Update the database
            $update_sql = "UPDATE products SET image_path = ? WHERE product_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $new_path, $row['product_id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo "</table>";
    
    // If we did updates, show success message
    if (!empty($path_to_use)) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 20px 0; border-radius: 5px;'>
            All product image paths have been updated. Please refresh to see the changes.
            <a href='fix_product_images.php' style='color: #155724; text-decoration: underline;'>Refresh</a>
        </div>";
    }
}

// Get a sample image name to test with
$sample_image = "";
$result = $conn->query("SELECT image_path FROM products WHERE image_path IS NOT NULL LIMIT 1");
if ($result && $result->num_rows > 0) {
    $path = $result->fetch_assoc()['image_path'];
    $sample_image = basename($path);
}

// Test different path configurations
echo "<h2>Path Testing</h2>";
if (empty($sample_image)) {
    echo "<p>No sample image found to test with.</p>";
} else {
    echo "<p>Testing with sample image: <strong>" . $sample_image . "</strong></p>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 20px;'>";
    
    foreach ($possible_paths as $path) {
        $test_path = $path . $sample_image;
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 5px; width: 200px;'>";
        echo "<h3>Path: " . htmlspecialchars($path) . "</h3>";
        echo "<img src='" . $test_path . "' style='max-width: 150px; max-height: 150px; margin-bottom: 10px;'><br>";
        echo "<div>Path: " . htmlspecialchars($test_path) . "</div>";
        
        // Provide a fix link
        echo "<div style='margin-top: 10px;'>";
        echo "<a href='fix_product_images.php?fix=yes&path=" . urlencode($path) . "' 
               style='background-color: #4CAF50; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;'
               onclick='return confirm(\"Are you sure you want to update all image paths to use this prefix?\")'>
               Use This Path Format</a>";
        echo "</div>";
        
        echo "</div>";
    }
    echo "</div>";
}

// Custom path form
echo "<h2>Test Custom Path</h2>";
echo "<form method='get' action='fix_product_images.php'>";
echo "<input type='text' name='test_path' placeholder='Enter path to test (e.g. /custom/path/)' style='padding: 8px; width: 300px;'>";
echo "<button type='submit' style='padding: 8px 15px; background: #2196F3; color: white; border: none; cursor: pointer; margin-left: 10px;'>Test Path</button>";
echo "</form>";

// Image file location checker
echo "<h2>Physical Image Files</h2>";
$imgDir = "../../img"; // Path relative to this script

if (is_dir($imgDir)) {
    $images = scandir($imgDir);
    $imageCount = 0;
    
    echo "<p>Found the following image files in <code>$imgDir</code>:</p>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    
    foreach ($images as $image) {
        if ($image != "." && $image != ".." && !is_dir($imgDir . "/" . $image)) {
            // Only show image files
            $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                echo "<div style='text-align: center; margin: 5px; border: 1px solid #ccc; padding: 5px;'>";
                echo "<img src='../../img/" . $image . "' style='max-width: 100px; max-height: 100px;'><br>";
                echo "<span style='font-size: 0.8em;'>" . $image . "</span>";
                echo "</div>";
                $imageCount++;
            }
        }
    }
    
    if ($imageCount == 0) {
        echo "<p>No image files found in this directory.</p>";
    }
    
    echo "</div>";
} else {
    echo "<p>Image directory not found at <code>$imgDir</code>.</p>";
    
    // Try to locate the image directory
    $possibleDirs = [
        "../img",
        "../../img",
        "../../../img",
        "img",
        "/img"
    ];
    
    echo "<p>Checking other possible image directory locations:</p>";
    $found = false;
    
    foreach ($possibleDirs as $dir) {
        if (is_dir($dir)) {
            echo "<p>Found image directory at: <code>$dir</code></p>";
            $found = true;
        }
    }
    
    if (!$found) {
        echo "<p>Could not locate the image directory in common locations.</p>";
    }
}

// Close connection
$conn->close();

// Server information
echo "<h2>Server Information</h2>";
echo "<p>This information can help diagnose path issues:</p>";
echo "<ul>";
echo "<li><strong>Server Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Current Script Path:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "<li><strong>Web Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</li>";
echo "</ul>";

// Navigation
echo "<div style='margin-top: 30px;'>";
echo "<a href='check_products_table.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Back to Products</a>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";
?> 