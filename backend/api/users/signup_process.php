<?php
/**
 * User Signup API
 * Handles user registration for buyers and sellers
 */

// Headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

// Required files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/validate.php';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', null, 405);
}

// Get posted data (form data)
$data = $_POST;

// Sanitize inputs
foreach ($data as $key => $value) {
    if (is_string($value)) {
        $data[$key] = htmlspecialchars(trim($value));
    }
}

// Validate inputs
Validate::reset();
Validate::required('name', $data['name'] ?? '');
Validate::required('email', $data['email'] ?? '');
Validate::email('email', $data['email'] ?? '');
Validate::required('password', $data['password'] ?? '');
Validate::minLength('password', $data['password'] ?? '', 6);
Validate::required('phone_no', $data['phone_no'] ?? '');
Validate::required('user_type', $data['user_type'] ?? '');

// User type specific validation
if (($data['user_type'] ?? '') === 'buyer') {
    Validate::required('buyer_latitude', $data['buyer_latitude'] ?? '');
    Validate::required('buyer_longitude', $data['buyer_longitude'] ?? '');
} elseif (($data['user_type'] ?? '') === 'seller') {
    Validate::required('shop_name', $data['shop_name'] ?? '');
    Validate::required('address', $data['address'] ?? '');
} else {
    Validate::addError('user_type', 'Invalid user type');
}

if (Validate::hasErrors()) {
    Response::error('Validation failed', Validate::getErrors());
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindParam(':email', $data['email']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        Response::error('Email already in use. Please use a different email or login.');
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Hash password
    $hashedPassword = Auth::hashPassword($data['password']);
    
    // Insert into users table first
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, phone_no, role, status) 
        VALUES (:name, :email, :password, :phone_no, :role, 'active')
    ");
    
    $role = ($data['user_type'] === 'buyer') ? 'buyer' : 'seller';
    
    $stmt->bindParam(':name', $data['name']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':phone_no', $data['phone_no']);
    $stmt->bindParam(':role', $role);
    
    $stmt->execute();
    
    // Get the last inserted ID
    $userId = $db->lastInsertId();
    
    // Now insert into role-specific table
    if ($data['user_type'] === 'buyer') {
        $stmt = $db->prepare("
            INSERT INTO buyers (id, latitude, longitude) 
            VALUES (:id, :latitude, :longitude)
        ");
        
        $stmt->bindParam(':id', $userId);
        $stmt->bindParam(':latitude', $data['buyer_latitude']);
        $stmt->bindParam(':longitude', $data['buyer_longitude']);
        
        $stmt->execute();
    } else { // seller
        $stmt = $db->prepare("
            INSERT INTO sellers (id, shop_name, address) 
            VALUES (:id, :shop_name, :address)
        ");
        
        $stmt->bindParam(':id', $userId);
        $stmt->bindParam(':shop_name', $data['shop_name']);
        $stmt->bindParam(':address', $data['address']);
        
        $stmt->execute();
    }
    
    // Profile photo handling if provided
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $filename = time() . '_' . basename($_FILES['profile_photo']['name']);
        $targetPath = __DIR__ . '/../../uploads/profile_photos/' . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0777, true);
        }
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
            // Update user with profile photo path
            $stmt = $db->prepare("UPDATE users SET profile_photo = :photo WHERE id = :id");
            $relativePhotoPath = 'uploads/profile_photos/' . $filename;
            $stmt->bindParam(':photo', $relativePhotoPath);
            $stmt->bindParam(':id', $userId);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Fetch user data to return
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Remove password from user data
    unset($user['password']);
    
    // Start session for the user
    Auth::startSession($user);
    
    Response::success('Account created successfully', [
        'user' => $user
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    Response::error('Registration failed: ' . $e->getMessage());
} 