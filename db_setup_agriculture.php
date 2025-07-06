<?php
// Use the database credentials from the main config file
require_once 'includes/config.php';

echo "<h1>Direct Database Setup for Agricultural Products</h1>";

// No need to create a new connection, use the existing one from config.php
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "<p>Database connection successful</p>";

// Show current database
$result = $conn->query("SELECT DATABASE()");
$row = $result->fetch_row();
echo "<p>Current database: " . $row[0] . "</p>";

// List all tables in the database
$tables_result = $conn->query("SHOW TABLES");
echo "<p>Existing tables in database:</p>";
echo "<ul>";
while ($table = $tables_result->fetch_row()) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// Create agricultural_products table
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
    echo "<p>Agricultural products table created successfully.</p>";
} else {
    echo "<p>Error creating agricultural_products table: " . $conn->error . "</p>";
}

// Create agricultural_categories table
$create_categories_table = "CREATE TABLE IF NOT EXISTS agricultural_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
)";

if ($conn->query($create_categories_table)) {
    echo "<p>Agricultural categories table created successfully.</p>";
} else {
    echo "<p>Error creating agricultural_categories table: " . $conn->error . "</p>";
}

// Add sample category (just one for testing)
$insert_category = "INSERT INTO agricultural_categories (name, description) 
                   VALUES ('Rice', 'Different varieties of rice')";
if ($conn->query($insert_category)) {
    echo "<p>Added sample category: Rice</p>";
} else {
    echo "<p>Error adding category: " . $conn->error . "</p>";
}

// Get seller ID
$seller_result = $conn->query("SELECT seller_id FROM seller_profiles LIMIT 1");
if ($seller_result && $seller_result->num_rows > 0) {
    $seller = $seller_result->fetch_assoc();
    $seller_id = $seller['seller_id'];
    
    // Add sample product
    $insert_product = "INSERT INTO agricultural_products 
                      (name, description, category, price_per_kg, stock_quantity, is_available, seller_id, image) 
                      VALUES 
                      ('Basmati Rice', 'Premium long-grain aromatic rice.', 'Rice', 180.00, 500, 1, $seller_id, 'basmati_rice.jpg')";
    
    if ($conn->query($insert_product)) {
        echo "<p>Added sample product: Basmati Rice</p>";
    } else {
        echo "<p>Error adding product: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Error: No seller profiles found in the database. Please create a seller profile first.</p>";
}

// Create images directory
$ag_images_dir = __DIR__ . '/images/agricultural/';
if (!file_exists($ag_images_dir)) {
    if (mkdir($ag_images_dir, 0755, true)) {
        echo "<p>Created directory: images/agricultural/</p>";
    } else {
        echo "<p>Failed to create directory: images/agricultural/</p>";
    }
}

// Check if tables were created
echo "<p>Verifying tables:</p>";
$check_tables = $conn->query("SHOW TABLES LIKE 'agricultural_products'");
if ($check_tables->num_rows > 0) {
    echo "<p>✓ agricultural_products table exists</p>";
} else {
    echo "<p>✗ agricultural_products table does not exist</p>";
}

$check_categories = $conn->query("SHOW TABLES LIKE 'agricultural_categories'");
if ($check_categories->num_rows > 0) {
    echo "<p>✓ agricultural_categories table exists</p>";
} else {
    echo "<p>✗ agricultural_categories table does not exist</p>";
}

$conn->close();
echo "<p>Setup complete. <a href='pages/agricultural_products.php'>Try accessing the Agricultural Products page</a></p>";
?> 