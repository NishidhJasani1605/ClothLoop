<?php
require_once 'db_connection.php';

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

// Sanitize inputs
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
$password = $data['password'];
$bio = filter_var($data['bio'], FILTER_SANITIZE_STRING);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Hash password if it's not the placeholder
$passwordUpdate = '';
if ($password !== '********') {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $passwordUpdate = ", password = '$hashedPassword'";
}

// Update user information
$sql = "UPDATE users SET 
        email = ?, 
        phone = ?,
        bio = ?
        WHERE user_type = 'seller'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $phone, $bio);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$conn->close();
?> 