        // Get the sorting button and dropdown
        const sortBtn = document.getElementById('sortBtn');
        const sortDropdown = document.getElementById('sortDropdown');

        // Toggle the dropdown when clicking the sort button
        sortBtn.addEventListener('click', function() {
            sortDropdown.classList.toggle('show');
        });

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.btn-sort')) {
                if (sortDropdown.classList.contains('show')) {
                    sortDropdown.classList.remove('show');
                }
            }
        };

                // Add event listeners to buttons
        document.addEventListener("DOMContentLoaded", function() {
            const buttons = document.querySelectorAll("button");
            buttons.forEach(function(button) {
                button.addEventListener("click", function() {
                    console.log("Button clicked!");
                });
            });
        });

          // List of image sources
    const images = ["/ClothLoop/frontend/Image/1.jpg", "/ClothLoop/frontend/Image/2.jpg", "/ClothLoop/frontend/Image/3.jpg", "/ClothLoop/frontend/Image/4.jpg", "/ClothLoop/frontend/Image/5.jpg"];
    let currentImageIndex = 0;
    let interval;

    // Function to update the displayed image
    function changeImage(index) {
        const adImage = document.getElementById("ad-image");
        adImage.src = images[index];
    }

    // Function to go to the next image
    function nextImage() {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        changeImage(currentImageIndex);
    }

    // Function to go to the previous image
    function prevImage() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        changeImage(currentImageIndex);
    }

    // Start automatic image switching every 5 seconds
    function startAutoSwitch() {
        interval = setInterval(nextImage, 5000);
    }

    // Reset the interval to delay the next automatic switch when a manual switch occurs
    function resetInterval() {
        clearInterval(interval);
        startAutoSwitch();
    }

    // Event listeners for the next and previous buttons
    document.getElementById("next-btn").addEventListener("click", () => {
        nextImage();
        resetInterval();
    });

    document.getElementById("prev-btn").addEventListener("click", () => {
        prevImage();
        resetInterval();
    });

    // Initialize the auto-switching functionality
    startAutoSwitch();

        document.addEventListener('DOMContentLoaded', function() {
            const sortBtn = document.getElementById('sortBtn');
            const sidePanel = document.getElementById('sidePanel');
            const overlay = document.getElementById('overlay');

            // Open panel
            sortBtn.addEventListener('click', function() {
                sidePanel.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            });

            // Close panel when clicking overlay
            overlay.addEventListener('click', function() {
                sidePanel.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });

            // Close panel when clicking a sort option
            const sortOptions = sidePanel.querySelectorAll('a');
            sortOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidePanel.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            });

            // Close panel on ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidePanel.classList.contains('active')) {
                    sidePanel.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const toggleSwitch = document.querySelector('#checkbox');
            const currentTheme = localStorage.getItem('theme');

            if (currentTheme) {
                document.documentElement.setAttribute('data-theme', currentTheme);
                if (currentTheme === 'dark') {
                    toggleSwitch.checked = true;
                }
            }

            function switchTheme(e) {
                if (e.target.checked) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                }
            }

            toggleSwitch.addEventListener('change', switchTheme);
        });

        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const scrollElements = document.querySelectorAll('.scroll-fade');
            
            const elementInView = (el) => {
                const elementTop = el.getBoundingClientRect().top;
                return elementTop <= window.innerHeight;
            };
            
            const displayScrollElement = (element) => {
                element.classList.add('visible');
            };
            
            const handleScrollAnimation = () => {
                scrollElements.forEach((el) => {
                    if (elementInView(el)) {
                        displayScrollElement(el);
                    }
                });
            };
            
            window.addEventListener('scroll', handleScrollAnimation);
            handleScrollAnimation();
        });

        // Check if there's a handleLogout function and update it
        function handleLogout() {
            // Clear any stored user data
            localStorage.clear();
            // Redirect to login page
            window.location.href = '/ClothLoop/Registration/login.html';
        }
