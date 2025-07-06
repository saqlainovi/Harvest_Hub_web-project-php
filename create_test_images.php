<?php
// Script to create multiple test images for the products

$sourceImages = [
    'img/module_table_top.png',
    'img/module_table_bottom.png'
];

// Destination directory
$targetDir = 'img/';

// Check if directory exists, create if it doesn't
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
    echo "Created directory: $targetDir<br>";
}

// Create 10 sample fruit images
$fruits = ['apple', 'banana', 'orange', 'grape', 'mango', 'strawberry', 'blueberry', 'kiwi', 'pineapple', 'watermelon'];

$createdImages = [];

foreach ($fruits as $index => $fruit) {
    // Select source image (alternate between the two)
    $sourceImage = $sourceImages[$index % count($sourceImages)];
    
    if (!file_exists($sourceImage)) {
        echo "Source image not found: $sourceImage<br>";
        continue;
    }
    
    // Create a new filename
    $newFilename = $fruit . '_' . time() . '.png';
    $targetPath = $targetDir . $newFilename;
    
    // Copy the file
    if (copy($sourceImage, $targetPath)) {
        echo "Created image: $targetPath<br>";
        $createdImages[] = $newFilename;
    } else {
        echo "Failed to create image: $targetPath<br>";
    }
}

// Display the results
if (!empty($createdImages)) {
    echo "<h2>Created Test Images</h2>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    
    foreach ($createdImages as $image) {
        $imgPath = $targetDir . $image;
        echo "<div style='text-align: center; margin: 5px; border: 1px solid #ccc; padding: 5px;'>";
        echo "<img src='/$imgPath' style='max-width: 100px; max-height: 100px;'><br>";
        echo "<span style='font-size: 0.8em;'>$image</span><br>";
        
        // Add links to test different path formats
        echo "<div style='font-size: 0.7em; margin-top: 5px;'>";
        echo "Try: <a href='/$imgPath'>/$imgPath</a> | ";
        echo "<a href='/asraf idp2/$imgPath'>/asraf idp2/$imgPath</a> | ";
        echo "<a href='$imgPath'>$imgPath</a>";
        echo "</div>";
        
        echo "</div>";
    }
    
    echo "</div>";
    
    // Print path formats for updating in the database
    echo "<h2>Image Path Formats for Database</h2>";
    echo "<ul>";
    echo "<li><code>/img/" . $createdImages[0] . "</code> - Root-relative path</li>";
    echo "<li><code>/asraf idp2/img/" . $createdImages[0] . "</code> - Project-relative path</li>";
    echo "<li><code>img/" . $createdImages[0] . "</code> - Directory-relative path</li>";
    echo "</ul>";
}

// Now let's create a script to update the database with these images
echo "<h2>Database Update Script</h2>";
echo "<p>Copy and paste this into your PHP script to update your database:</p>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow: auto;'>";
echo "&lt;?php\n";
echo "// Database settings\n";
echo "\$db_host = \"localhost\";\n";
echo "\$db_user = \"root\";\n";
echo "\$db_pass = \"\";\n";
echo "\$db_name = \"fresh_harvest\";\n\n";
echo "// Create database connection\n";
echo "\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);\n\n";
echo "// Check connection\n";
echo "if (\$conn->connect_error) {\n";
echo "    die(\"Connection failed: \" . \$conn->connect_error);\n";
echo "}\n\n";
echo "// Image paths to use\n";
echo "\$imagePaths = [\n";

foreach ($createdImages as $image) {
    echo "    \"/asraf idp2/img/$image\",\n";
}

echo "];\n\n";
echo "// Get all products\n";
echo "\$result = \$conn->query(\"SELECT product_id FROM products\");\n\n";
echo "if (\$result && \$result->num_rows > 0) {\n";
echo "    \$i = 0;\n";
echo "    while (\$row = \$result->fetch_assoc()) {\n";
echo "        \$productId = \$row['product_id'];\n";
echo "        \$imagePath = \$imagePaths[\$i % count(\$imagePaths)];\n";
echo "        \n";
echo "        // Update product with image path\n";
echo "        \$stmt = \$conn->prepare(\"UPDATE products SET image_path = ? WHERE product_id = ?\");\n";
echo "        \$stmt->bind_param(\"si\", \$imagePath, \$productId);\n";
echo "        \$stmt->execute();\n";
echo "        \$stmt->close();\n";
echo "        \n";
echo "        echo \"Updated product \$productId with image: \$imagePath<br>\";\n";
echo "        \$i++;\n";
echo "    }\n";
echo "    echo \"<p>All products updated successfully!</p>\";\n";
echo "} else {\n";
echo "    echo \"<p>No products found in the database.</p>\";\n";
echo "}\n\n";
echo "// Close connection\n";
echo "\$conn->close();\n";
echo "?&gt;";
echo "</pre>";
?> 