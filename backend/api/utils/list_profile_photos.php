<?php
/**
 * List Profile Photos API
 * Lists all profile photos in the backend/uploads/profile_photos directory
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Directory path
$photosDir = __DIR__ . '/../../uploads/profile_photos/';

try {
    // Check if directory exists
    if (!is_dir($photosDir)) {
        // Create directory if it doesn't exist
        mkdir($photosDir, 0777, true);
        echo json_encode([
            'status' => 'success',
            'message' => 'No photos found. Directory created.',
            'photos' => []
        ]);
        exit;
    }
    
    // Get all profile photos
    $photos = [];
    $files = scandir($photosDir);
    
    foreach ($files as $file) {
        // Skip . and .. directories and non-image files
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Only include files with profile_ prefix
        if (strpos($file, 'profile_') === 0) {
            $photos[] = $file;
        }
    }
    
    // Return the list of photos
    echo json_encode([
        'status' => 'success',
        'message' => count($photos) > 0 ? 'Photos found' : 'No photos found',
        'photos' => $photos
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'status' => 'error',
        'message' => 'Error listing profile photos: ' . $e->getMessage()
    ]);
} 