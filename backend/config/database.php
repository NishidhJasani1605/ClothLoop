<?php
/**
 * Database Configuration Class
 * Handles database connection and provides a PDO instance
 */
class Database {
    // Database credentials
    private $host = "localhost";
    private $db_name = "clothloop";
    private $username = "root";
    private $password = "";
    private $conn;

    /**
     * Connect to the database
     * 
     * @return PDO Database connection object
     */
    public function connect() {
        $this->conn = null;

        try {
            // Try connecting to the database
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
        } catch(PDOException $e) {
            // Try to automatically create the database if it doesn't exist
            if ($e->getCode() == 1049) { // MySQL code for "Unknown database"
                try {
                    // Connect without specifying a database
                    $tempConn = new PDO(
                        "mysql:host=" . $this->host,
                        $this->username,
                        $this->password
                    );
                    $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Create the database
                    $tempConn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
                    
                    // Connect to the newly created database
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    $this->conn->exec("set names utf8");
                    
                    // Create the necessary tables
                    $this->createTables();
                    
                    return $this->conn;
                } catch(PDOException $e2) {
                    error_log("Error creating database: " . $e2->getMessage());
                    throw new Exception("Failed to create database. Please check your MySQL server is running and you have proper permissions.");
                }
            } else {
                // Log this error - but don't expose database credentials in response
                error_log("Database Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed. Please make sure MySQL is running in your XAMPP control panel.");
            }
        }

        return $this->conn;
    }
    
    /**
     * Create necessary database tables
     */
    private function createTables() {
        // Create users table
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                phone VARCHAR(15),
                role ENUM('buyer', 'seller', 'admin') NOT NULL,
                profile_image VARCHAR(255),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Create sellers table
        $this->conn->exec("
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        
        // Create sample seller account for testing
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        // Check if test seller exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = 'seller@example.com'");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            // Insert test seller user
            $this->conn->exec("
                INSERT INTO users (name, email, password, phone, role)
                VALUES ('Test Seller', 'seller@example.com', '$hashedPassword', '1234567890', 'seller')
            ");
            
            $sellerId = $this->conn->lastInsertId();
            
            // Insert seller record
            $this->conn->exec("
                INSERT INTO sellers (id, shop_name, description, address)
                VALUES ($sellerId, 'Test Shop', 'This is a test shop for demonstration', '123 Test Street')
            ");
        }
    }
    
    /**
     * Get connection (alias for connect)
     * This method is provided for backward compatibility
     * 
     * @return PDO Database connection object
     */
    public function getConnection() {
        return $this->connect();
    }
} 