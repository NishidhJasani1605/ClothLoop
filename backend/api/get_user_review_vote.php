<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use GET.'
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

// Validate required parameters
if (!isset($_GET['review_id']) || empty($_GET['review_id'])) {
    $response['message'] = "Parameter 'review_id' is required";
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Validate and sanitize input
$reviewId = mysqli_real_escape_string($conn, $_GET['review_id']);

// Check if review exists
$reviewQuery = "SELECT id FROM product_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $reviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Check if user has marked this review as helpful
$voteQuery = "SELECT id FROM review_helpful_votes WHERE user_id = '$userId' AND review_id = '$reviewId'";
$voteResult = mysqli_query($conn, $voteQuery);

if ($voteResult) {
    $hasVoted = mysqli_num_rows($voteResult) > 0;
    
    $response = [
        'status' => 'success',
        'data' => [
            'review_id' => $reviewId,
            'has_voted' => $hasVoted
        ]
    ];
} else {
    $response['message'] = 'Failed to check vote status: ' . mysqli_error($conn);
    http_response_code(500);
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 