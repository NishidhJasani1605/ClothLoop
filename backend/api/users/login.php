<?php
/**
 * Login API
 * Authenticates users and creates a session
 */

// Headers
header('Access-Control-Allow-Origin: http://localhost');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['email']) || !isset($data['password'])) {
    Response::error('Email and password are required');
    exit;
}

$email = $data['email'];
$password = $data['password'];

// For demo purposes, allow login with predefined admin credentials
if ($email === 'admin@clothloop.com' && $password === 'password') {
    // Create a demo admin user
    $user = [
        'id' => 3,
        'name' => 'Demo Admin',
        'email' => 'admin@clothloop.com',
        'role' => 'admin',
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

// For demo purposes, allow login with predefined credentials
if ($email === 'buyer@clothloop.com' && $password === 'password') {
    // Create a demo buyer user
    $user = [
        'id' => 2,
        'name' => 'Demo Buyer',
        'email' => 'buyer@clothloop.com',
        'role' => 'buyer',
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
        WHERE email = :email
    ");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // User not found
        Response::error('Invalid credentials');
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is suspended
    if ($user['status'] === 'suspended') {
        Response::error('Your account has been suspended. Please contact the administrator for more information.');
        exit;
    }
    
    // Check if user is inactive
    if ($user['status'] === 'inactive') {
        Response::error('Your account is inactive. Please activate your account.');
        exit;
    }
    
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