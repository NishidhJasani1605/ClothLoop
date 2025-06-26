<?php
/**
 * Upload Profile Photo API
 * Handles uploading a profile photo for a seller
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

// Check if user is authenticated
Auth::requireAuth();

// Get the current user
$user = Auth::getCurrentUser();

// Make sure the user is a seller
if ($user['role'] !== 'seller') {
    Response::error('Access denied. This endpoint is for sellers only.', null, 403);
}

// Check if a file was uploaded
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    Response::error('No file uploaded or upload error occurred.', null, 400);
}

try {
    // Set upload directory
    $upload_dir = __DIR__ . '/../../uploads/profile_photos/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate a unique filename
    $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
    $unique_filename = 'profile_' . $user['id'] . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;
    
    // Move the uploaded file
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
        // File path to store in the database (relative path)
        $relative_path = 'uploads/profile_photos/' . $unique_filename;
        
        // Connect to database
        $database = new Database();
        $db = $database->getConnection();
        
        // Update user profile photo
        $query = "UPDATE users SET profile_photo = :profile_photo WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':profile_photo', $relative_path);
        $stmt->bindParam(':id', $user['id']);
        $stmt->execute();
        
        // Return success response with file path
        Response::success('Profile photo uploaded successfully', [
            'file_path' => $relative_path
        ]);
    } else {
        Response::error('Failed to move uploaded file.');
    }
} catch (Exception $e) {
    Response::error('Error uploading profile photo: ' . $e->getMessage());
} 