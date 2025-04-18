<?php
/**
 * Login API
 * Authenticates users and creates a session
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

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    Response::error('Email and password are required');
}

$email = $data['email'];
$password = $data['password'];

// For demo purposes, allow login with predefined credentials
if ($email === 'seller@clothloop.com' && $password === 'password') {
    // Create a demo seller user
    $user = [
        'id' => 1,
        'name' => 'Demo Seller',
        'email' => 'seller@clothloop.com',
        'role' => 'seller',
        'phone_no' => '1234567890',
        'status' => 'active'
    ];
    
    // Start session
    Auth::startSession($user);
    
    // Return success response
    Response::success('Login successful', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    exit;
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if users table exists
    $checkTable = $db->prepare("SHOW TABLES LIKE 'users'");
    $checkTable->execute();
    
    if ($checkTable->rowCount() == 0) {
        // Users table doesn't exist yet
        Response::error('Invalid credentials');
        exit;
    }
    
    // Get user from database
    $stmt = $db->prepare("
        SELECT id, name, email, password, role, phone_no, status
        FROM users
        WHERE email = :email AND status = 'active'
    ");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // User not found or inactive
        Response::error('Invalid credentials');
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verify password
    if (!Auth::verifyPassword($password, $user['password'])) {
        // Password doesn't match
        Response::error('Invalid credentials');
        exit;
    }
    
    // Start session
    Auth::startSession($user);
    
    // Return success response
    Response::success('Login successful', [
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    Response::error('Login failed: ' . $e->getMessage());
} 