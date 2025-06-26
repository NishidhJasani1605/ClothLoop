<?php
// Set proper CORS headers
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = [
    'status' => 'debug',
    'message' => 'Database connection test',
    'data' => []
];

try {
    // Required files
    require_once __DIR__ . '/../../config/database.php';
    
    $response['data']['config_included'] = true;
    
    // Try database connection
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $response['data']['connection'] = 'successful';
        
        // Test if tables exist
        $tables = ['products', 'seller_reviews', 'customer_interests', 'users'];
        $tableResults = [];
        
        foreach ($tables as $table) {
            try {
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                $tableResults[$table] = $stmt->rowCount() > 0 ? 'exists' : 'missing';
            } catch (Exception $e) {
                $tableResults[$table] = 'error: ' . $e->getMessage();
            }
        }
        
        $response['data']['tables'] = $tableResults;
        
        // Try basic query
        try {
            $stmt = $conn->prepare("SELECT 1 as test");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['data']['basic_query'] = isset($result['test']) ? 'works' : 'failed';
        } catch (Exception $e) {
            $response['data']['basic_query'] = 'error: ' . $e->getMessage();
        }
        
    } catch (Exception $e) {
        $response['data']['connection'] = 'failed: ' . $e->getMessage();
    }
    
    // Success
    $response['status'] = 'success';
    
} catch (Exception $e) {
    // Handle errors
    $response['status'] = 'error';
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Send JSON response
echo json_encode($response, JSON_PRETTY_PRINT); 