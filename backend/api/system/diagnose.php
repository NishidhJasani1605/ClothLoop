<?php
/**
 * System Diagnostic API
 * Provides diagnostic information about the system
 */

// Required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection file
include_once '../../config/database.php';

// Response utility function
function sendResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Set appropriate HTTP status code
    if ($status !== 'success') {
        http_response_code(500);
    } else {
        http_response_code(200);
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Check database connection
    $database = new Database();
    $conn = $database->connect();
    
    // Array to store diagnostic results
    $diagnostics = [
        'database_connection' => true,
        'tables' => [],
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'permissions' => []
    ];
    
    // Check tables
    $requiredTables = ['users', 'sellers', 'products', 'categories', 'transactions', 'reviews'];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $conn->prepare("SHOW TABLES LIKE :table");
            $stmt->bindParam(':table', $table);
            $stmt->execute();
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                // Count rows in the table
                $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
                $countStmt->execute();
                $row = $countStmt->fetch(PDO::FETCH_ASSOC);
                $count = $row['count'];
                
                $diagnostics['tables'][$table] = [
                    'exists' => true,
                    'row_count' => $count
                ];
            } else {
                $diagnostics['tables'][$table] = [
                    'exists' => false,
                    'row_count' => 0
                ];
            }
        } catch (PDOException $e) {
            $diagnostics['tables'][$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Check write permissions on uploads directory
    $uploadsPath = '../../../backend/uploads';
    $diagnostics['permissions']['uploads_writable'] = is_writable($uploadsPath);
    
    // Check session
    $diagnostics['session'] = [
        'active' => session_status() === PHP_SESSION_ACTIVE,
        'id' => session_id() ?: 'No active session'
    ];
    
    // Overall system status
    $allTablesExist = true;
    foreach ($diagnostics['tables'] as $table => $info) {
        if (!$info['exists']) {
            $allTablesExist = false;
            break;
        }
    }
    
    if (!$diagnostics['database_connection']) {
        sendResponse('error', 'Database connection failed', $diagnostics);
    } else if (!$allTablesExist) {
        sendResponse('warning', 'Some required tables are missing', $diagnostics);
    } else if (!$diagnostics['permissions']['uploads_writable']) {
        sendResponse('warning', 'Uploads directory is not writable', $diagnostics);
    } else {
        sendResponse('success', 'System diagnostic completed with no issues', $diagnostics);
    }
    
} catch (Exception $e) {
    sendResponse('error', 'Diagnostic failed: ' . $e->getMessage());
} 