<?php
/**
 * API endpoint to synchronize a user's wishlist between client and server
 * - GET: Retrieve current user's wishlist from database
 * - POST: Update database with client's wishlist
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Log to debug
$logFile = '/tmp/wishlist_debug.log';
function logDebug($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logDebug("Sync wishlist request received: " . $_SERVER['REQUEST_METHOD']);
logDebug("Session data: " . json_encode($_SESSION));

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    logDebug("Database connection established");
    
    // Check if wishlist table exists
    $checkTableStmt = $conn->prepare("SHOW TABLES LIKE 'wishlist'");
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() == 0) {
        logDebug("wishlist table does not exist, creating it");
        // Table doesn't exist, so create it
        $createTableQuery = "
            CREATE TABLE wishlist (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                buyer_id INT(11) NOT NULL,
                product_id INT(11) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_wishlist_item (buyer_id, product_id),
                INDEX idx_buyer (buyer_id),
                INDEX idx_product (product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $conn->exec($createTableQuery);
        logDebug("wishlist table created successfully");
    } else {
        logDebug("wishlist table already exists");
    }
    
    // Attempt to get user ID
    $userId = null;
    
    // First check if user is authenticated using Auth utility
    if (class_exists('Auth') && method_exists('Auth', 'checkSession') && Auth::checkSession()) {
        $user = Auth::getCurrentUser();
        $userId = $user['id'];
        logDebug("User authenticated via Auth class: user_id = {$userId}, role = {$user['role']}");
        
        // Verify user is a buyer
        if (isset($user['role']) && $user['role'] !== 'buyer') {
            logDebug("User is not a buyer, role = {$user['role']}");
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Only buyers can manage wishlists',
                'data' => null
            ]);
            exit();
        }
    } else {
        // Manual session check
        logDebug("Auth class check failed, trying manual session check");
        
        // Check for user_id in $_SESSION
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            logDebug("Found user_id in session: {$userId}");
        } 
        else if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
            logDebug("Found user.id in session: {$userId}");
        }
        // Check POST data
        else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            logDebug("POST data: " . $json);
            
            if (isset($data['user_id'])) {
                $userId = $data['user_id'];
                logDebug("Found user_id in POST data: {$userId}");
            }
        }
        // Check GET parameters
        else if (isset($_GET['user_id'])) {
            $userId = $_GET['user_id'];
            logDebug("Found user_id in GET params: {$userId}");
        }
        
        if (!$userId) {
            logDebug("No user_id found in any source, authentication failed");
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Authentication required. Please log in.',
                'data' => null
            ]);
            exit();
        } else {
            logDebug("Using user_id from alternate source: {$userId}");
        }
    }
    
    // Handle GET request - retrieve wishlist from database
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        logDebug("Processing GET request for user_id: {$userId}");
        
        // Get products from wishlist table
        $stmt = $conn->prepare("
            SELECT w.product_id, p.title, p.rental_price, p.description, pi.image_path,
                   u.name as seller_name
            FROM wishlist w
            LEFT JOIN products p ON w.product_id = p.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            LEFT JOIN users u ON p.seller_id = u.id
            WHERE w.buyer_id = :buyer_id
            ORDER BY w.created_at DESC
        ");
        $stmt->bindParam(':buyer_id', $userId);
        $stmt->execute();
        
        $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logDebug("Found " . count($wishlistItems) . " items in wishlist");
        
        // Format the response
        $responseData = [
            'status' => 'success',
            'message' => 'Wishlist retrieved successfully',
            'data' => [
                'items' => $wishlistItems,
                'ids' => array_column($wishlistItems, 'product_id')
            ]
        ];
        logDebug("Sending response: " . json_encode($responseData));
        echo json_encode($responseData);
        exit();
    }
    
    // Handle POST request - update database with client's wishlist
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get POST data (from JSON)
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        logDebug("Processing POST request for user_id: {$userId}, data: " . substr($json, 0, 200) . "...");
        
        if (!isset($data['items']) || !is_array($data['items'])) {
            logDebug("Invalid request: missing wishlist items");
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request: missing wishlist items',
                'data' => null
            ]);
            exit();
        }
        
        // Get the product IDs from the request
        $productIds = array_map(function($item) {
            // Handle both object format and direct ID format
            if (is_array($item) && isset($item['id'])) {
                return (int)$item['id'];
            }
            return (int)$item;
        }, $data['items']);
        
        // Filter out invalid IDs (should be positive integers)
        $productIds = array_filter($productIds, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        logDebug("Processing " . count($productIds) . " product IDs: " . implode(', ', $productIds));
        
        // Begin transaction
        $conn->beginTransaction();
        logDebug("Transaction started");
        
        try {
            // First, remove all existing wishlist items for this user
            $deleteStmt = $conn->prepare("DELETE FROM wishlist WHERE buyer_id = :buyer_id");
            $deleteStmt->bindParam(':buyer_id', $userId);
            $deleteStmt->execute();
            logDebug("Deleted existing wishlist items");
            
            // Then, add all the new wishlist items
            if (!empty($productIds)) {
                $insertValues = [];
                $insertParams = [];
                
                foreach ($productIds as $index => $productId) {
                    $buyerParam = ":buyer_id_" . $index;
                    $productParam = ":product_id_" . $index;
                    
                    $insertValues[] = "($buyerParam, $productParam)";
                    $insertParams[$buyerParam] = $userId;
                    $insertParams[$productParam] = $productId;
                }
                
                // Use INSERT IGNORE to silently ignore duplicate entries
                $insertQuery = "INSERT IGNORE INTO wishlist (buyer_id, product_id) VALUES " . implode(", ", $insertValues);
                $insertStmt = $conn->prepare($insertQuery);
                
                foreach ($insertParams as $param => $value) {
                    $insertStmt->bindValue($param, $value);
                }
                
                logDebug("Executing insert query: " . $insertQuery);
                $insertStmt->execute();
                logDebug("Inserted new wishlist items");
            }
            
            // Commit transaction
            $conn->commit();
            logDebug("Transaction committed");
            
            // Retrieve updated wishlist
            $stmt = $conn->prepare("
                SELECT w.product_id, p.title, p.rental_price, p.description, pi.image_path,
                       u.name as seller_name
                FROM wishlist w
                LEFT JOIN products p ON w.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                LEFT JOIN users u ON p.seller_id = u.id
                WHERE w.buyer_id = :buyer_id
                ORDER BY w.created_at DESC
            ");
            $stmt->bindParam(':buyer_id', $userId);
            $stmt->execute();
            
            $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            logDebug("Retrieved " . count($wishlistItems) . " items after synchronization");
            
            $responseData = [
                'status' => 'success',
                'message' => 'Wishlist synchronized successfully',
                'data' => [
                    'items' => $wishlistItems,
                    'ids' => array_column($wishlistItems, 'product_id')
                ]
            ];
            logDebug("Sending response: " . json_encode($responseData));
            echo json_encode($responseData);
            exit();
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollBack();
            logDebug("Error during wishlist sync: " . $e->getMessage());
            
            // Return a user-friendly error
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update wishlist: ' . $e->getMessage(),
                'data' => null
            ]);
            exit();
        }
    }
    
    // If we get here, the request method is not supported
    logDebug("Unsupported request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method. Only GET and POST are allowed.',
        'data' => null
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn && $conn->inTransaction()) {
        $conn->rollBack();
        logDebug("Transaction rolled back due to error");
    }
    
    logDebug("Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => null
    ]);
} 