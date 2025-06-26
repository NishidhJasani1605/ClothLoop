<?php
/**
 * Update Buyer Profile API
 * Allows buyers to update their profile information
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
require_once __DIR__ . '/../../models/Buyer.php';
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

    // Create user and buyer objects
    $user = new User($db);
    $buyer = new Buyer($db);
    
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
    
    // Handle file upload for profile photo
    $profilePhotoUrl = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/profile_photos/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $fileExt = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('profile_') . '.' . $fileExt;
        $targetFilePath = $uploadDir . $fileName;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
            $response['message'] = 'Invalid file type. Only JPG, PNG and GIF are allowed.';
            echo json_encode($response);
            exit;
        }
        
        // Check file size (5MB max)
        if ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) {
            $response['message'] = 'File is too large. Maximum size is 5MB.';
            echo json_encode($response);
            exit;
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFilePath)) {
            // Delete old profile photo if exists
            if ($user->profile_photo && file_exists($uploadDir . $user->profile_photo)) {
                unlink($uploadDir . $user->profile_photo);
            }
            
            $user->profile_photo = $fileName;
            // Construct absolute URL for profile photo
            $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $baseUrl .= $_SERVER['HTTP_HOST'];
            $baseUrl .= '/ClothLoop/backend/uploads/profile_photos/';
            $profilePhotoUrl = $baseUrl . $fileName;
        } else {
            $response['message'] = 'Failed to upload profile photo.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Update user data
    $user->name = isset($_POST['name']) ? $_POST['name'] : $user->name;
    $user->email = isset($_POST['email']) ? $_POST['email'] : $user->email;
    $user->phone_no = isset($_POST['phone_no']) ? $_POST['phone_no'] : $user->phone_no;
    
    // Update password if provided
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
    
    // Update user in database
    if ($user->update()) {
        // Update buyer location if provided
        if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
            $buyer->id = $userId; // Use id for the buyers table
            $buyer->readSingle(); // Get current data
            
            $buyer->latitude = $_POST['latitude'];
            $buyer->longitude = $_POST['longitude'];
            
            $buyer->update();
        }
        
        // Prepare response data
        $responseData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone_no' => $user->phone_no,
        ];
        
        // Add profile photo URL if updated
        if ($profilePhotoUrl) {
            $responseData['profile_photo_url'] = $profilePhotoUrl;
        }
        
        $response = [
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => $responseData
        ];
    } else {
        $response['message'] = 'Failed to update profile';
    }
    
} catch (Exception $e) {
    // Log error to file instead of output
    error_log('Update Profile API Error: ' . $e->getMessage());
    $response['message'] = 'Error updating profile data';
}

// Return clean JSON response
echo json_encode($response); 