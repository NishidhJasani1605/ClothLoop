<?php
/**
 * Update Seller Review API
 * Allows users to update their existing reviews for sellers
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, PATCH");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and model files
include_once '../../config/Database.php';
include_once '../../models/Review.php';
include_once '../../utils/Validator.php';
include_once '../../utils/Response.php';
include_once '../../utils/Auth.php';

// Make sure user is authenticated
Auth::requireAuth();
$current_user = Auth::getCurrentUser();
$user_id = $current_user['id'];

// Get PUT/PATCH data
$data = json_decode(file_get_contents("php://input"), true);

// Initialize validator
$validator = new Validator();

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate review object
$review = new Review($db);

// Validate required fields
if (!isset($data['review_id'])) {
    Response::error('Missing review ID', null, 400);
}

// Validate review_id
if (!$validator->validateId($data['review_id'])) {
    Response::error('Invalid review ID format', null, 400);
}

// Check if review exists and belongs to the current user
$review->id = $data['review_id'];
$existing_review = $review->getById($review->id);

if (!$existing_review) {
    Response::error('Review not found', null, 404);
}

if ($existing_review['user_id'] != $user_id) {
    Response::error('You can only update your own reviews', null, 403);
}

// Validate rating if provided
if (isset($data['rating'])) {
    if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        Response::error('Rating must be between 1 and 5', null, 400);
    }
    $review->rating = $data['rating'];
}

// Validate content if provided
if (isset($data['content'])) {
    if (strlen($data['content']) < 5 || strlen($data['content']) > 1000) {
        Response::error('Review content must be between 5 and 1000 characters', null, 400);
    }
    $review->content = $data['content'];
}

// Check if at least one field is being updated
if (!isset($data['rating']) && !isset($data['content'])) {
    Response::error('No fields to update', null, 400);
}

// Set the updated timestamp
$review->updated_at = date('Y-m-d H:i:s');

// Update the review
try {
    if ($review->update()) {
        // Get the updated review with user info
        $updated_review = $review->getById($review->id);
        
        // Format the review for response
        $response_data = [
            'id' => $updated_review['id'],
            'seller_id' => $updated_review['seller_id'],
            'user_id' => $updated_review['user_id'],
            'user_name' => $updated_review['user_name'],
            'user_profile_image' => $updated_review['profile_photo'],
            'rating' => intval($updated_review['rating']),
            'content' => $updated_review['content'],
            'created_at' => $updated_review['created_at'],
            'updated_at' => $updated_review['updated_at']
        ];
        
        Response::success('Review updated successfully', $response_data);
    } else {
        Response::error('Failed to update review', null, 500);
    }
} catch (Exception $e) {
    Response::error('Error updating review: ' . $e->getMessage(), null, 500);
} 