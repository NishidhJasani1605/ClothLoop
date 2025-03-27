<?php
// Example PHP backend
header('Content-Type: application/json');
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: Seller_Dashboard.php');
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "clothloop");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'];
$password = $data['password'];

// Prepare SQL statement
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    // Verify password
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        echo json_encode([
            'success' => true,
            'user_type' => $user['user_type']
        ]);
    } else {
        echo json_encode(['error' => 'Invalid password']);
    }
} else {
    echo json_encode(['error' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="signup" href="signup.html">
    <link rel="stylesheet" href="login.css">
    <title>Login - ClothLoop</title>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form id="login-form">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br><br>
            <div class="button-container">
                <input type="submit" value="Login">
            </div>
        </form>
        
        <div class="separator">
            <div class="line"></div>
            <p>or</p>
            <div class="line"></div>
        </div>
        
        <button type="button" class="google-btn" id="googleSignIn">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" alt="Google logo">
            Continue with Google
        </button>
        <div style="text-align: center;">
            <p>Don't have an account? <a href="signup.html">Sign up</a></p>
        </div>
    </div>
    
    <script>
    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('process_login.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred during login');
        }
    });
    </script>
</body>
</html> 