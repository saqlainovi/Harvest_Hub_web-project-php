<?php
/**
 * This script downloads placeholder images for all fruits listed in the database
 */

// Configuration
$fruits = [
    'alphonso_mango', 'green_mango', 'jackfruit', 'papaya', 'banana',
    'sweet_orange', 'lemon', 'key_lime', 'pomelo', 'grapefruit',
    'litchi', 'plum', 'peach', 'cherry', 'apricot',
    'organic_pineapple', 'organic_strawberry', 'organic_watermelon', 'organic_mandarin', 'organic_dragonfruit',
    'watermelon', 'honeydew', 'cantaloupe', 'organic_blueberry', 'organic_raspberry'
];

// Create directory if it doesn't exist
$directory = 'images/fruits';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
    echo "Created directory: $directory<br>\n";
}

// Download placeholder images
foreach ($fruits as $fruit) {
    $destination = "$directory/$fruit.jpg";
    
    // Skip if file already exists
    if (file_exists($destination)) {
        echo "File already exists: $destination<br>\n";
        continue;
    }
    
    // Display name for placeholder
    $display_name = str_replace('_', ' ', ucwords($fruit, '_'));
    
    // URL encode the display name
    $encoded_name = urlencode($display_name);
    
    // Use placeholder.com service
    $url = "https://via.placeholder.com/800x600?text=$encoded_name";
    
    // Try to download the image
    $image_content = @file_get_contents($url);
    
    if ($image_content === false) {
        echo "Failed to download image for $fruit<br>\n";
        continue;
    }
    
    // Save the file
    if (file_put_contents($destination, $image_content) !== false) {
        echo "Downloaded placeholder image: $destination<br>\n";
    } else {
        echo "Failed to save image: $destination<br>\n";
    }
}

echo "All placeholder images have been downloaded. If any failed, check the messages above.<br>\n";
?> 