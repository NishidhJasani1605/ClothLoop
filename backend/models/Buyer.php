<?php
/**
 * Buyer Model
 * Handles buyer-specific database operations
 */
class Buyer {
    // Database connection and table name
    private $conn;
    private $table_name = "buyers";
    
    // Properties
    public $id;
    public $user_id;
    public $latitude;
    public $longitude;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read single buyer by id
    public function readSingle() {
        try {
            // Query to select a single buyer by id
            $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind ID
            $stmt->bindParam(1, $this->id);
            
            // Execute query
            $stmt->execute();
            
            // Check if we found a record
            if ($stmt->rowCount() > 0) {
                // Get retrieved row
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set properties from whatever columns exist
                foreach ($row as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            // Log the error
            error_log('Buyer readSingle error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Create new buyer
    public function create() {
        try {
            // Determine available columns from a sample record
            $columns = $this->getTableColumns();
            
            if (empty($columns)) {
                error_log('No columns found in buyers table');
                return false;
            }
            
            // Build query dynamically based on available columns
            $query = "INSERT INTO " . $this->table_name . " SET ";
            $params = [];
            
            foreach ($columns as $column) {
                // Skip auto-increment columns
                if ($column == 'id' && !$this->id) continue;
                if ($column == 'created_at' || $column == 'updated_at') continue;
                
                // Only include columns that exist in this object and have values
                if (property_exists($this, $column) && isset($this->$column)) {
                    $query .= "$column=:$column, ";
                    $params[":$column"] = $this->$column;
                }
            }
            
            // Remove trailing comma and space
            $query = rtrim($query, ", ");
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            // Execute query
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // Log the error
            error_log('Buyer create error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Update buyer
    public function update() {
        try {
            // Determine available columns from a sample record
            $columns = $this->getTableColumns();
            
            if (empty($columns)) {
                error_log('No columns found in buyers table');
                return false;
            }
            
            // Build query dynamically based on available columns
            $query = "UPDATE " . $this->table_name . " SET ";
            $params = [];
            
            foreach ($columns as $column) {
                // Skip id column for updates
                if ($column == 'id') continue;
                
                // Skip created_at, handle updated_at specially
                if ($column == 'created_at') continue;
                if ($column == 'updated_at') {
                    $query .= "updated_at=NOW(), ";
                    continue;
                }
                
                // Only include columns that exist in this object
                if (property_exists($this, $column)) {
                    $query .= "$column=:$column, ";
                    $params[":$column"] = $this->$column ?? null;
                }
            }
            
            // Remove trailing comma and space
            $query = rtrim($query, ", ");
            
            // Add WHERE clause
            $query .= " WHERE id = :id";
            $params[':id'] = $this->id; // Use id
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            // Execute query
            if(!$stmt->execute()) {
                error_log('Buyer update error: ' . implode(', ', $stmt->errorInfo()));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            // Log the error
            error_log('Buyer update error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Delete buyer
    public function delete() {
        try {
            // Query to delete record
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind data
            $stmt->bindParam(1, $this->id);
            
            // Execute query
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // Log the error
            error_log('Buyer delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Helper method to get table columns
    private function getTableColumns() {
        try {
            // Get table structure
            $stmt = $this->conn->query("DESCRIBE " . $this->table_name);
            $columns = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            
            return $columns;
        } catch (PDOException $e) {
            error_log('Error getting table columns: ' . $e->getMessage());
            return [];
        }
    }
} 