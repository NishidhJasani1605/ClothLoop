<?php
/**
 * Categories List API
 * Returns all product categories
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all categories
    $query = "SELECT id, name, description FROM categories ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    Response::success('Categories retrieved successfully', $categories);
    
} catch (Exception $e) {
    // Log error
    error_log("Error retrieving categories: " . $e->getMessage());
    
    // Return error response
    Response::error('Failed to retrieve categories: ' . $e->getMessage());
} 