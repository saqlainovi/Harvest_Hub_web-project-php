<?php
/**
 * This script creates sample fruit images for development purposes
 * It generates colored placeholder images with the fruit name
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// List of fruits based on the sample data
$fruits = [
    'alphonso_mango',
    'green_mango',
    'jackfruit',
    'papaya',
    'banana',
    'sweet_orange',
    'lemon',
    'key_lime',
    'pomelo',
    'grapefruit',
    'litchi',
    'plum',
    'peach',
    'cherry',
    'apricot',
    'organic_pineapple',
    'organic_strawberry',
    'organic_watermelon',
    'organic_mandarin',
    'organic_dragonfruit',
    'watermelon',
    'honeydew',
    'cantaloupe',
    'organic_blueberry',
    'organic_raspberry',
    'placeholder'
];

// Set up image dimensions
$width = 400;
$height = 300;

// Ensure the img directory exists
$img_dir = __DIR__ . '/img';
if (!file_exists($img_dir)) {
    mkdir($img_dir, 0755, true);
}

// Function to create a colored placeholder image with text
function createPlaceholderImage($text, $filename, $width = 400, $height = 300) {
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Generate a fruit-appropriate background color
    $text_lower = strtolower($text);
    if (strpos($text_lower, 'mango') !== false) {
        $bg_color = imagecolorallocate($image, 255, 215, 0); // Yellow
    } elseif (strpos($text_lower, 'watermelon') !== false) {
        $bg_color = imagecolorallocate($image, 220, 20, 60); // Red
    } elseif (strpos($text_lower, 'banana') !== false) {
        $bg_color = imagecolorallocate($image, 255, 255, 0); // Yellow
    } elseif (strpos($text_lower, 'orange') !== false || strpos($text_lower, 'mandarin') !== false) {
        $bg_color = imagecolorallocate($image, 255, 165, 0); // Orange
    } elseif (strpos($text_lower, 'lime') !== false || strpos($text_lower, 'apple') !== false) {
        $bg_color = imagecolorallocate($image, 0, 128, 0); // Green
    } elseif (strpos($text_lower, 'blue') !== false) {
        $bg_color = imagecolorallocate($image, 0, 0, 255); // Blue
    } elseif (strpos($text_lower, 'strawberry') !== false || strpos($text_lower, 'raspberry') !== false || strpos($text_lower, 'cherry') !== false) {
        $bg_color = imagecolorallocate($image, 220, 20, 60); // Red
    } elseif ($text === 'placeholder') {
        $bg_color = imagecolorallocate($image, 200, 200, 200); // Gray
    } else {
        // Random pastel color for other fruits
        $bg_color = imagecolorallocate($image, 
            rand(100, 200), 
            rand(100, 200), 
            rand(100, 200)
        );
    }
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Text color (white)
    $text_color = imagecolorallocate($image, 255, 255, 255);
    
    // Format text
    $display_text = str_replace('_', ' ', $text);
    $display_text = ucwords($display_text);
      // Get font size and position
    $font_size = 5; // Using built-in fonts (1-5)
    
    // Calculate text position (approximate centering)
    $text_width = strlen($display_text) * 10; // Approximate width
    $text_height = 20; // Approximate height
    $x = ($width - $text_width) / 2;
    $y = ($height + $text_height) / 2;
    
    // Add text
    imagestring($image, $font_size, $x, $y, $display_text, $text_color);
    
    // Save image
    imagejpeg($image, $filename, 90);
    
    // Free memory
    imagedestroy($image);
    
    return $filename;
}

// Create images for all fruits
$created_images = [];
echo "<h1>Creating Sample Fruit Images</h1>";
echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";

foreach ($fruits as $fruit) {
    $filename = "{$img_dir}/{$fruit}.jpg";
    createPlaceholderImage($fruit, $filename);
    $created_images[] = $fruit;
    
    echo "<div style='text-align: center; width: 200px;'>";
    echo "<img src='img/{$fruit}.jpg' style='width: 180px; height: auto; border: 1px solid #ddd;'>";
    echo "<p>{$fruit}.jpg</p>";
    echo "</div>";
}

echo "</div>";

echo "<p>Successfully created " . count($created_images) . " sample images in the img directory.</p>";
echo "<p><a href='pages/fruits.php'>Go to Fruits Page</a> to see the images in action.</p>";
