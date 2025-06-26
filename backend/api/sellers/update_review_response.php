<?php
/**
 * Update Review Response API
 * Allows sellers to respond to customer reviews
 */

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
}

// Check if user is authenticated
Auth::requireAuth();

// Get the current user
$user = Auth::getCurrentUser();

// Make sure the user is a seller
if ($user['role'] !== 'seller') {
    Response::error('Access denied. This endpoint is for sellers only.');
}

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate inputs
if (!isset($data['review_id']) || empty($data['review_id'])) {
    Response::error('Review ID is required');
}

if (!isset($data['response']) || empty($data['response'])) {
    Response::error('Response text is required');
}

$reviewId = $data['review_id'];
$responseText = $data['response'];

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify the review belongs to this seller
    $checkQuery = "
        SELECT id 
        FROM reviews 
        WHERE id = :review_id AND seller_id = :seller_id
    ";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':review_id', $reviewId);
    $checkStmt->bindParam(':seller_id', $user['id']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::error('Review not found or does not belong to you', null, 403);
    }
    
    // Update the review with seller's response
    $updateQuery = "
        UPDATE reviews 
        SET seller_response = :response,
            response_date = NOW() 
        WHERE id = :review_id AND seller_id = :seller_id
    ";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':response', $responseText);
    $updateStmt->bindParam(':review_id', $reviewId);
    $updateStmt->bindParam(':seller_id', $user['id']);
    $updateStmt->execute();
    
    if ($updateStmt->rowCount() > 0) {
        Response::success('Response added successfully');
    } else {
        Response::error('Failed to update review response');
    }
    
} catch (Exception $e) {
    Response::error('Error updating review response: ' . $e->getMessage());
} 