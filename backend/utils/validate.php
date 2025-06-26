<?php
/**
 * Validation Utility
 * Handles input validation and sanitization
 */

class Validate {
    private static $errors = [];
    
    /**
     * Reset errors array
     * 
     * @return void
     */
    public static function reset() {
        self::$errors = [];
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Array of validation errors
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Check if validation has any errors
     * 
     * @return bool True if there are errors
     */
    public static function hasErrors() {
        return count(self::$errors) > 0;
    }
    
    /**
     * Add an error to the errors array
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    public static function addError($field, $message) {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        
        self::$errors[$field][] = $message;
    }
    
    /**
     * Validate that a field is not empty
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function required($field, $value, $message = null) {
        $valid = !empty(trim($value));
        
        if (!$valid) {
            self::addError($field, $message ?: "$field is required");
        }
        
        return $valid;
    }
    
    /**
     * Validate email format
     * 
     * @param string $field Field name
     * @param string $value Email address
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function email($field, $value, $message = null) {
        $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        
        if (!$valid) {
            self::addError($field, $message ?: "$field must be a valid email address");
        }
        
        return $valid;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param int $length Minimum length
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function minLength($field, $value, $length, $message = null) {
        $valid = strlen($value) >= $length;
        
        if (!$valid) {
            self::addError($field, $message ?: "$field must be at least $length characters");
        }
        
        return $valid;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param int $length Maximum length
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function maxLength($field, $value, $length, $message = null) {
        $valid = strlen($value) <= $length;
        
        if (!$valid) {
            self::addError($field, $message ?: "$field must not exceed $length characters");
        }
        
        return $valid;
    }
    
    /**
     * Validate number range
     * 
     * @param string $field Field name
     * @param numeric $value Field value
     * @param numeric $min Minimum value
     * @param numeric $max Maximum value
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function range($field, $value, $min, $max, $message = null) {
        $valid = is_numeric($value) && $value >= $min && $value <= $max;
        
        if (!$valid) {
            self::addError($field, $message ?: "$field must be between $min and $max");
        }
        
        return $valid;
    }
    
    /**
     * Validate that a value is numeric
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function numeric($field, $value, $message = null) {
        $valid = is_numeric($value);
        
        if (!$valid) {
            self::addError($field, $message ?: "$field must be a number");
        }
        
        return $valid;
    }
    
    /**
     * Validate a value against a pattern
     * 
     * @param string $field Field name
     * @param string $value Field value
     * @param string $pattern Regular expression pattern
     * @param string $message Custom error message (optional)
     * @return bool True if valid
     */
    public static function pattern($field, $value, $pattern, $message = null) {
        $valid = preg_match($pattern, $value);
        
        if (!$valid) {
            self::addError($field, $message ?: "$field has an invalid format");
        }
        
        return $valid;
    }
    
    /**
     * Sanitize input to prevent XSS
     * 
     * @param mixed $input Input to sanitize
     * @return mixed Sanitized input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
        } else {
            // Convert special characters to HTML entities
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        
        return $input;
    }
} 