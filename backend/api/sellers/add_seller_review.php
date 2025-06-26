<?php
/**
 * Add Seller Review API
 * Allows authenticated users to submit reviews for sellers
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and model files
include_once '../../config/Database.php';
include_once '../../models/Review.php';
include_once '../../models/User.php';
include_once '../../models/Seller.php';
include_once '../../models/Order.php';
include_once '../../utils/Validator.php';
include_once '../../utils/Response.php';
include_once '../../utils/Auth.php';

// Make sure user is authenticated
Auth::requireAuth();
$current_user = Auth::getCurrentUser();
$user_id = $current_user['id'];

// Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Initialize validator and other objects
$validator = new Validator();

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate objects
$review = new Review($db);
$user = new User($db);
$seller = new Seller($db);
$order = new Order($db);

// Validate required fields
if (!isset($data['seller_id']) || 
    !isset($data['rating']) || 
    !isset($data['content'])) {
    Response::error('Missing required fields', null, 400);
}

// Validate seller_id
if (!$validator->validateId($data['seller_id'])) {
    Response::error('Invalid seller ID format', null, 400);
}

// Validate rating is between 1 and 5
if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
    Response::error('Rating must be between 1 and 5', null, 400);
}

// Validate content length
if (strlen($data['content']) < 5 || strlen($data['content']) > 1000) {
    Response::error('Review content must be between 5 and 1000 characters', null, 400);
}

// Check if seller exists
$seller->seller_id = $data['seller_id'];
$seller_data = $seller->getSingle();
if (!$seller_data) {
    Response::error('Seller not found', null, 404);
}

// Check if the user has purchased from this seller before
// This can be adjusted based on your business logic
$has_purchased = $order->hasUserPurchasedFromSeller($user_id, $data['seller_id']);
if (!$has_purchased) {
    Response::error('You can only review sellers you have purchased from', null, 403);
}

// Check if the user has already reviewed this seller
$existing_review = $review->getByUserAndSeller($user_id, $data['seller_id']);
if ($existing_review) {
    Response::error('You have already reviewed this seller. Please update your existing review instead.', null, 409);
}

// Set review properties
$review->seller_id = $data['seller_id'];
$review->user_id = $user_id;
$review->rating = $data['rating'];
$review->content = $data['content'];
$review->created_at = date('Y-m-d H:i:s');

// Create the review
try {
    $db->beginTransaction();
    
    if ($review->create()) {
        $db->commit();
        
        // Get the created review with user info
        $created_review = $review->getById($review->id);
        
        // Format the review for response
        $response_data = [
            'id' => $created_review['id'],
            'seller_id' => $created_review['seller_id'],
            'user_id' => $created_review['user_id'],
            'user_name' => $created_review['user_name'],
            'user_profile_image' => $created_review['profile_photo'],
            'rating' => intval($created_review['rating']),
            'content' => $created_review['content'],
            'created_at' => $created_review['created_at']
        ];
        
        Response::success('Review created successfully', $response_data, 201);
    } else {
        $db->rollBack();
        Response::error('Failed to create review', null, 500);
    }
} catch (Exception $e) {
    $db->rollBack();
    Response::error('Error creating review: ' . $e->getMessage(), null, 500);
} 