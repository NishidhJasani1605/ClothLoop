<?php
/**
 * Setup script for wishlist table
 * This script checks if the wishlist table exists and creates it if it doesn't
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'data' => null
];

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();

    // Check if wishlist table exists
    $checkTableStmt = $conn->prepare("SHOW TABLES LIKE 'wishlist'");
    $checkTableStmt->execute();
    
    if ($checkTableStmt->rowCount() == 0) {
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
        
        $response['status'] = 'success';
        $response['message'] = 'wishlist table created successfully';
    } else {
        // Table exists, check if it has all required columns
        $checkColumnsStmt = $conn->prepare("SHOW COLUMNS FROM wishlist");
        $checkColumnsStmt->execute();
        $columns = $checkColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'buyer_id', 'product_id', 'created_at'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (!empty($missingColumns)) {
            // Add missing columns
            foreach ($missingColumns as $missingColumn) {
                switch ($missingColumn) {
                    case 'created_at':
                        $conn->exec("ALTER TABLE wishlist ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                        break;
                    case 'buyer_id':
                        $conn->exec("ALTER TABLE wishlist ADD COLUMN buyer_id INT(11) NOT NULL");
                        $conn->exec("ALTER TABLE wishlist ADD INDEX idx_buyer (buyer_id)");
                        break;
                    case 'product_id':
                        $conn->exec("ALTER TABLE wishlist ADD COLUMN product_id INT(11) NOT NULL");
                        $conn->exec("ALTER TABLE wishlist ADD INDEX idx_product (product_id)");
                        break;
                    // id column would be unlikely to be missing, but handle it just in case
                    case 'id':
                        $conn->exec("ALTER TABLE wishlist ADD COLUMN id INT(11) AUTO_INCREMENT PRIMARY KEY FIRST");
                        break;
                }
            }
            
            // Add unique key if both buyer_id and product_id exist
            if (!in_array('buyer_id', $missingColumns) && !in_array('product_id', $missingColumns)) {
                try {
                    $conn->exec("ALTER TABLE wishlist ADD UNIQUE KEY unique_wishlist_item (buyer_id, product_id)");
                } catch (PDOException $e) {
                    // Unique key might already exist
                }
            }
            
            $response['status'] = 'success';
            $response['message'] = 'Added missing columns to wishlist table: ' . implode(', ', $missingColumns);
        } else {
            $response['status'] = 'success';
            $response['message'] = 'wishlist table already exists with all required columns';
        }
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
} finally {
    echo json_encode($response);
} 