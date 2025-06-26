<?php
/**
 * Database Tables Setup Script
 * Creates necessary tables for the ClothLoop application
 */

// Required files
require_once __DIR__ . '/../../config/database.php';

// Get database connection
$database = new Database();
$conn = $database->getConnection();

try {
    // Enable foreign key constraints
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create users table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone_no VARCHAR(20),
        role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer',
        status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create sellers table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS sellers (
        id INT PRIMARY KEY,
        shop_name VARCHAR(100),
        address VARCHAR(255),
        description TEXT,
        profile_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create buyers table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS buyers (
        id INT PRIMARY KEY,
        shipping_address VARCHAR(255),
        profile_photo VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create categories table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        parent_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    )");
    
    // Create products table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        description TEXT,
        category_id INT,
        size VARCHAR(50),
        occasion VARCHAR(100),
        rental_price DECIMAL(10,2) NOT NULL,
        status ENUM('available', 'unavailable', 'rented') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        terms TEXT,
        views INT DEFAULT 0,
        FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");
    
    // Create product_images table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS product_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        is_primary BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    
    // Create seller_reviews table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS seller_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL,
        buyer_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review TEXT,
        response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
        FOREIGN KEY (buyer_id) REFERENCES buyers(id) ON DELETE CASCADE
    )");
    
    // Create product_reviews table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        buyer_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (buyer_id) REFERENCES buyers(id) ON DELETE CASCADE
    )");
    
    // Create customer_interests table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS customer_interests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        buyer_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Insert some demo data for categories
    $checkCategories = $conn->query("SELECT COUNT(*) FROM categories");
    if ($checkCategories->fetchColumn() == 0) {
        $demoCategories = [
            ['name' => 'Men', 'description' => 'Men\'s Clothing'],
            ['name' => 'Women', 'description' => 'Women\'s Clothing'],
            ['name' => 'Kids', 'description' => 'Kids Clothing'],
            ['name' => 'Ethnic', 'description' => 'Ethnic Wear'],
            ['name' => 'Formal', 'description' => 'Formal Wear']
        ];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($demoCategories as $category) {
            $stmt->execute([$category['name'], $category['description']]);
        }
    }
    
    // Insert demo interest data
    $checkInterests = $conn->query("SELECT COUNT(*) FROM customer_interests");
    if ($checkInterests->fetchColumn() == 0) {
        // First check if we have any products and users
        $checkProducts = $conn->query("SELECT COUNT(*) FROM products");
        $checkUsers = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'buyer'");
        
        if ($checkProducts->fetchColumn() > 0 && $checkUsers->fetchColumn() > 0) {
            // Get some product IDs
            $productsStmt = $conn->query("SELECT id FROM products LIMIT 5");
            $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get some buyer IDs
            $buyersStmt = $conn->query("SELECT id FROM users WHERE role = 'buyer' LIMIT 3");
            $buyerIds = $buyersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($productIds) && !empty($buyerIds)) {
                $stmt = $conn->prepare("INSERT INTO customer_interests (product_id, buyer_id) VALUES (?, ?)");
                
                // Create some sample interests
                foreach ($productIds as $productId) {
                    foreach ($buyerIds as $buyerId) {
                        // Randomly decide to create an interest (about 70% chance)
                        if (rand(1, 10) <= 7) {
                            $stmt->execute([$productId, $buyerId]);
                        }
                    }
                }
            }
        }
    }
    
    echo "Tables created successfully<br>";
    echo "Demo data inserted successfully<br>";
    
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "<br>";
} 