<?php
/**
 * Add Seller Review API
 * Allows users to submit reviews for sellers when submitting product reviews
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database connection
require_once __DIR__ . '/../../config/database.php';

// Initialize response
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate required fields
    if (!isset($data['seller_id']) || 
        !isset($data['rating']) || 
        !isset($data['content']) ||
        !isset($data['product_id'])) {
        $response['message'] = 'Missing required fields';
        echo json_encode($response);
        exit;
    }
    
    // Validate seller_id
    if (!is_numeric($data['seller_id'])) {
        $response['message'] = 'Invalid seller ID format';
        echo json_encode($response);
        exit;
    }
    
    // Validate rating is between 1 and 5
    if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        $response['message'] = 'Rating must be between 1 and 5';
        echo json_encode($response);
        exit;
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if seller_reviews table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'seller_reviews'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        // Create the table if it doesn't exist
        $createTableQuery = "
            CREATE TABLE seller_reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                seller_id INT NOT NULL,
                user_id VARCHAR(50),
                user_name VARCHAR(100) NOT NULL,
                product_id INT,
                rating INT NOT NULL,
                content TEXT NOT NULL,
                seller_response TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $db->exec($createTableQuery);
    }
    
    // Set default user name if not provided
    $userName = isset($data['user_name']) && !empty($data['user_name']) ? $data['user_name'] : 'Anonymous User';
    $userId = isset($data['user_id']) ? $data['user_id'] : null;
    
    // Check if this user has already reviewed this seller
    if ($userId) {
        $checkExisting = $db->prepare("
            SELECT id FROM seller_reviews 
            WHERE seller_id = :seller_id AND user_id = :user_id
        ");
        $checkExisting->bindParam(':seller_id', $data['seller_id']);
        $checkExisting->bindParam(':user_id', $userId);
        $checkExisting->execute();
        
        if ($checkExisting->rowCount() > 0) {
            // Update existing review instead of creating a new one
            $existingReview = $checkExisting->fetch(PDO::FETCH_ASSOC);
            $reviewId = $existingReview['id'];
            
            $updateQuery = "
                UPDATE seller_reviews 
                SET rating = :rating, 
                    content = :content,
                    product_id = :product_id,
                    updated_at = NOW()
                WHERE id = :review_id
            ";
            
            $stmt = $db->prepare($updateQuery);
            $stmt->bindParam(':rating', $data['rating']);
            $stmt->bindParam(':content', $data['content']);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':review_id', $reviewId);
            
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Review updated successfully';
                $response['data'] = [
                    'id' => $reviewId,
                    'seller_id' => $data['seller_id'],
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'product_id' => $data['product_id'],
                    'rating' => (int)$data['rating'],
                    'content' => $data['content'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // Update seller average rating
                updateSellerAverageRating($db, $data['seller_id']);
                
                echo json_encode($response);
                exit;
            } else {
                $response['message'] = 'Failed to update review';
                echo json_encode($response);
                exit;
            }
        }
    }
    
    // Create a new review
    $insertQuery = "
        INSERT INTO seller_reviews 
        (seller_id, user_id, user_name, product_id, rating, content) 
        VALUES 
        (:seller_id, :user_id, :user_name, :product_id, :rating, :content)
    ";
    
    $stmt = $db->prepare($insertQuery);
    $stmt->bindParam(':seller_id', $data['seller_id']);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':user_name', $userName);
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->bindParam(':rating', $data['rating']);
    $stmt->bindParam(':content', $data['content']);
    
    if ($stmt->execute()) {
        // Get the new review ID
        $reviewId = $db->lastInsertId();
        
        $response['status'] = 'success';
        $response['message'] = 'Review created successfully';
        $response['data'] = [
            'id' => $reviewId,
            'seller_id' => $data['seller_id'],
            'user_id' => $userId,
            'user_name' => $userName,
            'product_id' => $data['product_id'],
            'rating' => (int)$data['rating'],
            'content' => $data['content'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Update seller average rating
        updateSellerAverageRating($db, $data['seller_id']);
    } else {
        $response['message'] = 'Failed to create review';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
}

/**
 * Update seller average rating
 * 
 * @param PDO $db Database connection
 * @param int $sellerId Seller ID
 * @return void
 */
function updateSellerAverageRating($db, $sellerId) {
    try {
        // Calculate average rating
        $query = "SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews 
                  FROM seller_reviews 
                  WHERE seller_id = :seller_id";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':seller_id', $sellerId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $avgRating = $result['avg_rating'];
            $totalReviews = $result['total_reviews'];
            
            // Check if sellers table exists
            $checkTable = $db->prepare("SHOW TABLES LIKE 'sellers'");
            $checkTable->execute();
            
            if ($checkTable->rowCount() > 0) {
                // Check if avg_rating column exists in sellers table
                $checkColumn = $db->prepare("SHOW COLUMNS FROM sellers LIKE 'avg_rating'");
                $checkColumn->execute();
                
                if ($checkColumn->rowCount() > 0) {
                    // Update the sellers table with new average rating
                    $updateQuery = "UPDATE sellers 
                                   SET avg_rating = :avg_rating, 
                                       total_reviews = :total_reviews 
                                   WHERE id = :seller_id";
                                   
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->bindParam(':avg_rating', $avgRating);
                    $updateStmt->bindParam(':total_reviews', $totalReviews);
                    $updateStmt->bindParam(':seller_id', $sellerId);
                    
                    $updateStmt->execute();
                }
            }
        }
    } catch (Exception $e) {
        // Log the error but don't stop execution
        error_log("Error updating seller average rating: " . $e->getMessage());
    }
} 