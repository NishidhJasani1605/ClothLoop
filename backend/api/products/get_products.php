<?php
/**
 * Get Products API
 * Lists or searches for products with various filtering options
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../utils/response.php';

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', null, 405);
}

// Get search/filter parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : DEFAULT_PAGE_SIZE;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$size = isset($_GET['size']) ? trim($_GET['size']) : '';
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'created_at';
$sortDir = isset($_GET['sort_dir']) && strtolower($_GET['sort_dir']) === 'asc' ? 'ASC' : 'DESC';
$sellerId = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 0;
$occasion = isset($_GET['occasion']) ? trim($_GET['occasion']) : '';

// Ensure valid pagination
if ($page < 1) $page = 1;
if ($limit < 1 || $limit > 50) $limit = DEFAULT_PAGE_SIZE;

// Calculate offset
$offset = ($page - 1) * $limit;

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Build query
    $query = "
        SELECT 
            p.*,
            COALESCE(p.status, 'inactive') AS status_normalized,
            c.name AS category_name,
            u.name AS seller_name,
            s.shop_name,
            (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS primary_image,
            (SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id) AS review_count,
            (SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id) AS avg_rating
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        JOIN sellers s ON p.seller_id = s.id
        JOIN users u ON s.id = u.id
        WHERE (p.status = 'available' OR p.status = 'active' OR p.status IS NULL)
        AND (p.is_hidden = 0 OR p.is_hidden IS NULL)
    ";
    
    // Add filters
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (p.title LIKE :search OR p.description LIKE :search)";
        $searchTerm = "%$search%";
        $params[':search'] = $searchTerm;
    }
    
    if ($category > 0) {
        $query .= " AND p.category_id = :category";
        $params[':category'] = $category;
    }
    
    if (!empty($size)) {
        $query .= " AND p.size = :size";
        $params[':size'] = $size;
    }
    
    if ($minPrice > 0) {
        $query .= " AND p.rental_price >= :min_price";
        $params[':min_price'] = $minPrice;
    }
    
    if ($maxPrice > 0) {
        $query .= " AND p.rental_price <= :max_price";
        $params[':max_price'] = $maxPrice;
    }
    
    if ($sellerId > 0) {
        $query .= " AND p.seller_id = :seller_id";
        $params[':seller_id'] = $sellerId;
    }
    
    if (!empty($occasion)) {
        $query .= " AND p.occasion LIKE :occasion";
        $params[':occasion'] = "%$occasion%";
    }
    
    // Add sorting
    $allowedSortFields = ['title', 'rental_price', 'created_at', 'views'];
    if (!in_array($sortBy, $allowedSortFields)) {
        $sortBy = 'created_at';
    }
    
    $query .= " ORDER BY p.$sortBy $sortDir";
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
    $stmt = $db->prepare($countQuery);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Add limit and offset for pagination
    $query .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    // Execute final query
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results
    $totalPages = ceil($totalCount / $limit);
    
    $result = [
        'products' => $products,
        'pagination' => [
            'total_items' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ];
    
    Response::success('Products retrieved successfully', $result);
} catch (Exception $e) {
    Response::error('Error retrieving products: ' . $e->getMessage());
} 