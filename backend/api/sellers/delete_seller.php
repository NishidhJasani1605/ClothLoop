<?php
/**
 * Delete Seller API
 * Deletes a seller account and related data
 */

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once '../../config/database.php';
require_once '../../utils/response.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log session data
error_log("Session data in delete_seller.php: " . print_r($_SESSION, true));

// Check if user is authenticated as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    Response::error('Unauthorized access. Admin privileges required.', 403);
    exit;
}

// Get JSON input
$json_data = file_get_contents('php://input');
error_log("Raw input: " . $json_data);

// Check for empty input
if (empty($json_data)) {
    Response::error('Empty request body');
    exit;
}

// Parse JSON data
$data = json_decode($json_data, true);

// Check for JSON parsing errors
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    Response::error('Invalid JSON: ' . json_last_error_msg());
    exit;
}

// Check if seller_id is provided
if (empty($data['seller_id'])) {
    Response::error('Seller ID is required');
    exit;
}

$seller_id = $data['seller_id'];
error_log("Processing deletion for seller ID: " . $seller_id);

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Function to check if a table exists
    function tableExists($db, $table) {
        try {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking if table exists: " . $e->getMessage());
            return false;
        }
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // First, verify the seller exists
    if (tableExists($db, 'sellers')) {
        $stmt = $db->prepare("SELECT id FROM sellers WHERE id = ?");
        $stmt->execute([$seller_id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Seller with ID $seller_id not found");
        }
    }
    
    // Get all product IDs for this seller
    $product_ids = [];
    if (tableExists($db, 'products')) {
        $stmt = $db->prepare("SELECT id FROM products WHERE seller_id = ?");
        $stmt->execute([$seller_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product_ids[] = $row['id'];
        }
        error_log("Found " . count($product_ids) . " products for seller ID $seller_id");
    }
    
    // Delete product reviews
    if (!empty($product_ids) && tableExists($db, 'product_reviews')) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $query = "DELETE FROM product_reviews WHERE product_id IN ($placeholders)";
        error_log("Running query: " . $query);
        $stmt = $db->prepare($query);
        $stmt->execute($product_ids);
        error_log("Deleted reviews for seller's products");
    }
    
    // Delete products
    if (tableExists($db, 'products')) {
        $query = "DELETE FROM products WHERE seller_id = ?";
        error_log("Running query: " . $query);
        $stmt = $db->prepare($query);
        $stmt->execute([$seller_id]);
        error_log("Deleted products for seller ID $seller_id");
    }
    
    // Delete review responses (if table exists)
    if (tableExists($db, 'review_responses')) {
        $query = "DELETE FROM review_responses WHERE seller_id = ?";
        error_log("Running query: " . $query);
        $stmt = $db->prepare($query);
        $stmt->execute([$seller_id]);
        error_log("Deleted review responses for seller ID $seller_id");
    }
    
    // Delete seller
    if (tableExists($db, 'sellers')) {
        $query = "DELETE FROM sellers WHERE id = ?";
        error_log("Running query: " . $query);
        $stmt = $db->prepare($query);
        $stmt->execute([$seller_id]);
        error_log("Deleted seller record for ID $seller_id");
    }
    
    // Set user to inactive
    if (tableExists($db, 'users')) {
        $query = "UPDATE users SET status = 'inactive' WHERE id = ?";
        error_log("Running query: " . $query);
        $stmt = $db->prepare($query);
        $stmt->execute([$seller_id]);
        error_log("Updated user status to inactive for ID $seller_id");
    }
    
    // Commit transaction
    $db->commit();
    
    error_log("Deletion successful for seller ID: " . $seller_id);
    Response::success('Seller account and related data deleted successfully');
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $error_message = $e->getMessage();
    error_log("Error deleting seller: " . $error_message);
    
    Response::error('Database error: ' . $error_message);
}
?> 