<?php
class Review {
    // Database connection and table name
    private $conn;
    private $table = 'product_reviews';

    // Review properties
    public $id;
    public $order_id;
    public $product_id;
    public $buyer_id;
    public $seller_id;
    public $rating;
    public $review_text;
    public $seller_response;
    public $response_date;
    public $created_at;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    // Get reviews for a seller with pagination and optional rating filter
    public function getSellerReviews($seller_id, $limit, $offset, $rating = null) {
        // Base query - joining with products to get reviews for products from this seller
        $query = 'SELECT pr.id, pr.product_id, pr.buyer_id, pr.rating, pr.review as review_text, 
                      pr.created_at, 
                      u.name as buyer_name, u.profile_photo as buyer_image,
                      p.title as product_name, p.id as product_id, p.seller_id
                  FROM ' . $this->table . ' pr
                  JOIN products p ON pr.product_id = p.id
                  LEFT JOIN users u ON pr.buyer_id = u.id
                  WHERE p.seller_id = :seller_id';
        
        // Add rating filter if specified
        if ($rating !== null) {
            $query .= ' AND pr.rating = :rating';
        }
        
        // Add ordering and pagination
        $query .= ' ORDER BY pr.created_at DESC LIMIT :limit OFFSET :offset';
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $seller_id = htmlspecialchars(strip_tags($seller_id));
        $limit = htmlspecialchars(strip_tags($limit));
        $offset = htmlspecialchars(strip_tags($offset));
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $seller_id);
        if ($rating !== null) {
            $stmt->bindParam(':rating', $rating);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        $reviews = [];
        
        // Check if any reviews
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Format the review data
                $review_item = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'buyer_id' => $row['buyer_id'],
                    'buyer_name' => $row['buyer_name'],
                    'buyer_image' => $row['buyer_image'],
                    'seller_id' => $row['seller_id'],
                    'rating' => $row['rating'],
                    'review_text' => $row['review_text'],
                    'created_at' => $row['created_at']
                ];
                
                array_push($reviews, $review_item);
            }
        }
        
        return $reviews;
    }
    
    // Count total reviews for a seller with optional rating filter
    public function countSellerReviews($seller_id, $rating = null) {
        // Base query - count reviews for products from this seller
        $query = 'SELECT COUNT(*) as total 
                 FROM ' . $this->table . ' pr
                 JOIN products p ON pr.product_id = p.id
                 WHERE p.seller_id = :seller_id';
        
        // Add rating filter if specified
        if ($rating !== null) {
            $query .= ' AND pr.rating = :rating';
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $seller_id = htmlspecialchars(strip_tags($seller_id));
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $seller_id);
        if ($rating !== null) {
            $stmt->bindParam(':rating', $rating);
        }
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }
    
    // Get rating summary for a seller (average rating and count by rating)
    public function getSellerRatingSummary($seller_id) {
        // Get average rating for a seller's products
        $avgQuery = 'SELECT AVG(pr.rating) as average 
                    FROM ' . $this->table . ' pr
                    JOIN products p ON pr.product_id = p.id
                    WHERE p.seller_id = :seller_id';
        $avgStmt = $this->conn->prepare($avgQuery);
        $avgStmt->bindParam(':seller_id', $seller_id);
        $avgStmt->execute();
        $avgRow = $avgStmt->fetch(PDO::FETCH_ASSOC);
        $average = $avgRow['average'] ? round($avgRow['average'], 1) : 0;
        
        // Get count by rating
        $countByRatingQuery = 'SELECT pr.rating, COUNT(*) as count 
                              FROM ' . $this->table . ' pr
                              JOIN products p ON pr.product_id = p.id
                              WHERE p.seller_id = :seller_id 
                              GROUP BY pr.rating 
                              ORDER BY pr.rating DESC';
        $countStmt = $this->conn->prepare($countByRatingQuery);
        $countStmt->bindParam(':seller_id', $seller_id);
        $countStmt->execute();
        
        $ratingBreakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        
        while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
            $rating = min(5, max(1, (int)$row['rating'])); // Ensure rating is between 1-5
            $ratingBreakdown[$rating] = (int)$row['count'];
        }
        
        // Get total count
        $totalQuery = 'SELECT COUNT(*) as total 
                     FROM ' . $this->table . ' pr
                     JOIN products p ON pr.product_id = p.id
                     WHERE p.seller_id = :seller_id';
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bindParam(':seller_id', $seller_id);
        $totalStmt->execute();
        $totalRow = $totalStmt->fetch(PDO::FETCH_ASSOC);
        $total = (int)$totalRow['total'];
        
        return [
            'average' => $average,
            'total' => $total,
            'breakdown' => $ratingBreakdown
        ];
    }
    
    // Create a new review
    public function create() {
        // Create query
        $query = 'INSERT INTO ' . $this->table . ' 
                 (product_id, buyer_id, rating, review)
                 VALUES (:product_id, :buyer_id, :rating, :review_text)';
                 
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->buyer_id = htmlspecialchars(strip_tags($this->buyer_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->review_text = htmlspecialchars(strip_tags($this->review_text));
        
        // Bind parameters
        $stmt->bindParam(':product_id', $this->product_id);
        $stmt->bindParam(':buyer_id', $this->buyer_id);
        $stmt->bindParam(':rating', $this->rating);
        $stmt->bindParam(':review_text', $this->review_text);
        
        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Update an existing review with response from seller
    public function addSellerResponse() {
        // Update query
        $query = 'UPDATE ' . $this->table . '
                 SET seller_response = :seller_response,
                     response_date = NOW()
                 WHERE id = :id';
                 
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->seller_response = htmlspecialchars(strip_tags($this->seller_response));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':seller_response', $this->seller_response);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete a review
    public function delete() {
        // Delete query
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete all reviews for a seller's products
    public function deleteBySellerId($sellerId) {
        // Delete query - must join with products to get the seller's product reviews
        $query = 'DELETE pr FROM ' . $this->table . ' pr
                 JOIN products p ON pr.product_id = p.id
                 WHERE p.seller_id = :seller_id';
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $sellerId = htmlspecialchars(strip_tags($sellerId));
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $sellerId);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?> 