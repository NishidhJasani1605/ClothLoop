<?php
/**
 * Logout API
 * Ends the user session
 */

// Set appropriate CORS headers
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Completely clear all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Force regenerate session ID for security
session_regenerate_id(true);

// Destroy the session.
session_destroy();

// Set PHPSESSID cookie with expired date to force browser to remove it
setcookie('PHPSESSID', '', time() - 3600, '/');

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Logout successful'
]); 