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
$reviewQuery = "SELECT * FROM product_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $reviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Check if user has already marked this review as helpful
$helpfulQuery = "SELECT * FROM review_helpful_votes WHERE review_id = '$reviewId' AND user_id = '$userId'";
$helpfulResult = mysqli_query($conn, $helpfulQuery);

$isHelpful = ($helpfulResult && mysqli_num_rows($helpfulResult) > 0);

// Get total helpful votes for this review
$totalVotesQuery = "SELECT helpful_votes FROM product_reviews WHERE id = '$reviewId'";
$totalVotesResult = mysqli_query($conn, $totalVotesQuery);
$totalVotes = 0;

if ($totalVotesResult && mysqli_num_rows($totalVotesResult) > 0) {
    $totalVotesData = mysqli_fetch_assoc($totalVotesResult);
    $totalVotes = (int)$totalVotesData['helpful_votes'];
}

$response = [
    'status' => 'success',
    'data' => [
        'review_id' => $reviewId,
        'is_helpful' => $isHelpful,
        'total_helpful_votes' => $totalVotes
    ]
];

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 