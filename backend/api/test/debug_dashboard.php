<?php
/**
 * TEST ENDPOINT ONLY - NOT FOR PRODUCTION
 * Debug version of dashboard_stats.php that provides detailed error information
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';

// Enable maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Store all errors
$errors = [];
$warnings = [];

try {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session status
    $sessionStarted = session_status() == PHP_SESSION_ACTIVE;
    $errors[] = "Session active: " . ($sessionStarted ? "Yes" : "No");
    
    // Check if user is in session
    $userInSession = isset($_SESSION['user']);
    $errors[] = "User in session: " . ($userInSession ? "Yes" : "No");
    
    if ($userInSession) {
        $errors[] = "User data: " . json_encode($_SESSION['user']);
        $errors[] = "User role: " . $_SESSION['user']['role'];
    } else {
        // Create a test seller user if not in session
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'Test Seller',
            'email' => 'seller@clothloop.com',
            'role' => 'seller',
            'phone_no' => '1234567890',
            'status' => 'active'
        ];
        $_SESSION['last_activity'] = time();
        $errors[] = "Created test user session";
    }
    
    // Try to get the current user using Auth class
    $seller = Auth::getCurrentUser();
    $errors[] = "Auth::getCurrentUser(): " . ($seller ? json_encode($seller) : "NULL");
    
    // Now the connection
    try {
        $database = new Database();
        $db = $database->getConnection();
        $errors[] = "Database connection: Success";
    } catch (Exception $e) {
        $errors[] = "Database connection error: " . $e->getMessage();
    }
    
    // Check which tables exist
    try {
        $tables = ["users", "products", "seller_reviews", "customer_interests", "product_images", "categories"];
        foreach ($tables as $table) {
            $checkTable = $db->prepare("SHOW TABLES LIKE '$table'");
            $checkTable->execute();
            $exists = ($checkTable->rowCount() > 0);
            $errors[] = "Table '$table' exists: " . ($exists ? "Yes" : "No");
        }
    } catch (Exception $e) {
        $errors[] = "Error checking tables: " . $e->getMessage();
    }
    
    // Initialize dashboard data
    $dashboardData = [
        'seller_name' => $seller['name'],
        'total_products' => 0,
        'interested_customers' => 0,
        'average_rating' => 0,
        'interested_customers_list' => []
    ];
    
    // Respond with debug info
    echo json_encode([
        'status' => 'debug',
        'message' => 'Dashboard debug information',
        'data' => $dashboardData,
        'debug' => [
            'errors' => $errors,
            'warnings' => $warnings,
            'session_data' => $_SESSION,
            'server' => $_SERVER,
            'php_version' => phpversion()
        ]
    ]);
    
} catch (Exception $e) {
    // Provide detailed error information
    echo json_encode([
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'debug' => [
            'errors' => $errors,
            'warnings' => $warnings,
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} 