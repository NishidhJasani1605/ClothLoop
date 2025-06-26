<?php
/**
 * Update Buyer Profile API
 * Allows buyers to update their profile information
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/validate.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// If no data provided
if (!$data) {
    Response::error('No data provided');
}

// Validate input
Validate::reset();
Validate::required('name', $data['name'] ?? '');
Validate::required('email', $data['email'] ?? '');
Validate::email('email', $data['email'] ?? '');
Validate::required('phone_no', $data['phone_no'] ?? '');

// Optional password change
if (isset($data['password']) && !empty($data['password'])) {
    Validate::minLength('password', $data['password'], 6);
}

if (Validate::hasErrors()) {
    Response::error('Validation failed', Validate::getErrors());
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if email is already in use by another user
    if ($data['email'] !== $user['email']) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            Response::error('Email is already in use by another account.');
        }
    }
    
    // Update user basic info
    $query = "UPDATE users SET name = :name, email = :email, phone_no = :phone_no";
    $params = [
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':phone_no' => $data['phone_no'],
        ':id' => $user['id']
    ];
    
    // Add password to update if provided
    if (isset($data['password']) && !empty($data['password'])) {
        $query .= ", password = :password";
        $params[':password'] = Auth::hashPassword($data['password']);
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    $stmt->execute();
    
    // Update buyer-specific info if provided
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $stmt = $db->prepare("
            UPDATE buyers 
            SET latitude = :latitude, longitude = :longitude
            WHERE id = :id
        ");
        
        $stmt->bindParam(':latitude', $data['latitude']);
        $stmt->bindParam(':longitude', $data['longitude']);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    // Fetch updated user data
    $stmt = $db->prepare("
        SELECT u.*, b.address, b.latitude, b.longitude
        FROM users u
        JOIN buyers b ON u.id = b.id
        WHERE u.id = :id
    ");
    
    $stmt->bindParam(':id', $user['id']);
    $stmt->execute();
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Remove sensitive information
    unset($updatedUser['password']);
    
    // Update session with new user data
    Auth::startSession($updatedUser);
    
    Response::success('Profile updated successfully', [
        'buyer' => $updatedUser
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Response::error('Failed to update profile: ' . $e->getMessage());
} 