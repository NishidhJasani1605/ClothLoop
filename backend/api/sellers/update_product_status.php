<?php
/**
 * Update Product Status API
 * Allows a seller to update the status of their product (active/inactive)
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

// Check if seller is authenticated
Auth::requireRole('seller');

// Get current seller
$seller = Auth::getCurrentUser();
$sellerId = $seller['id'];

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Log received data for debugging
error_log("Update product status - Received data: " . json_encode($data));

// Validate input
if (!isset($data['id'])) {
    error_log("Update product status - Error: Product ID is required");
    Response::error('Product ID is required');
}

// Map frontend status values to database enum values
if (isset($data['status'])) {
    if ($data['status'] === 'active') {
        $data['status'] = 'available';
    } else if ($data['status'] === 'inactive') {
        $data['status'] = 'unavailable';
    }
}

// Now check against the allowed enum values
if (!isset($data['status']) || !in_array($data['status'], ['available', 'rented', 'unavailable'])) {
    error_log("Update product status - Error: Invalid status: " . ($data['status'] ?? 'undefined'));
    Response::error('Valid status is required (available, rented, or unavailable)');
}

$productId = $data['id'];
$status = $data['status'];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Debug database connection
    if (!$db) {
        error_log("Update product status - Error: Failed to connect to database");
        Response::error('Database connection failed');
    }
    
    // Check if product exists and belongs to this seller
    $stmt = $db->prepare("
        SELECT id, status 
        FROM products 
        WHERE id = :id AND seller_id = :seller_id
    ");
    $stmt->bindParam(':id', $productId);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        error_log("Update product status - Error: Product not found or not owned by seller. Product ID: $productId, Seller ID: $sellerId");
        Response::error('Product not found or you do not have permission to update it');
    }
    
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Update product status - Current product status: " . ($product['status'] ?? 'NULL'));
    
    // Update product status
    $updateQuery = "
        UPDATE products 
        SET status = :status, updated_at = NOW() 
        WHERE id = :id AND seller_id = :seller_id
    ";
    
    error_log("Update product status - Executing query: $updateQuery with params - id: $productId, status: $status, seller_id: $sellerId");
    
    $stmt = $db->prepare($updateQuery);
    $stmt->bindParam(':id', $productId);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->bindParam(':status', $status);
    $result = $stmt->execute();
    
    if ($result) {
        error_log("Update product status - Success: Status updated for product $productId to '$status'");
        // Return success response
        Response::success('Product status updated successfully');
    } else {
        error_log("Update product status - Error: Failed to update status. PDO error: " . json_encode($stmt->errorInfo()));
        Response::error('Failed to update product status');
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Error updating product status: " . $e->getMessage());
    
    // Return error response
    Response::error('Failed to update product status: ' . $e->getMessage());
} 