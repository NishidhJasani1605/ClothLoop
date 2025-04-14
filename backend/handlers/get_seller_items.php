<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session to get user info
session_start();

// Include database configuration
require_once '../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in'
    ]);
    exit;
}

try {
    // Get the user ID from session
    $user_id = $_SESSION['user_id'];
    
    // Prepare the SQL statement to get all user's cloth items
    $stmt = $conn->prepare("
        SELECT c.*, u.email 
        FROM cloth_items c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        // Process the images - convert comma-separated list to array
        $images = explode(',', $row['images']);
        $processed_images = [];
        
        foreach ($images as $image) {
            $processed_images[] = "/ClothLoop/uploads/cloth_items/{$user_id}/" . $image;
        }
        
        $row['image_urls'] = $processed_images;
        $items[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 