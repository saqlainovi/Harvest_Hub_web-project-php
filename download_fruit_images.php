<?php
// Script to download sample fruit images

echo "<h1>Download Sample Fruit Images</h1>";

// Define image URLs for common fruits
$fruit_images = [
    'apple' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a6/Pink_lady_and_cross_section.jpg/330px-Pink_lady_and_cross_section.jpg',
        'apple.jpg'
    ],
    'banana' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Banana-Single.jpg/330px-Banana-Single.jpg',
        'banana.jpg'
    ],
    'orange' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e3/Oranges_-_whole-halved-segment.jpg/330px-Oranges_- _whole-halved-segment.jpg',
        'orange.jpg'
    ],
    'grapes' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/b/bb/Table_grapes_on_white.jpg/330px-Table_grapes_on_white.jpg',
        'grapes.jpg'
    ],
    'strawberry' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4c/Garden_strawberry_%28Fragaria_%C3%97_ananassa%29_single2.jpg/330px-Garden_strawberry_%28Fragaria_%C3%97_ananassa%29_single2.jpg',
        'strawberry.jpg'
    ],
    'mango' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/49/Mango_- _single.jpg/330px-Mango_- _single.jpg',
        'mango.jpg'
    ],
    'kiwi' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/Kiwi_aka.jpg/330px-Kiwi_aka.jpg',
        'kiwi.jpg'
    ],
    'pineapple' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/Pineapple_and_cross_section.jpg/330px-Pineapple_and_cross_section.jpg',
        'pineapple.jpg'
    ],
    'watermelon' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/4/47/Taiwan_2009_Tainan_City_Organic_Farm_Watermelon_FRD_7962.jpg/330px-Taiwan_2009_Tainan_City_Organic_Farm_Watermelon_FRD_7962.jpg',
        'watermelon.jpg'
    ],
    'lemon' => [
        'https://upload.wikimedia.org/wikipedia/commons/thumb/f/f7/Lemon_- _whole_and_split.jpg/330px-Lemon_- _whole_and_split.jpg',
        'lemon.jpg'
    ]
];

// Destination directory
$img_dir = 'img';

// Create the directory if it doesn't exist
if (!is_dir($img_dir)) {
    if (mkdir($img_dir, 0777, true)) {
        echo "<p style='color: green;'>Created img directory.</p>";
    } else {
        echo "<p style='color: red;'>Failed to create img directory. Please check permissions.</p>";
        exit;
    }
}

// Function to download an image
function download_image($url, $path) {
    $content = @file_get_contents($url);
    
    if ($content === false) {
        return false;
    }
    
    return file_put_contents($path, $content);
}

// Download images
$downloaded = [];
$failed = [];

foreach ($fruit_images as $fruit => $image_info) {
    $url = $image_info[0];
    $filename = $image_info[1];
    $path = $img_dir . '/' . $filename;
    
    // Skip if file already exists
    if (file_exists($path)) {
        echo "<p>Image for {$fruit} already exists at {$path}.</p>";
        $downloaded[] = [
            'fruit' => $fruit,
            'path' => $path,
            'status' => 'already exists'
        ];
        continue;
    }
    
    // Download the image
    echo "<p>Downloading {$fruit} image from {$url}...</p>";
    
    if (download_image($url, $path)) {
        echo "<p style='color: green;'>Successfully downloaded {$fruit} image to {$path}.</p>";
        $downloaded[] = [
            'fruit' => $fruit,
            'path' => $path,
            'status' => 'downloaded'
        ];
    } else {
        echo "<p style='color: red;'>Failed to download {$fruit} image.</p>";
        $failed[] = [
            'fruit' => $fruit,
            'url' => $url
        ];
    }
}

// Summary
echo "<h2>Download Summary</h2>";
echo "<p>Total images: " . count($fruit_images) . "</p>";
echo "<p>Successfully downloaded or found: " . count($downloaded) . "</p>";
echo "<p>Failed to download: " . count($failed) . "</p>";

// Display downloaded images
if (!empty($downloaded)) {
    echo "<h2>Downloaded Images</h2>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";
    
    foreach ($downloaded as $item) {
        $path = $item['path'];
        $fruit = $item['fruit'];
        
        echo "<div style='text-align: center; margin: 5px; border: 1px solid #ccc; padding: 5px;'>";
        echo "<img src='/{$path}' style='max-width: 100px; max-height: 100px;'><br>";
        echo "<strong>{$fruit}</strong><br>";
        echo "<small>{$item['status']}</small>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Create a script to update the database with these images
echo "<h2>Update Database</h2>";
echo "<p>Use this script to update your product images in the database:</p>";

echo "<form method='post' action='update_db_images.php'>";
echo "<input type='hidden' name='update_db' value='yes'>";
echo "<button type='submit' style='padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer;'>Update Product Images in Database</button>";
echo "</form>";

// Create the database update script
$update_script = "<?php
// Database settings
\$db_host = \"localhost\";
\$db_user = \"root\";
\$db_pass = \"\";
\$db_name = \"fresh_harvest\";

// Create database connection
\$conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);

// Check connection
if (\$conn->connect_error) {
    die(\"Connection failed: \" . \$conn->connect_error);
}

echo \"<h1>Update Product Images in Database</h1>\";

// Image paths to use
\$image_paths = [";

foreach ($downloaded as $item) {
    $fruit = $item['fruit'];
    $filename = basename($item['path']);
    $update_script .= "\n    '{$fruit}' => '/asraf idp2/img/{$filename}',";
}

$update_script .= "
];

// Get all products
\$result = \$conn->query(\"SELECT product_id, name FROM products\");

if (!\$result) {
    echo \"<p style='color: red;'>Error querying products: \" . \$conn->error . \"</p>\";
} elseif (\$result->num_rows == 0) {
    echo \"<p>No products found in the database.</p>\";
} else {
    echo \"<h2>Updating Product Images</h2>\";
    \$updated_count = 0;
    
    while (\$row = \$result->fetch_assoc()) {
        \$productId = \$row['product_id'];
        \$name = strtolower(\$row['name']);
        
        // Find the matching fruit image
        \$image_path = null;
        
        foreach (\$image_paths as \$fruit => \$path) {
            if (strpos(\$name, \$fruit) !== false) {
                \$image_path = \$path;
                break;
            }
        }
        
        // If no match found, use the first image as default
        if (!\$image_path && !empty(\$image_paths)) {
            \$image_path = reset(\$image_paths);
        }
        
        if (\$image_path) {
            // Update the product image path
            \$stmt = \$conn->prepare(\"UPDATE products SET image_path = ? WHERE product_id = ?\");
            \$stmt->bind_param(\"si\", \$image_path, \$productId);
            \$result2 = \$stmt->execute();
            \$stmt->close();
            
            if (\$result2) {
                echo \"<p>Updated product #{\$productId}: {\$row['name']}<br>\";
                echo \"Image path: {\$image_path}</p>\";
                \$updated_count++;
            } else {
                echo \"<p style='color: red;'>Failed to update product #{\$productId}: {\$row['name']}</p>\";
            }
        } else {
            echo \"<p style='color: orange;'>No matching image found for product #{\$productId}: {\$row['name']}</p>\";
        }
    }
    
    echo \"<div style='background-color: #d4edda; color: #155724; padding: 10px; margin: 20px 0; border-radius: 5px;'>\";
    echo \"Successfully updated {\$updated_count} product image paths.\";
    echo \"</div>\";
}

// Close connection
\$conn->close();

// Navigation
echo \"<div style='margin-top: 30px;'>\";
echo \"<a href='check_products_images.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Check Images</a>\";
echo \"<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>\";
echo \"</div>\";
?>";

// Write the update script to a file
file_put_contents('update_db_images.php', $update_script);

// Navigation
echo "<div style='margin-top: 30px;'>";
echo "<a href='check_products_table_structure.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Check Database Structure</a>";
echo "<a href='check_products_images.php' style='padding: 10px 15px; background: #2196F3; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Check Images</a>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";
?> 