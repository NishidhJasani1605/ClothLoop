<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
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

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

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
$reviewQuery = "SELECT helpful_votes FROM product_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $reviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

// Get helpful votes count
$reviewData = mysqli_fetch_assoc($reviewResult);
$helpfulVotes = (int)$reviewData['helpful_votes'];

// Count total number of votes
$votesCountQuery = "SELECT COUNT(*) as total_votes FROM review_helpful_votes WHERE review_id = '$reviewId'";
$votesCountResult = mysqli_query($conn, $votesCountQuery);
$totalVotes = 0;

if ($votesCountResult && mysqli_num_rows($votesCountResult) > 0) {
    $votesData = mysqli_fetch_assoc($votesCountResult);
    $totalVotes = (int)$votesData['total_votes'];
}

$response = [
    'status' => 'success',
    'data' => [
        'review_id' => $reviewId,
        'helpful_votes' => $helpfulVotes,
        'total_votes' => $totalVotes
    ]
];

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 