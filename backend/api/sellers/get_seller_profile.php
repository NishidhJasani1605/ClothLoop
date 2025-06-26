<?php
/**
 * Get Seller Profile API
 * Retrieves the profile information of a seller
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

// Include database and model files
include_once '../../config/database.php';
include_once '../../models/User.php';
include_once '../../models/Seller.php';
include_once '../../models/Review.php';
include_once '../../models/Product.php';
include_once '../../utils/Validator.php';
include_once '../../utils/response.php';
include_once '../../utils/auth.php';

// Initialize validator
$validator = new Validator();

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate objects
$user = new User($db);
$seller = new Seller($db);
$review = new Review($db);
$product = new Product($db);

// Check if this is a request for the current logged-in seller or a specific seller
$seller_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$seller_id) {
    // If no ID provided, check if user is logged in
    Auth::requireAuth();
    $current_user = Auth::getCurrentUser();
    
    // Make sure the user is a seller
    if ($current_user['role'] !== 'seller') {
        Response::error('Access denied. This endpoint is for sellers only.', null, 403);
    }
    
    $seller_id = $current_user['id'];
}

// Validate seller ID
if (!$validator->validateId($seller_id)) {
    Response::error('Invalid seller ID format', null, 400);
}

try {
    // Get seller user information
    $user->id = $seller_id;
    $user_data = $user->getSingle();
    
    // Check if user exists and is a seller
    if (!$user_data || $user_data['role'] !== 'seller') {
        Response::error('Seller not found', null, 404);
    }
    
    // Get seller shop information
    $seller->id = $seller_id;
    $shop_data = $seller->getSingle();
    
    // Get seller rating summary (with error handling for missing tables)
    $rating_summary = [
        'average_rating' => 0,
        'total_reviews' => 0,
        'rating_5' => 0,
        'rating_4' => 0,
        'rating_3' => 0,
        'rating_2' => 0,
        'rating_1' => 0
    ];
    try {
        $rating_summary = $review->getSellerRatingSummary($seller_id);
    } catch (Exception $e) {
        // If table doesn't exist, we'll use the defaults
        if (strpos($e->getMessage(), "doesn't exist") === false) {
            // Re-throw if it's not a missing table error
            throw $e;
        }
    }
    
    // Get seller product count (with error handling for missing tables)
    $product_count = 0;
    try {
        $product_count = $product->countSellerProducts($seller_id);
    } catch (Exception $e) {
        // If table doesn't exist, we'll use the default (0)
        if (strpos($e->getMessage(), "doesn't exist") === false) {
            // Re-throw if it's not a missing table error
            throw $e;
        }
    }
    
    // Prepare response data
    $response_data = [
        'id' => $user_data['id'],
        'name' => $user_data['name'],
        'email' => $user_data['email'],
        'phone' => $user_data['phone_no'] ?? null,
        'profile_image' => $user_data['profile_photo'] ?? null,
        'shop_name' => $shop_data['shop_name'] ?? null,
        'description' => $shop_data['description'] ?? null,
        'address' => $shop_data['address'] ?? null,
        'latitude' => $shop_data['latitude'] ?? null,
        'longitude' => $shop_data['longitude'] ?? null,
        'created_at' => $user_data['created_at'],
        'rating' => [
            'average' => floatval($rating_summary['average_rating'] ?? 0),
            'count' => intval($rating_summary['total_reviews'] ?? 0),
            'distribution' => [
                '5' => intval($rating_summary['rating_5'] ?? 0),
                '4' => intval($rating_summary['rating_4'] ?? 0),
                '3' => intval($rating_summary['rating_3'] ?? 0),
                '2' => intval($rating_summary['rating_2'] ?? 0),
                '1' => intval($rating_summary['rating_1'] ?? 0)
            ]
        ],
        'product_count' => intval($product_count)
    ];
    
    // Send success response
    Response::success('Seller profile retrieved successfully', $response_data);
    
} catch (Exception $e) {
    Response::error('Error retrieving seller profile: ' . $e->getMessage(), null, 500);
} 