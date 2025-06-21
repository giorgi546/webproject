<?php
// includes/config.php - Main configuration file

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shopping_website');

// Site Configuration
define('SITE_URL', 'http://localhost/shopping-website');
define('SITE_NAME', 'ShopMaster');
define('ADMIN_EMAIL', 'admin@shopmaster.com');

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Email Configuration (for email verification)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');

// Security Configuration
define('ENCRYPTION_KEY', 'your-secret-key-here-change-this');
define('PASSWORD_MIN_LENGTH', 8);

// Pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $class_file = __DIR__ . '/../classes/' . $class_name . '.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
?>