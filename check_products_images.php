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

echo "<h1>Product Image Path Check</h1>";

// Get all products with images
$result = $conn->query("SELECT product_id, name, image_path FROM products");

if (!$result) {
    echo "<p>Error querying products: " . $conn->error . "</p>";
} elseif ($result->num_rows == 0) {
    echo "<p>No products found in the database.</p>";
} else {
    echo "<h2>Current Product Images</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f2f2f2;'>
        <th style='padding: 8px; text-align: left;'>ID</th>
        <th style='padding: 8px; text-align: left;'>Name</th>
        <th style='padding: 8px; text-align: left;'>Image Path</th>
        <th style='padding: 8px; text-align: left;'>Image Preview</th>
    </tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . $row['product_id'] . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($row['image_path'] ?? 'NULL') . "</td>";
        echo "<td style='padding: 8px;'>";
        
        if (!empty($row['image_path'])) {
            // Try to display the image
            echo "<img src='" . htmlspecialchars($row['image_path']) . "' style='max-width: 100px; max-height: 100px;'><br>";
            echo "<small>Used path: " . htmlspecialchars($row['image_path']) . "</small>";
        } else {
            echo "<em>No image</em>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Close connection
$conn->close();
?> 