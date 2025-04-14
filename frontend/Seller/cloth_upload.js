// Image upload preview
function previewImages() {
    const files = document.getElementById('images').files;
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    for (let i = 0; i < files.length && i < 5; i++) { // Limit to 5 images
        const img = document.createElement('img');
        img.src = URL.createObjectURL(files[i]);
        preview.appendChild(img);
    }
}

// Initialize when document is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for the "Use Current Location" button
    document.getElementById('useCurrentLocationBtn').addEventListener('click', getCurrentLocation);
    
    // Add event listener for form submission
    document.getElementById('clothForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Show loading state
        const submitButton = document.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        submitButton.textContent = 'Submitting...';
        submitButton.disabled = true;
        
        // Create a FormData object
        const formData = new FormData();
        
        // Add text data
        formData.append('title', document.getElementById('title').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('size', document.getElementById('size').value);
        formData.append('color', document.getElementById('color').value);
        formData.append('price', document.getElementById('price').value);
        formData.append('contact', document.getElementById('contact').value);
        formData.append('whatsapp', document.getElementById('whatsapp').value);
        formData.append('shopName', document.getElementById('shopName').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('terms', document.getElementById('terms').value);
        formData.append('location', document.getElementById('location').value);
        
        // Add image files
        const imageFiles = document.getElementById('images').files;
        for (let i = 0; i < imageFiles.length; i++) {
            formData.append('images[]', imageFiles[i]);
        }
        
        // Submit the form data
        fetch('/ClothLoop/backend/handlers/cloth_upload_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Reset the button
            submitButton.textContent = originalButtonText;
            submitButton.disabled = false;
            
            if (data.success) {
                alert('Cloth item uploaded successfully!');
                // Redirect to seller dashboard
                window.location.href = 'seller_home.html';
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            // Reset the button
            submitButton.textContent = originalButtonText;
            submitButton.disabled = false;
            
            console.error('Error:', error);
            alert('An error occurred while uploading. Please try again.');
        });
    });
    
    // Make the upload area also trigger file browse dialog
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.addEventListener('click', function() {
            document.getElementById('images').click();
        });
        
        // Add drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            document.getElementById('images').files = e.dataTransfer.files;
            previewImages();
        });
    }
});

// Geolocation and reverse geocoding
function getCurrentLocation() {
    if (navigator.geolocation) {
        const locationBtn = document.getElementById('useCurrentLocationBtn');
        const originalText = locationBtn.textContent;
        locationBtn.textContent = 'Getting location...';
        locationBtn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                // Store coordinates in the hidden field
                document.getElementById('location').value = `${latitude}, ${longitude}`;
                
                // Get human-readable address using reverse geocoding
                reverseGeocode(latitude, longitude);
                
                // Reset button
                locationBtn.textContent = originalText;
                locationBtn.disabled = false;
            },
            (error) => {
                alert("Unable to retrieve your location: " + error.message);
                console.error("Geolocation error:", error);
                
                // Reset button
                locationBtn.textContent = originalText;
                locationBtn.disabled = false;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert("Geolocation is not supported by your browser.");
    }
}

async function reverseGeocode(latitude, longitude) {
    const apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // Replace with your API key
    const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latitude},${longitude}&key=${apiKey}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === "OK" && data.results.length > 0) {
            const address = data.results[0].formatted_address;
            
            // Update the address field with the geocoded address
            document.getElementById('address').value = address;
            
            // Add visual feedback
            const addressField = document.getElementById('address');
            addressField.style.backgroundColor = '#e8f7f3';
            setTimeout(() => {
                addressField.style.backgroundColor = '';
            }, 2000);
        } else {
            console.warn("Unable to find a human-readable address for the given location.");
        }
    } catch (error) {
        console.error("Error while fetching address:", error);
    }
}