<?php
// Set the appropriate CORS headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = [
    'http://localhost', 
    'http://127.0.0.1',
    'http://localhost:8080',
    'http://localhost:3000'
];

// Allow from any of the allowed origins
if (in_array($origin, $allowed_origins) || strpos($origin, 'clothloop') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // Fallback for development
    header("Access-Control-Allow-Origin: *");
}

// Always set these headers
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Check if products table exists and has the rental_price column
    try {
        $checkColumn = $conn->prepare("SHOW COLUMNS FROM products LIKE 'rental_price'");
        $checkColumn->execute();
        $hasRentalPrice = $checkColumn->rowCount() > 0;
        
        // If rental_price column doesn't exist, check for price column
        if (!$hasRentalPrice) {
            $checkColumn = $conn->prepare("SHOW COLUMNS FROM products LIKE 'price'");
            $checkColumn->execute();
            $hasPrice = $checkColumn->rowCount() > 0;
            
            if (!$hasPrice) {
                // Neither column exists, log an error
                error_log("Error: Neither 'rental_price' nor 'price' column exists in products table");
            }
        }
    } catch (PDOException $e) {
        error_log("Error checking columns: " . $e->getMessage());
    }
    
    // Initialize stats with default values
    $productsCount = 0;
    $avgPrice = 0;
    $userCounts = ['buyers' => 0, 'sellers' => 0];
    $orderStats = ['total_orders' => 0, 'total_revenue' => 0];
    $categories = [];
    
    // Get total products count
    try {
        $productQuery = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $productStmt = $conn->prepare($productQuery);
        $productStmt->execute();
        $productsCount = $productStmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (PDOException $e) {
        error_log("Error getting product count: " . $e->getMessage());
    }
    
    // Get average product price
    try {
        if (isset($hasRentalPrice) && $hasRentalPrice) {
            $avgPriceQuery = "SELECT AVG(rental_price) as avg_price FROM products WHERE status = 'active'";
        } else if (isset($hasPrice) && $hasPrice) {
            $avgPriceQuery = "SELECT AVG(price) as avg_price FROM products WHERE status = 'active'";
        } else {
            throw new PDOException("No price column available");
        }
        
        $avgPriceStmt = $conn->prepare($avgPriceQuery);
        $avgPriceStmt->execute();
        $result = $avgPriceStmt->fetch(PDO::FETCH_ASSOC);
        $avgPrice = $result['avg_price'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error getting average price: " . $e->getMessage());
    }
    
    // Get total users count
    try {
        $userQuery = "SELECT 
                        (SELECT COUNT(*) FROM buyers) as buyers,
                        (SELECT COUNT(*) FROM sellers) as sellers";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->execute();
        $userCounts = $userStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user counts: " . $e->getMessage());
    }
    
    // Get total orders count and revenue
    try {
        $orderQuery = "SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM orders";
        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute();
        $orderStats = $orderStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting order stats: " . $e->getMessage());
    }
    
    // Get product categories distribution
    try {
        $categoryQuery = "SELECT c.name, COUNT(p.id) as count 
                        FROM products p
                        JOIN categories c ON p.category_id = c.id
                        WHERE p.status = 'active'
                        GROUP BY c.name
                        ORDER BY count DESC";
        $categoryStmt = $conn->prepare($categoryQuery);
        $categoryStmt->execute();
        $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting category distribution: " . $e->getMessage());
    }
    
    // Compile all stats
    $response['data'] = [
        'products_count' => $productsCount,
        'avg_product_price' => round($avgPrice, 2),
        'buyers_count' => $userCounts['buyers'],
        'sellers_count' => $userCounts['sellers'],
        'total_users' => $userCounts['buyers'] + $userCounts['sellers'],
        'orders_count' => $orderStats['total_orders'],
        'total_revenue' => $orderStats['total_revenue'],
        'category_distribution' => $categories
    ];
    
    $response['success'] = true;
    $response['message'] = 'Dashboard statistics retrieved successfully';
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Return JSON response
echo json_encode($response); 