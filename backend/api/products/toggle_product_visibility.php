<?php
/**
 * Toggle Product Visibility API
 * Allows admins to hide/show products
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
    exit;
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check for required fields
if (!isset($data->product_id) || !isset($data->visibility)) {
    Response::error('Missing required parameters', null, 400);
    exit;
}

// Authenticate admin
try {
    Auth::requireAuth();
    $currentUser = Auth::getCurrentUser();

    if ($currentUser['role'] !== 'admin') {
        Response::error('Only administrators can toggle product visibility', null, 403);
        exit;
    }
} catch (Exception $e) {
    Response::error('Authentication error: ' . $e->getMessage(), null, 401);
    exit;
}

// Convert input to appropriate types
$productId = intval($data->product_id);
$makeVisible = intval($data->visibility) === 1; // 1 = make visible, 0 = hide

if ($productId <= 0) {
    Response::error('Invalid product ID', null, 400);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $checkProductQuery = "SELECT id, seller_id FROM products WHERE id = :product_id";
    $checkStmt = $db->prepare($checkProductQuery);
    $checkStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    $productInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$productInfo) {
        Response::error('Product not found', null, 404);
        exit;
    }
    
    $sellerId = $productInfo['seller_id'];
    
    // Start transaction
    $db->beginTransaction();
    
    // Update product visibility
    $isHiddenValue = $makeVisible ? 0 : 1;
    $query = "UPDATE products SET is_hidden = :is_hidden WHERE id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':is_hidden', $isHiddenValue, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        throw new PDOException("Failed to update product visibility");
    }
    
    // If product is being hidden, add admin restriction message
    if (!$makeVisible) {
        // Check if a restriction notification already exists
        $checkQuery = "SELECT id FROM seller_notifications 
                        WHERE seller_id = :seller_id 
                        AND product_id = :product_id 
                        AND type = 'restriction' 
                        AND is_read = 0";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':seller_id', $sellerId, PDO::PARAM_INT);
        $checkStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
        $checkStmt->execute();
        
        // If no notification exists, create one
        if ($checkStmt->rowCount() === 0) {
            $message = "Your product has been hidden by an administrator due to policy violations.";
            $notifQuery = "INSERT INTO seller_notifications (seller_id, product_id, message, type, created_at) 
                           VALUES (:seller_id, :product_id, :message, 'restriction', NOW())";
            $notifStmt = $db->prepare($notifQuery);
            $notifStmt->bindParam(':seller_id', $sellerId, PDO::PARAM_INT);
            $notifStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $notifStmt->bindParam(':message', $message);
            $notifStmt->execute();
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    Response::success('Product visibility updated successfully', [
        'product_id' => $productId,
        'is_hidden' => $isHiddenValue,
        'message' => $makeVisible ? 'Product is now visible' : 'Product has been hidden'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('Database error in toggle_product_visibility.php: ' . $e->getMessage());
    Response::error('Database error: ' . $e->getMessage(), null, 500);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log('General error in toggle_product_visibility.php: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), null, 500);
    exit;
} 