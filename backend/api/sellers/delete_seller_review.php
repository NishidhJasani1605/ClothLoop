<?php
/**
 * Delete Seller Review API
 * Allows users to delete their reviews for sellers
 */

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if request method is DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use DELETE.'
    ]);
    exit;
}

// Include database connection
require_once '../../config/db_connect.php';

// Include authentication
require_once '../../auth/authenticate.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Check if user is admin
$checkAdminQuery = "SELECT role FROM users WHERE id = '$user_id'";
$adminResult = mysqli_query($conn, $checkAdminQuery);

if (!$adminResult || mysqli_num_rows($adminResult) === 0) {
    $response['message'] = 'Authentication failed';
    echo json_encode($response);
    exit;
}

$userData = mysqli_fetch_assoc($adminResult);
if ($userData['role'] !== 'admin') {
    $response['message'] = 'Access denied. Only administrators can delete reviews';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Get review ID from URL parameter
$reviewId = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : null;

// Validate review ID
if (!$reviewId || !is_numeric($reviewId)) {
    $response['message'] = 'Invalid or missing review ID';
    echo json_encode($response);
    exit;
}

// Check if the review exists
$checkReviewQuery = "SELECT * FROM seller_reviews WHERE id = '$reviewId'";
$reviewResult = mysqli_query($conn, $checkReviewQuery);

if (!$reviewResult || mysqli_num_rows($reviewResult) === 0) {
    $response['message'] = 'Review not found';
    echo json_encode($response);
    exit;
}

// Get review details before deletion for logging purposes
$reviewData = mysqli_fetch_assoc($reviewResult);

// Delete the review
$deleteQuery = "DELETE FROM seller_reviews WHERE id = '$reviewId'";
$deleteResult = mysqli_query($conn, $deleteQuery);

if ($deleteResult) {
    // Log the deletion action
    $logQuery = "INSERT INTO admin_logs (admin_id, action, details, created_at) 
                VALUES ('$user_id', 'delete_review', 'Deleted seller review ID: $reviewId for seller ID: {$reviewData['seller_id']}', NOW())";
    mysqli_query($conn, $logQuery);
    
    $response['status'] = 'success';
    $response['message'] = 'Review deleted successfully';
    $response['data'] = [
        'deleted_review_id' => $reviewId,
        'seller_id' => $reviewData['seller_id']
    ];
} else {
    $response['message'] = 'Failed to delete review: ' . mysqli_error($conn);
}

// Close database connection
mysqli_close($conn);

// Return the response
echo json_encode($response);
?> 