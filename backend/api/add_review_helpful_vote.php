<?php
// Enable cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed. Only POST requests are accepted."]);
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
    echo json_encode(["error" => "You must be logged in to vote on reviews."]);
    exit;
}

// Check if review_id is provided
if (!isset($_POST['review_id']) || empty($_POST['review_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Review ID is required."]);
    exit;
}

// Sanitize input
$review_id = intval($_POST['review_id']);
$user_id = $user['id'];

// Check if the review exists
$stmt = $conn->prepare("SELECT id, helpful_count FROM product_reviews WHERE id = ?");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Review not found."]);
    $stmt->close();
    $conn->close();
    exit;
}

$review = $result->fetch_assoc();
$stmt->close();

// Check if the user has already voted for this review
$stmt = $conn->prepare("SELECT id FROM review_helpful_votes WHERE review_id = ? AND user_id = ?");
$stmt->bind_param("ii", $review_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$already_voted = $result->num_rows > 0;
$stmt->close();

// Begin transaction
$conn->begin_transaction();

try {
    if ($already_voted) {
        // User already voted, so remove their vote
        $stmt = $conn->prepare("DELETE FROM review_helpful_votes WHERE review_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $review_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Decrement helpful count
        $new_count = $review['helpful_count'] > 0 ? $review['helpful_count'] - 1 : 0;
        $stmt = $conn->prepare("UPDATE product_reviews SET helpful_count = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_count, $review_id);
        $stmt->execute();
        $stmt->close();

        $response = [
            "success" => true,
            "message" => "Vote removed successfully.",
            "action" => "removed",
            "helpful_count" => $new_count
        ];
    } else {
        // Add user's vote
        $stmt = $conn->prepare("INSERT INTO review_helpful_votes (review_id, user_id, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $review_id, $user_id);
        $stmt->execute();
        $stmt->close();

        // Increment helpful count
        $new_count = $review['helpful_count'] + 1;
        $stmt = $conn->prepare("UPDATE product_reviews SET helpful_count = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_count, $review_id);
        $stmt->execute();
        $stmt->close();

        $response = [
            "success" => true,
            "message" => "Vote added successfully.",
            "action" => "added",
            "helpful_count" => $new_count
        ];
    }

    // Commit transaction
    $conn->commit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(["error" => "Database error occurred: " . $e->getMessage()]);
    $conn->close();
    exit;
}

// Close connection
$conn->close();

// Return response
echo json_encode($response);
?> 