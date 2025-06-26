<?php
/**
 * Get Seller Products API
 * Returns all products for a specific seller
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', null, 405);
    exit;
}

// Get seller ID from request
$sellerId = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;

if ($sellerId <= 0) {
    Response::error('Invalid seller ID', null, 400);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if seller exists
    $checkSellerQuery = "SELECT COUNT(*) FROM users WHERE id = :seller_id AND role = 'seller'";
    $checkStmt = $db->prepare($checkSellerQuery);
    $checkStmt->bindParam(':seller_id', $sellerId);
    $checkStmt->execute();
    
    if ($checkStmt->fetchColumn() == 0) {
        Response::error('Seller not found', null, 404);
        exit;
    }
    
    // Get all products for this seller with necessary joins
    $query = "SELECT p.id, p.seller_id, p.title as name, p.description, p.category_id, 
              p.size, p.occasion, p.rental_price as price, p.status, p.is_hidden,
              c.name AS category_name,
              COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id), 0) AS rating,
              (SELECT pi.image_path 
               FROM product_images pi 
               WHERE pi.product_id = p.id 
               ORDER BY pi.is_primary DESC, pi.id ASC 
               LIMIT 1) AS image_path,
              (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS review_count
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.seller_id = :seller_id
              ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->execute();
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process image paths to ensure they're consistently formatted
    foreach ($products as &$product) {
        if (!empty($product['image_path'])) {
            // If image path already starts with 'uploads/', don't add the prefix
            if (strpos($product['image_path'], 'uploads/') === 0) {
                // The path is already relative, keep it as is
            } else {
                // Ensure the path has the correct format
                $product['image_path'] = 'uploads/products/' . $product['id'] . '/' . basename($product['image_path']);
            }
        }
    }
    unset($product); // Break the reference
    
    // Return response
    Response::success('Seller products retrieved successfully', [
        'products' => $products
    ]);
    
} catch (PDOException $e) {
    error_log('Database error in get_seller_products.php: ' . $e->getMessage());
    Response::error('Database error: ' . $e->getMessage(), null, 500);
    exit;
} catch (Exception $e) {
    error_log('General error in get_seller_products.php: ' . $e->getMessage());
    Response::error('Error: ' . $e->getMessage(), null, 500);
    exit;
} 