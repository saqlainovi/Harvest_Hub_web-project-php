<?php
// Include configuration
require_once 'includes/config.php';

// Check if we have a database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

echo "Starting table creation process...<br>";

// Drop the table if it exists
$drop_query = "DROP TABLE IF EXISTS agricultural_products";
if ($conn->query($drop_query)) {
    echo "Dropped existing agricultural_products table.<br>";
} else {
    echo "Error dropping table: " . $conn->error . "<br>";
}

// Create the table with correct schema
$create_query = "CREATE TABLE agricultural_products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_kg DECIMAL(10, 2) NOT NULL,
    stock_quantity DECIMAL(10, 2) NOT NULL DEFAULT 0,
    is_organic TINYINT(1) NOT NULL DEFAULT 0,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES seller_profiles(seller_id) ON DELETE CASCADE
)";

if ($conn->query($create_query)) {
    echo "Successfully created agricultural_products table.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

echo "Table creation process completed.<br>";
echo "<a href='seller/add_agricultural_product.php'>Go to Add Agricultural Product</a>";
?> 