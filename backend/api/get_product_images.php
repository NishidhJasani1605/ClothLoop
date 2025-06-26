<?php
/**
 * Get Product Images API
 * Returns all images for a product by ID from the uploads directory
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get product ID from request
if (!isset($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Product ID is required'
    ]);
    exit;
}

$productId = intval($_GET['id']);
$debug = isset($_GET['debug']) && $_GET['debug'] === 'true';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'No images found',
    'images' => []
];

// Define the product uploads directory
$uploadDir = __DIR__ . '/../uploads/products/' . $productId;
$absolutePath = realpath($uploadDir);

// Add debug info
if ($debug) {
    $response['debug'] = [
        'product_id' => $productId,
        'upload_dir' => $uploadDir,
        'absolute_path' => $absolutePath,
        'dir_exists' => is_dir($uploadDir),
        'server_path' => __DIR__
    ];
}

// If directory exists, get images
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $imagePaths = [];
    
    if ($debug) {
        $response['debug']['files'] = $files;
    }
    
    // Use absolute URLs for better compatibility
    $serverName = $_SERVER['SERVER_NAME'];
    $serverPort = $_SERVER['SERVER_PORT'] != '80' ? ':'.$_SERVER['SERVER_PORT'] : '';
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $baseUrl = $protocol.'://'.$serverName.$serverPort;
    
    // First, add the main product image
    $mainImageUrl = $baseUrl."/ClothLoop/backend/api/image_display.php?type=product&id={$productId}";
    $imagePaths[] = $mainImageUrl;
    
    // Loop through all files in the directory
    foreach ($files as $file) {
        // Skip . and .. entries
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Check if file is an image
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $imageExtensions)) {
            // Full file path for checking
            $fullPath = $uploadDir . '/' . $file;
            
            if ($debug) {
                $response['debug']['file_' . $file] = [
                    'exists' => file_exists($fullPath),
                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                ];
            }
            
            // Add the specific image URL with the file parameter
            $imageUrl = $baseUrl."/ClothLoop/backend/api/image_display.php?type=product&id={$productId}&file=".urlencode($file);
            // Only add if it's not already in the array
            if (!in_array($imageUrl, $imagePaths)) {
                $imagePaths[] = $imageUrl;
            }
        }
    }
    
    // Update response if images found
    if (count($imagePaths) > 0) {
        $response = [
            'status' => 'success',
            'message' => count($imagePaths) . ' image(s) found',
            'images' => $imagePaths
        ];
        
        if ($debug) {
            $response['debug'] = $response['debug'] ?? [];
        }
    }
}

// Send JSON response
echo json_encode($response);
exit; 