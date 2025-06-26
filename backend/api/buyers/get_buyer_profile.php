<?php
/**
 * Get Buyer Profile API
 * Retrieves buyer profile information
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', null, 405);
}

// Require authentication
Auth::requireAuth();

// Get current user
$user = Auth::getCurrentUser();

// Ensure the user is a buyer
if ($user['role'] !== 'buyer') {
    Response::error('Access denied. This endpoint is for buyers only.', null, 403);
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get buyer info including location data
    $stmt = $db->prepare("
        SELECT u.*, b.address, b.latitude, b.longitude
        FROM users u
        JOIN buyers b ON u.id = b.id
        WHERE u.id = :id
    ");
    
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Remove sensitive information
        unset($buyer['password']);
        
        Response::success('Buyer profile retrieved successfully', [
            'buyer' => $buyer
        ]);
    } else {
        Response::error('Failed to retrieve buyer profile', null, 404);
    }
} catch (Exception $e) {
    Response::error('Error retrieving buyer profile: ' . $e->getMessage());
} 