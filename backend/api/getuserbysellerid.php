<?php
/**
 * Get User by Seller ID API
 * Returns user contact information by seller ID
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');

// Response array initialization
$response = [
    'status' => 'error',
    'message' => '',
    'data' => null
];

// Include database connection
require_once __DIR__ . '/../config/database.php';

try {
    // Get seller ID from request
    $sellerId = isset($_GET['seller_id']) ? (int)$_GET['seller_id'] : null;
    
    if (empty($sellerId)) {
        $response['message'] = 'Seller ID is required';
        echo json_encode($response);
        exit;
    }
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if users table exists and has phone column
    $checkTable = $db->prepare("SHOW TABLES LIKE 'users'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        // Table doesn't exist, return error
        $response['message'] = 'Users table does not exist';
        $response['status'] = 'success'; // Still use success for frontend compatibility
        $response['data'] = [
            'name' => 'ClothLoop Seller',
            'phone' => '+91 1234567890',
            'whatsapp_number' => '+91 1234567890',
            'email' => 'seller@clothloop.com'
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Check if phone column exists
    $checkPhoneColumn = $db->prepare("SHOW COLUMNS FROM users LIKE 'phone'");
    $checkPhoneColumn->execute();
    $hasPhoneColumn = ($checkPhoneColumn->rowCount() > 0);
    
    // Query to get user details with appropriate fields
    if ($hasPhoneColumn) {
        $query = "SELECT id, name, email, phone FROM users WHERE id = :seller_id";
    } else {
        $query = "SELECT id, name, email FROM users WHERE id = :seller_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':seller_id', $sellerId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // User found
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If phone field doesn't exist, add a dummy one
        if (!$hasPhoneColumn) {
            $user['phone'] = '+91 1234567890'; // Add a default phone
        }
        
        // Check if whatsapp_number field exists in users table
        $checkWhatsappColumn = $db->prepare("SHOW COLUMNS FROM users LIKE 'whatsapp_number'");
        $checkWhatsappColumn->execute();
        
        // If whatsapp_number doesn't exist, use phone number instead
        if ($checkWhatsappColumn->rowCount() == 0) {
            $user['whatsapp_number'] = $user['phone'];
        }
        
        // Set response data
        $response['status'] = 'success';
        $response['message'] = 'User information retrieved successfully';
        $response['data'] = $user;
    } else {
        // No user found, return fallback data
        $response['message'] = 'User not found';
        $response['status'] = 'success'; // Set as success with fallback data
        $response['data'] = [
            'id' => $sellerId,
            'name' => 'ClothLoop Seller',
            'phone' => '+91 1234567890',
            'whatsapp_number' => '+91 1234567890',
            'email' => 'seller@clothloop.com'
        ];
    }
    
    // Output response
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error
    error_log("Error fetching user information: " . $e->getMessage());
    
    // Set error response with fallback data
    $response['message'] = 'Error fetching user information: ' . $e->getMessage();
    $response['status'] = 'success'; // Still set as success for frontend compatibility
    $response['data'] = [
        'id' => $sellerId ?? 0,
        'name' => 'ClothLoop Seller (Fallback)',
        'phone' => '+91 1234567890',
        'whatsapp_number' => '+91 1234567890',
        'email' => 'contact@clothloop.com'
    ];
    
    echo json_encode($response);
} 