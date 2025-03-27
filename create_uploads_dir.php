<?php
// Script to create upload directories and default profile image if they don't exist

// Define directory path
$profileDir = 'uploads/profile_pictures';

// Create directory if it doesn't exist
if (!file_exists($profileDir)) {
    mkdir($profileDir, 0777, true);
    echo "Created directory: $profileDir<br>";
}

// Check if default profile image exists
$defaultImagePath = "$profileDir/default.png";

if (!file_exists($defaultImagePath)) {
    // Create a simple default profile image if it doesn't exist
    $size = 200;
    $image = imagecreatetruecolor($size, $size);
    
    // Set a blue background
    $bgColor = imagecolorallocate($image, 54, 162, 235);
    imagefill($image, 0, 0, $bgColor);
    
    // Draw a circle for the avatar
    $circleColor = imagecolorallocate($image, 255, 255, 255);
    imagefilledellipse($image, $size/2, $size/2, $size-60, $size-60, $circleColor);
    
    // Draw a simple person silhouette
    $silhouetteColor = imagecolorallocate($image, 74, 85, 104);
    imagefilledellipse($image, $size/2, $size/2-15, $size/3, $size/3, $silhouetteColor);
    imagefilledrectangle($image, $size/2-$size/6, $size/2+5, $size/2+$size/6, $size/2+$size/3, $silhouetteColor);
    
    // Save the image
    imagepng($image, $defaultImagePath);
    imagedestroy($image);
    
    echo "Created default profile image: $defaultImagePath<br>";
}

echo "<p>Setup completed successfully!</p>";
echo "<p><a href='settings.html'>Go to Settings</a></p>";
?> 