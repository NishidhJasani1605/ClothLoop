<?php
/**
 * Seller Model Class
 * Handles all seller-related database operations
 */
class Seller {
    // Database connection and table name
    private $conn;
    private $table = 'sellers';

    // Seller properties
    public $id;
    public $seller_id;
    public $shop_name;
    public $description;
    public $address;
    public $latitude;
    public $longitude;
    public $created_at;
    public $updated_at;
    public $avg_rating;
    public $total_reviews;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get a single seller by ID
     * 
     * @return array|bool Seller data or false if not found
     */
    public function getSingle() {
        // First, determine which ID to use and what column to query
        if (isset($this->id)) {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $idParam = $this->id;
            $idName = ':id';
        } else if (isset($this->seller_id)) {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
            $idParam = $this->seller_id;
            $idName = ':id';
        } else {
            return false; // No ID available
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam($idName, $idParam);
        
        // Execute query
        $stmt->execute();
        
        // Check if seller exists
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }

    /**
     * Find a seller by ID
     * 
     * @param int $id Seller ID
     * @return bool True if seller exists, false otherwise
     */
    public function findById($id) {
        $query = "SELECT id FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $id);
        
        // Execute query
        $stmt->execute();
        
        // Return true if seller exists
        return $stmt->rowCount() > 0;
    }

    /**
     * Create a new seller
     * 
     * @return bool True if created successfully, false otherwise
     */
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  (id, shop_name, description, address, latitude, longitude) 
                  VALUES (:id, :shop_name, :description, :address, :latitude, :longitude)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->shop_name = htmlspecialchars(strip_tags($this->shop_name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->latitude = htmlspecialchars(strip_tags($this->latitude));
        $this->longitude = htmlspecialchars(strip_tags($this->longitude));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':shop_name', $this->shop_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    /**
     * Update a seller
     * 
     * @return bool True if updated successfully, false otherwise
     */
    public function update() {
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET shop_name = :shop_name, 
                      description = :description, 
                      address = :address, 
                      latitude = :latitude, 
                      longitude = :longitude, 
                      updated_at = NOW()
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->shop_name = htmlspecialchars(strip_tags($this->shop_name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->latitude = htmlspecialchars(strip_tags($this->latitude));
        $this->longitude = htmlspecialchars(strip_tags($this->longitude));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':shop_name', $this->shop_name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    /**
     * Get all sellers with pagination
     * 
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of sellers
     */
    public function getAll($limit = 10, $offset = 0) {
        $query = "SELECT s.*, u.name, u.email, u.phone
                  FROM " . $this->table . " s
                  JOIN users u ON s.id = u.id
                  ORDER BY s.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total sellers
     * 
     * @return int Number of sellers
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }

    /**
     * Update seller's rating statistics
     * 
     * @param int $sellerId The seller ID
     * @param float $avgRating Average rating
     * @param int $totalReviews Total number of reviews
     * @return bool True if updated successfully, false otherwise
     */
    public function updateRatingStats($sellerId, $avgRating, $totalReviews) {
        $query = "UPDATE " . $this->table . " 
                  SET avg_rating = :avg_rating, 
                      total_reviews = :total_reviews
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':avg_rating', $avgRating);
        $stmt->bindParam(':total_reviews', $totalReviews, PDO::PARAM_INT);
        $stmt->bindParam(':id', $sellerId);
        
        // Execute query
        return $stmt->execute();
    }
    
    /**
     * Get all sellers with their user information, ordered by a specific field
     * 
     * @param string $sort_by Field to sort by (avg_rating, total_reviews, name, created_at)
     * @param string $sort_order Sort order (ASC or DESC)
     * @return array Array of sellers with user information
     */
    public function getAllWithUserInfo($sort_by = 'avg_rating', $sort_order = 'ASC') {
        // Determine the correct table for the sort field
        $sortTable = 's';
        if ($sort_by === 'name') {
            $sortTable = 'u';
        }
        
        // Build the ORDER BY clause
        $orderClause = "";
        if ($sort_by === 'avg_rating') {
            // Use COALESCE to handle NULL values, defaulting to 0
            $orderClause = "ORDER BY COALESCE(s.avg_rating, 0) " . $sort_order;
        } else if ($sort_by === 'total_reviews') {
            $orderClause = "ORDER BY COALESCE(s.total_reviews, 0) " . $sort_order;
        } else {
            $orderClause = "ORDER BY " . $sortTable . "." . $sort_by . " " . $sort_order;
        }
        
        $query = "SELECT s.*, u.name, u.email, u.phone_no, u.status, u.profile_photo
                  FROM " . $this->table . " s
                  JOIN users u ON s.id = u.id
                  WHERE u.role = 'seller'
                  " . $orderClause;
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete seller record
     * 
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind parameter
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        return $stmt->execute();
    }
}
?> 