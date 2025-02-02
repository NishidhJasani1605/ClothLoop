<?php

// Include database configuration
require_once 'config/db_connect.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Check if user is accessing the correct page type
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_page = ($_SESSION['user_type'] === 'buyer' && $current_page === 'Buyer_Dashboard.html') ||
                ($_SESSION['user_type'] === 'seller' && $current_page === 'seller_home.html');

if (!$allowed_page) {
    if ($_SESSION['user_type'] === 'buyer') {
        header('Location: Buyer_Dashboard.html');
    } 
    if ($_SESSION['user_type'] === 'seller') {
        header('Location: seller_home.html');
    }
    exit();
}
?> 