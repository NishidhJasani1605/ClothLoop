<?php
/**
 * Get Cloth Image API
 * Returns the image for a cloth/product by ID
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
if (!isset($_GET['id'])) {
    Response::error('Product ID is required');
    exit;
}

$productId = $_GET['id'];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Query to get the product details including image path
    $query = "SELECT * FROM products WHERE id = :product_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check for image fields in various possible column names
        $imageFields = ['image_path', 'image_url', 'image', 'thumbnail', 'photo'];
        $imagePath = null;
        
        foreach ($imageFields as $field) {
            if (isset($product[$field]) && !empty($product[$field])) {
                $imagePath = $product[$field];
                break;
            }
        }
        
        if ($imagePath) {
            // Return success with image path
            Response::success('Image path retrieved successfully', [
                'product_id' => $productId,
                'image' => $imagePath
            ]);
        } else {
            // No image found in the record
            Response::success('No image found for this product', [
                'product_id' => $productId,
                'image' => 'frontend/assets/images/placeholder.png'
            ]);
        }
    } else {
        // Product not found
        Response::error('Product not found');
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error in get_cloth_image.php: " . $e->getMessage());
    
    // Return error response
    Response::error('Failed to retrieve image: ' . $e->getMessage());
} 