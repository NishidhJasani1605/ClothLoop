<?php
/**
 * Mark Notification Read API
 * Marks a seller notification as read
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Check for required fields
if (!isset($data->notification_id)) {
    Response::error('Missing required parameters', null, 400);
}

// Authenticate
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

if ($currentUser['role'] !== 'seller') {
    Response::error('Only sellers can mark their notifications as read', null, 403);
}

$notificationId = intval($data->notification_id);
$sellerId = $currentUser['id'];

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // First, verify the notification belongs to this seller
    $checkQuery = "SELECT id FROM seller_notifications WHERE id = :notification_id AND seller_id = :seller_id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
    $checkStmt->bindParam(':seller_id', $sellerId, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::error('Notification not found or does not belong to this seller', null, 404);
    }
    
    // Update notification to mark as read
    $updateQuery = "UPDATE seller_notifications SET is_read = 1 WHERE id = :notification_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Return success response
    Response::success('Notification marked as read successfully');
    
} catch (PDOException $e) {
    Response::error('Database error: ' . $e->getMessage(), null, 500);
} catch (Exception $e) {
    Response::error('Error: ' . $e->getMessage(), null, 500);
} 