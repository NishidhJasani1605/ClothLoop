<?php
/**
 * Product Image API
 * Fetches the image for a product by product_id
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

// Get product ID from request
if (!isset($_GET['product_id'])) {
    Response::error('Product ID is required');
    exit;
}

$productId = $_GET['product_id'];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product_images table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'product_images'");
    $checkTable->execute();
    
    $imageData = null;
    $possiblePaths = [];
    
    if ($checkTable->rowCount() > 0) {
        // Query to get the product image from product_images table
        $query = "SELECT * FROM product_images WHERE product_id = :product_id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        // Check if image exists
        if ($stmt->rowCount() > 0) {
            $imageData = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Product image found in product_images table: " . json_encode($imageData));
            
            if (!empty($imageData['image_path'])) {
                $possiblePaths[] = [
                    'source' => 'product_images.image_path',
                    'path' => $imageData['image_path']
                ];
            }
        } else {
            error_log("No image found in product_images table for product ID: $productId");
        }
    }
    
    // Check in products table directly for an image_url
    $checkProductsTable = $db->prepare("SHOW TABLES LIKE 'products'");
    $checkProductsTable->execute();
    
    if ($checkProductsTable->rowCount() > 0) {
        $productQuery = "SELECT * FROM products WHERE id = :product_id LIMIT 1";
        $productStmt = $db->prepare($productQuery);
        $productStmt->bindParam(':product_id', $productId);
        $productStmt->execute();
        
        if ($productStmt->rowCount() > 0) {
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);
            error_log("Product found: " . json_encode($product));
            
            // Check for image fields in the product record
            $imageFields = ['image_url', 'image_path', 'image', 'thumbnail', 'photo'];
            
            foreach ($imageFields as $field) {
                if (isset($product[$field]) && !empty($product[$field])) {
                    $possiblePaths[] = [
                        'source' => "products.$field",
                        'path' => $product[$field]
                    ];
                }
            }
        }
    }
    
    // If we found image paths, return them
    if (!empty($possiblePaths)) {
        $responseData = [
            'product_id' => $productId,
            'image_data' => $imageData,
            'possible_paths' => $possiblePaths
        ];
        
        Response::success('Product image data retrieved successfully', $responseData);
    } else {
        // Try to find the default location based on product ID
        $possiblePaths[] = [
            'source' => 'default_location',
            'path' => "uploads/products/$productId/product_image.jpg"
        ];
        
        $possiblePaths[] = [
            'source' => 'default_location_alternate',
            'path' => "uploads/products/product_$productId.jpg"
        ];
        
        $responseData = [
            'product_id' => $productId,
            'image_data' => null,
            'possible_paths' => $possiblePaths
        ];
        
        Response::success('No image records found, but returning possible paths', $responseData);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching product image: " . $e->getMessage());
    
    // Return error response
    Response::error('Failed to fetch product image: ' . $e->getMessage());
} 