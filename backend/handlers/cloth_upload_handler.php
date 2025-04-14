<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers to handle AJAX requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    
    // Create upload directory if it doesn't exist
    $upload_dir = "../../uploads/cloth_items/{$user_id}/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Process form data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $size = isset($_POST['size']) ? trim($_POST['size']) : '';
        $color = isset($_POST['color']) ? trim($_POST['color']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $whatsapp = isset($_POST['whatsapp']) ? trim($_POST['whatsapp']) : '';
        $shop_name = isset($_POST['shopName']) ? trim($_POST['shopName']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $terms = isset($_POST['terms']) ? trim($_POST['terms']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        
        // Validate required fields
        if (empty($title) || empty($size) || empty($color) || $price <= 0 || 
            empty($contact) || empty($shop_name) || empty($address)) {
            throw new Exception("Please fill in all required fields");
        }
        
        // Handle image uploads
        $uploaded_files = [];
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $file_count = count($_FILES['images']['name']);
            
            // Limit to 5 images
            $file_count = min($file_count, 5);
            
            for ($i = 0; $i < $file_count; $i++) {
                // Skip empty file slots
                if ($_FILES['images']['error'][$i] == UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                if ($_FILES['images']['error'][$i] != UPLOAD_ERR_OK) {
                    throw new Exception("Upload error: " . $_FILES['images']['error'][$i]);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $new_filename = uniqid('cloth_') . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $upload_path)) {
                    $uploaded_files[] = $new_filename;
                } else {
                    throw new Exception("Failed to move uploaded file");
                }
            }
        } else {
            throw new Exception("Please upload at least one image");
        }
        
        // Store comma-separated image filenames
        $images_string = implode(',', $uploaded_files);
        
        // Prepare SQL statement to insert the cloth item
        $stmt = $conn->prepare("INSERT INTO cloth_items 
                              (user_id, title, description, size, color, price, contact, 
                              whatsapp, shop_name, address, terms, location, images) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Error preparing database statement: " . $conn->error);
        }
        
        $stmt->bind_param("issssdsssssss", 
                        $user_id, $title, $description, $size, $color, $price, 
                        $contact, $whatsapp, $shop_name, $address, $terms, $location, $images_string);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Cloth item added successfully',
                'item_id' => $conn->insert_id
            ]);
        } else {
            throw new Exception("Error inserting record: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        throw new Exception("Invalid request method");
    }
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