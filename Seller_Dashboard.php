<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db_connection.php';

// Get user information
$sql = "SELECT username, email, phone, user_type FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$userInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Redirect if not a seller
if ($userInfo['user_type'] !== 'seller') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Seller Dashboard</title>
  
  <!-- CSS Links -->
  <link rel="stylesheet" href="Seller_Dashboard.css"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
</head>
<body>
  <div class="container">
    <!-- Header -->
    <header class="main-header">
      <div class="header-left">
        <div class="logo">
          <img src="LOGO.webp" alt="APPLICATION logo" />
          <div class="brand-name">
          </div>
        </div>
      </div>
      <div class="header-right">
        <div class="account-info">
          <div class="notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
          </div>
          <div class="user-profile">
            <img src="Shop_logo.png" alt="User Shop logo" />
            <div class="user-details">
              <h4><?php echo htmlspecialchars($userInfo['username']); ?></h4>
              <p>Seller Account</p>
            </div>
            <i class="fas fa-chevron-down"></i>
            <div class="dropdown-menu">
              <a href="#"><i class="fas fa-user"></i> Profile</a>
              <a href="#" onclick="showSettings()"><i class="fas fa-cog"></i> Settings</a>
              <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Settings Section -->
    <div id="settings-section" class="main-content section hidden">
      <h1>Settings</h1>
      <div class="form-group">
        <label for="email">Email:</label>
        <input id="email" type="text" value="<?php echo htmlspecialchars($userInfo['email']); ?>"/>
      </div>
      <div class="form-group">
        <label for="phone">Phone Number:</label>
        <input id="phone" type="text" value="<?php echo htmlspecialchars($userInfo['phone']); ?>"/>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input id="password" type="password" value="********"/>
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirm Password:</label>
        <input id="confirm-password" type="password" value="********"/>
      </div>
      <div class="form-group">
        <label for="profile-picture">Profile Picture:</label>
        <input id="profile-picture" type="file"/>
      </div>
      <div class="form-group">
        <label for="bio">Bio:</label>
        <textarea id="bio">Tell us about yourself...</textarea>
      </div>
      <div class="form-group">
        <button type="button" onclick="saveSettings()">Save Changes</button>
      </div>
    </div>
  </div>
</body>
</html> 