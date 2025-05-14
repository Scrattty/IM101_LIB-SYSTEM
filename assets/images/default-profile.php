<?php
// Set content type to PNG image
header('Content-Type: image/png');

// Set dimensions
$width = 200;
$height = 200;

// Create blank image
$image = imagecreatetruecolor($width, $height);

// Set colors
$background = imagecolorallocate($image, 49, 119, 194); // Blue background
$textColor = imagecolorallocate($image, 255, 255, 255); // White text

// Fill background
imagefill($image, 0, 0, $background);

// Get initials from query string if provided, default to "U"
$name = isset($_GET['name']) ? $_GET['name'] : 'User';
$initials = strtoupper(substr($name, 0, 1));

// Set font size and position
$fontSize = 100;
$fontX = ($width - $fontSize * 0.6) / 2;
$fontY = ($height + $fontSize) / 2;

// Add text to image
imagestring($image, 5, $fontX, $fontY - 50, $initials, $textColor);

// Output image
imagepng($image);

// Clean up
imagedestroy($image);
?> 