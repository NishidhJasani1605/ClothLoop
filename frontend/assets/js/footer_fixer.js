/**
 * ClothLoop Footer Logo Fix
 * This script ensures the footer logo changes color based on the current theme.
 * In dark mode, it uses logo_f.png (white logo)
 * In light mode, it uses logo_b.png (black logo)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Function to update the footer logo based on theme
    function updateFooterLogo() {
        // Find all footer logo images (could be in multiple places)
        const footerLogoImages = document.querySelectorAll('.footer-logo img, #footer-logo');
        
        // Get current theme
        const isLightTheme = document.body.classList.contains('light-theme');
        
        // Update each footer logo image
        footerLogoImages.forEach(img => {
            const currentSrc = img.getAttribute('src');
            
            // Skip if not a logo image
            if (!currentSrc || (!currentSrc.includes('logo_b.png') && !currentSrc.includes('logo_f.png'))) {
                return;
            }
            
            // Create the appropriate path based on the current path
            const basePath = currentSrc.substring(0, currentSrc.lastIndexOf('/') + 1);
            const newLogoName = isLightTheme ? 'logo_b.png' : 'logo_f.png';
            const newSrc = basePath + newLogoName;
            
            // Only update if different
            if (currentSrc !== newSrc) {
                img.setAttribute('src', newSrc);
                console.log(`Footer logo updated to: ${newSrc}`);
            }
        });
    }
    
    // Run initially
    updateFooterLogo();
    
    // Watch for theme changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class' && mutation.target === document.body) {
                updateFooterLogo();
            }
        });
    });
    
    // Start observing body for class changes
    observer.observe(document.body, { attributes: true });
    
    // Also add listener to any theme toggle buttons
    const themeToggles = document.querySelectorAll('.theme-switcher, #theme-toggle');
    themeToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            // The actual class change will be caught by the observer,
            // but we'll also call updateFooterLogo directly for immediate effect
            setTimeout(updateFooterLogo, 50);
        });
    });
}); 