<?php
/**
 * Get Product Images API
 * Fetches all images for a product by its ID
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/response.php';

// Get product ID from request
if (!isset($_GET['id'])) {
    Response::error('Product ID is required');
    exit;
}

$productId = $_GET['id'];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    $images = [];
    
    // Check if product_images table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'product_images'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() > 0) {
        // Query to get all product images from product_images table
        $query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, id ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        // Check if images exist
        if ($stmt->rowCount() > 0) {
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Found " . count($images) . " images for product ID: $productId");
        } else {
            error_log("No images found in product_images table for product ID: $productId");
        }
    }
    
    // If no images found in product_images, check products table directly
    if (empty($images)) {
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
                        $images[] = [
                            'id' => 0,
                            'product_id' => $productId,
                            'image_path' => $product[$field],
                            'is_primary' => 1
                        ];
                        break; // Just use the first valid image field found
                    }
                }
            }
        }
    }
    
    // If we found images, return them
    if (!empty($images)) {
        Response::success('Product images retrieved successfully', $images);
    } else {
        // No images found, return default image suggestions
        $defaultImages = [
            [
                'id' => 0,
                'product_id' => $productId,
                'image_path' => "uploads/products/$productId/product_image.jpg",
                'is_primary' => 1
            ],
            [
                'id' => 0,
                'product_id' => $productId,
                'image_path' => "uploads/products/product_$productId.jpg",
                'is_primary' => 0
            ],
            [
                'id' => 0,
                'product_id' => $productId,
                'image_path' => "frontend/assets/images/placeholder.png",
                'is_primary' => 0
            ]
        ];
        
        Response::success('No images found, returning default options', $defaultImages);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching product images: " . $e->getMessage());
    
    // Return error response
    Response::error('Failed to fetch product images: ' . $e->getMessage());
} 