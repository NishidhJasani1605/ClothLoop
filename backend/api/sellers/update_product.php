<?php
/**
 * Update Product API
 * Allows a seller to update an existing product
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../config/constants.php';

// Debugging: Log the incoming data 
file_put_contents(__DIR__ . '/update_debug_log.txt', "Request method: " . $_SERVER['REQUEST_METHOD'] . "\nData: " . print_r($_POST, true) . "\nFiles: " . print_r($_FILES, true) . "\n\n", FILE_APPEND);

// Check if seller is authenticated
Auth::requireRole('seller');

// Get current seller
$seller = Auth::getCurrentUser();
$sellerId = $seller['id'];

// Get posted data (form data instead of JSON)
$data = $_POST;

// Validate required fields
if (!isset($data['id']) || empty($data['id'])) {
    Response::error('Product ID is required');
}

if (!isset($data['title']) || empty($data['title'])) {
    Response::error('Product title is required');
}

if (!isset($data['description']) || empty($data['description'])) {
    Response::error('Product description is required');
}

if (!isset($data['size']) || empty($data['size'])) {
    Response::error('Product size is required');
}

if (!isset($data['category_id']) || empty($data['category_id'])) {
    Response::error('Product category is required');
}

if (!isset($data['rental_price']) || !is_numeric($data['rental_price']) || $data['rental_price'] <= 0) {
    Response::error('Valid rental price is required');
}

$productId = $data['id'];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if products table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'products'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        // Products table doesn't exist yet
        Response::error('Cannot update product: Database table not found');
        exit;
    }
    
    // Verify the product belongs to the logged-in seller
    $stmt = $db->prepare("SELECT id FROM products WHERE id = :product_id AND seller_id = :seller_id");
    $stmt->bindParam(':product_id', $productId);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        Response::error('Product not found or you do not have permission to update it', null, 403);
    }
    
    // Verify category exists
    $categoryId = $data['category_id'];
    $stmt = $db->prepare("SELECT id FROM categories WHERE id = :id");
    $stmt->bindParam(':id', $categoryId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        Response::error('Invalid category selected');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update the product
    $stmt = $db->prepare("
        UPDATE products 
        SET title = :title, 
            description = :description,
            size = :size,
            category_id = :category_id,
            occasion = :occasion,
            rental_price = :rental_price,
            terms = :terms,
            updated_at = NOW()
        WHERE id = :id AND seller_id = :seller_id
    ");
    
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':size', $data['size']);
    $stmt->bindParam(':category_id', $categoryId);
    $stmt->bindParam(':occasion', $data['occasion'] ?? null);
    $stmt->bindParam(':rental_price', $data['rental_price']);
    $stmt->bindParam(':terms', $data['terms'] ?? null);
    $stmt->bindParam(':id', $productId);
    $stmt->bindParam(':seller_id', $sellerId);
    
    $stmt->execute();
    
    // Handle image uploads if any
    if (isset($_FILES['images']) && count($_FILES['images']['name']) > 0) {
        // Create product images directory if it doesn't exist
        $uploadDir = UPLOADS_PATH . '/products/' . $productId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Upload each image
        $fileCount = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['images']['tmp_name'][$i];
                $fileName = time() . '_' . basename($_FILES['images']['name'][$i]);
                $filePath = $uploadDir . '/' . $fileName;
                
                // Validate file type
                $fileType = $_FILES['images']['type'][$i];
                if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
                    continue; // Skip invalid file types
                }
                
                // Move uploaded file
                if (move_uploaded_file($tmpName, $filePath)) {
                    // Store image path in database
                    $imagePath = 'uploads/products/' . $productId . '/' . $fileName;
                    $isPrimary = ($i === 0 && !isset($data['keep_primary_image'])); // First image is primary unless specified
                    
                    $stmt = $db->prepare("
                        INSERT INTO product_images (product_id, image_path, is_primary)
                        VALUES (:product_id, :image_path, :is_primary)
                    ");
                    
                    $stmt->bindParam(':product_id', $productId);
                    $stmt->bindParam(':image_path', $imagePath);
                    $stmt->bindParam(':is_primary', $isPrimary, PDO::PARAM_BOOL);
                    $stmt->execute();
                }
            }
        }
    }
    
    // Handle deleted images
    if (isset($data['deleted_images']) && is_array($data['deleted_images']) && !empty($data['deleted_images'])) {
        foreach ($data['deleted_images'] as $imageId) {
            // Get image path before deleting
            $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = :id AND product_id = :product_id");
            $stmt->bindParam(':id', $imageId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $image = $stmt->fetch(PDO::FETCH_ASSOC);
                $imagePath = $image['image_path'];
                
                // Delete from database
                $stmt = $db->prepare("DELETE FROM product_images WHERE id = :id AND product_id = :product_id");
                $stmt->bindParam(':id', $imageId);
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
                
                // Delete file if it exists
                $fullPath = __DIR__ . '/../../../' . $imagePath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    Response::success('Product updated successfully', [
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Response::error('Failed to update product: ' . $e->getMessage());
} 