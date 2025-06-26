<?php
/**
 * Ban Seller API
 * Sets the seller's status to 'suspended'
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../utils/response.php';
require_once '../../utils/auth.php';

// Start session
session_start();

// Check if user is authenticated as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    Response::error('Unauthorized access', 403);
    exit;
}

// Get posted data
$data = json_decode(file_get_contents('php://input'), true);

// Check if seller_id is provided
if (!isset($data['seller_id'])) {
    Response::error('Seller ID is required');
    exit;
}

try {
    // Instantiate DB & connect
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate user object
    $user = new User($db);
    
    // Set user ID
    $user->id = $data['seller_id'];
    
    // Get user data to check if it's a seller
    $userData = $user->getSingle();
    
    if (!$userData) {
        Response::error('Seller not found');
        exit;
    }
    
    if ($userData['role'] !== 'seller') {
        Response::error('The specified user is not a seller');
        exit;
    }
    
    // Update status to suspended (banned)
    if ($user->updateStatus('suspended')) {
        // Log the action
        $adminId = $_SESSION['user']['id'];
        $logMessage = "Seller ID {$data['seller_id']} banned by Admin ID {$adminId}";
        error_log($logMessage, 3, '../../logs/admin_actions.log');
        
        Response::success('Seller banned successfully');
    } else {
        Response::error('Failed to ban seller');
    }
} catch (Exception $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
} 