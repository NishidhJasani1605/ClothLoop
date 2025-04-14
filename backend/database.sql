CREATE DATABASE clothloop;
USE clothloop;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    user_type ENUM('buyer', 'seller') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create cloth_items table
CREATE TABLE cloth_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    size VARCHAR(20) NOT NULL,
    color VARCHAR(30) NOT NULL, 
    price DECIMAL(10,2) NOT NULL,
    contact VARCHAR(15) NOT NULL,
    whatsapp VARCHAR(15) NOT NULL,
    shop_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    terms TEXT,
    location VARCHAR(50),
    images TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add index for faster queries
CREATE INDEX idx_cloth_user ON cloth_items(user_id); 