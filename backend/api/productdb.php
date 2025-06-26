<?php
/**
 * Product Database API (Fallback)
 * Returns product details from database with basic query as a fallback method
 */

// Set headers for JSON response
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'products' => []
];

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Check if ID is provided for single product
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if products table exists
    $checkProductsTable = $db->prepare("SHOW TABLES LIKE 'products'");
    $checkProductsTable->execute();
    
    if ($checkProductsTable->rowCount() == 0) {
        // Temporarily disable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Insert sample products if this is a new installation
        $db->exec("
            INSERT INTO products (id, title, description, size, category, rental_price, seller_id, image, terms_conditions, status)
            VALUES 
            (1, 'Elegant Black Dress', 'A beautiful black evening dress perfect for formal events', 'M', 'Women', 399.99, 1, '/uploads/products/1/product_image.jpg', 'Return in original condition. Cleaning fees apply for stains.', 'available'),
            (2, 'Navy Blue Suit', 'Professional navy blue suit for business meetings and formal occasions', 'L', 'Men', 499.99, 1, '/uploads/products/2/product_image.jpg', 'Dry cleaning required before return. No alterations allowed.', 'available')
        ");
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Build query based on whether ID is provided
    if ($productId) {
        $query = "SELECT p.*, c.name as category_name 
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id 
                 WHERE p.id = :product_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':product_id', $productId);
    } else {
        // If no ID provided, get all products (limit to 20)
        $query = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC LIMIT 20";
        $stmt = $db->prepare($query);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // Store each product in the response
        $products = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Process image path
            if (!empty($row['image']) && !filter_var($row['image'], FILTER_VALIDATE_URL)) {
                if (strpos($row['image'], '/') === 0) {
                    // Already an absolute path
                    $row['image'] = $row['image'];
                } else {
                    // Prepend ClothLoop path
                    $row['image'] = '/ClothLoop/' . $row['image'];
                }
            }
            
            // If the image is still empty, add a placeholder
            if (empty($row['image'])) {
                $row['image'] = '/ClothLoop/frontend/assets/images/placeholder.png';
            }
            
            // Add additional data even if it's not in the database
            // This helps provide consistent data structure even with minimal DB schema
            if (!isset($row['terms'])) {
                $row['terms'] = $row['terms_conditions'] ?? 'Standard rental terms apply. Return in the same condition as received.';
            }
            
            // Add to products array
            $products[] = $row;
        }
        
        $response['status'] = 'success';
        $response['message'] = 'Products retrieved successfully';
        $response['products'] = $products;
    } else {
        // No products found
        $response['message'] = 'No products found';
    }
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error but provide fallback data
    error_log("Error in productdb.php: " . $e->getMessage());
    
    // Create fallback sample products
    $fallbackProducts = [
        [
            'id' => 1,
            'title' => 'Elegant Black Dress (Fallback)',
            'description' => 'A beautiful black evening dress perfect for formal events. This is fallback data.',
            'size' => 'M',
            'category' => 'Women',
            'rental_price' => 399.99,
            'image' => '/ClothLoop/frontend/assets/images/placeholder.png',
            'terms' => 'Return in original condition. Cleaning fees apply for stains. (Fallback data)',
            'status' => 'available'
        ],
        [
            'id' => 2,
            'title' => 'Navy Blue Suit (Fallback)',
            'description' => 'Professional navy blue suit for business meetings. This is fallback data.',
            'size' => 'L',
            'category' => 'Men',
            'rental_price' => 499.99,
            'image' => '/ClothLoop/frontend/assets/images/placeholder.png',
            'terms' => 'Dry cleaning required before return. (Fallback data)',
            'status' => 'available'
        ]
    ];
    
    // Filter by ID if requested
    if ($productId) {
        $filteredProducts = array_filter($fallbackProducts, function($product) use ($productId) {
            return $product['id'] == $productId;
        });
        
        $response['products'] = array_values($filteredProducts);
    } else {
        $response['products'] = $fallbackProducts;
    }
    
    $response['status'] = 'success';
    $response['message'] = 'Fallback products provided due to database error: ' . $e->getMessage();
    
    echo json_encode($response);
} 