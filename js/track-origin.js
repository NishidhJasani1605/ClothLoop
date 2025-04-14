/**
 * This script sets a session storage variable to track user origin (buyer or seller)
 * for proper navigation back from the About Us page.
 */

// Function to set the user origin for pages linking to About Us
function setUserOrigin(origin) {
    console.log("Setting user origin to:", origin);
    sessionStorage.setItem('userOrigin', origin);
}

// Add click event listeners to all About Us links in buyer pages
document.addEventListener('DOMContentLoaded', function() {
    console.log("Track-origin script loaded and running");
    
    // Check what page we're on for debugging
    const currentPath = window.location.pathname;
    console.log("Current page path:", currentPath);
    
    // For buyer pages
    const buyerAboutLinks = document.querySelectorAll('.buyer-page a[href*="about_us.html"]');
    console.log("Found " + buyerAboutLinks.length + " buyer about links");
    
    buyerAboutLinks.forEach(link => {
        link.addEventListener('click', function() {
            console.log("Buyer link clicked - setting origin to buyer");
            setUserOrigin('buyer');
        });
    });
    
    // For seller pages
    const sellerAboutLinks = document.querySelectorAll('.seller-page a[href*="about_us.html"]');
    console.log("Found " + sellerAboutLinks.length + " seller about links");
    
    sellerAboutLinks.forEach(link => {
        link.addEventListener('click', function() {
            console.log("Seller link clicked - setting origin to seller");
            setUserOrigin('seller');
        });
    });
    
    // For direct navigation links that need explicit origin setting
    const explicitBuyerLinks = document.querySelectorAll('.set-buyer-origin');
    console.log("Found " + explicitBuyerLinks.length + " explicit buyer links");
    
    explicitBuyerLinks.forEach(link => {
        link.addEventListener('click', function() {
            console.log("Explicit buyer link clicked");
            setUserOrigin('buyer');
        });
    });
    
    const explicitSellerLinks = document.querySelectorAll('.set-seller-origin');
    console.log("Found " + explicitSellerLinks.length + " explicit seller links");
    
    explicitSellerLinks.forEach(link => {
        link.addEventListener('click', function() {
            console.log("Explicit seller link clicked");
            setUserOrigin('seller');
        });
    });
    
    // Check if we already have a stored origin (for debug purposes)
    const currentOrigin = sessionStorage.getItem('userOrigin');
    console.log("Current stored origin:", currentOrigin || "none");
}); 