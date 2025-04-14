<?php
// Set headers and start session
header('Content-Type: application/json');
// Prevent browser from showing default error dialogs
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Start session
session_start();

// Disable error displaying (we'll handle errors ourselves)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to browser

// Set error handler to catch any PHP errors and return them as JSON
function errorHandler($errno, $errstr, $errfile, $errline) {
    $error = "PHP Error [$errno]: $errstr in $errfile on line $errline";
    error_log($error);
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred. Please try again later.',
        'debug' => $error
    ]);
    exit(1);
}
set_error_handler('errorHandler');

try {
    // Include database configuration
    require_once __DIR__ . '/config/db_connect.php';
    
    // Get and validate POST data
    $raw_data = file_get_contents('php://input');
    if (empty($raw_data)) {
        throw new Exception("No data received");
    }
    
    $data = json_decode($raw_data, true);
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON data: " . json_last_error_msg());
    }
    
    // Check if required fields are present
    if (!isset($data['username']) || !isset($data['password'])) {
        throw new Exception("Username and password are required");
    }
    
    $username = $data['username'];
    $password = $data['password'];
    
    // Log login attempt (for debugging)
    error_log("Login attempt for username: " . $username);
    
    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        error_log("Database connection error in login.php");
        throw new Exception("Database connection failed. Please try again later.");
    }
    
    // Prepare SQL statement with parameterized query
    // Try to find user by username first
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception("Database query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // If username not found, try with email 
    if ($result->num_rows == 0) {
        $stmt->close();
        
        // Try email as username
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
    }
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // First try with password_verify (for hashed passwords)
        $password_correct = password_verify($password, $user['password']);
        
        // If that fails, try direct comparison (for plain text passwords - not recommended)
        if (!$password_correct && $password === $user['password']) {
            $password_correct = true;
            // Log that password is stored in plain text (security issue)
            error_log("WARNING: User {$username} has plain text password stored!");
        }
        
        if ($password_correct) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $user['email'];
            
            // Return success response with user type
            echo json_encode([
                'success' => true,
                'user_type' => $user['user_type'],
                'message' => 'Login successful'
            ]);
        } else {
            error_log("Password verification failed for user: {$username}");
            throw new Exception("Invalid password. Please try again.");
        }
    } else {
        error_log("User not found: {$username}");
        throw new Exception("User not found. Please check your email or register a new account.");
    }
    
    // Close resources
    if (isset($stmt)) {
        $stmt->close();
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Login error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    // Close connection if it exists
    if (isset($conn)) {
        $conn->close();
    }
}
?> 