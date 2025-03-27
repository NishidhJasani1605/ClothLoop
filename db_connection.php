<?php
// Database connection parameters
$servername = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password
$dbname = "clothloop"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get seller information
function getSellerInfo() {
    global $conn;
    $sql = "SELECT username, email, phone FROM users WHERE user_type = 'seller' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Close connection (uncomment if this file is used independently)
// $conn->close();
?> 