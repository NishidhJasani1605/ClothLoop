<?php
/**
 * Migration script to transfer data from customer_interests to wishlist table
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
    
    echo "Starting migration from customer_interests to wishlist...\n";
    
    // Check if both tables exist
    $checkCITable = $conn->prepare("SHOW TABLES LIKE 'customer_interests'");
    $checkCITable->execute();
    
    $checkWishlistTable = $conn->prepare("SHOW TABLES LIKE 'wishlist'");
    $checkWishlistTable->execute();
    
    $ciExists = $checkCITable->rowCount() > 0;
    $wishlistExists = $checkWishlistTable->rowCount() > 0;
    
    if (!$ciExists) {
        $response['message'] = 'Source table customer_interests does not exist';
        echo json_encode($response);
        exit();
    }
    
    if (!$wishlistExists) {
        // Create the wishlist table
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
        echo "Created wishlist table\n";
    }
    
    // Count records in source table
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM customer_interests");
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    echo "Found {$totalRecords} records in customer_interests table\n";
    
    if ($totalRecords === 0) {
        $response['status'] = 'success';
        $response['message'] = 'No records to migrate';
        echo json_encode($response);
        exit();
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Copy data from customer_interests to wishlist with ON DUPLICATE KEY UPDATE
    // to handle any existing records in wishlist
    $copyQuery = "
        INSERT INTO wishlist (buyer_id, product_id, created_at)
        SELECT buyer_id, product_id, created_at FROM customer_interests
        ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)
    ";
    
    $conn->exec($copyQuery);
    
    // Count migrated records
    $countMigratedStmt = $conn->prepare("SELECT COUNT(*) FROM wishlist");
    $countMigratedStmt->execute();
    $migratedRecords = $countMigratedStmt->fetchColumn();
    
    // Commit transaction
    $conn->commit();
    
    $response['status'] = 'success';
    $response['message'] = "Migration complete: {$migratedRecords} records in wishlist table";
    $response['data'] = [
        'source_records' => $totalRecords,
        'destination_records' => $migratedRecords
    ];
    
    echo "Migration complete!\n";
    echo "Source records: {$totalRecords}\n";
    echo "Destination records: {$migratedRecords}\n";
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $response['message'] = 'Migration error: ' . $e->getMessage();
    echo "Error during migration: " . $e->getMessage() . "\n";
} finally {
    echo json_encode($response);
} 