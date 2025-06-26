<?php
/**
 * Test Product Listings API
 * Returns dummy product data for testing
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Create dummy products
$products = [];
for ($i = 1; $i <= 10; $i++) {
    $productId = ($page - 1) * 10 + $i;
    $products[] = [
        'id' => $productId,
        'name' => "Test Product $productId",
        'description' => "This is a test product description for product $productId",
        'price' => rand(10, 1000) . '.00',
        'status' => ['active', 'inactive', 'pending'][rand(0, 2)],
        'category_id' => rand(1, 5),
        'category_name' => 'Test Category ' . rand(1, 5),
        'seller_id' => 1,
        'created_at' => date('Y-m-d H:i:s', time() - rand(0, 30 * 24 * 60 * 60)),
        'updated_at' => date('Y-m-d H:i:s'),
        'image_url' => null, // Set to null to use the default placeholder
        'interest_count' => rand(0, 50)
    ];
}

// Calculate pagination metadata
$totalRecords = 100; // Simulate 100 total records
$totalPages = ceil($totalRecords / $limit);
$hasNextPage = $page < $totalPages;
$hasPrevPage = $page > 1;

// Format response
$response = [
    'status' => 'success',
    'message' => 'Test product listings retrieved successfully',
    'data' => [
        'products' => $products,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ]
    ]
];

// Return success response with product listings
echo json_encode($response); 