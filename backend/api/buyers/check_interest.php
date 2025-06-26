<?php
/**
 * API endpoint to check if a buyer is interested in a product
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
    'interested' => false,
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $response['message'] = 'Invalid request method. Only GET is allowed.';
        echo json_encode($response);
        exit();
    }

    // Get query parameters
    $buyerId = isset($_GET['buyer_id']) ? filter_var($_GET['buyer_id'], FILTER_VALIDATE_INT) : null;
    $productId = isset($_GET['product_id']) ? filter_var($_GET['product_id'], FILTER_VALIDATE_INT) : null;

    if (!$buyerId || !$productId) {
        $response['message'] = 'Missing or invalid buyer_id or product_id';
        echo json_encode($response);
        exit();
    }

    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();

    // Check if interest exists
    $stmt = $conn->prepare("SELECT id FROM customer_interests WHERE buyer_id = :buyer_id AND product_id = :product_id");
    $stmt->bindParam(':buyer_id', $buyerId);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();

    $interest = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($interest) {
        $response['status'] = 'success';
        $response['message'] = 'Buyer is interested in this product';
        $response['interested'] = true;
    } else {
        $response['status'] = 'success';
        $response['message'] = 'Buyer is not interested in this product';
        $response['interested'] = false;
    }

} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
} finally {
    echo json_encode($response);
} 