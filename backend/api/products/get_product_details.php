<?php
/**
 * Get Product Details API
 * Retrieves detailed information about a specific product
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
}

// Get product ID from URL parameter
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    Response::error('Invalid product ID');
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Increment view count
    $stmt = $db->prepare("UPDATE products SET views = views + 1 WHERE id = :id");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    // Get product details with seller info and category
    $stmt = $db->prepare("
        SELECT 
            p.*,
            c.name AS category_name,
            u.name AS seller_name,
            s.shop_name,
            s.rating AS seller_rating,
            s.total_ratings AS seller_total_ratings
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN sellers s ON p.seller_id = s.id
        JOIN users u ON s.id = u.id
        WHERE p.id = :id
    ");
    
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get product images
        $stmt = $db->prepare("
            SELECT image_path, is_primary
            FROM product_images
            WHERE product_id = :product_id
            ORDER BY is_primary DESC, id ASC
        ");
        
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get product reviews
        $stmt = $db->prepare("
            SELECT 
                r.id,
                r.rating,
                r.review_text,
                r.created_at,
                u.name AS reviewer_name
            FROM reviews r
            JOIN users u ON r.buyer_id = u.id
            WHERE r.product_id = :product_id
            ORDER BY r.created_at DESC
        ");
        
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate average review rating
        $averageRating = 0;
        $totalReviews = count($reviews);
        
        if ($totalReviews > 0) {
            $ratingSum = array_sum(array_column($reviews, 'rating'));
            $averageRating = round($ratingSum / $totalReviews, 1);
        }
        
        // Combine all data
        $result = [
            'product' => $product,
            'images' => $images,
            'reviews' => $reviews,
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews
        ];
        
        Response::success('Product details retrieved successfully', $result);
    } else {
        Response::error('Product not found', null, 404);
    }
} catch (Exception $e) {
    Response::error('Error retrieving product details: ' . $e->getMessage());
} 