<?php
/**
 * Get Image API
 * Returns the primary image for a product by ID, as a direct image response
 */

// Set headers for direct access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Get product ID from request
$productId = isset($_GET['id']) ? $_GET['id'] : null;

if (empty($productId)) {
    // If no product ID, return default placeholder
    $placeholderPath = __DIR__ . '/../../frontend/assets/images/placeholder.png';
    if (file_exists($placeholderPath)) {
        header('Content-Type: image/png');
        readfile($placeholderPath);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo "Image not found";
    }
    exit;
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    $imagePath = null;
    
    // First check product_images table (preferred)
    $checkTable = $db->prepare("SHOW TABLES LIKE 'product_images'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() > 0) {
        // Get primary image first
        $query = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC, id ASC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $imageData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($imageData['image_path'])) {
                $imagePath = $imageData['image_path'];
                error_log("Found primary image path: $imagePath");
            }
        }
    }
    
    // If not found in product_images, check products table
    if (empty($imagePath)) {
        $checkProductsTable = $db->prepare("SHOW TABLES LIKE 'products'");
        $checkProductsTable->execute();
        
        if ($checkProductsTable->rowCount() > 0) {
            $query = "SELECT * FROM products WHERE id = :product_id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Check various field names for image
                $imageFields = ['image_path', 'image_url', 'image', 'thumbnail', 'photo'];
                foreach ($imageFields as $field) {
                    if (isset($product[$field]) && !empty($product[$field])) {
                        $imagePath = $product[$field];
                        error_log("Found image in products table field $field: $imagePath");
                        break;
                    }
                }
            }
        }
    }
    
    // If image path found, attempt to serve it
    if (!empty($imagePath)) {
        // Check if it's a remote URL
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            // Redirect to remote URL
            header("Location: $imagePath");
            exit;
        }
        
        // Remove leading slash if present
        $imagePath = ltrim($imagePath, '/');
        
        // Handle relative paths
        if (strpos($imagePath, 'ClothLoop/') === 0) {
            $imagePath = substr($imagePath, 10); // Remove 'ClothLoop/'
        }
        
        // Construct full path
        $fullPath = __DIR__ . '/../../' . $imagePath;
        
        if (file_exists($fullPath)) {
            // Determine MIME type
            $mimeType = mime_content_type($fullPath);
            header("Content-Type: $mimeType");
            readfile($fullPath);
            exit;
        } else {
            error_log("Image file not found at path: $fullPath");
        }
    }
    
    // If we get here, try standard locations
    $possiblePaths = [
        __DIR__ . '/../../uploads/products/' . $productId . '/product_image.jpg',
        __DIR__ . '/../../uploads/products/product_' . $productId . '.jpg',
        __DIR__ . '/../../frontend/assets/images/placeholder.png'
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $mimeType = mime_content_type($path);
            header("Content-Type: $mimeType");
            readfile($path);
            exit;
        }
    }
    
    // If all else fails, return the default image
    $placeholderPath = __DIR__ . '/../../frontend/assets/images/placeholder.png';
    if (file_exists($placeholderPath)) {
        header('Content-Type: image/png');
        readfile($placeholderPath);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo "Image not found";
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching image: " . $e->getMessage());
    
    // Return default image
    $placeholderPath = __DIR__ . '/../../frontend/assets/images/placeholder.png';
    if (file_exists($placeholderPath)) {
        header('Content-Type: image/png');
        readfile($placeholderPath);
    } else {
        header('HTTP/1.0 404 Not Found');
        echo "Error: " . $e->getMessage();
    }
} 