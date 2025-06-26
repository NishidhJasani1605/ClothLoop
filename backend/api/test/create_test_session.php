<?php
/**
 * TEST ENDPOINT ONLY - NOT FOR PRODUCTION
 * Creates a test seller session for debugging
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

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Create a test seller user
    $testSeller = [
        'id' => 1,
        'name' => 'Test Seller',
        'email' => 'seller@clothloop.com',
        'role' => 'seller',
        'phone_no' => '1234567890',
        'status' => 'active'
    ];
    
    // Store in session
    $_SESSION['user'] = $testSeller;
    $_SESSION['last_activity'] = time();
    
    // Respond with success
    echo json_encode([
        'status' => 'success',
        'message' => 'Test seller session created successfully',
        'data' => [
            'user' => $testSeller
        ]
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create test session: ' . $e->getMessage()
    ]);
} 