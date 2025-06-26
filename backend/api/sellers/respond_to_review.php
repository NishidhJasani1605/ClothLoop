<?php
/**
 * Respond to Review API
 * Allows sellers to respond to product reviews
 */

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

// Get JSON data from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if data is valid JSON
if (!$data) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Include database connection
require_once '../../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Check if required fields are present
if (!isset($data['review_id']) || !isset($data['response']) || !isset($data['seller_id'])) {
    $response['message'] = 'Missing required fields';
    echo json_encode($response);
    exit;
}

// Get data from request
$reviewId = mysqli_real_escape_string($conn, $data['review_id']);
$responseText = mysqli_real_escape_string($conn, $data['response']);
$sellerId = mysqli_real_escape_string($conn, $data['seller_id']);
$reviewType = isset($data['review_type']) ? mysqli_real_escape_string($conn, $data['review_type']) : 'product';

// Validate data
if (empty($reviewId) || empty($responseText) || empty($sellerId)) {
    $response['message'] = 'Review ID, response text, and seller ID cannot be empty';
    echo json_encode($response);
    exit;
}

// Verify that the review exists and belongs to a product owned by this seller
if ($reviewType === 'product') {
    $checkQuery = "SELECT pr.id, p.seller_id 
                  FROM product_reviews pr
                  JOIN products p ON pr.product_id = p.id
                  WHERE pr.id = '$reviewId' AND p.seller_id = '$sellerId'";
} else {
    // For other review types like seller reviews
    $checkQuery = "SELECT id FROM seller_reviews WHERE id = '$reviewId' AND seller_id = '$sellerId'";
}

$checkResult = mysqli_query($conn, $checkQuery);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    $response['message'] = 'Review not found or you do not have permission to respond to this review';
    echo json_encode($response);
    exit;
}

// Check if a response table exists, if not create it
$checkTableQuery = "SHOW TABLES LIKE 'reviews_responses'";
$tableExists = mysqli_query($conn, $checkTableQuery);

if (!$tableExists || mysqli_num_rows($tableExists) === 0) {
    $createTableQuery = "CREATE TABLE reviews_responses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        review_id INT(11) NOT NULL,
        review_type VARCHAR(20) NOT NULL DEFAULT 'product',
        response TEXT NOT NULL,
        response_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_review (review_id, review_type)
    )";
    
    if (!mysqli_query($conn, $createTableQuery)) {
        $response['message'] = 'Failed to create responses table: ' . mysqli_error($conn);
        echo json_encode($response);
        exit;
    }
}

// Check if there's already a response for this review
$checkResponseQuery = "SELECT id FROM reviews_responses WHERE review_id = '$reviewId' AND review_type = '$reviewType'";
$responseExists = mysqli_query($conn, $checkResponseQuery);

if ($responseExists && mysqli_num_rows($responseExists) > 0) {
    // Update existing response
    $updateQuery = "UPDATE reviews_responses 
                   SET response = '$responseText', response_date = CURRENT_TIMESTAMP 
                   WHERE review_id = '$reviewId' AND review_type = '$reviewType'";
    
    if (mysqli_query($conn, $updateQuery)) {
        $response['status'] = 'success';
        $response['message'] = 'Response updated successfully';
    } else {
        $response['message'] = 'Failed to update response: ' . mysqli_error($conn);
    }
} else {
    // Insert new response
    $insertQuery = "INSERT INTO reviews_responses (review_id, review_type, response) 
                   VALUES ('$reviewId', '$reviewType', '$responseText')";
    
    if (mysqli_query($conn, $insertQuery)) {
        $response['status'] = 'success';
        $response['message'] = 'Response submitted successfully';
    } else {
        $response['message'] = 'Failed to submit response: ' . mysqli_error($conn);
    }
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response); 