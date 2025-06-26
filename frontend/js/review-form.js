// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Find the review form and send button
    const reviewForm = document.getElementById('reviewForm') || document.querySelector('form');
    const sendButton = document.getElementById('sendReview') || document.querySelector('button[type="submit"]');
    
    // Find the star rating elements
    const stars = document.querySelectorAll('.rating-stars input[type="radio"]') || 
                  document.querySelectorAll('.rating-stars input');
    
    // Current rating value
    let currentRating = 5; // Default to 5 stars
    
    // Add event listeners to stars if they exist
    if (stars.length > 0) {
        stars.forEach(star => {
            star.addEventListener('change', function() {
                currentRating = parseInt(this.value, 10);
                console.log('Rating selected:', currentRating);
                
                // If there's a hidden rating input, update it
                const hiddenRating = document.getElementById('ratingValue');
                if (hiddenRating) {
                    hiddenRating.value = currentRating;
                }
            });
        });
    }
    
    // Handle form submission
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(event) {
            // Prevent default form submission
            event.preventDefault();
            
            // Get the product ID from the form or page
            const productId = document.querySelector('input[name="product_id"]')?.value || 
                              window.productId || // Check for global variable
                              new URLSearchParams(window.location.search).get('id'); // Check URL
            
            // Get the review text
            const reviewText = document.querySelector('textarea[name="review"]')?.value || 
                              document.querySelector('textarea')?.value || '';
            
            // Validate inputs
            if (!productId) {
                alert('Product ID is missing');
                return;
            }
            
            if (!reviewText.trim()) {
                alert('Please write a review');
                return;
            }
            
            // Prepare the data
            const reviewData = {
                product_id: productId,
                rating: currentRating,
                review: reviewText
            };
            
            // Log the data being sent
            console.log('Submitting review:', reviewData);
            
            // Send the data to the server
            fetch('/backend/api/submit_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(reviewData)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Review submission response:', data);
                
                if (data.status === 'success') {
                    // Show success message
                    alert('Thank you for your review!');
                    
                    // Reset the form
                    reviewForm.reset();
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error submitting review:', error);
                alert('Failed to submit review. Please try again later.');
            });
        });
    } else {
        console.error('Review form not found on the page');
    }
}); 