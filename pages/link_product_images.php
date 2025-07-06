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

// Process form submission
$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    if (isset($_POST['product_id']) && isset($_POST['image_path'])) {
        $product_id = (int) $_POST['product_id'];
        $image_path = $conn->real_escape_string($_POST['image_path']);
        
        // Update product with image path
        $sql = "UPDATE products SET image_path = '$image_path' WHERE product_id = $product_id";
        
        if ($conn->query($sql) === TRUE) {
            $success = true;
            $message = "Image successfully linked to product!";
        } else {
            $message = "Error updating product: " . $conn->error;
        }
    } else {
        $message = "Missing required fields.";
    }
}

// Get product details if successful
if ($success && isset($product_id)) {
    $result = $conn->query("SELECT * FROM products WHERE product_id = $product_id");
    $product = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Product Image - Fresh Harvest</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .product-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-image {
            max-width: 300px;
            max-height: 300px;
            display: block;
            margin: 0 auto 15px;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
        .button.secondary {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <h1>Link Product Image</h1>
    
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success && isset($product)): ?>
        <div class="product-card">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <?php if (!empty($product['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
            <?php endif; ?>
            <p><strong>Price:</strong> $<?php echo htmlspecialchars($product['price']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></p>
            <p><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock_quantity']); ?> units</p>
            <p><strong>Image Path:</strong> <?php echo htmlspecialchars($product['image_path'] ?? 'No image'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="actions" style="margin-top: 20px;">
        <a href="check_products_table.php" class="button">Link More Images</a>
        <a href="../index.php" class="button secondary">Return to Homepage</a>
    </div>
</body>
</html>
<?php
// Close connection
$conn->close();
?> 