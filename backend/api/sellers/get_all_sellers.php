<?php
/**
 * Get All Sellers API
 * Returns all sellers with their ratings and reviews
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once '../../config/database.php';
require_once '../../models/Seller.php';
require_once '../../models/User.php';
require_once '../../models/Review.php';
require_once '../../utils/response.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    Response::error('Unauthorized access', 403);
    exit;
}

try {
    // Instantiate DB & connect
    $database = new Database();
    $db = $database->getConnection();

    // Instantiate seller object
    $seller = new Seller($db);
    $user = new User($db);
    $review = new Review($db);

    // Get sorting parameter
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'avg_rating';
    $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

    // Validate sort parameters
    $allowed_sort_by = ['avg_rating', 'total_reviews', 'name', 'created_at'];
    $allowed_sort_order = ['ASC', 'DESC'];

    if (!in_array($sort_by, $allowed_sort_by)) {
        $sort_by = 'avg_rating';
    }

    if (!in_array(strtoupper($sort_order), $allowed_sort_order)) {
        $sort_order = 'ASC';
    }

    // Get sellers with their user info, ordered by average rating
    try {
        $sellers = $seller->getAllWithUserInfo($sort_by, $sort_order);
        
        if ($sellers) {
            // For each seller, get their reviews
            foreach ($sellers as &$sellerData) {
                // Get rating summary
                $ratingSummary = $review->getSellerRatingSummary($sellerData['id']);
                $sellerData['rating_summary'] = $ratingSummary;
                
                // Get latest 5 reviews
                $latestReviews = $review->getSellerReviews($sellerData['id'], 5, 0);
                $sellerData['latest_reviews'] = $latestReviews;
            }
            
            Response::success('Sellers retrieved successfully', [
                'sellers' => $sellers
            ]);
        } else {
            Response::success('No sellers found', [
                'sellers' => []
            ]);
        }
    } catch (PDOException $e) {
        // Log the SQL error
        error_log('SQL Error in get_all_sellers.php: ' . $e->getMessage());
        Response::error('Database error: ' . $e->getMessage(), 500);
    }
} catch (Exception $e) {
    // Log the general error
    error_log('General Error in get_all_sellers.php: ' . $e->getMessage());
    Response::error('Server error: ' . $e->getMessage(), 500);
} 