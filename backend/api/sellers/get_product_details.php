<?php
/**
 * Get Product Details API for Sellers
 * Retrieves detailed information about a specific product owned by a seller
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';

// Initialize an empty response array
$response = ['status' => 'error', 'message' => 'An error occurred'];

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Get product ID from URL parameter
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
    exit;
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get product details with seller info and category
    $query = "
        SELECT 
            p.*,
            pi.image_path,
            c.name AS category_name,
            s.shop_name,
            s.address AS shop_address,
            u.phone_no AS contact_number
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN (
            SELECT product_id, image_path FROM product_images WHERE is_primary = 1
            UNION 
            SELECT product_id, MIN(image_path) FROM product_images GROUP BY product_id
        ) pi ON p.id = pi.product_id
        JOIN sellers s ON p.seller_id = s.id
        JOIN users u ON s.id = u.id
        WHERE p.id = :id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format product data for frontend
        $formattedProduct = [
            'id' => $product['id'],
            'name' => $product['title'],
            'description' => $product['description'],
            'price_per_day' => $product['rental_price'],
            'size' => $product['size'],
            'category' => $product['category_name'],
            'category_id' => $product['category_id'],
            'occasion' => isset($product['occasion']) ? $product['occasion'] : 'Any Occasion',
            'shop_name' => $product['shop_name'],
            'shop_address' => $product['shop_address'],
            'contact_number' => $product['contact_number'],
            'terms_and_conditions' => isset($product['terms']) ? $product['terms'] : 'Standard rental terms apply.',
            'status' => $product['status'],
            'image_path' => $product['image_path'] ?? null
        ];
        
        $response = [
            'status' => 'success',
            'message' => 'Product details retrieved successfully',
            'product' => $formattedProduct
        ];
    } else {
        $response = [
            'status' => 'error',
            'message' => 'Product not found'
        ];
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Error retrieving product details: ' . $e->getMessage()
    ];
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit; 