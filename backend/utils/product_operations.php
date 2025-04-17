<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection from config
require_once __DIR__ . '/../config/db_connect.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Get the request method
$request_method = $_SERVER['REQUEST_METHOD'];

// Get the operation type
$operation = isset($_GET['operation']) ? $_GET['operation'] : '';

// Handle different operations based on request method
switch ($request_method) {
    case 'GET':
        // Fetch products
        if ($operation === 'fetch') {
            fetchProducts();
        } elseif ($operation === 'fetch_all') {
            fetchAllProducts();
        }
        break;
    
    case 'POST':
        // Add/update product
        if ($operation === 'add') {
            addProduct();
        } elseif ($operation === 'update') {
            updateProduct();
        } elseif ($operation === 'delete') {
            deleteProduct();
        }
        break;
    
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        break;
}

// Function to fetch all products
function fetchProducts() {
    global $conn;
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get seller ID from session or from a URL parameter for debugging
    $seller_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Debug: If we don't have a session ID, check for URL parameter
    if (!$seller_id && isset($_GET['seller_id'])) {
        $seller_id = $_GET['seller_id'];
    }
    
    // If still no seller ID, check local storage via GET parameter
    if (!$seller_id && isset($_GET['local_storage_id'])) {
        $seller_id = $_GET['local_storage_id'];
    }
    
    // If no seller ID available, return an error
    if (!$seller_id) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'No seller ID found in session or parameters',
            'debug' => [
                'session_status' => session_status(),
                'session_data' => $_SESSION ?? 'No session'
            ]
        ]);
        return;
    }
    
    try {
        $sql = "SELECT * FROM cloth_details WHERE seller_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Get the image
            $image_data = null;
            
            // Check if we have an image record
            $img_sql = "SELECT image_data, image_type FROM cloth_images WHERE cloth_id = ? LIMIT 1";
            $img_stmt = $conn->prepare($img_sql);
            $img_stmt->bind_param("i", $row['id']);
            $img_stmt->execute();
            $img_result = $img_stmt->get_result();
            
            if ($img_row = $img_result->fetch_assoc()) {
                // Convert the BLOB data to a base64 string for display in HTML
                $image_data = 'data:image/' . $img_row['image_type'] . ';base64,' . base64_encode($img_row['image_data']);
            } else {
                $image_data = '../Image/placeholder.jpg';
            }
            
            // Get seller's shop name
            $shop_name = 'ClothLoop Seller'; // Default name
            $shop_query = "SELECT shop_name FROM sellers WHERE id = ? LIMIT 1";
            $shop_stmt = $conn->prepare($shop_query);
            $shop_stmt->bind_param("i", $row['seller_id']);
            $shop_stmt->execute();
            $shop_result = $shop_stmt->get_result();
            
            if ($shop_row = $shop_result->fetch_assoc()) {
                if (!empty($shop_row['shop_name'])) {
                    $shop_name = $shop_row['shop_name'];
                }
            }
            $shop_stmt->close();
            
            // Format the product data
            $products[] = [
                'id' => $row['id'],
                'title' => $row['cloth_title'],
                'description' => $row['description'],
                'size' => $row['size'],
                'category' => $row['category'],
                'rentalPrice' => $row['rental_price'],
                'contactNo' => $row['contact_number'],
                'whatsappNo' => $row['whatsapp_number'],
                'terms' => $row['terms_and_conditions'],
                'created_at' => $row['created_at'],
                'image' => $image_data,
                'shop_name' => $shop_name,
                'seller_id' => $row['seller_id'] // Include seller ID for debugging
            ];
            
            $img_stmt->close();
        }
        
        $stmt->close();
        echo json_encode([
            'status' => 'success', 
            'products' => $products,
            'debug' => [
                'seller_id' => $seller_id,
                'count' => count($products)
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error fetching products: ' . $e->getMessage(),
            'debug' => [
                'seller_id' => $seller_id,
                'error' => $e->getMessage()
            ]
        ]);
    }
}

// Function to fetch all products for buyers
function fetchAllProducts() {
    global $conn;
    
    // Start session if not started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    try {
        // Verify database connection
        if ($conn->connect_error) {
            throw new Exception("Database connection error: " . $conn->connect_error);
        }

        // Check if the cloth_details table exists
        $tables_result = $conn->query("SHOW TABLES LIKE 'cloth_details'");
        if (!$tables_result) {
            throw new Exception("Error checking tables: " . $conn->error);
        }
        
        if ($tables_result->num_rows === 0) {
            throw new Exception("cloth_details table does not exist. Please run setup script first.");
        }
        
        // Get user type and ID from URL parameters or session
        $user_type = isset($_GET['user_type']) ? $_GET['user_type'] : 
                    (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'buyer');
        
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 
                  (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0);
        
        // Check if we're looking for a specific product by ID
        $specific_product_id = isset($_GET['id']) ? $_GET['id'] : null;
        
        // Clear any existing session seller data when accessing as buyer
        if ($user_type === 'buyer' && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'seller') {
            // Only for this request - don't change the actual session
            $user_type = 'buyer';
            $user_id = 0;
        }
        
        // Force showing all products if explicitly requesting as buyer
        if (isset($_GET['user_type']) && $_GET['user_type'] === 'buyer') {
            $user_type = 'buyer';
            $user_id = 0;
        }
        
        // Category filter (optional)
        $category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
        
        // Price filter (optional)
        $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
        $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;
        
        // Build query - directly use cloth_details table
        $sql = "SELECT cd.*, s.shop_name, s.shop_address, s.shop_location 
               FROM cloth_details cd 
               LEFT JOIN sellers s ON cd.seller_id = s.id 
               WHERE 1=1";
        
        // If requesting a specific product by ID, add that condition with highest priority
        if ($specific_product_id !== null) {
            // Handle both numeric and string IDs - use direct equality comparison
            $specific_product_id = $conn->real_escape_string($specific_product_id);
            $sql .= " AND cd.id = '$specific_product_id'";
            
            // Skip other filtering conditions if we're looking for a specific product
        } 
        else {
            // Add seller filter for sellers (only show their own products)
            if ($user_type === 'seller' && $user_id > 0) {
                $sql .= " AND cd.seller_id = $user_id";
            }
            
            // Add category filter if provided
            if (!empty($category)) {
                $sql .= " AND cd.category = '$category'";
            }
            
            // Add price filter
            $sql .= " AND cd.rental_price >= $min_price";
            if ($max_price < PHP_FLOAT_MAX) {
                $sql .= " AND cd.rental_price <= $max_price";
            }
        }
        
        // Execute the query with debugging
        error_log("Executing SQL query: " . $sql);
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $products = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Get product image directly from cloth_details table
                $image_data = '../../assets/images/placeholder.png'; // Default image
                
                // Use cloth_photo directly if available
                if (!empty($row['cloth_photo'])) {
                    $image_type = $row['photo_type'] ?? 'jpeg';
                    $image_data = 'data:image/' . $image_type . ';base64,' . base64_encode($row['cloth_photo']);
                }
                
                // Try to get shop name from sellers table
                $shop_name = 'ClothLoop Seller'; // Default name
                $shop_location = '';
                
                if ($row['seller_id'] > 0) {
                    $seller_query = "SELECT shop_name, shop_location FROM sellers WHERE id = {$row['seller_id']} LIMIT 1";
                    $seller_result = $conn->query($seller_query);

                    if ($seller_result && $seller_result->num_rows > 0) {
                        $seller_row = $seller_result->fetch_assoc();
                        if (!empty($seller_row['shop_name'])) {
                            $shop_name = $seller_row['shop_name'];
                        }
                        // Get shop_location if available
                        $shop_location = !empty($seller_row['shop_location']) ? $seller_row['shop_location'] : '';
                    }
                }

                // Format the product data
                $products[] = [
                    'id' => $row['id'],
                    'cloth_title' => $row['cloth_title'] ?? '',
                    'title' => $row['cloth_title'] ?? '',  // Add title alias for consistency
                    'description' => $row['description'] ?? '',
                    'size' => $row['size'] ?? '',
                    'category' => $row['category'] ?? '',
                    'rental_price' => floatval($row['rental_price'] ?? 0),
                    'contact_number' => $row['contact_number'] ?? '',
                    'whatsapp_number' => $row['whatsapp_number'] ?? '',
                    'terms_and_conditions' => $row['terms_and_conditions'] ?? '',
                    'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
                    'seller_id' => $row['seller_id'] ?? 0,
                    'shop_name' => $shop_name,
                    'shop_location' => $shop_location ?? '',
                    'image' => $image_data,
                    'cloth_photo' => !empty($row['cloth_photo']) ? true : false,
                    'photo_type' => $row['photo_type'] ?? 'jpeg'
                ];
            }
        }
        
        // Return success response with products
        echo json_encode([
            'status' => 'success', 
            'products' => $products,
            'count' => count($products),
            'debug' => [
                'query' => $sql,
                'user_type' => $user_type,
                'user_id' => $user_id,
                'result_rows' => $result->num_rows,
                'php_version' => PHP_VERSION,
                'mysql_version' => $conn->server_info
            ]
        ]);
    } catch (Exception $e) {
        // Return error response
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error fetching products: ' . $e->getMessage(),
            'debug' => [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'php_version' => PHP_VERSION,
                'mysql_version' => $conn->server_info ?? 'Unknown'
            ]
        ]);
    }
}

// Helper function to get list of tables in the database
function getTablesList($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    return $tables;
}

// Function to add a new product
function addProduct() {
    global $conn;
    
    // Get seller ID from POST data
    $seller_id = isset($_POST['seller_id']) ? $_POST['seller_id'] : null;
    
    if (!$seller_id) {
        echo json_encode(['status' => 'error', 'message' => 'Seller ID is required']);
        return;
    }
    
    // Get form data
    $title = isset($_POST['title']) ? $_POST['title'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $size = isset($_POST['size']) ? $_POST['size'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : 'General';
    $rental_price = isset($_POST['rental_price']) ? $_POST['rental_price'] : 0;
    $contact_number = isset($_POST['contact_number']) ? $_POST['contact_number'] : '';
    $whatsapp_number = isset($_POST['whatsapp_number']) ? $_POST['whatsapp_number'] : '';
    $terms_and_conditions = isset($_POST['terms_and_conditions']) ? $_POST['terms_and_conditions'] : '';
    
    // Validate required fields
    if (empty($title) || empty($description) || empty($rental_price)) {
        echo json_encode(['status' => 'error', 'message' => 'Title, description, and rental price are required']);
        return;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Handle image upload
        $image_data = null;
        $image_type = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_type = $_FILES['image']['type']; // Use MIME type directly
            
            // Read image data
            $image_data = file_get_contents($image_tmp);
        }
        
        // If we have image data, include it in the INSERT statement
        if ($image_data !== null) {
            $sql = "INSERT INTO cloth_details (
                    seller_id, 
                    cloth_title, 
                    description, 
                    size, 
                    category, 
                    rental_price, 
                    contact_number, 
                    whatsapp_number, 
                    terms_and_conditions,
                    cloth_photo,
                    photo_type,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param(
                "issssdsssss", 
                $seller_id,
                $title,
                $description,
                $size,
                $category,
                $rental_price,
                $contact_number,
                $whatsapp_number,
                $terms_and_conditions,
                $image_data,
                $image_type
            );
        } else {
            // No image data, proceed without it
            $sql = "INSERT INTO cloth_details (
                    seller_id, 
                    cloth_title, 
                    description, 
                    size, 
                    category, 
                    rental_price, 
                    contact_number, 
                    whatsapp_number, 
                    terms_and_conditions, 
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
                
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            
            $stmt->bind_param(
                "issssdsss", 
                $seller_id,
                $title,
                $description,
                $size,
                $category,
                $rental_price,
                $contact_number,
                $whatsapp_number,
                $terms_and_conditions
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        
        $cloth_id = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Product added successfully', 'id' => $cloth_id]);
    } catch (Exception $e) {
        // Roll back transaction
        $conn->rollback();
        
        // Return error response
        echo json_encode(['status' => 'error', 'message' => 'Error adding product: ' . $e->getMessage()]);
    }
}

// Function to update an existing product
function updateProduct() {
    global $conn;
    
    // Get JSON data from request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        return;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update cloth_details table
        $sql = "UPDATE cloth_details 
                SET cloth_title = ?, description = ?, size = ?, category = ?, 
                    rental_price = ?, contact_number = ?, whatsapp_number = ?, terms_and_conditions = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssdsssi", 
            $data['title'], 
            $data['description'], 
            $data['size'], 
            $data['category'], 
            $data['rentalPrice'], 
            $data['contactNo'], 
            $data['whatsappNo'], 
            $data['terms'],
            $data['id']
        );
        
        $stmt->execute();
        
        // Handle image update if included
        if (!empty($data['image']) && strpos($data['image'], 'data:image') === 0) {
            // Extract base64 data from the provided image string
            $image_parts = explode(";base64,", $data['image']);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            
            // Check if there's already an image record for this cloth
            $check_sql = "SELECT id FROM cloth_images WHERE cloth_id = ? LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $data['id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing image record
                $img_sql = "UPDATE cloth_images SET image_data = ?, image_type = ? WHERE cloth_id = ?";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("ssi", $image_base64, $image_type, $data['id']);
            } else {
                // Insert new image record
                $img_sql = "INSERT INTO cloth_images (cloth_id, image_data, image_type) VALUES (?, ?, ?)";
                $img_stmt = $conn->prepare($img_sql);
                $img_stmt->bind_param("iss", $data['id'], $image_base64, $image_type);
            }
            
            $img_stmt->execute();
            $img_stmt->close();
            $check_stmt->close();
        }
        
        $stmt->close();
        $conn->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Product updated successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error updating product: ' . $e->getMessage()]);
    }
}

// Function to delete a product
function deleteProduct() {
    global $conn;
    
    // Get product ID (support both POST and JSON input)
    $product_id = 0;
    
    // Try to get from POST data first
    if (isset($_POST['id'])) {
        $product_id = intval($_POST['id']);
    } 
    // If not in POST, try to get from JSON data
    else {
        $json_data = json_decode(file_get_contents('php://input'), true);
        if (isset($json_data['id'])) {
            $product_id = intval($json_data['id']);
        }
    }
    
    if (!$product_id) {
        echo json_encode(['status' => 'error', 'message' => 'Product ID is required']);
        return;
    }
    
    // Debug info
    $debug = [
        'product_id' => $product_id,
        'post_data' => $_POST,
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set'
    ];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete related images first
        $img_sql = "DELETE FROM cloth_images WHERE cloth_id = ?";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("i", $product_id);
        $img_stmt->execute();
        $img_stmt->close();
        
        // Delete the product
        $sql = "DELETE FROM cloth_details WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Product deleted successfully',
            'id' => $product_id
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Error deleting product: ' . $e->getMessage(),
            'debug' => $debug
        ]);
    }
}

// Close connection
$conn->close();
?> 