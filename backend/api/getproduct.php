<?php
/**
 * Get Product API
 * Returns product details by ID in JSON format
 */

// Set headers for JSON response
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Get product ID from request
$productId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (empty($productId)) {
    $response['message'] = 'Missing product ID';
    echo json_encode($response);
    exit;
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if products table exists
    $checkProductsTable = $db->prepare("SHOW TABLES LIKE 'products'");
    $checkProductsTable->execute();
    
    if ($checkProductsTable->rowCount() == 0) {
        // Create products table if it doesn't exist
        $db->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                size VARCHAR(50),
                category VARCHAR(100),
                rental_price DECIMAL(10, 2) NOT NULL,
                seller_id INT(11),
                image VARCHAR(255),
                terms_conditions TEXT,
                status ENUM('available', 'rented', 'inactive') DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB
        ");
        
        // Insert sample products
        $db->exec("
            INSERT INTO products (id, title, description, size, category, rental_price, seller_id, image, terms_conditions, status)
            VALUES 
            (1, 'Elegant Black Dress', 'A beautiful black evening dress perfect for formal events', 'M', 'Women', 399.99, 1, '/uploads/products/1/product_image.jpg', 'Return in original condition. Cleaning fees apply for stains.', 'available'),
            (2, 'Navy Blue Suit', 'Professional navy blue suit for business meetings and formal occasions', 'L', 'Men', 499.99, 1, '/uploads/products/2/product_image.jpg', 'Dry cleaning required before return. No alterations allowed.', 'available')
        ");
        
        // Let the user know we've created sample data
        $response['message'] = 'Created products table with sample data';
    }
    
    // Check if users table exists and has phone column
    $checkUsersTable = $db->prepare("SHOW TABLES LIKE 'users'");
    $checkUsersTable->execute();

    if ($checkUsersTable->rowCount() > 0) {
        // Check if phone column exists
        $checkPhoneColumn = $db->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
        $checkPhoneColumn->execute();
        
        if ($checkPhoneColumn->rowCount() > 0) {
            // Phone column exists, use the original query
            $query = "SELECT p.*, 
                        s.shop_name, s.address as shop_address, s.latitude, s.longitude,
                        u.phone as contact_number,
                        c.name as category_name
                    FROM products p
                    LEFT JOIN sellers s ON p.seller_id = s.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = :product_id 
                    LIMIT 1";
        } else {
            // Phone column doesn't exist, modify query
            $query = "SELECT p.*, 
                        s.shop_name, s.address as shop_address, s.latitude, s.longitude,
                        c.name as category_name
                    FROM products p
                    LEFT JOIN sellers s ON p.seller_id = s.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = :product_id 
                    LIMIT 1";
        }
    } else {
        // Users table doesn't exist, use simple query
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE p.id = :product_id LIMIT 1";
    }

    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Product found, return details
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Process image path if needed
        if (!empty($product['image']) && !filter_var($product['image'], FILTER_VALIDATE_URL)) {
            // Convert relative path to absolute URL if needed
            if (strpos($product['image'], '/') === 0) {
                // It's already an absolute path from the server root
                $product['image'] = $product['image'];
            } else {
                // Prepend path
                $product['image'] = '/ClothLoop/' . $product['image'];
            }
        }
        
        // Set response data
        $response['status'] = 'success';
        $response['message'] = 'Product details retrieved successfully';
        $response['data'] = $product;
    } else {
        // No product found
        $response['message'] = 'Product not found';
    }
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching product details: " . $e->getMessage());
    
    // Set error response
    $response['message'] = 'Error fetching product details: ' . $e->getMessage();
    echo json_encode($response);
} 