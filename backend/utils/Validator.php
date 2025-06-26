<?php
/**
 * Validator class
 * Provides validation methods for user input
 */
class Validator {
    /**
     * Validate if the given ID is valid
     * 
     * @param mixed $id The ID to validate
     * @return bool True if valid, false otherwise
     */
    public function validateId($id) {
        // Check if ID is numeric and positive
        return is_numeric($id) && $id > 0;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email The email to validate
     * @return bool True if valid, false otherwise
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $phone The phone number to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePhone($phone) {
        // Simple validation to check if phone number has at least 10 digits
        return preg_match('/^\+?[0-9]{10,15}$/', $phone) === 1;
    }
    
    /**
     * Validate password strength
     * 
     * @param string $password The password to validate
     * @return bool True if valid, false otherwise
     */
    public function validatePassword($password) {
        // Password must be at least 8 characters long
        return strlen($password) >= 8;
    }
    
    /**
     * Validate coordinates
     * 
     * @param float $latitude Latitude to validate
     * @param float $longitude Longitude to validate
     * @return bool True if valid, false otherwise
     */
    public function validateCoordinates($latitude, $longitude) {
        // Check if latitude is between -90 and 90
        $validLat = is_numeric($latitude) && $latitude >= -90 && $latitude <= 90;
        
        // Check if longitude is between -180 and 180
        $validLng = is_numeric($longitude) && $longitude >= -180 && $longitude <= 180;
        
        return $validLat && $validLng;
    }
    
    /**
     * Sanitize input to prevent XSS attacks
     * 
     * @param string $input The input to sanitize
     * @return string Sanitized input
     */
    public function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}
?> 