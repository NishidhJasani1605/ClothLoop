<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// Include database connection and authentication
require_once '../config/db_connect.php';
require_once '../auth/auth.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Check if user is authenticated
$userId = authenticate($conn);
if (!$userId) {
    $response['message'] = 'Authentication required';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $response['message'] = 'Invalid JSON data';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Validate required fields
$requiredFields = ['product_id', 'rating', 'review_text'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        $response['message'] = "Field '$field' is required";
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
}

// Validate and sanitize input
$productId = mysqli_real_escape_string($conn, $input['product_id']);
$rating = intval($input['rating']);
$reviewText = mysqli_real_escape_string($conn, $input['review_text']);
$images = isset($input['images']) ? $input['images'] : [];

// Validate rating (1-5)
if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Rating must be between 1 and 5';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Validate review text length
if (strlen($reviewText) < 10 || strlen($reviewText) > 1000) {
    $response['message'] = 'Review text must be between 10 and 1000 characters';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Check if product exists
$productQuery = "SELECT * FROM products WHERE id = '$productId'";
$productResult = mysqli_query($conn, $productQuery);

if (!$productResult || mysqli_num_rows($productResult) === 0) {
    $response['message'] = 'Product not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Check if user has purchased this product
$purchaseQuery = "SELECT * FROM orders o 
                  INNER JOIN order_items oi ON o.id = oi.order_id 
                  WHERE o.user_id = '$userId' 
                  AND oi.product_id = '$productId' 
                  AND o.status = 'completed'";
$purchaseResult = mysqli_query($conn, $purchaseQuery);
$verifiedPurchase = ($purchaseResult && mysqli_num_rows($purchaseResult) > 0) ? 1 : 0;

// Check if user has already reviewed this product
$existingReviewQuery = "SELECT * FROM product_reviews WHERE user_id = '$userId' AND product_id = '$productId'";
$existingReviewResult = mysqli_query($conn, $existingReviewQuery);

if ($existingReviewResult && mysqli_num_rows($existingReviewResult) > 0) {
    $response['message'] = 'You have already reviewed this product';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Create review
$currentDate = date('Y-m-d H:i:s');
$insertQuery = "INSERT INTO product_reviews (product_id, user_id, rating, review_text, verified_purchase, created_at, updated_at) 
                VALUES ('$productId', '$userId', '$rating', '$reviewText', '$verifiedPurchase', '$currentDate', '$currentDate')";

if (mysqli_query($conn, $insertQuery)) {
    $reviewId = mysqli_insert_id($conn);
    
    // Add review images if provided
    if (!empty($images) && is_array($images)) {
        foreach ($images as $imageUrl) {
            $safeImageUrl = mysqli_real_escape_string($conn, $imageUrl);
            $imageQuery = "INSERT INTO review_images (review_id, image_url, created_at) 
                          VALUES ('$reviewId', '$safeImageUrl', '$currentDate')";
            mysqli_query($conn, $imageQuery);
        }
    }
    
    // Get user data
    $userQuery = "SELECT name, email, profile_image FROM users WHERE id = '$userId'";
    $userResult = mysqli_query($conn, $userQuery);
    $userData = mysqli_fetch_assoc($userResult);
    
    // Mask email for privacy
    $maskedEmail = '';
    if (isset($userData['email']) && !empty($userData['email'])) {
        $parts = explode('@', $userData['email']);
        if (count($parts) > 1) {
            $username = $parts[0];
            $domain = $parts[1];
            $maskedEmail = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2) . '@' . $domain;
        }
    }
    
    // Return created review
    $response = [
        'status' => 'success',
        'message' => 'Review added successfully',
        'data' => [
            'id' => $reviewId,
            'product_id' => $productId,
            'user_id' => $userId,
            'user_name' => $userData['name'] ?? 'Anonymous',
            'user_email' => $maskedEmail,
            'profile_image' => $userData['profile_image'] ?? null,
            'rating' => $rating,
            'review_text' => $reviewText,
            'verified_purchase' => (bool)$verifiedPurchase,
            'created_at' => date('M d, Y'),
            'images' => $images,
            'helpful_votes' => 0
        ]
    ];
    
    http_response_code(201);
} else {
    $response['message'] = 'Failed to add review: ' . mysqli_error($conn);
    http_response_code(500);
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 