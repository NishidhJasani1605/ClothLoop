<?php
/**
 * Check Session API
 * Verifies if user is authenticated and returns basic session info
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

// Check if session exists first
$sessionExists = isset($_SESSION) && !empty($_SESSION) && isset($_SESSION['user']);

// Check authentication through Auth class
$isAuthenticated = Auth::checkSession();
$user = null;

if ($isAuthenticated) {
    $user = Auth::getCurrentUser();
    
    // Return basic user info without sensitive data
    $userInfo = [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    Response::success('User is authenticated', [
        'authenticated' => true,
        'user' => $userInfo,
        'session_id' => session_id(),
        'session_exists' => $sessionExists
    ]);
} else {
    // User is not authenticated
    Response::success('User is not authenticated', [
        'authenticated' => false,
        'user' => null,
        'session_id' => session_id(),
        'session_exists' => $sessionExists
    ]);
} 