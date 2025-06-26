<?php
/**
 * Get Seller Reviews API
 * Fetches all reviews for a specific seller from product reviews and seller reviews tables
 */

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use GET.'
    ]);
    exit;
}

// Include database connection
require_once '../../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Get seller ID from URL parameter
$sellerId = isset($_GET['seller_id']) ? mysqli_real_escape_string($conn, $_GET['seller_id']) : null;

// Validate seller ID
if (!$sellerId || !is_numeric($sellerId)) {
    $response['message'] = 'Invalid or missing seller ID';
    echo json_encode($response);
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Check if the seller exists
$checkSellerQuery = "SELECT * FROM sellers WHERE id = '$sellerId'";
$sellerResult = mysqli_query($conn, $checkSellerQuery);

if (!$sellerResult || mysqli_num_rows($sellerResult) === 0) {
    $response['message'] = 'Seller not found';
    echo json_encode($response);
    exit;
}

$sellerData = mysqli_fetch_assoc($sellerResult);

// Check if seller_reviews table exists
$tableCheckQuery = "SHOW TABLES LIKE 'seller_reviews'";
$tableCheckResult = mysqli_query($conn, $tableCheckQuery);
$sellerReviewsTableExists = mysqli_num_rows($tableCheckResult) > 0;

// Initialize reviews array
$reviews = [];
$totalReviews = 0;

// PART 1: Get reviews from product_reviews table
// Get total reviews count for pagination - Only reviews for products from this seller
$countQuery = "SELECT COUNT(*) as total 
               FROM product_reviews pr 
               JOIN products p ON pr.product_id = p.id 
               WHERE p.seller_id = '$sellerId'";
$countResult = mysqli_query($conn, $countQuery);
$productReviewsCount = mysqli_fetch_assoc($countResult)['total'];
$totalReviews += $productReviewsCount;

// Query to get reviews with product and buyer information
if ($productReviewsCount > 0) {
    $query = "SELECT pr.id, pr.product_id, pr.buyer_id, pr.rating, pr.review as review_text, pr.created_at,
              p.title as product_name, p.seller_id,
              u.name as buyer_name, u.profile_photo as buyer_photo,
              'product_review' as review_type
              FROM product_reviews pr
              JOIN products p ON pr.product_id = p.id
              LEFT JOIN users u ON pr.buyer_id = u.id
              WHERE p.seller_id = '$sellerId'
              ORDER BY pr.created_at DESC";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Format the created_at date
            $row['review_date'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
            
            // Add to reviews array
            $reviews[] = $row;
        }
    }
}

// PART 2: Get reviews from seller_reviews table if it exists
if ($sellerReviewsTableExists) {
    $countSellerReviewsQuery = "SELECT COUNT(*) as total FROM seller_reviews WHERE seller_id = '$sellerId'";
    $countSellerResult = mysqli_query($conn, $countSellerReviewsQuery);
    $sellerReviewsCount = mysqli_fetch_assoc($countSellerResult)['total'];
    $totalReviews += $sellerReviewsCount;
    
    if ($sellerReviewsCount > 0) {
        $sellerReviewsQuery = "SELECT 
                sr.id, 
                NULL as product_id,
                sr.buyer_id, 
                sr.rating, 
                sr.review_text, 
                sr.created_at,
                NULL as product_name,
                sr.seller_id,
                u.name as buyer_name,
                u.profile_photo as buyer_photo,
                'seller_review' as review_type
            FROM seller_reviews sr
            LEFT JOIN users u ON sr.buyer_id = u.id
            WHERE sr.seller_id = '$sellerId'
            ORDER BY sr.created_at DESC";
        
        $sellerReviewsResult = mysqli_query($conn, $sellerReviewsQuery);
        
        if ($sellerReviewsResult) {
            while ($row = mysqli_fetch_assoc($sellerReviewsResult)) {
                // Format the created_at date
                $row['review_date'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
                
                // Add to reviews array
                $reviews[] = $row;
            }
        }
    }
}

// Sort combined reviews by date (newest first)
usort($reviews, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Apply pagination to the combined results
$totalPages = ceil($totalReviews / $limit);
$paginatedReviews = array_slice($reviews, $offset, $limit);

// Calculate average rating from all reviews
$totalRating = 0;
$ratingsCount = count($reviews);

if ($ratingsCount > 0) {
    foreach ($reviews as $review) {
        $totalRating += floatval($review['rating']);
    }
    $avgRating = round($totalRating / $ratingsCount, 1);
} else {
    $avgRating = 0;
}

// Calculate rating distribution
$ratingDistribution = [
    '5' => 0,
    '4' => 0,
    '3' => 0,
    '2' => 0,
    '1' => 0
];

if ($ratingsCount > 0) {
    // Count ratings in each category
    $ratingCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    
    foreach ($reviews as $review) {
        $rating = round(floatval($review['rating']));
        if ($rating < 1) $rating = 1;
        if ($rating > 5) $rating = 5;
        
        $ratingCounts[$rating]++;
    }
    
    // Calculate percentages
    foreach ($ratingCounts as $rating => $count) {
        $ratingDistribution["$rating"] = ($count / $ratingsCount) * 100;
    }
}

// Prepare response
$response['status'] = 'success';
$response['message'] = count($paginatedReviews) > 0 ? 'Reviews retrieved successfully' : 'No reviews found for this seller';
$response['data'] = [
    'seller' => [
        'id' => $sellerData['id'],
        'name' => isset($sellerData['shop_name']) ? $sellerData['shop_name'] : '',
        'avg_rating' => $avgRating
    ],
    'stats' => [
        'average_rating' => $avgRating,
        'total_reviews' => (int)$totalReviews,
        'rating_percentages' => $ratingDistribution
    ],
    'reviews' => $paginatedReviews,
    'pagination' => [
        'total_reviews' => (int)$totalReviews,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'limit' => $limit
    ]
];

// Close database connection
mysqli_close($conn);

// Return the response
echo json_encode($response);
?> 