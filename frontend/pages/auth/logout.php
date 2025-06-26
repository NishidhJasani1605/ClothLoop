<?php
/**
 * Logout Script
 * Destroys the current user session and redirects to login page
 */

// Start the session
session_start();

// Get current user ID for JavaScript
$user_id = $_SESSION['user_id'] ?? null;

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// If we don't have a user_id to preserve wishlist, redirect immediately
if (!$user_id) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out | ClothLoop</title>
</head>
<body>
    <p>Logging out...</p>
    
    <script>
        // Preserve the user-specific wishlist data
        try {
            const userId = '<?php echo $user_id; ?>';
            const wishlistKey = `wishlist_${userId}`;
            
            // Get the user's wishlist
            const userWishlist = JSON.parse(localStorage.getItem(wishlistKey) || '[]');
            
            // If the user has a wishlist, save it to local storage under both the user-specific key and the general key
            if (userWishlist.length > 0) {
                // Store as generic wishlist for use when not logged in
                localStorage.setItem('wishlist', JSON.stringify(userWishlist));
                console.log(`Preserved ${userWishlist.length} wishlist items for user ID ${userId}`);
            }
            
            // Clear the user ID from localStorage so wishlist functions know user is logged out
            localStorage.removeItem('user_id');
            
        } catch (e) {
            console.error('Error preserving wishlist during logout:', e);
        }
        
        // Redirect to login page after preserving wishlist
        window.location.href = 'login.php';
    </script>
</body>
</html> 