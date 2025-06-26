<?php
/**
 * Constants File
 * Contains application-wide constants and settings
 */

// Base paths
define('ROOT_PATH', realpath(dirname(__FILE__) . '/../..'));
define('BACKEND_PATH', ROOT_PATH . '/backend');
define('UPLOADS_PATH', BACKEND_PATH . '/uploads');

// URLs
define('BASE_URL', 'http://localhost/ClothLoop');
define('API_URL', BASE_URL . '/backend/api');

// Upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 10);

// Auth
define('JWT_SECRET', 'clothloop_secret_key_change_in_production');
define('SESSION_DURATION', 86400); // 24 hours in seconds

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_SELLER', 'seller');
define('ROLE_BUYER', 'buyer');

// Date format for database
define('DB_DATE_FORMAT', 'Y-m-d H:i:s');

// Error reporting
define('DEBUG_MODE', true); // Set to false in production 