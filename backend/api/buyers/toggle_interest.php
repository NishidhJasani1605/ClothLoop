<?php
/**
 * API endpoint to toggle customer interest in a product
 * If interest exists, it will be removed
 * If interest doesn't exist, it will be added
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'action' => '',
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Invalid request method. Only POST is allowed.';
        echo json_encode($response);
        exit();
    }

    // Get POST data (either from form or JSON)
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // If JSON parsing failed, try POST data
    if (!$data) {
        $data = $_POST;
    }

    // Check required fields
    if (!isset($data['buyer_id']) || !isset($data['product_id'])) {
        $response['message'] = 'Missing required fields: buyer_id and product_id';
        echo json_encode($response);
        exit();
    }

    $buyerId = filter_var($data['buyer_id'], FILTER_VALIDATE_INT);
    $productId = filter_var($data['product_id'], FILTER_VALIDATE_INT);

    if (!$buyerId || !$productId) {
        $response['message'] = 'Invalid buyer_id or product_id';
        echo json_encode($response);
        exit();
    }

    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();

    // First check if interest already exists
    $checkStmt = $conn->prepare("SELECT id FROM customer_interests WHERE buyer_id = :buyer_id AND product_id = :product_id");
    $checkStmt->bindParam(':buyer_id', $buyerId);
    $checkStmt->bindParam(':product_id', $productId);
    $checkStmt->execute();

    $interest = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($interest) {
        // Interest exists, so remove it
        $deleteStmt = $conn->prepare("DELETE FROM customer_interests WHERE id = :id");
        $deleteStmt->bindParam(':id', $interest['id']);
        $deleteStmt->execute();

        $response['status'] = 'success';
        $response['message'] = 'Interest removed successfully';
        $response['action'] = 'removed';
    } else {
        // Interest doesn't exist, so add it
        $insertStmt = $conn->prepare("INSERT INTO customer_interests (buyer_id, product_id) VALUES (:buyer_id, :product_id)");
        $insertStmt->bindParam(':buyer_id', $buyerId);
        $insertStmt->bindParam(':product_id', $productId);
        $insertStmt->execute();

        $response['status'] = 'success';
        $response['message'] = 'Interest added successfully';
        $response['action'] = 'added';
    }

} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
} finally {
    echo json_encode($response);
} 