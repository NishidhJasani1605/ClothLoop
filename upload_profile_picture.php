<?php
require_once 'db_connection.php';

if (!isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

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
$newFileName = uniqid('profile_', true) . '.' . $fileExtension;
$targetPath = $uploadDir . $newFileName;

// Validate file type
$allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
if (!in_array($fileExtension, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Move uploaded file
if (move_uploaded_file($fileTmpName, $targetPath)) {
    // Update database with new profile picture path
    $sql = "UPDATE users SET profile_picture = ? WHERE user_type = 'seller'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $targetPath);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'path' => $targetPath]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error moving uploaded file']);
}

$conn->close();
?> 