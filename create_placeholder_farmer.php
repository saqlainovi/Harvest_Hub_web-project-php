<?php
// Script to create a placeholder image for farmers

// Set image dimensions
$width = 400;
$height = 400;

// Create image
$image = imagecreatetruecolor($width, $height);

// Set colors
$background_color = imagecolorallocate($image, 245, 245, 245); // Light gray background
$text_color = imagecolorallocate($image, 50, 150, 50); // Green text color
$border_color = imagecolorallocate($image, 100, 180, 100); // Lighter green border

// Fill background
imagefill($image, 0, 0, $background_color);

// Draw border
imagerectangle($image, 0, 0, $width - 1, $height - 1, $border_color);
imagerectangle($image, 1, 1, $width - 2, $height - 2, $border_color);
imagerectangle($image, 2, 2, $width - 3, $height - 3, $border_color);

// Add text
$text = "Farmer Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$text_x = ($width - $text_width) / 2;
$text_y = ($height - $text_height) / 2;

imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);

// Add icon-like elements to represent a farmer/agriculture
// Draw a simple tractor-like shape
$tractor_color = imagecolorallocate($image, 80, 140, 80);
imagefilledrectangle($image, $width/2 - 60, $height/2 + 40, $width/2 + 60, $height/2 + 60, $tractor_color);
imagefilledrectangle($image, $width/2 - 40, $height/2 + 20, $width/2, $height/2 + 40, $tractor_color);
imagefilledellipse($image, $width/2 - 40, $height/2 + 70, 30, 30, $border_color);
imagefilledellipse($image, $width/2 + 40, $height/2 + 70, 30, 30, $border_color);

// Save image
$output_file = 'img/placeholder_farmer.jpg';
imagejpeg($image, $output_file, 90);
imagedestroy($image);

echo "Placeholder farmer image created at: $output_file";
?> 