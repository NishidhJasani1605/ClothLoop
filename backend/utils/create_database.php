<?php
/**
 * Database Setup Script
 * Creates the database and necessary tables for ClothLoop
 */

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "clothloop";

try {
    // Create connection without database (to create the database)
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created or already exists successfully <br>";
    
    // Connect to the new database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone_no VARCHAR(20) NOT NULL,
        role ENUM('admin', 'seller', 'buyer') NOT NULL,
        profile_photo VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    echo "Table 'users' created or already exists successfully <br>";
    
    // Create buyers table
    $sql = "CREATE TABLE IF NOT EXISTS buyers (
        id INT(11) UNSIGNED PRIMARY KEY,
        latitude DECIMAL(10, 8) DEFAULT NULL,
        longitude DECIMAL(11, 8) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    echo "Table 'buyers' created or already exists successfully <br>";
    
    // Create sellers table
    $sql = "CREATE TABLE IF NOT EXISTS sellers (
        id INT(11) UNSIGNED PRIMARY KEY,
        shop_name VARCHAR(100) NOT NULL,
        address TEXT NOT NULL,
        shop_logo VARCHAR(255) DEFAULT NULL,
        shop_description TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    
    $conn->exec($sql);
    echo "Table 'sellers' created or already exists successfully <br>";
    
    echo "<br>Database setup completed successfully!";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null; 