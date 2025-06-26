<?php
/**
 * Response Utility
 * Standardizes API responses for consistent format
 */

class Response {
    /**
     * Send a success response
     * 
     * @param string $message Success message
     * @param array $data Optional data to include
     * @param int $statusCode HTTP status code (default 200)
     * @return void
     */
    public static function success($message, $data = null, $statusCode = 200) {
        self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param array $errors Optional detailed errors
     * @param int $statusCode HTTP status code (default 400)
     * @return void
     */
    public static function error($message, $errors = null, $statusCode = 400) {
        self::send([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Send the actual response and exit
     * 
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @return void
     */
    private static function send($data, $statusCode) {
        // Set HTTP response code
        http_response_code($statusCode);
        
        // Set content type to JSON
        header('Content-Type: application/json');
        
        // Allow CORS for frontend development
        header('Access-Control-Allow-Origin: http://localhost');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
        header('Access-Control-Allow-Credentials: true');
        
        // Output JSON and exit
        echo json_encode($data);
        exit;
    }
} 