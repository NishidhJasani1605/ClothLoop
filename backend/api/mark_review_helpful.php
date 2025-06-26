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
require_once '../auth/auth_functions.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Check if user is authenticated
$auth = isAuthenticated();
if (!$auth['success']) {
    $response['message'] = 'Authentication failed. Please log in.';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$userId = $auth['user_id'];

// Get JSON data from the request
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate required data
if (!isset($data['review_id']) || empty($data['review_id'])) {
    $response['message'] = "Parameter 'review_id' is required";
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Validate and sanitize input
$reviewId = mysqli_real_escape_string($conn, $data['review_id']);

// Check if review exists
$reviewQuery = "SELECT id FROM product_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $reviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Check if user has already marked this review as helpful
$checkQuery = "SELECT id FROM review_helpful_votes WHERE user_id = '$userId' AND review_id = '$reviewId'";
$checkResult = mysqli_query($conn, $checkQuery);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    // User has already marked this review as helpful, so remove their vote
    $deleteQuery = "DELETE FROM review_helpful_votes WHERE user_id = '$userId' AND review_id = '$reviewId'";
    
    if (mysqli_query($conn, $deleteQuery)) {
        // Update the helpful_votes count in the product_reviews table
        $updateQuery = "UPDATE product_reviews SET helpful_votes = helpful_votes - 1 WHERE id = '$reviewId'";
        mysqli_query($conn, $updateQuery);
        
        $response = [
            'status' => 'success',
            'message' => 'Helpful vote removed successfully',
            'data' => [
                'review_id' => $reviewId,
                'marked_helpful' => false
            ]
        ];
    } else {
        $response['message'] = 'Failed to remove helpful vote: ' . mysqli_error($conn);
        http_response_code(500);
    }
} else {
    // User has not marked this review as helpful yet, so add their vote
    $insertQuery = "INSERT INTO review_helpful_votes (user_id, review_id, created_at) VALUES ('$userId', '$reviewId', NOW())";
    
    if (mysqli_query($conn, $insertQuery)) {
        // Update the helpful_votes count in the product_reviews table
        $updateQuery = "UPDATE product_reviews SET helpful_votes = helpful_votes + 1 WHERE id = '$reviewId'";
        mysqli_query($conn, $updateQuery);
        
        $response = [
            'status' => 'success',
            'message' => 'Review marked as helpful',
            'data' => [
                'review_id' => $reviewId,
                'marked_helpful' => true
            ]
        ];
    } else {
        $response['message'] = 'Failed to mark review as helpful: ' . mysqli_error($conn);
        http_response_code(500);
    }
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 