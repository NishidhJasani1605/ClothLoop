<?php
/**
 * Add Review API
 * Adds a new review for a product
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no data was sent
    if (empty($data)) {
        $response['message'] = 'No data provided';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    $requiredFields = ['product_id', 'rating', 'review'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        $response['message'] = 'Missing required fields: ' . implode(', ', $missingFields);
        echo json_encode($response);
        exit;
    }
    
    // Validate rating
    if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        $response['message'] = 'Rating must be a number between 1 and 5';
        echo json_encode($response);
        exit;
    }
    
    // Validate product_id
    if (!is_numeric($data['product_id'])) {
        $response['message'] = 'Product ID must be a number';
        echo json_encode($response);
        exit;
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if products table exists
    $checkProductsTable = $db->prepare("SHOW TABLES LIKE 'products'");
    $checkProductsTable->execute();
    
    if ($checkProductsTable->rowCount() == 0) {
        $response['message'] = 'Products table does not exist';
        echo json_encode($response);
        exit;
    }
    
    // Verify product exists
    $productQuery = "SELECT id FROM products WHERE id = :product_id";
    $productStmt = $db->prepare($productQuery);
    $productStmt->bindParam(':product_id', $data['product_id']);
    $productStmt->execute();
    
    if ($productStmt->rowCount() == 0) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if reviews table exists
    $checkReviewsTable = $db->prepare("SHOW TABLES LIKE 'product_reviews'");
    $checkReviewsTable->execute();
    
    if ($checkReviewsTable->rowCount() == 0) {
        // Temporarily disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Create reviews table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS product_reviews (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                product_id INT(11) NOT NULL,
                user_id INT(11),
                user_name VARCHAR(100) NOT NULL,
                rating INT(1) NOT NULL,
                review TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Set default user name if not provided
    $userName = isset($data['user_name']) && !empty($data['user_name']) ? $data['user_name'] : 'Anonymous User';
    
    // Insert the review
    $query = "INSERT INTO product_reviews (product_id, user_name, rating, review) 
              VALUES (:product_id, :user_name, :rating, :review)";
              
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':product_id', $data['product_id']);
    $stmt->bindParam(':user_name', $userName);
    $stmt->bindParam(':rating', $data['rating']);
    $stmt->bindParam(':review', $data['review']);
    
    if ($stmt->execute()) {
        // Get the newly created review ID
        $reviewId = $db->lastInsertId();
        
        // Update product average rating
        updateProductAverageRating($db, $data['product_id']);
        
        // Return the created review
        $response['status'] = 'success';
        $response['message'] = 'Review added successfully';
        $response['data'] = [
            'id' => $reviewId,
            'product_id' => $data['product_id'],
            'reviewer_name' => $userName,
            'rating' => (int)$data['rating'],
            'review_text' => $data['review'],
            'date' => date('Y-m-d\TH:i:s')
        ];
    } else {
        $response['message'] = 'Failed to add review';
    }
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Error adding review: " . $e->getMessage());
    
    // Set error response
    $response['message'] = 'Error adding review: ' . $e->getMessage();
    echo json_encode($response);
}

/**
 * Update product average rating
 * 
 * @param PDO $db Database connection
 * @param int $productId Product ID
 * @return void
 */
function updateProductAverageRating($db, $productId) {
    try {
        // Calculate average rating
        $query = "SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews 
                  FROM product_reviews 
                  WHERE product_id = :product_id";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $avgRating = $result['avg_rating'];
            $totalReviews = $result['total_reviews'];
            
            // Update products table if it has avg_rating column
            $checkColumn = $db->prepare("SHOW COLUMNS FROM products LIKE 'avg_rating'");
            $checkColumn->execute();
            
            if ($checkColumn->rowCount() > 0) {
                $updateQuery = "UPDATE products 
                               SET avg_rating = :avg_rating 
                               WHERE id = :product_id";
                               
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':avg_rating', $avgRating);
                $updateStmt->bindParam(':product_id', $productId);
                $updateStmt->execute();
            }
        }
    } catch (Exception $e) {
        error_log("Error updating product average rating: " . $e->getMessage());
    }
} 