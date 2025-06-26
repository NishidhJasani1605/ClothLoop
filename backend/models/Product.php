<?php
/**
 * Product Model Class
 * Handles all product-related database operations
 */
class Product {
    // Database connection and table name
    private $conn;
    private $table = 'products';

    // Product properties
    public $id;
    public $seller_id;
    public $category_id;
    public $name;
    public $description;
    public $price;
    public $image_url;
    public $status;
    public $created_at;
    public $updated_at;

    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get all seller products with pagination
     * 
     * @param int $sellerId The seller ID
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @param string $search Optional search term
     * @param int $categoryId Optional category filter
     * @param string $status Optional status filter
     * @return array Array of products
     */
    public function getSellerProducts($sellerId, $limit = 10, $offset = 0, $search = '', $categoryId = null, $status = null) {
        // Base query
        $query = "SELECT p.*, c.name as category_name
                  FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.seller_id = :seller_id";
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        }
        
        // Add category filter if provided
        if ($categoryId !== null) {
            $query .= " AND p.category_id = :category_id";
        }
        
        // Add status filter if provided
        if ($status !== null) {
            $query .= " AND p.status = :status";
        }
        
        // Add ordering and pagination
        $query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $sellerId);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        if ($categoryId !== null) {
            $stmt->bindParam(':category_id', $categoryId);
        }
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count seller products
     * 
     * @param int $sellerId The seller ID
     * @param string $search Optional search term
     * @param int $categoryId Optional category filter
     * @param string $status Optional status filter
     * @return int Number of products
     */
    public function countSellerProducts($sellerId, $search = '', $categoryId = null, $status = null) {
        // Base query
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE seller_id = :seller_id";
        
        // Add search condition if provided
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR description LIKE :search)";
        }
        
        // Add category filter if provided
        if ($categoryId !== null) {
            $query .= " AND category_id = :category_id";
        }
        
        // Add status filter if provided
        if ($status !== null) {
            $query .= " AND status = :status";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $sellerId);
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        
        if ($categoryId !== null) {
            $stmt->bindParam(':category_id', $categoryId);
        }
        
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$row['total'];
    }

    /**
     * Get a single product by ID
     * 
     * @return array Product data or false if not found
     */
    public function getSingle() {
        $query = "SELECT p.*, c.name as category_name
                  FROM " . $this->table . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :id LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        // Check if product exists
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return false;
    }

    /**
     * Create a new product
     * 
     * @return bool True if created successfully, false otherwise
     */
    public function create() {
        // Create query
        $query = "INSERT INTO " . $this->table . " 
                  (seller_id, category_id, name, description, price, image_url, status) 
                  VALUES (:seller_id, :category_id, :name, :description, :price, :image_url, :status)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->seller_id = htmlspecialchars(strip_tags($this->seller_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind parameters
        $stmt->bindParam(':seller_id', $this->seller_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':status', $this->status);
        
        // Execute query
        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    /**
     * Update a product
     * 
     * @return bool True if updated successfully, false otherwise
     */
    public function update() {
        // Create query
        $query = "UPDATE " . $this->table . " 
                  SET category_id = :category_id, 
                      name = :name, 
                      description = :description, 
                      price = :price, 
                      image_url = :image_url, 
                      status = :status,
                      updated_at = NOW()
                  WHERE id = :id AND seller_id = :seller_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->seller_id = htmlspecialchars(strip_tags($this->seller_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->image_url = htmlspecialchars(strip_tags($this->image_url));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':seller_id', $this->seller_id);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':image_url', $this->image_url);
        $stmt->bindParam(':status', $this->status);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    /**
     * Delete a product
     * 
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete() {
        // Create query
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND seller_id = :seller_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean data
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->seller_id = htmlspecialchars(strip_tags($this->seller_id));
        
        // Bind parameters
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':seller_id', $this->seller_id);
        
        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        
        return false;
    }

    /**
     * Delete all products by seller ID
     * 
     * @param int $sellerId The seller ID
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteAllBySellerId($sellerId) {
        // First delete associated product images
        $imageQuery = "SELECT id FROM " . $this->table . " WHERE seller_id = :seller_id";
        $imageStmt = $this->conn->prepare($imageQuery);
        $imageStmt->bindParam(':seller_id', $sellerId);
        $imageStmt->execute();
        
        if ($imageStmt->rowCount() > 0) {
            while ($row = $imageStmt->fetch(PDO::FETCH_ASSOC)) {
                // Delete product images from storage
                $imagesDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/' . $row['id'];
                if (is_dir($imagesDir)) {
                    $files = glob($imagesDir . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    // Remove the directory
                    rmdir($imagesDir);
                }
                
                // Delete from product_images table
                $deleteImagesQuery = "DELETE FROM product_images WHERE product_id = :product_id";
                $deleteImagesStmt = $this->conn->prepare($deleteImagesQuery);
                $deleteImagesStmt->bindParam(':product_id', $row['id']);
                $deleteImagesStmt->execute();
            }
        }
        
        // Next delete related entries in various tables
        $tables = ['customer_interests', 'wishlist', 'orders', 'product_reviews'];
        foreach ($tables as $table) {
            $query = "DELETE FROM " . $table . " WHERE product_id IN (SELECT id FROM " . $this->table . " WHERE seller_id = :seller_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':seller_id', $sellerId);
            $stmt->execute();
        }
        
        // Finally, delete all products
        $query = "DELETE FROM " . $this->table . " WHERE seller_id = :seller_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seller_id', $sellerId);
        
        return $stmt->execute();
    }
}
?> 