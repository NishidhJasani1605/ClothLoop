<?php
/**
 * Authentication Utility
 * Handles user authentication, sessions, and authorization
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/response.php';

class Auth {
    private static $currentUser = null;
    
    /**
     * Start a user session
     * 
     * @param array $user User data to store in session
     * @return void
     */
    public static function startSession($user) {
        // Remove password from user data
        if (isset($user['password'])) {
            unset($user['password']);
        }
        
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store user data and last activity time
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        
        // Set session cookie parameters for better security
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is logged in and session is valid
     * 
     * @return bool True if session is valid, false otherwise
     */
    public static function checkSession() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user session exists
        if (!isset($_SESSION['user'])) {
            return false;
        }
        
        // Check if session has expired
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_DURATION)) {
            self::endSession();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        // Store user data for convenience
        self::$currentUser = $_SESSION['user'];
        
        return true;
    }
    
    /**
     * Get the currently logged in user
     * 
     * @return array|null User data or null if not logged in
     */
    public static function getCurrentUser() {
        if (self::$currentUser === null) {
            if (!self::checkSession()) {
                return null;
            }
        }
        
        return self::$currentUser;
    }
    
    /**
     * End the current session (logout)
     * 
     * @return void
     */
    public static function endSession() {
        // Start session if not already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = [];
        
        // If a session cookie was set, destroy it
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Reset current user
        self::$currentUser = null;
    }
    
    /**
     * Require authentication for an endpoint
     * Will automatically send error response if not authenticated
     * 
     * @return void
     */
    public static function requireAuth() {
        if (!self::checkSession()) {
            Response::error('Authentication required', null, 401);
        }
    }
    
    /**
     * Require specific role(s) for an endpoint
     * Will automatically send error response if not authorized
     * 
     * @param string|array $allowedRoles Role or roles that can access this endpoint
     * @return void
     */
    public static function requireRole($allowedRoles) {
        self::requireAuth();
        
        $user = self::getCurrentUser();
        
        // Convert single role to array for consistent checking
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        // Check if user has any of the allowed roles
        if (!in_array($user['role'], $allowedRoles)) {
            Response::error('You do not have permission to access this resource', null, 403);
        }
    }
    
    /**
     * Hash a password for secure storage
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
} 