-- ClothLoop Database Updates
-- Run this script to apply all necessary changes to your database

-- Check if the database exists, create if not
CREATE DATABASE IF NOT EXISTS `clothloop`;
USE `clothloop`;

-- Make sure the users table exists first (as other tables reference it)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_no` varchar(20) DEFAULT NULL,
  `role` enum('admin','seller','buyer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `email_2` (`email`),
  KEY `role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create buyers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `buyers` (
  `id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `buyers_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create sellers table if it doesn't exist
CREATE TABLE IF NOT EXISTS `sellers` (
  `id` int(11) NOT NULL,
  `shop_name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create categories table if it doesn't exist
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create products table if it doesn't exist
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `occasion` varchar(50) DEFAULT NULL,
  `rental_price` decimal(10,2) NOT NULL,
  `status` enum('available','rented','unavailable') DEFAULT 'available',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `terms` text DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create product_images table if it doesn't exist
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create product_reviews table if it doesn't exist
CREATE TABLE IF NOT EXISTS `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `rating` decimal(3,1) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `buyer_id` (`buyer_id`),
  CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create customer_interests table if it doesn't exist
CREATE TABLE IF NOT EXISTS `customer_interests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `customer_interests_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_interests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create wishlist table if it doesn't exist
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_id` (`buyer_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `buyer_id_2` (`buyer_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `buyers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create seller_notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `seller_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','restriction','success') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `seller_notifications_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seller_notifications_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing tables with any missing columns
-- Update buyers table to ensure all columns exist
ALTER TABLE `buyers` 
ADD COLUMN IF NOT EXISTS `address` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `latitude` decimal(10,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `longitude` decimal(11,8) DEFAULT NULL;

-- Update users table to ensure all columns exist
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `profile_photo` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `status` enum('active','inactive','suspended') DEFAULT 'active',
MODIFY COLUMN IF EXISTS `phone_no` varchar(20) DEFAULT NULL;

-- Update products table to ensure all columns exist
ALTER TABLE `products`
ADD COLUMN IF NOT EXISTS `views` int(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `terms` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
MODIFY COLUMN IF EXISTS `status` enum('available','rented','unavailable') DEFAULT 'available';

-- Update sellers table to ensure all columns exist
ALTER TABLE `sellers`
ADD COLUMN IF NOT EXISTS `shop_name` varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `description` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `address` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `latitude` decimal(10,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `longitude` decimal(11,8) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `avg_rating` decimal(3,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS `total_reviews` int(11) DEFAULT 0;

-- Insert default category data if categories table is empty
INSERT INTO `categories` (`name`, `description`)
SELECT 'Men', 'Men\'s clothing' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Men');

INSERT INTO `categories` (`name`, `description`)
SELECT 'Women', 'Women\'s clothing' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Women');

INSERT INTO `categories` (`name`, `description`)
SELECT 'Kids', 'Kids\' clothing' WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Kids');

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);
CREATE INDEX IF NOT EXISTS idx_products_status ON products(status);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- End of update script
-- Run this script to ensure your database schema is up-to-date 