<?php
/**
 * Get Buyer Profile API
 * Retrieves buyer profile information including location data
 */

// No output before headers
ob_start();

// Disable error display in output
ini_set('display_errors', 0);
error_reporting(0);

// Headers
header('Access-Control-Allow-Origin: http://localhost');  // Update with your domain
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true'); // Important for cookies

// Clean any buffered output
ob_end_clean();

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Buyer.php';
require_once __DIR__ . '/../../utils/auth.php';

// Initialize response array
$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

// Process only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

    // Create user and buyer objects
    $user = new User($db);
    $buyer = new Buyer($db);
    
    // Get user data by ID
    if (!$user->readById($userId)) {
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit;
    }
    
    // Verify user is a buyer - check both user_type and role fields for compatibility
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
    
    // Use mock data for testing
    $userData = [
        'id' => $user->id,
        'name' => $user->name ?? 'Test User',
        'email' => $user->email ?? 'test@example.com',
        'phone_no' => $user->phone_no ?? '1234567890',
        'created_at' => $user->created_at ?? date('Y-m-d H:i:s'),
        'profile_photo_url' => null,
        'latitude' => null, 
        'longitude' => null
    ];
    
    // Try to get buyer location data if available
    try {
        $buyer->id = $userId;
        if ($buyer->readSingle()) {
            $userData['latitude'] = $buyer->latitude;
            $userData['longitude'] = $buyer->longitude;
            
            // Only add properties that exist in the Buyer model
            // and are set in this particular buyer record
            if (isset($buyer->address)) {
                $userData['address'] = $buyer->address;
            }
        }
    } catch (Exception $e) {
        error_log('Error fetching buyer location: ' . $e->getMessage());
        // Continue even if location data can't be fetched
    }
    
    // Add profile photo URL if available
    if (!empty($user->profile_photo)) {
        // Construct absolute URL for profile photo
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        $baseUrl .= '/ClothLoop/backend/uploads/profile_photos/';
        $userData['profile_photo_url'] = $baseUrl . $user->profile_photo;
    }
    
    // Success response
    $response = [
        'status' => 'success',
        'message' => 'Profile data retrieved successfully',
        'user' => $userData
    ];
    
} catch (Exception $e) {
    // Log error to file instead of output
    error_log('Get Profile API Error: ' . $e->getMessage());
    $response['message'] = 'Error retrieving profile data';
}

// Return clean JSON response
echo json_encode($response); 