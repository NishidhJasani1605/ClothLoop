<?php
/**
 * Login Handler
 * This script handles the login form submission and creates a session for authenticated users
 */

session_start();

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email and password from form
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Connect to database
    $conn = new mysqli("localhost", "root", "", "clothloop");
    
    if ($conn->connect_error) {
        $error_message = "Database connection error: " . $conn->connect_error;
    } else {
        // Check user credentials
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Simple password verify (in production use password_verify)
            // For demo, checking plain password against stored password
            if ($password === $user['password']) {
                // Store session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'] ?? 'buyer';
                $_SESSION['user_name'] = $user['name'] ?? 'User';
                $_SESSION['user_email'] = $user['email'];
                
                // Store user ID for JavaScript
                $user_id = $user['id'];
                
                // Redirect based on role
                if (isset($user['role']) && $user['role'] === 'seller') {
                    header('Location: ../seller/seller_dashboard.html');
                } else {
                    header('Location: ../buyer/buyer_dashboard.html');
                }
                exit;
            } else {
                $error_message = "Invalid password";
            }
        } else {
            // Check for demo credentials
            if ($email === 'seller@gmail.com' && $password === 'seller123') {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_role'] = 'seller';
                $_SESSION['user_name'] = 'Nishidh';
                $_SESSION['user_email'] = 'seller@gmail.com';
                $user_id = 1;
                header('Location: ../seller/seller_dashboard.html');
                exit;
            } else if ($email === 'buyer@gmail.com' && $password === 'buyer123') {
                $_SESSION['user_id'] = 3;
                $_SESSION['user_role'] = 'buyer';
                $_SESSION['user_name'] = 'Buyer';
                $_SESSION['user_email'] = 'buyer@gmail.com';
                $user_id = 3;
                header('Location: ../buyer/buyer_dashboard.html');
                exit;
            } else if ($email === 'buyer2@gmail.com' && $password === 'buyer123') {
                $_SESSION['user_id'] = 5;
                $_SESSION['user_role'] = 'buyer';
                $_SESSION['user_name'] = 'Buyer 2';
                $_SESSION['user_email'] = 'buyer2@gmail.com';
                $user_id = 5;
                header('Location: ../buyer/buyer_dashboard.html');
                exit;
            } else {
                $error_message = "Invalid email or password";
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ClothLoop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Theme Variables */
        :root {
            /* Dark Theme (Default) */
            --primary-color: #000000;
            --secondary-color: #333333;
            --accent-color: #ffffff;
            --text-color: #ffffff;
            --light-bg: #111111;
            --card-bg: #1a1a1a;
            --footer-bg: #000000;
            --footer-text: #cccccc;
            --border-radius: 0;
            --box-shadow: 0 5px 10px rgba(0, 0, 0, 0.8);
            --error-color: #FF3B30;
        }

        /* Light Theme Variables */
        .light-theme {
            --primary-color: #ffffff;
            --secondary-color: #000000;
            --accent-color: #000000;
            --text-color: #000000;
            --light-bg: #f5f5f5;
            --card-bg: #ffffff;
            --footer-bg: #f5f5f5;
            --footer-text: #000000;
            --border-radius: 0;
            --box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-color);
            background-color: var(--light-bg);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Theme Switcher */
        .theme-switcher {
            background: var(--primary-color);
            border: 2px solid var(--accent-color);
            color: var(--accent-color);
            width: 40px;
            height: 40px;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 20px;
            transition: all 0.2s linear;
        }
        
        .theme-switcher:hover {
            transform: skewX(-5deg);
            box-shadow: 3px 3px 0 rgba(128, 128, 128, 0.3);
        }

        /* Navigation */
        nav {
            background-color: var(--primary-color);
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(255, 255, 255, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        nav .logo {
            color: var(--accent-color);
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            letter-spacing: 3px;
            text-transform: uppercase;
            position: relative;
        }

        nav .logo i {
            margin-right: 10px;
        }

        nav ul {
            display: flex;
            list-style: none;
        }

        nav ul li {
            margin-left: 1.5rem;
        }

        nav ul li a {
            color: var(--accent-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0;
            transition: all 0.2s linear;
            font-weight: 500;
            border-bottom: 2px solid transparent;
        }

        nav ul li a:hover, nav ul li a.active {
            background-color: transparent;
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
        }

        /* Login Form */
        .login-container {
            max-width: 450px;
            margin: 120px auto 50px;
            padding: 40px;
            background-color: var(--card-bg);
            box-shadow: var(--box-shadow);
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--accent-color);
        }

        .login-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--accent-color);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--accent-color);
        }

        .form-input {
            width: 100%;
            height: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background-color: rgba(255, 255, 255, 0.05);
            padding: 0 15px;
            font-size: 16px;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 3px 3px 0 rgba(128, 128, 128, 0.1);
            transform: translateY(-2px) skewX(-2deg);
        }

        .btn {
            width: 100%;
            height: 50px;
            border: none;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            margin-top: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--accent-color);
            border: 2px solid var(--accent-color);
        }

        .btn-primary:hover {
            transform: translateY(-3px) skewX(-5deg);
            box-shadow: 5px 5px 0 rgba(128, 128, 128, 0.2);
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 14px;
        }

        .links a {
            color: var(--accent-color);
            text-decoration: none;
            transition: all 0.2s ease;
            opacity: 0.8;
        }

        .links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        .error-message {
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            background-color: var(--error-color);
            color: white;
            border-left: 5px solid #d62d22;
            animation: fadeIn 0.5s ease-out;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                width: 90%;
                padding: 30px 20px;
            }
            
            nav {
                flex-direction: column;
                padding: 1rem;
            }
            
            nav ul {
                margin-top: 1rem;
            }
            
            nav ul li {
                margin-left: 0.5rem;
                margin-right: 0.5rem;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Footer */
        .footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 1rem 0 1rem;
            clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
            padding-top: 1rem !important;
            margin-top: auto;
            width: 100%;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem 0;
            border-top: none;
            text-align: center;
            font-size: 0.9rem;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--accent-color);
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <a href="../../index.html" class="logo">
            <img src="../../assets/images/logo_f.png" alt="ClothLoop Logo" style="height: 40px; margin-right: 10px;"> ClothLoop
        </a>
        <div style="display: flex; align-items: center;">
            <ul>
                <li><a href="../../index.html">Home</a></li>
                <li><a href="../about/about_us.html">About</a></li>
                <li><a href="login.php" class="active">Login</a></li>
                <li><a href="signup.html">Sign Up</a></li>
            </ul>
            <button class="theme-switcher" id="theme-toggle" aria-label="Toggle theme">
                <i class="fas fa-sun"></i>
            </button>
        </div>
    </nav>

    <!-- Login Container -->
    <div class="login-container">
        <h1 class="login-title">Login</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message show"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Login</button>
            
            <div class="links">
                <a href="forgot_password.html">Forgot Password?</a>
                <a href="signup.html">Create Account</a>
            </div>
        </form>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-bottom">
            <div class="footer-logo">
                <img src="../../assets/images/logo_f.png" alt="ClothLoop Logo" style="height: 40px; margin-right: 10px;"> ClothLoop
            </div>
            <p>&copy; 2024 ClothLoop. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Theme switcher functionality
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle.querySelector('i');
            
            // Check for saved theme preference
            const savedTheme = localStorage.getItem('clothloop-theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
            
            // Toggle theme
            themeToggle.addEventListener('click', () => {
                document.body.classList.toggle('light-theme');
                
                // Update icon
                if (document.body.classList.contains('light-theme')) {
                    themeIcon.classList.remove('fa-sun');
                    themeIcon.classList.add('fa-moon');
                    localStorage.setItem('clothloop-theme', 'light');
                } else {
                    themeIcon.classList.remove('fa-moon');
                    themeIcon.classList.add('fa-sun');
                    localStorage.setItem('clothloop-theme', 'dark');
                }
            });

            // Handle form submission - Add wishlist persistence feature
            document.querySelector('form').addEventListener('submit', function(e) {
                // Save the current anonymous/guest wishlist before login
                try {
                    const tempWishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                    
                    // Store the email for later user ID lookup
                    const emailInput = document.getElementById('email');
                    const email = emailInput.value.trim();
                    
                    if (email) {
                        localStorage.setItem('login_email', email);
                        
                        // If email matches a known user, pre-store their user ID
                        if (email === 'buyer@gmail.com') {
                            localStorage.setItem('pending_user_id', '3');
                            // Store the temp wishlist for this user if there are items
                            if (tempWishlist.length > 0) {
                                localStorage.setItem('wishlist_3', JSON.stringify(tempWishlist));
                            }
                        } else if (email === 'buyer2@gmail.com') {
                            localStorage.setItem('pending_user_id', '5');
                            // Store the temp wishlist for this user if there are items
                            if (tempWishlist.length > 0) {
                                localStorage.setItem('wishlist_5', JSON.stringify(tempWishlist));
                            }
                        } else if (email === 'seller@gmail.com') {
                            localStorage.setItem('pending_user_id', '1');
                        }
                    }
                } catch (e) {
                    console.error('Error saving wishlist data before login:', e);
                }
            });
            
            <?php if (isset($user_id)): ?>
            // Store the user ID in localStorage for JS to use
            localStorage.setItem('user_id', '<?php echo $user_id; ?>');
            
            // Handle wishlist data persistence when logging in
            try {
                const userId = '<?php echo $user_id; ?>';
                const wishlistKey = `wishlist_${userId}`;
                
                // Check if we have a temporary wishlist saved for this login
                const pendingUserId = localStorage.getItem('pending_user_id');
                if (pendingUserId === userId) {
                    // Clear the pending user ID as we've handled it
                    localStorage.removeItem('pending_user_id');
                }
                
                // Always check if there's an old wishlist to merge
                const oldWishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
                const userWishlist = JSON.parse(localStorage.getItem(wishlistKey) || '[]');
                
                if (oldWishlist.length > 0 || userWishlist.length > 0) {
                    // Merge old and user wishlists without duplicates
                    const mergedWishlist = [...new Set([...userWishlist, ...oldWishlist])];
                    
                    // Save the merged wishlist to this user's key
                    localStorage.setItem(wishlistKey, JSON.stringify(mergedWishlist));
                    console.log(`Merged ${oldWishlist.length} anonymous items with ${userWishlist.length} user items`);
                }
            } catch (e) {
                console.error('Error merging wishlist data on login:', e);
            }
            <?php endif; ?>
        });
    </script>
</body>
</html> 