<?php
require_once 'db_connection.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Prepare SQL statement
$sql = "SELECT email, phone, user_type, bio, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'user_type' => $user['user_type'],
        'bio' => $user['bio'] ?? '',
        'profile_picture' => $user['profile_picture'] ?? ''
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?> 