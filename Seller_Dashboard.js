// analysis.js

// Chart Initialization
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    initializeComments();
    initializeNavigation();
});

function initializeCharts() {
    // Only initialize charts if the analysis section is visible
    if (!document.getElementById('analysis-section').classList.contains('active')) {
        return;
    }
    
    // Get the context of the canvas element
    var productSalesChartCtx = document.getElementById('product-sales-chart').getContext('2d');
    var revenueChartCtx = document.getElementById('revenue-chart').getContext('2d');
    var customerEngagementChartCtx = document.getElementById('customer-engagement-chart').getContext('2d');

    // Create the charts
    var productSalesChart = new Chart(productSalesChartCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            datasets: [{
                label: 'Product Likes',
                data: [10, 20, 15, 30, 25],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

    var revenueChart = new Chart(revenueChartCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
            datasets: [{
                label: 'Views',
                data: [1000, 2000, 1500, 3000, 2500],
                backgroundColor: [
                    'rgba(255, 99 , 132, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });

    var customerEngagementChart = new Chart(customerEngagementChartCtx, {
        type: 'pie',
        data: {
            labels: ['Facebook', 'Instagram', 'Twitter', 'Email'],
            datasets: [{
                label: 'Customer Engagement',
                data: [40, 30, 20, 10],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
}

// Comment System Functions
function initializeComments() {
    // Add event listeners for comment buttons if needed
}

//  comment js

function toggleReplyForm(button) {
    // Close all other open forms first
    const allForms = document.querySelectorAll('.reply-form.active');
    allForms.forEach(form => {
      if (form !== button.nextElementSibling) {
        form.classList.remove('active');
      }
    });
    
    const form = button.nextElementSibling;
    form.classList.toggle('active');
    
    if (form.classList.contains('active')) {
      form.querySelector('textarea').focus();
    }
  }
  
  function submitReply(button) {
    const form = button.parentElement;
    const textarea = form.querySelector('textarea');
    const content = textarea.value.trim();
    
    if (!content) {
      alert('Please enter a reply');
      return;
    }
    
    const repliesContainer = form.nextElementSibling;
    const replyElement = document.createElement('div');
    replyElement.className = 'reply';
    
    // Get current user info (you should replace this with actual user data)
    const currentUser = {
      name: 'Current User',
      avatar: 'user_avatar.jpg'
    };
    
    replyElement.innerHTML = `
      <i class="fas fa-times delete-reply" onclick="deleteReply(this)"></i>
      <h5>${currentUser.name}</h5>
      <p>${content}</p>
    `;
    
    repliesContainer.appendChild(replyElement);
    textarea.value = '';
    form.classList.remove('active');
  }
  
  function deleteReply(deleteButton) {
    const reply = deleteButton.parentElement;
    reply.style.opacity = '0';
    reply.style.transform = 'translateX(20px)';
    
    setTimeout(() => {
      reply.remove();
    }, 300);
  }

function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(l => l.classList.remove('active'));
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const sectionId = `${this.dataset.section}-section`;
            const section = document.getElementById(sectionId);
            if (section) {
                section.classList.add('active');
                
                // Reinitialize charts if showing analysis section
                if (sectionId === 'analysis-section') {
                    initializeCharts();
                }
            }
        });
    });
    // Add this to your existing JavaScript file
    document.querySelector('.user-profile').addEventListener('click', function(e) {
      this.classList.toggle('active');
      e.stopPropagation();
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
      document.querySelector('.user-profile').classList.remove('active');
    });
}

function handleLogout() {
    window.location.href = 'login.html';
}

// Function to save settings
async function saveSettings() {
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const bio = document.getElementById('bio').value;
    
    // Basic validation
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    try {
        const response = await fetch('update_settings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email,
                phone,
                password,
                bio
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Settings updated successfully!');
        } else {
            alert('Error updating settings: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating settings');
    }
}

// Handle profile picture upload
document.getElementById('profile-picture').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('profile_picture', file);
    
    try {
        const response = await fetch('upload_profile_picture.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Profile picture updated successfully!');
        } else {
            alert('Error updating profile picture: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while uploading profile picture');
    }
});

  