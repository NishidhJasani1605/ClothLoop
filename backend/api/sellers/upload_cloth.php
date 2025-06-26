<?php
/**
 * Upload/Edit Cloth API
 * Allows sellers to upload new clothing items or edit existing ones
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/validate.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
}

// Require authentication
Auth::requireAuth();

// Get current user
$user = Auth::getCurrentUser();

// Ensure the user is a seller
if ($user['role'] !== 'seller') {
    Response::error('Access denied. This endpoint is for sellers only.', null, 403);
}

// Debugging: Log the incoming data 
file_put_contents(__DIR__ . '/debug_log.txt', "Request method: " . $_SERVER['REQUEST_METHOD'] . "\nData: " . print_r($_POST, true) . "\nFiles: " . print_r($_FILES, true) . "\n\n", FILE_APPEND);

// Get posted data (form data)
$data = $_POST;

// Validate inputs
Validate::reset();
Validate::required('title', $data['title'] ?? '');
Validate::required('description', $data['description'] ?? '');
Validate::required('size', $data['size'] ?? '');
Validate::required('category_id', $data['category_id'] ?? '');
Validate::required('rental_price', $data['rental_price'] ?? '');
Validate::numeric('rental_price', $data['rental_price'] ?? '');

// Check if this is an edit or a new upload
$isEdit = isset($data['id']) && !empty($data['id']);

// Only require images for new products
if (!$isEdit && (!isset($_FILES['images']) || empty($_FILES['images']['name'][0]))) {
    Validate::addError('images', 'At least one product image is required');
}

if (Validate::hasErrors()) {
    Response::error('Validation failed', Validate::getErrors());
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // If editing, verify the product belongs to the seller
    if ($isEdit) {
        $stmt = $db->prepare("
            SELECT id FROM products 
            WHERE id = :id AND seller_id = :seller_id
        ");
        
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':seller_id', $user['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            Response::error('You do not have permission to edit this product', null, 403);
        }
    }
    
    // Get category ID directly from form data
    $categoryId = $data['category_id'] ?? null;

    // Verify the category exists
    if ($categoryId) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = :id");
        $stmt->bindParam(':id', $categoryId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            Response::error('Selected category does not exist');
        }
    }
    
    if ($isEdit) {
        // Update existing product
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
        $stmt->bindParam(':occasion', $data['occasion']);
        $stmt->bindParam(':rental_price', $data['rental_price']);
        $stmt->bindParam(':terms', $data['terms']);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':seller_id', $user['id']);
        
        $stmt->execute();
        $productId = $data['id'];
        
        // If updating images, handle old images first
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0]) && isset($data['update_images']) && $data['update_images'] === 'true') {
            // If explicitly updating images, optionally delete old ones from database
            $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            // Optionally, delete old image files from the directory too
            $uploadDir = UPLOADS_PATH . '/products/' . $productId;
            if (file_exists($uploadDir) && is_dir($uploadDir)) {
                $files = glob($uploadDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file); // Delete the file
                    }
                }
            }
        }
    } else {
        // Insert new product
        $stmt = $db->prepare("
            INSERT INTO products (
                seller_id, title, description, size, category_id, 
                occasion, rental_price, status, terms
            )
            VALUES (
                :seller_id, :title, :description, :size, :category_id,
                :occasion, :rental_price, 'available', :terms
            )
        ");
        
        $stmt->bindParam(':seller_id', $user['id']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':size', $data['size']);
        $stmt->bindParam(':category_id', $categoryId);
        $stmt->bindParam(':occasion', $data['occasion']);
        $stmt->bindParam(':rental_price', $data['rental_price']);
        $stmt->bindParam(':terms', $data['terms']);
        
        $stmt->execute();
        $productId = $db->lastInsertId();
    }
    
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
                // Add microseconds to ensure uniqueness
                $fileName = time() . '_' . microtime(true) . '_' . basename($_FILES['images']['name'][$i]);
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
                    $isPrimary = ($i === 0); // First image is primary
                    
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
    
    // Commit transaction
    $db->commit();
    
    Response::success($isEdit ? 'Product updated successfully' : 'Product added successfully', [
        'product_id' => $productId
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Response::error(($isEdit ? 'Failed to update product: ' : 'Failed to add product: ') . $e->getMessage());
} 