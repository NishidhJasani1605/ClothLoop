<?php
/**
 * Check Product Images API
 * Checks if product images exist in the uploads directory and returns the path
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'image_path' => null
];

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    $response['message'] = 'Product ID is required';
    echo json_encode($response);
    exit;
}

$productId = intval($_GET['product_id']);

// Define possible paths
$basePath = realpath(__DIR__ . '/../../'); // Go up two levels to reach the ClothLoop root directory
$productFolder = $basePath . '/backend/uploads/products/' . $productId;
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Check if directory exists
if (is_dir($productFolder)) {
    // Try to find an image in the directory
    if ($handle = opendir($productFolder)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                // Get file extension
                $fileInfo = pathinfo($entry);
                $extension = strtolower($fileInfo['extension'] ?? '');
                
                // Check if this is an image file
                if (in_array($extension, $allowedExtensions)) {
                    // Found an image, return its path
                    $imagePath = '/ClothLoop/backend/uploads/products/' . $productId . '/' . $entry;
                    $response['status'] = 'success';
                    $response['message'] = 'Image found';
                    $response['image_path'] = $imagePath;
                    
                    // Log for debugging
                    error_log("Found image for product $productId at $imagePath");
                    
                    echo json_encode($response);
                    closedir($handle);
                    exit;
                }
            }
        }
        closedir($handle);
    }
}

// If we get here, no images were found
$response['message'] = 'No images found for product ' . $productId;

// Let's check if the product exists in the database and has an image_url field
require_once '../config/db_connect.php';

$query = "SELECT image_url, image_path, image_filename FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);
    
    // Check if there's an image_url field with data
    if (!empty($product['image_url'])) {
        $response['status'] = 'success';
        $response['message'] = 'Image URL found in database';
        $response['image_path'] = $product['image_url'];
        echo json_encode($response);
        exit;
    }
    
    // Check if there's an image_path field with data
    if (!empty($product['image_path'])) {
        $imagePath = $product['image_path'];
        if (!preg_match('/^https?:\/\//', $imagePath)) {
            // If it's not an absolute URL, make it relative to the ClothLoop root
            $imagePath = '/ClothLoop/' . ltrim($imagePath, '/');
        }
        $response['status'] = 'success';
        $response['message'] = 'Image path found in database';
        $response['image_path'] = $imagePath;
        echo json_encode($response);
        exit;
    }
    
    // Check if there's an image_filename field with data
    if (!empty($product['image_filename'])) {
        $response['status'] = 'success';
        $response['message'] = 'Image filename found in database';
        $response['image_path'] = '/ClothLoop/backend/uploads/products/' . $productId . '/' . $product['image_filename'];
        echo json_encode($response);
        exit;
    }
}

// No images found anywhere
echo json_encode($response);
?> 