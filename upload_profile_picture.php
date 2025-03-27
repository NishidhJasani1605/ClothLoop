<?php
require_once 'db_connection.php';

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

$user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
$file = $_FILES['profile_picture'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileError = $file['error'];

// Check for errors
if ($fileError !== 0) {
    echo json_encode(['success' => false, 'message' => 'Error uploading file']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = 'uploads/profile_pictures/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$newFileName = 'user_' . $user_id . '_' . uniqid() . '.' . $fileExtension;
$targetPath = $uploadDir . $newFileName;

// Validate file type
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($fileExtension, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: jpg, jpeg, png, gif, webp']);
    exit;
}

// Validate file size (max 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

// Move uploaded file
if (move_uploaded_file($fileTmpName, $targetPath)) {
    // Update database with new profile picture path
    $sql = "UPDATE users SET profile_picture = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $targetPath, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'path' => $targetPath
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error moving uploaded file']);
}

$conn->close();
?> 