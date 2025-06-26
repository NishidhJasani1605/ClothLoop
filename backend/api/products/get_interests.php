<?php
// Required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and session files
require_once '../../config/database.php';
require_once '../check_session.php';

// Check if method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["message" => "Method not allowed. Please use GET."]);
    exit;
}

// Check if user is logged in and is a seller
if (!isSessionValid() || !isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'seller') {
    http_response_code(401); // Unauthorized
    echo json_encode(["message" => "Unauthorized. Please log in as a seller."]);
    exit;
}

// Check if product_id is provided
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["message" => "Unable to get interests. Product ID is required."]);
    exit;
}

$product_id = $_GET['product_id'];

try {
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Make sure the product belongs to the current seller
    $checkProduct = "SELECT p.id FROM products p 
                    JOIN sellers s ON p.seller_id = s.id 
                    WHERE p.id = ? AND s.user_id = ?";
    $stmt = $db->prepare($checkProduct);
    $stmt->execute([$product_id, $_SESSION['user']['id']]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(403); // Forbidden
        echo json_encode(["message" => "Access denied. This product does not belong to you."]);
        exit;
    }
    
    // Get interests for the product
    $query = "SELECT ci.id, ci.product_id, ci.buyer_id, ci.created_at, 
              u.username, u.email, b.phone, b.address
              FROM customer_interests ci
              JOIN buyers b ON ci.buyer_id = b.user_id
              JOIN users u ON b.user_id = u.id
              WHERE ci.product_id = ?
              ORDER BY ci.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$product_id]);
    
    if ($stmt->rowCount() > 0) {
        // Interests array
        $interests_arr = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $interest_item = [
                "id" => $row['id'],
                "product_id" => $row['product_id'],
                "buyer_id" => $row['buyer_id'],
                "username" => $row['username'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "address" => $row['address'],
                "created_at" => $row['created_at']
            ];
            
            array_push($interests_arr, $interest_item);
        }
        
        http_response_code(200); // OK
        echo json_encode([
            "message" => "Interests found.",
            "count" => count($interests_arr),
            "interests" => $interests_arr
        ]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(["message" => "No interests found for this product."]);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?> 