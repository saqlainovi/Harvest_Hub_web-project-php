<?php
// Configuration
$fruits = [
    'alphonso_mango', 'green_mango', 'jackfruit', 'papaya', 'banana',
    'sweet_orange', 'lemon', 'key_lime', 'pomelo', 'grapefruit',
    'litchi', 'plum', 'peach', 'cherry', 'apricot',
    'organic_pineapple', 'organic_strawberry', 'organic_watermelon', 'organic_mandarin', 'organic_dragonfruit',
    'watermelon', 'honeydew', 'cantaloupe', 'organic_blueberry', 'organic_raspberry'
];

$colors = [
    'alphonso_mango' => ['background' => [255, 223, 0], 'text' => [0, 0, 0]],
    'green_mango' => ['background' => [144, 238, 144], 'text' => [0, 0, 0]],
    'jackfruit' => ['background' => [255, 204, 0], 'text' => [0, 0, 0]],
    'papaya' => ['background' => [255, 127, 80], 'text' => [0, 0, 0]],
    'banana' => ['background' => [255, 255, 0], 'text' => [0, 0, 0]],
    
    'sweet_orange' => ['background' => [255, 165, 0], 'text' => [0, 0, 0]],
    'lemon' => ['background' => [255, 255, 0], 'text' => [0, 0, 0]],
    'key_lime' => ['background' => [173, 255, 47], 'text' => [0, 0, 0]],
    'pomelo' => ['background' => [255, 222, 173], 'text' => [0, 0, 0]],
    'grapefruit' => ['background' => [255, 99, 71], 'text' => [0, 0, 0]],
    
    'litchi' => ['background' => [220, 20, 60], 'text' => [255, 255, 255]],
    'plum' => ['background' => [142, 69, 133], 'text' => [255, 255, 255]],
    'peach' => ['background' => [255, 218, 185], 'text' => [0, 0, 0]],
    'cherry' => ['background' => [165, 42, 42], 'text' => [255, 255, 255]],
    'apricot' => ['background' => [251, 206, 177], 'text' => [0, 0, 0]],
    
    'organic_pineapple' => ['background' => [255, 223, 0], 'text' => [0, 0, 0]],
    'organic_strawberry' => ['background' => [255, 0, 0], 'text' => [255, 255, 255]],
    'organic_watermelon' => ['background' => [144, 238, 144], 'text' => [255, 0, 0]],
    'organic_mandarin' => ['background' => [255, 140, 0], 'text' => [0, 0, 0]],
    'organic_dragonfruit' => ['background' => [255, 0, 255], 'text' => [255, 255, 255]],
    
    'watermelon' => ['background' => [152, 251, 152], 'text' => [255, 0, 0]],
    'honeydew' => ['background' => [240, 255, 240], 'text' => [0, 0, 0]],
    'cantaloupe' => ['background' => [255, 200, 70], 'text' => [0, 0, 0]],
    'organic_blueberry' => ['background' => [65, 105, 225], 'text' => [255, 255, 255]],
    'organic_raspberry' => ['background' => [220, 20, 60], 'text' => [255, 255, 255]]
];

// Create directories if they don't exist
$directory = 'images/fruits';
if (!file_exists($directory)) {
    mkdir($directory, 0777, true);
    echo "Created directory: $directory\n";
}

// Font settings
$font_size = 20;
$width = 800;
$height = 600;

// Generate placeholders
foreach ($fruits as $fruit) {
    $image = imagecreatetruecolor($width, $height);
    
    // Use color from array or default to white background with black text
    $bg = isset($colors[$fruit]) ? $colors[$fruit]['background'] : [255, 255, 255];
    $text_color = isset($colors[$fruit]) ? $colors[$fruit]['text'] : [0, 0, 0];
    
    // Set background color
    $background = imagecolorallocate($image, $bg[0], $bg[1], $bg[2]);
    imagefill($image, 0, 0, $background);
    
    // Add text
    $text_color = imagecolorallocate($image, $text_color[0], $text_color[1], $text_color[2]);
    $display_text = str_replace('_', ' ', ucwords($fruit, '_'));
    
    // Calculate position to center text
    $text_box = imagettfbbox($font_size, 0, 'arial.ttf', $display_text);
    if (!$text_box) {
        // If TTF not available, use built-in font
        $text_width = strlen($display_text) * imagefontwidth(5);
        $text_height = imagefontheight(5);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 + $text_height;
        imagestring($image, 5, $x, $y, $display_text, $text_color);
    } else {
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[1] - $text_box[7];
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 + $text_height;
        imagettftext($image, $font_size, 0, $x, $y, $text_color, 'arial.ttf', $display_text);
    }
    
    // Draw a fruit-like shape
    $shape_color = imagecolorallocate($image, abs($bg[0] - 50) % 256, abs($bg[1] - 50) % 256, abs($bg[2] - 50) % 256);
    $center_x = $width / 2;
    $center_y = $height / 2;
    $radius = min($width, $height) / 3;
    imagefilledellipse($image, $center_x, $center_y, $radius, $radius, $shape_color);
    
    // Add "Placeholder Image" text
    $placeholder_text = "Placeholder Image";
    if (!$text_box) {
        $text_width = strlen($placeholder_text) * imagefontwidth(3);
        $x = ($width - $text_width) / 2;
        $y = $height - 40;
        imagestring($image, 3, $x, $y, $placeholder_text, $text_color);
    } else {
        $text_box = imagettfbbox($font_size - 5, 0, 'arial.ttf', $placeholder_text);
        $text_width = $text_box[2] - $text_box[0];
        $x = ($width - $text_width) / 2;
        $y = $height - 30;
        imagettftext($image, $font_size - 5, 0, $x, $y, $text_color, 'arial.ttf', $placeholder_text);
    }
    
    // Save the image
    $filename = "$directory/$fruit.jpg";
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    
    echo "Created placeholder image: $filename\n";
}

echo "All placeholder images have been generated.\n";
?> 