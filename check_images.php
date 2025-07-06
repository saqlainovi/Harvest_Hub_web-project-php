<?php
// Check if image files exist

$imgDir = __DIR__ . '/img/';
$images = [
    'placeholder.jpg',
    'alphonso_mango.jpg',
    'litchi.jpg',
    'organic_pineapple.jpg'
];

echo "<h1>Image File Check</h1>";
echo "<p>Image directory: " . $imgDir . "</p>";

if (!is_dir($imgDir)) {
    echo "<p style='color: red;'>ERROR: Image directory does not exist!</p>";
} else {
    echo "<p style='color: green;'>Image directory exists.</p>";
    
    echo "<h2>Checking image files:</h2>";
    echo "<ul>";
    foreach ($images as $image) {
        $fullPath = $imgDir . $image;
        if (file_exists($fullPath)) {
            echo "<li style='color: green;'>✓ $image exists (size: " . filesize($fullPath) . " bytes)</li>";
        } else {
            echo "<li style='color: red;'>✗ $image does NOT exist</li>";
        }
    }
    echo "</ul>";
    
    echo "<h2>Directory listing of /img/:</h2>";
    echo "<ul>";
    $files = scandir($imgDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
}

// Check browser access to images
echo "<h2>Testing direct browser access:</h2>";
$baseUrl = "http://localhost/asraf idp2/img/";
foreach ($images as $image) {
    $imgUrl = $baseUrl . $image;
    echo "<p><strong>$image:</strong> <a href='$imgUrl' target='_blank'>$imgUrl</a></p>";
    echo "<p><img src='$imgUrl' alt='$image' style='max-width: 200px; border: 1px solid #ccc;'></p>";
}
?> 