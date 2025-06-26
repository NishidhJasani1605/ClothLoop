<?php
// Enable cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed. Only GET requests are accepted."]);
    exit;
}

// Include database connection and authentication
require_once '../db/db_connect.php';
require_once '../auth/auth_functions.php';

// Initialize response array
$response = array();

// Check if user is authenticated
$user = authenticate();
if (!$user) {
    http_response_code(401);
    echo json_encode(["error" => "You must be logged in to view your votes."]);
    exit;
}

// Check if review_ids parameter is provided
if (!isset($_GET['review_ids']) || empty($_GET['review_ids'])) {
    http_response_code(400);
    echo json_encode(["error" => "Review IDs parameter is required."]);
    exit;
}

// Parse review IDs from comma-separated string
$review_ids_string = $_GET['review_ids'];
$review_ids_array = explode(',', $review_ids_string);

// Sanitize review IDs
$sanitized_ids = array();
foreach ($review_ids_array as $id) {
    if (is_numeric(trim($id))) {
        $sanitized_ids[] = intval(trim($id));
    }
}

if (empty($sanitized_ids)) {
    http_response_code(400);
    echo json_encode(["error" => "No valid review IDs provided."]);
    exit;
}

// Get user ID
$user_id = $user['id'];

// Prepare placeholders for the query
$placeholders = str_repeat('?,', count($sanitized_ids) - 1) . '?';
$types = str_repeat('i', count($sanitized_ids));

// Prepare and execute query to get user's votes
$stmt = $conn->prepare("SELECT review_id FROM review_helpful_votes WHERE user_id = ? AND review_id IN ($placeholders)");

// Create parameter array starting with user_id
$params = array_merge([$user_id], $sanitized_ids);

// Create reference array for bind_param
$bind_params = array();
$bind_params[] = "i" . $types; // "i" for user_id + types for review_ids

foreach ($params as $key => $value) {
    $bind_params[] = &$params[$key];
}

// Call bind_param with dynamic parameters
call_user_func_array(array($stmt, 'bind_param'), $bind_params);
$stmt->execute();
$result = $stmt->get_result();

// Build the response with the user's votes
$voted_reviews = array();
while ($row = $result->fetch_assoc()) {
    $voted_reviews[] = intval($row['review_id']);
}

$response = [
    "success" => true,
    "voted_reviews" => $voted_reviews
];

// Close connection
$stmt->close();
$conn->close();

// Return response
echo json_encode($response);
?> 