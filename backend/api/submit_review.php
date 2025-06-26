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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Get POST data (try both JSON and form data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    // If not JSON, try regular POST data
    $input = $_POST;
}

// Log the received data for debugging
error_log("Received review data: " . print_r($input, true));

// Check required fields
if (!isset($input['product_id']) || !isset($input['rating'])) {
    $response['message'] = 'Missing required fields: product_id and rating are required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Get review text from either 'review' or 'review_text' field
$reviewText = '';
if (isset($input['review'])) {
    $reviewText = $input['review'];
} elseif (isset($input['review_text'])) {
    $reviewText = $input['review_text'];
} else {
    $response['message'] = 'Review text is required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Sanitize inputs
$productId = mysqli_real_escape_string($conn, $input['product_id']);
$rating = intval($input['rating']);
$reviewText = mysqli_real_escape_string($conn, $reviewText);

// If buyer_id is not provided, use a default value of 1 instead of NULL
$buyerId = isset($input['buyer_id']) ? mysqli_real_escape_string($conn, $input['buyer_id']) : '1';

// Get user information if available
$userName = 'Anonymous User';
if (isset($input['user_name']) && !empty($input['user_name'])) {
    $userName = mysqli_real_escape_string($conn, $input['user_name']);
} else if ($buyerId !== 'NULL' && $buyerId > 0) {
    // Try to get user name from database
    $userQuery = "SELECT name FROM users WHERE id = $buyerId";
    $userResult = mysqli_query($conn, $userQuery);
    if ($userResult && mysqli_num_rows($userResult) > 0) {
        $userRow = mysqli_fetch_assoc($userResult);
        $userName = $userRow['name'];
    }
}

// Log additional information for debugging
error_log("Review from: $userName (Buyer ID: $buyerId)");

// Validate rating (1-5)
if ($rating < 1 || $rating > 5) {
    $response['message'] = 'Rating must be between 1 and 5';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Current timestamp for created_at
$currentDate = date('Y-m-d H:i:s');

// Create the SQL query to insert the review
$insertQuery = "INSERT INTO product_reviews (product_id, buyer_id, rating, review, created_at) 
                VALUES ('$productId', $buyerId, $rating, '$reviewText', '$currentDate')";

// Log the query for debugging
error_log("Executing query: $insertQuery");

// Execute the query
if (mysqli_query($conn, $insertQuery)) {
    $reviewId = mysqli_insert_id($conn);
    
    $response = [
        'status' => 'success',
        'message' => 'Review submitted successfully',
        'data' => [
            'id' => $reviewId,
            'product_id' => $productId,
            'buyer_id' => $buyerId,
            'rating' => $rating,
            'review' => $reviewText,
            'created_at' => $currentDate
        ]
    ];
    
    http_response_code(201); // Created
} else {
    $error = mysqli_error($conn);
    error_log("Database error: $error");
    $response['message'] = "Failed to submit review: $error";
    http_response_code(500);
}

// Close database connection
mysqli_close($conn);

// Return the response
echo json_encode($response);
?> 