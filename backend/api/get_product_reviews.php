<?php
// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use GET.'
    ]);
    exit;
}

// Include database connection
require_once '../config/db_connect.php';

// Initialize response array
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Get product ID from URL parameter
if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    $response['message'] = 'Product ID is required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$productId = mysqli_real_escape_string($conn, $_GET['product_id']);

// Check if product exists
$productQuery = "SELECT * FROM products WHERE id = '$productId'";
$productResult = mysqli_query($conn, $productQuery);

if (!$productResult || mysqli_num_rows($productResult) === 0) {
    $response['message'] = 'Product not found';
    http_response_code(404);
    echo json_encode($response);
    exit;
}

$productData = mysqli_fetch_assoc($productResult);

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 10;
$offset = ($page - 1) * $limit;

// Get total number of reviews for pagination
$countQuery = "SELECT COUNT(*) as total FROM product_reviews WHERE product_id = '$productId'";
$countResult = mysqli_query($conn, $countQuery);
$totalReviews = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalReviews / $limit);

// Get reviews with user information
$reviewsQuery = "SELECT pr.*, u.name, u.email, u.profile_image 
                FROM product_reviews pr 
                LEFT JOIN users u ON pr.user_id = u.id 
                WHERE pr.product_id = '$productId' 
                ORDER BY pr.created_at DESC 
                LIMIT $offset, $limit";
$reviewsResult = mysqli_query($conn, $reviewsQuery);

// Calculate average rating
$ratingQuery = "SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = '$productId'";
$ratingResult = mysqli_query($conn, $ratingQuery);
$avgRating = mysqli_fetch_assoc($ratingResult)['avg_rating'];

// Format reviews
$reviews = [];
if ($reviewsResult && mysqli_num_rows($reviewsResult) > 0) {
    while ($row = mysqli_fetch_assoc($reviewsResult)) {
        // Mask email for privacy
        $maskedEmail = '';
        if (isset($row['email']) && !empty($row['email'])) {
            $parts = explode('@', $row['email']);
            if (count($parts) > 1) {
                $username = $parts[0];
                $domain = $parts[1];
                $maskedEmail = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2) . '@' . $domain;
            }
        }
        
        // Format date
        $date = new DateTime($row['created_at']);
        $formattedDate = $date->format('M d, Y');
        
        $reviews[] = [
            'id' => $row['id'],
            'product_id' => $row['product_id'],
            'user_id' => $row['user_id'],
            'user_name' => $row['name'] ?? 'Anonymous',
            'user_email' => $maskedEmail,
            'profile_image' => $row['profile_image'] ?? null,
            'rating' => (int)$row['rating'],
            'review_text' => $row['review_text'],
            'created_at' => $formattedDate,
            'verified_purchase' => (bool)$row['verified_purchase'],
            'helpful_votes' => (int)$row['helpful_votes']
        ];
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Reviews retrieved successfully',
        'data' => [
            'product' => [
                'id' => $productData['id'],
                'name' => $productData['name'],
                'average_rating' => round($avgRating, 1)
            ],
            'reviews' => $reviews,
            'pagination' => [
                'total_reviews' => (int)$totalReviews,
                'total_pages' => (int)$totalPages,
                'current_page' => (int)$page,
                'limit' => (int)$limit
            ]
        ]
    ];
} else {
    $response = [
        'status' => 'success',
        'message' => 'No reviews found for this product',
        'data' => [
            'product' => [
                'id' => $productData['id'],
                'name' => $productData['name'],
                'average_rating' => 0
            ],
            'reviews' => [],
            'pagination' => [
                'total_reviews' => 0,
                'total_pages' => 0,
                'current_page' => (int)$page,
                'limit' => (int)$limit
            ]
        ]
    ];
}

// Close database connection
mysqli_close($conn);

// Return response
echo json_encode($response);
?> 