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

// Count existing products
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$existingCount = 0;
if ($result) {
    $existingCount = $result->fetch_assoc()['count'];
}

echo "<h1>Adding Demo Products with Images</h1>";
echo "<p>Found $existingCount existing products in the database.</p>";

// Get available images
$imgDir = "../../img"; // Path relative to this script
$imgWebDir = "/asraf idp2/img"; // Web path to images
$images = [];

if (is_dir($imgDir)) {
    $allFiles = scandir($imgDir);
    foreach ($allFiles as $file) {
        if ($file != "." && $file != ".." && !is_dir($imgDir . "/" . $file)) {
            // Only add image files
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $images[] = $imgWebDir . "/" . $file;
            }
        }
    }
}

if (empty($images)) {
    die("<p>No images found in the directory. Please add images to $imgDir first.</p>");
}

echo "<p>Found " . count($images) . " images in the directory.</p>";

// Sample fruit products data
$fruits = [
    ['name' => 'Fresh Apples', 'description' => 'Crisp and juicy apples freshly harvested from our orchard.', 'price' => 2.99, 'stock' => 100],
    ['name' => 'Organic Bananas', 'description' => 'Sweet and nutritious organic bananas. Perfect for smoothies or a healthy snack.', 'price' => 1.99, 'stock' => 150],
    ['name' => 'Ripe Strawberries', 'description' => 'Sweet and juicy strawberries, perfect for desserts or eating fresh.', 'price' => 3.49, 'stock' => 80],
    ['name' => 'Fresh Oranges', 'description' => 'Juicy oranges full of vitamin C. Great for juicing or eating.', 'price' => 2.79, 'stock' => 120],
    ['name' => 'Organic Blueberries', 'description' => 'Antioxidant-rich organic blueberries picked at peak ripeness.', 'price' => 4.99, 'stock' => 60],
    ['name' => 'Ripe Watermelon', 'description' => 'Sweet and refreshing watermelon, perfect for hot summer days.', 'price' => 5.99, 'stock' => 45],
    ['name' => 'Green Grapes', 'description' => 'Crisp and sweet seedless green grapes.', 'price' => 3.99, 'stock' => 90],
    ['name' => 'Red Cherries', 'description' => 'Sweet and tart cherries, perfect for snacking or baking.', 'price' => 6.99, 'stock' => 70],
    ['name' => 'Ripe Mangoes', 'description' => 'Sweet and tropical mangoes, rich in flavor and nutrients.', 'price' => 2.49, 'stock' => 85],
    ['name' => 'Fresh Pineapple', 'description' => 'Tropical and juicy pineapple, perfect for desserts or fruit salads.', 'price' => 3.99, 'stock' => 55],
    ['name' => 'Red Apples', 'description' => 'Sweet and crisp red apples, a perfect healthy snack.', 'price' => 2.79, 'stock' => 110],
    ['name' => 'Ripe Peaches', 'description' => 'Juicy and sweet peaches, perfect for eating fresh or in desserts.', 'price' => 3.29, 'stock' => 65],
    ['name' => 'Fresh Kiwi', 'description' => 'Tangy and sweet kiwi fruit, packed with vitamin C.', 'price' => 0.99, 'stock' => 95],
    ['name' => 'Juicy Pears', 'description' => 'Sweet and juicy pears, great for snacking or salads.', 'price' => 2.49, 'stock' => 75],
    ['name' => 'Plump Plums', 'description' => 'Sweet and slightly tart plums, perfect for eating fresh.', 'price' => 2.99, 'stock' => 80],
    ['name' => 'Fresh Limes', 'description' => 'Tart and aromatic limes, perfect for cooking or beverages.', 'price' => 0.69, 'stock' => 120],
    ['name' => 'Juicy Lemons', 'description' => 'Bright and zesty lemons, essential for cooking and beverages.', 'price' => 0.79, 'stock' => 130],
    ['name' => 'Red Grapefruit', 'description' => 'Tangy and slightly sweet grapefruit, rich in vitamin C.', 'price' => 1.99, 'stock' => 70],
    ['name' => 'Sweet Cantaloupe', 'description' => 'Sweet and aromatic cantaloupe, perfect for fruit salads.', 'price' => 4.99, 'stock' => 40],
    ['name' => 'Fresh Blackberries', 'description' => 'Juicy and sweet-tart blackberries, rich in antioxidants.', 'price' => 4.49, 'stock' => 55]
];

// Add products to database with random images
$addedCount = 0;
echo "<h2>Adding Products:</h2>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 20px;'>";

foreach ($fruits as $fruit) {
    // Select random image
    $randomImage = $images[array_rand($images)];
    
    // Insert product
    $name = $conn->real_escape_string($fruit['name']);
    $description = $conn->real_escape_string($fruit['description']);
    $price = (float) $fruit['price'];
    $stock = (int) $fruit['stock'];
    $imagePath = $conn->real_escape_string($randomImage);
    
    $sql = "INSERT INTO products (name, description, price, stock_quantity, image_path) 
            VALUES ('$name', '$description', $price, $stock, '$imagePath')";
    
    if ($conn->query($sql) === TRUE) {
        $productId = $conn->insert_id;
        $addedCount++;
        
        // Display product card
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; width: 200px; text-align: center;'>";
        echo "<img src='$randomImage' style='max-width: 150px; max-height: 150px; margin-bottom: 10px;'>";
        echo "<h3>" . htmlspecialchars($fruit['name']) . "</h3>";
        echo "<p>$" . number_format($fruit['price'], 2) . "</p>";
        echo "<p><small>ID: $productId</small></p>";
        echo "</div>";
    } else {
        echo "<p>Error adding " . htmlspecialchars($fruit['name']) . ": " . $conn->error . "</p>";
    }
}

echo "</div>";
echo "<p>Successfully added $addedCount new products to the database.</p>";

// Provide links to continue
echo "<div style='margin-top: 30px;'>";
echo "<a href='check_products_table.php' style='padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>View All Products</a>";
echo "<a href='../index.php' style='padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Return to Homepage</a>";
echo "</div>";

// Close connection
$conn->close();
?> 