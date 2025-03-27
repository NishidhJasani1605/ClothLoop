<?php
require_once 'db_connection.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Validate required fields
if (!isset($data['user_id']) || empty($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

if (!isset($data['email']) || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Sanitize inputs
$user_id = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone = isset($data['phone']) ? filter_var($data['phone'], FILTER_SANITIZE_STRING) : '';
$bio = isset($data['bio']) ? filter_var($data['bio'], FILTER_SANITIZE_STRING) : '';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if email already exists for another user
$checkEmailSql = "SELECT id FROM users WHERE email = ? AND id != ?";
$checkStmt = $conn->prepare($checkEmailSql);
$checkStmt->bind_param("si", $email, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Prepare base SQL statement
$sql = "UPDATE users SET email = ?, phone = ?, bio = ?";
$types = "sss";
$params = [$email, $phone, $bio];

// Process password if provided
if (isset($data['password']) && $data['password'] !== '********') {
    $password = $data['password'];
    
    // Validate password strength
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit;
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql .= ", password = ?";
    $types .= "s";
    $params[] = $hashedPassword;
}

// Add WHERE clause
$sql .= " WHERE id = ?";
$types .= "i";
$params[] = $user_id;

// Execute update
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?> 