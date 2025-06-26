<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and session files
require_once '../../config/database.php';
require_once '../check_session.php';

// Check if method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Method not allowed. Please use POST."]);
    exit;
}

// Check if user is logged in and is a buyer
if (!isSessionValid() || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'buyer') {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Unauthorized. Please log in as a buyer."]);
    exit;
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check if product_id is provided
if (!isset($data->product_id) || empty($data->product_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Unable to add interest. Product ID is required."]);
    exit;
}

$product_id = $data->product_id;
$buyer_id = $_SESSION['user']['id'];

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $checkProduct = "SELECT id FROM products WHERE id = ?";
    $stmt = $db->prepare($checkProduct);
    $stmt->execute([$product_id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(["message" => "Product not found."]);
        exit;
    }
    
    // Check if interest already exists
    $checkInterest = "SELECT id FROM customer_interests WHERE product_id = ? AND buyer_id = ?";
    $stmt = $db->prepare($checkInterest);
    $stmt->execute([$product_id, $buyer_id]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(["message" => "Interest already exists."]);
        exit;
    }
    
    // Add the interest
    $query = "INSERT INTO customer_interests (product_id, buyer_id, created_at) VALUES (?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id, $buyer_id]);
    
    if ($stmt->rowCount() > 0) {
        http_response_code(201); // Created
        echo json_encode(["message" => "Interest successfully added."]);
    } else {
        http_response_code(503); // Service Unavailable
        echo json_encode(["message" => "Unable to add interest."]);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?> 