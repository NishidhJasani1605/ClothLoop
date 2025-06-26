// Check if logged-in user is admin and redirect to admin page
document.addEventListener('DOMContentLoaded', function() {
    // Check session for user role
    fetch('/backend/api/users/check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success' && data.data.user && data.data.user.role === 'admin') {
                // User is admin, check if not already on admin page
                if (!window.location.pathname.includes('/admin/')) {
                    window.location.href = '/frontend/pages/admin/seller_monitoring.html';
                }
            }
        })
        .catch(error => {
            console.error('Error checking session:', error);
        });
}); 