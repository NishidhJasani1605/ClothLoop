<?php
/**
 * Get Seller Notifications API
 * Returns notifications for the current seller
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', null, 405);
}

// Authenticate
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

if ($currentUser['role'] !== 'seller') {
    Response::error('Only sellers can access their notifications', null, 403);
}

// Get parameters
$type = isset($_GET['type']) ? $_GET['type'] : null;
$isRead = isset($_GET['is_read']) ? intval($_GET['is_read']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$sellerId = $currentUser['id'];

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Build query
    $query = "SELECT * FROM seller_notifications WHERE seller_id = :seller_id";
    $params = [':seller_id' => $sellerId];
    
    // Add type filter if specified
    if ($type !== null) {
        $query .= " AND type = :type";
        $params[':type'] = $type;
    }
    
    // Add read status filter if specified
    if ($isRead !== null) {
        $query .= " AND is_read = :is_read";
        $params[':is_read'] = $isRead;
    }
    
    // Add order and limit
    $query .= " ORDER BY created_at DESC LIMIT :limit";
    
    // Prepare and execute statement
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $param => $value) {
        $stmt->bindValue($param, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return response
    Response::success('Seller notifications retrieved successfully', [
        'notifications' => $notifications
    ]);
    
} catch (PDOException $e) {
    Response::error('Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage(), null, 500);
} 