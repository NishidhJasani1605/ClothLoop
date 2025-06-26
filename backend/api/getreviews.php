<?php
/**
 * Get Reviews API
 * Returns reviews for a specific seller or product with pagination
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Check if product_id is provided
if (isset($_GET['product_id'])) {
    // This is a request for product reviews
    $productId = mysqli_real_escape_string($conn, $_GET['product_id']);
    
    // Pagination parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // Validate product ID exists
    $productQuery = "SELECT id, title FROM products WHERE id = '$productId'";
    $productResult = mysqli_query($conn, $productQuery);
    
    if (!$productResult || mysqli_num_rows($productResult) === 0) {
        $response['message'] = 'Product not found';
        echo json_encode($response);
        exit;
    }
    
    $productData = mysqli_fetch_assoc($productResult);
    
    // Count total reviews for pagination
    $countQuery = "SELECT COUNT(*) as total FROM product_reviews WHERE product_id = '$productId'";
    $countResult = mysqli_query($conn, $countQuery);
    $totalReviews = mysqli_fetch_assoc($countResult)['total'];
    
    // Calculate average rating
    $ratingQuery = "SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = '$productId'";
    $ratingResult = mysqli_query($conn, $ratingQuery);
    $avgRating = mysqli_fetch_assoc($ratingResult)['avg_rating'];
    
    // Get reviews
    $reviewsQuery = "SELECT pr.*, u.name 
                    FROM product_reviews pr 
                    LEFT JOIN users u ON pr.buyer_id = u.id 
                    WHERE pr.product_id = '$productId' 
                    ORDER BY pr.created_at DESC 
                    LIMIT $offset, $limit";
    
    $reviewsResult = mysqli_query($conn, $reviewsQuery);
    
    // Format reviews
    $reviews = [];
    if ($reviewsResult && mysqli_num_rows($reviewsResult) > 0) {
        while ($row = mysqli_fetch_assoc($reviewsResult)) {
            // Format date for display
            $date = new DateTime($row['created_at']);
            $formattedDate = $date->format('Y-m-d');
            
            // Use user_name from product_reviews table or name from users table
            $reviewerName = isset($row['name']) && !empty($row['name']) 
                ? $row['name'] 
                : (isset($row['user_name']) && !empty($row['user_name']) ? $row['user_name'] : 'Anonymous');
            
            $reviews[] = [
                'id' => $row['id'],
                'product_id' => $row['product_id'], 
                'reviewer_name' => $reviewerName,
                'rating' => (float)$row['rating'],
                'review_text' => $row['review'],
                'date' => $formattedDate
            ];
        }
    }
    
    // Prepare response
    $response = [
        'status' => 'success',
        'message' => 'Reviews retrieved successfully',
        'data' => $reviews,
        'meta' => [
            'total' => (int)$totalReviews,
            'average_rating' => $avgRating ? round((float)$avgRating, 1) : 0
        ]
    ];
} 
// Check if seller_id is provided (original functionality)
else if (isset($_GET['seller_id'])) {
    $sellerId = mysqli_real_escape_string($conn, $_GET['seller_id']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;

    // Validate seller ID
    $sellerQuery = "SELECT id FROM sellers WHERE id = '$sellerId'";
    $sellerResult = mysqli_query($conn, $sellerQuery);
    
    if (!$sellerResult || mysqli_num_rows($sellerResult) === 0) {
        $response['message'] = 'Seller not found';
        echo json_encode($response);
        exit;
    }
    
    // Attempt to use the Review model for consistency
    try {
        // Include necessary files
        require_once '../config/database.php';
        require_once '../models/Review.php';
        
        // Create database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Create Review object
        $reviewModel = new Review($db);
        
        // Get reviews
        $reviewsData = $reviewModel->getSellerReviews($sellerId, $limit, $offset, $rating);
        $totalReviews = $reviewModel->countSellerReviews($sellerId, $rating);
        $ratingSummary = $reviewModel->getSellerRatingSummary($sellerId);
        
        // Set response
        $response = [
            'status' => 'success',
            'message' => 'Reviews retrieved successfully',
            'data' => $reviewsData,
            'meta' => [
                'total' => $totalReviews,
                'page' => ceil($offset / $limit) + 1,
                'limit' => $limit,
                'average' => $ratingSummary['average'],
                'breakdown' => $ratingSummary['breakdown']
            ]
        ];
    } catch (Exception $e) {
        // Fallback to direct database query if model approach fails
        error_log("Error using Review model: " . $e->getMessage());
        
        // Get reviews directly from product_reviews via products table
        $reviewsQuery = "SELECT pr.*, u.name, p.title as product_name 
                        FROM product_reviews pr 
                        JOIN products p ON pr.product_id = p.id
                        LEFT JOIN users u ON pr.buyer_id = u.id 
                        WHERE p.seller_id = '$sellerId'
                        ORDER BY pr.created_at DESC 
                        LIMIT $offset, $limit";
        
        $reviewsResult = mysqli_query($conn, $reviewsQuery);
        
        // Calculate average rating
        $ratingQuery = "SELECT AVG(pr.rating) as avg_rating 
                       FROM product_reviews pr 
                       JOIN products p ON pr.product_id = p.id
                       WHERE p.seller_id = '$sellerId'";
        
        $ratingResult = mysqli_query($conn, $ratingQuery);
        $avgRating = mysqli_fetch_assoc($ratingResult)['avg_rating'];
        
        // Get total reviews count
        $countQuery = "SELECT COUNT(*) as total 
                      FROM product_reviews pr 
                      JOIN products p ON pr.product_id = p.id
                      WHERE p.seller_id = '$sellerId'";
        
        $countResult = mysqli_query($conn, $countQuery);
        $totalReviews = mysqli_fetch_assoc($countResult)['total'];
        
        // Format reviews
        $reviews = [];
        if ($reviewsResult && mysqli_num_rows($reviewsResult) > 0) {
            while ($row = mysqli_fetch_assoc($reviewsResult)) {
                // Format date
                $date = new DateTime($row['created_at']);
                $formattedDate = $date->format('Y-m-d');
                
                // Get reviewer name
                $reviewerName = $row['name'] ?? 'Anonymous';
                
                $reviews[] = [
                    'id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'reviewer_name' => $reviewerName,
                    'rating' => (float)$row['rating'],
                    'review_text' => $row['review'],
                    'date' => $formattedDate
                ];
            }
        }
        
        // Calculate rating breakdown
        $breakdownQuery = "SELECT 
                          FLOOR(pr.rating) as rating_value, 
                          COUNT(*) as count 
                          FROM product_reviews pr 
                          JOIN products p ON pr.product_id = p.id
                          WHERE p.seller_id = '$sellerId'
                          GROUP BY FLOOR(pr.rating)";
        
        $breakdownResult = mysqli_query($conn, $breakdownQuery);
        
        $breakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0
        ];
        
        if ($breakdownResult) {
            while ($row = mysqli_fetch_assoc($breakdownResult)) {
                $rating = min(5, max(1, (int)$row['rating_value']));
                $breakdown[$rating] = (int)$row['count'];
            }
        }
        
        // Prepare response
        $response = [
            'status' => 'success',
            'message' => count($reviews) > 0 ? 'Seller reviews retrieved successfully' : 'No reviews found for this seller',
            'data' => $reviews,
            'meta' => [
                'average' => $avgRating ? round((float)$avgRating, 1) : 0,
                'total' => (int)$totalReviews,
                'breakdown' => $breakdown
            ]
        ];
    }
} 
else {
    $response['message'] = 'Either product_id or seller_id is required';
}

// Return response
echo json_encode($response);
?> 