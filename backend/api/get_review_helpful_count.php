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
$reviewQuery = "SELECT id, helpful_count FROM product_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $reviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

$reviewData = mysqli_fetch_assoc($reviewResult);

// Get the count of helpful votes
$countQuery = "SELECT COUNT(*) as vote_count FROM review_helpful_votes WHERE review_id = '$reviewId'";
$countResult = mysqli_query($conn, $countQuery);

if ($countResult) {
    $voteData = mysqli_fetch_assoc($countResult);
    $helpfulCount = (int)$voteData['vote_count'];
    
    // If the count in the product_reviews table doesn't match the actual count,
    // update the product_reviews table
    if ($helpfulCount !== (int)$reviewData['helpful_count']) {
        $updateQuery = "UPDATE product_reviews SET helpful_count = $helpfulCount WHERE id = '$reviewId'";
        mysqli_query($conn, $updateQuery);
    }
    
    $response = [
        'status' => 'success',
        'data' => [
            'review_id' => $reviewId,
            'helpful_count' => $helpfulCount
        ]
    ];
} else {
    $response['message'] = 'Failed to get helpful count: ' . mysqli_error($conn);
    http_response_code(500);
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 