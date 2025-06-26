<?php
/**
 * Get Shop API
 * Returns shop/seller information by seller ID
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Get seller ID from request
    $sellerId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (empty($sellerId)) {
        $response['message'] = 'Seller ID is required';
        echo json_encode($response);
        exit;
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if sellers table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'sellers'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        // Temporarily disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Create sellers table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS sellers (
                id INT(11) PRIMARY KEY,
                shop_name VARCHAR(100) NOT NULL,
                description TEXT,
                address VARCHAR(255),
                latitude DECIMAL(10, 8),
                longitude DECIMAL(11, 8),
                shop_logo VARCHAR(255),
                avg_rating DECIMAL(3, 2) DEFAULT 0,
                total_reviews INT(11) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Insert sample seller for testing
        $db->exec("
            INSERT INTO sellers (id, shop_name, description, address, latitude, longitude)
            VALUES 
            (1, 'ClothLoop Shop', 'Premier destination for clothing rentals', '123 Fashion Street, Mumbai, India', 19.0760, 72.8777)
        ");
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Check if users table exists and has phone column
    $checkUsersTable = $db->prepare("SHOW TABLES LIKE 'users'");
    $checkUsersTable->execute();

    $hasPhoneColumn = false;
    if ($checkUsersTable->rowCount() > 0) {
        // Check if phone column exists
        $checkPhoneColumn = $db->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
        $checkPhoneColumn->execute();
        $hasPhoneColumn = ($checkPhoneColumn->rowCount() > 0);
    }

    // Query to get seller details based on available columns
    if ($hasPhoneColumn) {
        $query = "SELECT s.*, u.phone, u.email
                    FROM sellers s
                    LEFT JOIN users u ON s.id = u.id
                    WHERE s.id = :seller_id";
    } else {
        $query = "SELECT s.*, u.email
                    FROM sellers s
                    LEFT JOIN users u ON s.id = u.id
                    WHERE s.id = :seller_id";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Seller found
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Process shop logo if exists
        if (!empty($seller['shop_logo']) && !filter_var($seller['shop_logo'], FILTER_VALIDATE_URL)) {
            if (strpos($seller['shop_logo'], '/') === 0) {
                // Already an absolute path
                $seller['shop_logo'] = $seller['shop_logo'];
            } else {
                // Prepend path
                $seller['shop_logo'] = '/ClothLoop/' . $seller['shop_logo'];
            }
        }
        
        // Ensure address is never null or empty
        if (empty($seller['address'])) {
            $seller['address'] = 'Location not available';
        }
        
        // Set response data
        $response['status'] = 'success';
        $response['message'] = 'Seller information retrieved successfully';
        $response['data'] = $seller;
    } else {
        // No seller found, try to get basic user info
        $userQuery = "SELECT id, name, phone, email FROM users WHERE id = :id";
        $userStmt = $db->prepare($userQuery);
        $userStmt->bindParam(':id', $sellerId);
        $userStmt->execute();
        
        if ($userStmt->rowCount() > 0) {
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            // Create a basic seller info
            $seller = [
                'id' => $user['id'],
                'shop_name' => $user['name'] . "'s Shop",
                'description' => 'No shop description available',
                'address' => 'Location not available',
                'phone' => $user['phone'] ?? 'Not provided',
                'email' => $user['email'] ?? 'Not provided'
            ];
            
            $response['status'] = 'success';
            $response['message'] = 'Basic seller information retrieved';
            $response['data'] = $seller;
        } else {
            // Completely not found
            $response['message'] = 'Seller not found';
            
            // Return fallback data
            $response['data'] = [
                'id' => $sellerId,
                'shop_name' => 'ClothLoop Shop',
                'description' => 'Seller information not available',
                'address' => 'Location not available'
            ];
        }
    }
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching seller information: " . $e->getMessage());
    
    // Set error response with fallback data
    $response['message'] = 'Error fetching seller information: ' . $e->getMessage();
    $response['data'] = [
        'id' => $sellerId ?? 0,
        'shop_name' => 'ClothLoop Shop (Fallback)',
        'description' => 'Error retrieving shop information',
        'address' => 'Location not available'
    ];
    
    echo json_encode($response);
} 