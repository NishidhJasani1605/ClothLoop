<?php
/**
 * Product Listings API
 * Returns products for a specific seller
 */

// Set appropriate CORS headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = [
    'http://localhost', 
    'http://127.0.0.1',
    'http://localhost:8080',
    'http://localhost:3000'
];

// Allow from any of the allowed origins
if (in_array($origin, $allowed_origins) || strpos($origin, 'clothloop') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Required files
require_once __DIR__ . '/../../config/database.php';

// Verify authentication
$authenticated = false;
$userId = null;
$userRole = null;

// Check session first
if (isset($_SESSION['user']) && isset($_SESSION['user']['id']) && isset($_SESSION['user']['role'])) {
    $userId = $_SESSION['user']['id'];
    $userRole = $_SESSION['user']['role'];
    if ($userRole === 'seller') {
        $authenticated = true;
    }
}

// For backward compatibility - check old session variables if user object doesn't exist
if (!$authenticated && isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    if ($userRole === 'seller') {
        $authenticated = true;
    }
}

// If not authenticated via session, check query parameters
if (!$authenticated) {
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
    $userRole = isset($_GET['user_role']) ? $_GET['user_role'] : null;
    
    if ($userId && $userRole === 'seller') {
        $authenticated = true;
    }
}

// For debugging - log authentication status
error_log("Product listings authentication check: User ID = " . ($userId ?? 'null') . ", Role = " . ($userRole ?? 'null') . ", Authenticated = " . ($authenticated ? 'true' : 'false'));

// If still not authenticated, allow demo seller access for testing
// ONLY if running in development mode (allow override for strict auth)
$isDevelopment = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
if (!$authenticated && $isDevelopment && !isset($_GET['strict_auth'])) {
    $userId = 1; // Demo seller ID
    $userRole = 'seller';
    $authenticated = true;
    error_log("Using demo seller credentials for product listings - DEVELOPMENT MODE ONLY");
}

// Initialize response
$response = [
    'status' => 'success',
    'message' => '',
    'data' => [
        'products' => [],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 1,
            'total_products' => 0,
            'per_page' => 10
        ]
    ]
];

try {
    // Check if authenticated
    if (!$authenticated) {
        http_response_code(401);
        $response['status'] = 'error';
        $response['message'] = 'Authentication required';
        echo json_encode($response);
        exit();
    }
    
    // Get database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify that the user is actually a seller
    $authQuery = "SELECT id, role FROM users WHERE id = ? AND role = 'seller'";
    $authStmt = $db->prepare($authQuery);
    $authStmt->bindParam(1, $userId);
    $authStmt->execute();
    
    if ($authStmt->rowCount() === 0) {
        // Not a valid seller
        $response['status'] = 'error';
        $response['message'] = 'Unauthorized access or invalid seller ID';
        echo json_encode($response);
        exit;
    }
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // Get search, category, and status filters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    // Prepare base query
    $baseQuery = "SELECT p.*, 
                   COALESCE(p.status, 'inactive') AS status_normalized,
                   c.name as category_name,
                   pi.image_path,
                   (SELECT COUNT(*) FROM customer_interests ci WHERE ci.product_id = p.id) as interest_count
                 FROM products p 
                 LEFT JOIN categories c ON p.category_id = c.id
                 LEFT JOIN (
                     SELECT product_id, image_path 
                     FROM product_images 
                     WHERE is_primary = 1
                     UNION
                     SELECT product_id, MIN(image_path) as image_path
                     FROM product_images
                     GROUP BY product_id
                 ) pi ON p.id = pi.product_id
                 WHERE p.seller_id = :seller_id";
    $countQuery = "SELECT COUNT(*) as total FROM products WHERE seller_id = :seller_id";
    
    // Add filters to query
    $params = [':seller_id' => $userId];
    
    if (!empty($search)) {
        $baseQuery .= " AND (p.title LIKE :search OR p.description LIKE :search)";
        $countQuery .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($category)) {
        $baseQuery .= " AND p.category_id = :category_id";
        $countQuery .= " AND category_id = :category_id";
        $params[':category_id'] = $category;
    }
    
    if (!empty($status)) {
        $baseQuery .= " AND p.status = :status";
        $countQuery .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    // Add order by and pagination
    $baseQuery .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
    
    try {
        // Log the query for debugging
        error_log("Product listings query: " . $baseQuery);
        error_log("Params: " . json_encode($params));
        error_log("Offset: " . $offset . ", Limit: " . $limit);
        
        // Try to query the database
        $stmt = $db->prepare($baseQuery);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind limit and offset separately
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        // Get total count
        $countStmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== ':limit' && $key !== ':offset') {
                $countStmt->bindValue($key, $value);
            }
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Fetch products
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log number of products found for debugging
        error_log("Number of products found: " . count($products));
        if (count($products) > 0) {
            error_log("First product: " . json_encode($products[0], JSON_PRETTY_PRINT));
        }
        
        // Check if products exist
        if (count($products) > 0) {
            $response['data']['products'] = $products;
            $response['data']['pagination'] = [
                'current_page' => $page,
                'total_pages' => ceil($totalProducts / $limit),
                'total_products' => $totalProducts,
                'per_page' => $limit
            ];
        } else {
            // Use sample products if no real products found
            $response['data']['products'] = [];
            $response['data']['pagination'] = [
                'current_page' => 1,
                'total_pages' => 1,
                'total_products' => 0,
                'per_page' => 0
            ];
        }
    } catch (PDOException $e) {
        // If database query fails, use sample products
        $response['data']['products'] = [];
        $response['data']['pagination'] = [
            'current_page' => 1,
            'total_pages' => 1,
            'total_products' => 0,
            'per_page' => 0
        ];
    }
    
    // Return success response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    $response['status'] = 'error';
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
} 