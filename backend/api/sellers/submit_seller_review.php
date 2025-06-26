<?php
/**
 * Submit Seller Review API
 * Allows buyers to submit reviews for sellers
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// Check if user is authenticated
if (!Auth::isAuthenticated()) {
    Response::error('Unauthorized - Please log in to submit a review', null, 401);
}

// Get current user
$user = Auth::getCurrentUser();

// Verify user is a buyer
if ($user['role'] !== 'buyer') {
    Response::error('Only buyers can submit reviews', null, 403);
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (!isset($data['seller_id']) || !isset($data['rating']) || !isset($data['review_text']) || !isset($data['order_id'])) {
    Response::error('Missing required fields: seller_id, rating, review_text, and order_id are required', null, 400);
}

// Validate rating is between 1 and 5
if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
    Response::error('Rating must be a number between 1 and 5', null, 400);
}

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // First check if user has already reviewed this order
    $checkQuery = "SELECT id FROM reviews WHERE buyer_id = :buyer_id AND order_id = :order_id AND seller_id = :seller_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':buyer_id', $user['id']);
    $checkStmt->bindParam(':order_id', $data['order_id']);
    $checkStmt->bindParam(':seller_id', $data['seller_id']);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $db->rollBack();
        Response::error('You have already submitted a review for this order', null, 400);
    }
    
    // Verify the order exists and belongs to the user
    $orderQuery = "SELECT * FROM orders WHERE id = :order_id AND buyer_id = :buyer_id AND seller_id = :seller_id AND status = 'completed'";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':order_id', $data['order_id']);
    $orderStmt->bindParam(':buyer_id', $user['id']);
    $orderStmt->bindParam(':seller_id', $data['seller_id']);
    $orderStmt->execute();
    
    if ($orderStmt->rowCount() === 0) {
        $db->rollBack();
        Response::error('Invalid order or you are not authorized to review this order', null, 400);
    }
    
    // Insert the review
    $query = "INSERT INTO reviews 
              (seller_id, buyer_id, order_id, rating, review_text, created_at) 
              VALUES 
              (:seller_id, :buyer_id, :order_id, :rating, :review_text, NOW())";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':seller_id', $data['seller_id']);
    $stmt->bindParam(':buyer_id', $user['id']);
    $stmt->bindParam(':order_id', $data['order_id']);
    $stmt->bindParam(':rating', $data['rating']);
    $stmt->bindParam(':review_text', $data['review_text']);
    
    if (!$stmt->execute()) {
        $db->rollBack();
        Response::error('Failed to submit review', null, 500);
    }
    
    $review_id = $db->lastInsertId();
    
    // Update seller's average rating
    $updateRatingQuery = "UPDATE sellers SET 
                         total_reviews = (SELECT COUNT(*) FROM reviews WHERE seller_id = :seller_id),
                         avg_rating = (SELECT ROUND(AVG(rating), 1) FROM reviews WHERE seller_id = :seller_id)
                         WHERE user_id = :seller_id";
                         
    $updateStmt = $db->prepare($updateRatingQuery);
    $updateStmt->bindParam(':seller_id', $data['seller_id']);
    
    if (!$updateStmt->execute()) {
        $db->rollBack();
        Response::error('Failed to update seller rating', null, 500);
    }
    
    // Commit transaction
    $db->commit();
    
    // Get the submitted review
    $getReviewQuery = "SELECT 
                        r.id, 
                        r.buyer_id as user_id, 
                        u.name as user_name, 
                        u.profile_photo, 
                        r.order_id, 
                        r.rating, 
                        r.review_text as comment, 
                        r.created_at,
                        r.seller_response,
                        r.response_date
                      FROM reviews r
                      JOIN users u ON r.buyer_id = u.id
                      WHERE r.id = :review_id";
                      
    $getReviewStmt = $db->prepare($getReviewQuery);
    $getReviewStmt->bindParam(':review_id', $review_id);
    $getReviewStmt->execute();
    $review = $getReviewStmt->fetch(PDO::FETCH_ASSOC);
    
    Response::success('Review submitted successfully', [
        'review' => [
            'id' => $review['id'],
            'user_id' => $review['user_id'],
            'user_name' => $review['user_name'],
            'profile_photo' => $review['profile_photo'],
            'order_id' => $review['order_id'],
            'rating' => (int)$review['rating'],
            'comment' => $review['comment'],
            'created_at' => $review['created_at'],
            'seller_response' => $review['seller_response'],
            'response_date' => $review['response_date']
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    Response::error('Error submitting review: ' . $e->getMessage());
} 