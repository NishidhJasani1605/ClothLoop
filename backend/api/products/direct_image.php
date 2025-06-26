<?php
/**
 * Direct Product Image API
 * Returns the first image found for a product
 */

// Set headers for cross-origin access
header('Access-Control-Allow-Origin: *');

// Enable error reporting but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get product ID from request
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$debug = isset($_GET['debug']) && $_GET['debug'] === 'true';

// Validate required parameters
if (empty($productId)) {
    if ($debug) {
        echo "Error: product_id parameter is required";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'product_id parameter is required']);
    }
    exit;
}

// Set base directory
$baseDir = dirname(dirname(dirname(__DIR__))); // ClothLoop root directory
$productDir = "{$baseDir}/backend/uploads/products/{$productId}";

// Check if debug mode
if ($debug) {
    header('Content-Type: text/html');
    echo "<h1>Product Image Debug</h1>";
    echo "<p>Product ID: {$productId}</p>";
    echo "<p>Product Directory: {$productDir}</p>";
    echo "<p>Directory exists: " . (is_dir($productDir) ? "YES" : "NO") . "</p>";
}

// Define fallback image path
$fallbackImage = "{$baseDir}/frontend/assets/images/placeholder.png";

// Check if the product directory exists
if (is_dir($productDir)) {
    // List all files in the directory
    $files = scandir($productDir);
    
    if ($debug) {
        echo "<h2>Files in Directory</h2>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>{$file}</li>";
            }
        }
        echo "</ul>";
    }
    
    // Look for image files
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    foreach ($files as $file) {
        if ($file == "." || $file == "..") continue;
        
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($extension, $allowedExtensions)) {
            $imagePath = "{$productDir}/{$file}";
            
            if ($debug) {
                echo "<p>Image found: {$imagePath}</p>";
                echo "<img src='/ClothLoop/backend/uploads/products/{$productId}/{$file}' style='max-width: 300px;'>";
            } else {
                // Determine content type
                $contentType = 'image/jpeg'; // Default
                if ($extension == 'png') $contentType = 'image/png';
                elseif ($extension == 'gif') $contentType = 'image/gif';
                elseif ($extension == 'webp') $contentType = 'image/webp';
                
                // Output the image
                header("Content-Type: {$contentType}");
                readfile($imagePath);
            }
            exit;
        }
    }
}

// If we reach here, try to get image from database
// Include database connection
require_once dirname(dirname(__DIR__)) . '/config/db_connect.php';

// Query the database for product image
$query = "SELECT image_url, image_path, image_filename FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    if ($debug) {
        echo "<h2>Database Record</h2>";
        echo "<pre>" . print_r($row, true) . "</pre>";
    }
    
    // Check image fields
    if (!empty($row['image_url'])) {
        if ($debug) {
            echo "<p>Image URL found: {$row['image_url']}</p>";
            echo "<img src='{$row['image_url']}' style='max-width: 300px;'>";
        } else {
            header("Location: {$row['image_url']}");
        }
        exit;
    }
    
    if (!empty($row['image_path'])) {
        $imagePath = $row['image_path'];
        
        // Check if path is relative or absolute
        if (!preg_match('/^https?:\/\//', $imagePath)) {
            $imagePath = "{$baseDir}/{$imagePath}";
        }
        
        if (file_exists($imagePath)) {
            if ($debug) {
                echo "<p>Image path found: {$imagePath}</p>";
                echo "<img src='/ClothLoop/{$row['image_path']}' style='max-width: 300px;'>";
            } else {
                $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                $contentType = 'image/jpeg'; // Default
                if ($ext == 'png') $contentType = 'image/png';
                elseif ($ext == 'gif') $contentType = 'image/gif';
                elseif ($ext == 'webp') $contentType = 'image/webp';
                
                header("Content-Type: {$contentType}");
                readfile($imagePath);
            }
            exit;
        }
    }
    
    if (!empty($row['image_filename'])) {
        $imagePath = "{$productDir}/{$row['image_filename']}";
        
        if (file_exists($imagePath)) {
            if ($debug) {
                echo "<p>Image filename found: {$row['image_filename']}</p>";
                echo "<img src='/ClothLoop/backend/uploads/products/{$productId}/{$row['image_filename']}' style='max-width: 300px;'>";
            } else {
                $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                $contentType = 'image/jpeg'; // Default
                if ($ext == 'png') $contentType = 'image/png';
                elseif ($ext == 'gif') $contentType = 'image/gif';
                elseif ($ext == 'webp') $contentType = 'image/webp';
                
                header("Content-Type: {$contentType}");
                readfile($imagePath);
            }
            exit;
        }
    }
}

// If we reach here, use the fallback image
if ($debug) {
    echo "<h2>No Image Found</h2>";
    echo "<p>Using fallback image: {$fallbackImage}</p>";
    echo "<img src='/ClothLoop/frontend/assets/images/placeholder.png' style='max-width: 300px;'>";
} else {
    header("Content-Type: image/png");
    if (file_exists($fallbackImage)) {
        readfile($fallbackImage);
    } else {
        // If fallback image doesn't exist, output a small placeholder
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAGQAAABkCAYAAABw4pVUAAAABmJLR0QA/wD/AP+gvaeTAAAAB3RJTUUH5AoHAB8iS+8OHAAABg9JREFUeNrtnU1oXFUUx3/nvsxMJi9pxtq0pa1NbRGpC7GLahdtXVS7EBeuXAkVXImutFvBhQtdiCCKbi2CQpEKRVGoWz+w1m/UaqvVJk0bkzZJk8xM5t2jZGLMZN68d+e9N5P5/8Ksl5n3/rn33HPPPWcERbRsZW0SeBQ4BpwBThDRYU2tRMR14Dzi13j0uYx8CdJZBIFYkRXgMnKJeKhcOj6tHyZWZA2ZJx5vGfp1Kfr8PGJJmMfQfg/RfzDvJXQqZKNmiV8ndJwc2TBbY6Q4/wngGHAQ2J/zxN4gfmthgQcIAJzO8yHjO3zdJPJQiTiMudbwmXRUmx6T+Ri81xdVhN5J4OaWX01hJp1ORZuUALa4QX9QdITrS6o6DG/NiLJp1EOZAe7MWqEKskqsmI6VxbGyd0cJ4UDnJnQRYr0/w/tqYRX+3Uah+eHmkqIGxvzfDZSnRt2LuQl/KLTcF0sI1Yk3YzqODEVxKLNFXwA6jBwNT4WFQx92eAzBl67G3VImLRSIQrB6R9FjzEsZSlUoNYCUMldRCx+z99FwKFNl62AZnCp5B5WhjQxRKaMDvZAO/KUodR2GMtcpLvOAQJSmU4dXGcAUckHAXPtZHUZ+gMFTOvRgDCfLCWcaQoiI4w7GTAU8A7PVakO5LKXdUlEKbMhQbtLhUTlYm8d9sxxw0dKx+hj4Y91vAVbV4WDGtc6pwsxSKKVTAKdtKy2MQKwqzEQohvY+oZQUxEZg+hqG0vxcY1LHDf2gKMoWY7hbxzd8rKE3hTJlQCbqMJC4x4L9CEonbQR+4dM6tXBGmGuEmRfNVxk3g1xUaUwgCrIfXi3jcjPLShxo3I4qI2pY19YVGG9JeXNYcGOIU/MlXLGiuLamfJGx1i3lp+UMn3u64yVjJRTLJgSlcnfAE1XPGdbJWnTF0d+QVJRZqRcP0u0OlCoqS3eCUgAHB5j+JF3JBEp3O1pFVJT9w8LJsqc766J8nqW82hJFuZWFw6UCK5/gRRMpN/n1KlPZKSHMewF5n5Lv13KqUDFepyQrKPOSCU+XJaxoNMvKoiiDVuGxTQN9dMKwFZwsKKMslHxY+rKs3FOBNBZuvGg00sOlkDQQ3t3XWxQuDUzZ+KJHxBueoAQs4EFIUxbygKZ0Tua+Lkm+ZW/j51JI7pFWHoFpC+X9GvmoMK/Lm9Q+FrIL1l5IK5M8KkrFBzhdMH2dJrfLoigtKPfv6txZyy8l0SXZ+61KgdKa7Vd/LmUWxLT0DjPZGRDFZeFxdXhGtOV6uOv0bvOiYOXrNGpZLaYrFbdmTNnPM7X0n7KgKzB5LTwtbEHx7WaFrEt4WOSZk3BQ1NHNl/K0/ZDq8IbxXXldRCCndhYyiSGTooNWDe/pTxKSJ6kcSi0Q5c6eYE9a9vXIipLPohLPoPREkJ3gxO4WlPuqnnpXzw+YHM9O9gzZlVXXPadUfBaqsY1gR6Z6GvzjUnowFfV4MUlQHq44Gm03xrqPT/vhLDsQrKKEwN1bXWx7VPM+aPFSUUCYx4nFZlLAtvZElJVxUZrY3jWUvcBGGQmlT4lnF8xkQTlSLjbfsG3h7IOWR3YGSlMLz7Vvn9wxh7+dDOWjP51DmRTlVNnDFTVuOzSdYvXXmoRSnXLUCjKrY4RZcKyYp5nv1Xgmixla92qoKCqKYYdxpB3KHMZ1GH5sBTyTtFVvxzXLMtX4Iitp56eRxVLO8EAm9zmTZdoZWQ1bUZRjuQ1b0bJPrYqSRdEa8XrKY+cQZNOGzTB8dkFpPxXzRJbXOadpSgcwKgPM/6/2M3yFzIhPP0ywK8SvRvw6Yl+Ib3PED8WHZJpq3Vbks2U+JNNhQ748YSl68PnbsOzXiJ9HfLEhTFPR50vRv5+Pnmeeo8OKXueFYmKdg6LZwbixzldVFEVRFEVRFFdi2D1RWCK24u0XDVcJvRFFURRFURRFURRFURRFURRFURSlkPIvtv1mnPJvBr4AAAAASUVORK5CYII=');
    }
}
?> 