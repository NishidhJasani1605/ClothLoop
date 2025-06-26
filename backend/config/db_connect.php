<?php
// Database connection configuration
$host = "localhost";  // Usually localhost for local development
$username = "root";   // Default MySQL username
$password = "";      // Default password is empty for XAMPP
$database = "clothloop";  // Your database name

// Create database connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    // Log error and return a friendly message
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}

// Set charset to ensure proper handling of special characters
mysqli_set_charset($conn, "utf8mb4");
?> 