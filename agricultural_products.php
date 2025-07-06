<?php
// Script to create agricultural products table and add sample data
require_once 'includes/config.php';

echo "<h1>Agricultural Products Setup</h1>";

// Create agricultural_products table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS agricultural_products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    seller_id INT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(seller_id)
)";

if ($conn->query($create_table_sql)) {
    echo "<p>Agricultural products table created successfully or already exists.</p>";
} else {
    echo "<p>Error creating table: " . $conn->error . "</p>";
    exit;
}

// Check if agricultural_categories table exists, if not create it
$create_categories_table = "CREATE TABLE IF NOT EXISTS agricultural_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
)";

if ($conn->query($create_categories_table)) {
    echo "<p>Agricultural categories table created successfully or already exists.</p>";
} else {
    echo "<p>Error creating categories table: " . $conn->error . "</p>";
    exit;
}

// Add sample categories if they don't exist
$categories = [
    ['name' => 'Rice', 'description' => 'Different varieties of rice'],
    ['name' => 'Flour', 'description' => 'Various types of flour for baking and cooking'],
    ['name' => 'Seeds', 'description' => 'Agricultural seeds for planting and consumption'],
    ['name' => 'Grains', 'description' => 'Various whole grains and cereals'],
    ['name' => 'Pulses', 'description' => 'Lentils, beans, and other legumes']
];

foreach ($categories as $category) {
    $check_category = $conn->query("SELECT * FROM agricultural_categories WHERE name = '{$category['name']}'");
    
    if ($check_category->num_rows == 0) {
        $insert_category = "INSERT INTO agricultural_categories (name, description) 
                           VALUES ('{$category['name']}', '{$category['description']}')";
        if ($conn->query($insert_category)) {
            echo "<p>Added category: {$category['name']}</p>";
        } else {
            echo "<p>Error adding category {$category['name']}: " . $conn->error . "</p>";
        }
    }
}

// Create images directory for agricultural products
$ag_images_dir = __DIR__ . '/images/agricultural/';
if (!file_exists($ag_images_dir)) {
    mkdir($ag_images_dir, 0755, true);
    echo "<p>Created directory: images/agricultural/</p>";
}

// Sample agricultural products data
$products = [
    [
        'name' => 'Basmati Rice',
        'description' => 'Premium long-grain aromatic rice, perfect for biryanis and pilaf dishes.',
        'category' => 'Rice',
        'price_per_kg' => 180.00,
        'stock_quantity' => 500,
        'image' => 'basmati_rice.jpg'
    ],
    [
        'name' => 'Brown Rice',
        'description' => 'Whole grain rice with the bran intact, offering more fiber and nutrients.',
        'category' => 'Rice',
        'price_per_kg' => 150.00,
        'stock_quantity' => 350,
        'image' => 'brown_rice.jpg'
    ],
    [
        'name' => 'Sticky Rice',
        'description' => 'Glutinous rice commonly used in Asian desserts and dishes.',
        'category' => 'Rice',
        'price_per_kg' => 195.00,
        'stock_quantity' => 200,
        'image' => 'sticky_rice.jpg'
    ],
    [
        'name' => 'Wheat Flour',
        'description' => 'All-purpose wheat flour for making breads, cakes, and pastries.',
        'category' => 'Flour',
        'price_per_kg' => 85.00,
        'stock_quantity' => 400,
        'image' => 'wheat_flour.jpg'
    ],
    [
        'name' => 'Rice Flour',
        'description' => 'Gluten-free flour made from finely milled rice, ideal for gluten-free baking.',
        'category' => 'Flour',
        'price_per_kg' => 120.00,
        'stock_quantity' => 250,
        'image' => 'rice_flour.jpg'
    ],
    [
        'name' => 'Corn Flour',
        'description' => 'Fine flour made from dried corn kernels, used for thickening and baking.',
        'category' => 'Flour',
        'price_per_kg' => 95.00,
        'stock_quantity' => 300,
        'image' => 'corn_flour.jpg'
    ],
    [
        'name' => 'Sunflower Seeds',
        'description' => 'Nutrient-rich sunflower seeds, great for snacking or adding to recipes.',
        'category' => 'Seeds',
        'price_per_kg' => 320.00,
        'stock_quantity' => 150,
        'image' => 'sunflower_seeds.jpg'
    ],
    [
        'name' => 'Pumpkin Seeds',
        'description' => 'Nutritious green seeds from pumpkins, rich in magnesium and antioxidants.',
        'category' => 'Seeds',
        'price_per_kg' => 350.00,
        'stock_quantity' => 100,
        'image' => 'pumpkin_seeds.jpg'
    ],
    [
        'name' => 'Chia Seeds',
        'description' => 'Superfood packed with omega-3 fatty acids, fiber, and protein.',
        'category' => 'Seeds',
        'price_per_kg' => 450.00,
        'stock_quantity' => 80,
        'image' => 'chia_seeds.jpg'
    ],
    [
        'name' => 'Barley',
        'description' => 'Versatile grain used in soups, stews, salads, and brewing.',
        'category' => 'Grains',
        'price_per_kg' => 110.00,
        'stock_quantity' => 280,
        'image' => 'barley.jpg'
    ],
    [
        'name' => 'Quinoa',
        'description' => 'Complete protein grain with all nine essential amino acids.',
        'category' => 'Grains',
        'price_per_kg' => 380.00,
        'stock_quantity' => 200,
        'image' => 'quinoa.jpg'
    ],
    [
        'name' => 'Oats',
        'description' => 'Whole grain oats for porridge, baking, and health recipes.',
        'category' => 'Grains',
        'price_per_kg' => 130.00,
        'stock_quantity' => 350,
        'image' => 'oats.jpg'
    ],
    [
        'name' => 'Red Lentils',
        'description' => 'Quick-cooking lentils that break down easily for soups and curries.',
        'category' => 'Pulses',
        'price_per_kg' => 140.00,
        'stock_quantity' => 250,
        'image' => 'red_lentils.jpg'
    ],
    [
        'name' => 'Chickpeas',
        'description' => 'Versatile legumes for curries, salads, hummus, and more.',
        'category' => 'Pulses',
        'price_per_kg' => 120.00,
        'stock_quantity' => 320,
        'image' => 'chickpeas.jpg'
    ],
    [
        'name' => 'Black Beans',
        'description' => 'Protein-rich beans for Latin American dishes, salads, and soups.',
        'category' => 'Pulses',
        'price_per_kg' => 135.00,
        'stock_quantity' => 280,
        'image' => 'black_beans.jpg'
    ]
];

// Get the first seller ID for sample data
$seller_id = 1;
$seller_result = $conn->query("SELECT seller_id FROM seller_profiles LIMIT 1");
if ($seller_result && $seller_result->num_rows > 0) {
    $seller = $seller_result->fetch_assoc();
    $seller_id = $seller['seller_id'];
}

// Add sample products if they don't exist
foreach ($products as $product) {
    // Get category ID
    $category_result = $conn->query("SELECT category_id FROM agricultural_categories WHERE name = '{$product['category']}'");
    $category_id = 0;
    
    if ($category_result && $category_result->num_rows > 0) {
        $category = $category_result->fetch_assoc();
        $category_id = $category['category_id'];
    }
    
    // Check if product already exists
    $check_product = $conn->query("SELECT * FROM agricultural_products WHERE name = '{$product['name']}'");
    
    if ($check_product->num_rows == 0) {
        $insert_product = "INSERT INTO agricultural_products 
                          (name, description, category, price_per_kg, stock_quantity, is_available, seller_id, image) 
                          VALUES 
                          ('{$product['name']}', '{$product['description']}', '{$product['category']}',
                           {$product['price_per_kg']}, {$product['stock_quantity']}, 1, $seller_id, '{$product['image']}')";
        
        if ($conn->query($insert_product)) {
            echo "<p>Added product: {$product['name']}</p>";
            
            // Create sample image
            $image_path = $ag_images_dir . $product['image'];
            if (!file_exists($image_path)) {
                // Create a solid color image based on category
                $width = 400;
                $height = 300;
                $image = imagecreatetruecolor($width, $height);
                
                // Set color based on category
                switch ($product['category']) {
                    case 'Rice':
                        $color = imagecolorallocate($image, 255, 248, 220); // Light yellowish
                        break;
                    case 'Flour':
                        $color = imagecolorallocate($image, 245, 245, 245); // Off white
                        break;
                    case 'Seeds':
                        $color = imagecolorallocate($image, 210, 180, 140); // Light brown
                        break;
                    case 'Grains':
                        $color = imagecolorallocate($image, 222, 184, 135); // Tan/brown
                        break;
                    case 'Pulses':
                        $color = imagecolorallocate($image, 205, 133, 63); // Medium brown
                        break;
                    default:
                        $color = imagecolorallocate($image, 200, 200, 200); // Gray
                }
                
                // Fill background
                imagefill($image, 0, 0, $color);
                
                // Add product name text
                $text_color = imagecolorallocate($image, 50, 50, 50);
                $font_size = 5;
                $text = $product['name'];
                
                // Center the text
                $text_box = imagettfbbox($font_size, 0, 'Arial', $text);
                $text_width = $text_box[2] - $text_box[0];
                $text_height = $text_box[7] - $text_box[1];
                $x = ($width - $text_width) / 2;
                $y = ($height - $text_height) / 2;
                
                // Use basic text function since imagettftext requires font files
                imagestring($image, $font_size, $x, $y, $text, $text_color);
                
                // Save the image
                imagejpeg($image, $image_path, 90);
                imagedestroy($image);
                
                echo "<p>Generated image for: {$product['name']}</p>";
            }
        } else {
            echo "<p>Error adding product {$product['name']}: " . $conn->error . "</p>";
        }
    }
}

echo "<h2>Agricultural Products Setup Complete</h2>";
echo "<p><a href='pages/agricultural_products.php'>View Agricultural Products</a></p>";
?> 