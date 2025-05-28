<?php
// This utility script creates a proper file structure and dummy images
// This is needed since we're running in demo mode without actual database connection

// Create uploads directory if it doesn't exist
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}

// Create properties directory if it doesn't exist
if (!is_dir('uploads/properties')) {
    mkdir('uploads/properties', 0777, true);
}

echo "<h2>Image Path Fixing Utility</h2>";
echo "<div style='font-family: Arial, sans-serif; padding: 20px; line-height: 1.5;'>";

// Since we're in demo mode, let's create placeholder images for all demo properties
// In a real setup, we'd copy from database

// Create sample property images for demos
$sampleProperties = [
    1 => 'Modern Apartment with City View',
    2 => 'Spacious Family Home with Garden',
    3 => 'Luxury Condo in Downtown',
    4 => 'Beachfront Villa',
    5 => 'Mountain Cabin Retreat',
    6 => 'Suburban House with Pool',
    7 => 'Bahay ni Kuya'
];

$sampleImagesCreated = 0;

// For each property, create some image files
foreach ($sampleProperties as $propertyId => $propertyName) {
    // Create 1-3 images per property
    $imageCount = min(3, $propertyId);
    
    for ($i = 0; $i < $imageCount; $i++) {
        $isPrimary = ($i === 0) ? 1 : 0; // First image is primary
        $filename = "property_{$propertyId}_image_{$i}.jpg";
        $targetPath = "uploads/properties/{$filename}";
        
        // Check if this is one of the manually uploaded images from the database
        if (file_exists($targetPath)) {
            echo "Image for Property #{$propertyId} ('{$propertyName}') already exists at {$targetPath}<br>";
            continue;
        }
        
        // Create a sample color image - in a real environment, this would be a real property image
        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        
        // Generate a unique color based on property ID
        $r = ($propertyId * 50) % 255;
        $g = (255 - ($propertyId * 30)) % 255;
        $b = ($propertyId * 70) % 255;
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        
        // Fill the background
        imagefill($image, 0, 0, $bgColor);
        
        // Add property name text
        $propertyText = str_replace(' with ', "\nwith ", $propertyName);
        $propertyText = wordwrap($propertyText, 20, "\n");
        $lines = explode("\n", $propertyText);
        
        // Center and draw each line of text
        $fontSize = 5;
        $lineHeight = imagefontheight($fontSize) + 5;
        $y = ($height - (count($lines) * $lineHeight)) / 2;
        
        foreach ($lines as $line) {
            $textWidth = imagefontwidth($fontSize) * strlen($line);
            $x = ($width - $textWidth) / 2;
            imagestring($image, $fontSize, $x, $y, $line, $textColor);
            $y += $lineHeight;
        }
        
        // Add property ID
        imagestring($image, 4, 10, 10, "Property ID: {$propertyId}", $textColor);
        
        // Add primary/secondary indicator
        imagestring($image, 4, 10, 30, $isPrimary ? "PRIMARY IMAGE" : "Secondary Image", $textColor);
        
        // Save the image
        imagejpeg($image, $targetPath, 90);
        imagedestroy($image);
        
        echo "Created sample image for Property #{$propertyId} ('{$propertyName}') at {$targetPath}<br>";
        $sampleImagesCreated++;
    }
}

echo "<p>Created {$sampleImagesCreated} sample property images.</p>";

// Create symbolic links or copies that point to these images
echo "<p>Ensuring all paths are accessible...</p>";

// Create symlink to uploads folder in seller directory if it doesn't exist
if (!file_exists('seller/uploads') && is_dir('seller')) {
    if (function_exists('symlink')) {
        @symlink('../uploads', 'seller/uploads');
        echo "Created symbolic link from seller/uploads to ../uploads<br>";
    } else {
        // If symlinks not supported, copy directory structure
        @mkdir('seller/uploads/properties', 0777, true);
        echo "Created directory seller/uploads/properties<br>";
    }
}

// Also create an .htaccess file in uploads to ensure direct access
$htaccessContent = <<<EOT
# Allow direct access to images
<IfModule mod_authz_core.c>
    Require all granted
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Allow from all
</IfModule>
EOT;

file_put_contents('uploads/.htaccess', $htaccessContent);
echo "Created .htaccess file in uploads directory to ensure web access<br>";

echo "</div>";
echo "<hr>";
echo "<p>Image path setup complete. Your property images should now display correctly.</p>";
echo "<p><a href='index.php' class='btn btn-primary'>Return to Home Page</a></p>";

// Load GD library for image generation
function checkGdLibrary() {
    if (!extension_loaded('gd')) {
        echo "<p style='color: red;'>Warning: GD library not available. Image generation will not work.</p>";
        return false;
    }
    return true;
}

// Check GD library
checkGdLibrary();
?>