<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    // Redirect to login page with error parameter
    header('Location: ../../Registration/login.html?error=login_required');
    exit();
}

// Check if user is a seller
if ($_SESSION['user_type'] !== 'seller') {
    // Redirect to appropriate dashboard based on user type
    if ($_SESSION['user_type'] === 'buyer') {
        header('Location: ../frontend/Buyer/Buyer_Dashboard.html');
    } else {
        // If user type is unknown, redirect to login
        header('Location: ../../Registration/login.html');
    }
    exit();
}

// If we get here, the user is logged in as a seller
// You can also fetch additional user data if needed
$user_id = $_SESSION['user_id'];
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
?> 