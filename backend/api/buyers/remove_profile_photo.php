<?php
/**
 * Remove Buyer Profile Photo API
 * Removes the profile photo for the authenticated buyer
 */

// No output before headers
ob_start();

// Disable error display in output
ini_set('display_errors', 0);
error_reporting(0);

// Headers
header('Access-Control-Allow-Origin: http://localhost');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true'); // Important for cookies

// Clean any buffered output
ob_end_clean();

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/auth.php';

// Initialize response array
$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    $response['message'] = 'Not authenticated';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user']['id'];

try {
    // Database connection
    $database = new Database();
    $db = $database->connect();

    // Create user object
    $user = new User($db);
    
    // Get user data by ID
    if (!$user->readById($userId)) {
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit;
    }
    
    // Verify user is a buyer
    $isBuyer = false;
    if (isset($user->user_type) && $user->user_type === 'buyer') {
        $isBuyer = true;
    } elseif (isset($user->role) && $user->role === 'buyer') {
        $isBuyer = true;
    } elseif (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'buyer') {
        $isBuyer = true;
    }

    if (!$isBuyer) {
        $response['message'] = 'Access denied. This endpoint is for buyers only.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has a profile photo
    if (empty($user->profile_photo)) {
        $response['message'] = 'No profile photo to remove';
        echo json_encode($response);
        exit;
    }
    
    // Delete the profile photo file
    $uploadDir = __DIR__ . '/../../uploads/profile_photos/';
    $photoPath = $uploadDir . $user->profile_photo;
    
    if (file_exists($photoPath)) {
        unlink($photoPath);
    }
    
    // Update user record to remove profile photo reference
    $user->profile_photo = null;
    
    if ($user->update()) {
        $response = [
            'status' => 'success',
            'message' => 'Profile photo removed successfully'
        ];
    } else {
        $response['message'] = 'Failed to update user record';
    }
    
} catch (Exception $e) {
    // Log error to file instead of output
    error_log('Remove Profile Photo API Error: ' . $e->getMessage());
    $response['message'] = 'Error removing profile photo';
}

// Return clean JSON response
echo json_encode($response); 