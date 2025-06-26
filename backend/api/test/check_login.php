<?php
/**
 * TEST ENDPOINT ONLY - NOT FOR PRODUCTION
 * Checks if the user is properly authenticated
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
$isAuthenticated = Auth::checkSession();

if ($isAuthenticated) {
    $user = Auth::getCurrentUser();
    echo json_encode([
        'status' => 'success',
        'authenticated' => true,
        'user' => [
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'id' => $user['id']
        ],
        'session_data' => [
            'session_id' => session_id(),
            'last_activity' => isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : null
        ]
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'authenticated' => false,
        'message' => 'Not authenticated',
        'session_data' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_active' => isset($_SESSION) && !empty($_SESSION)
        ]
    ]);
} 