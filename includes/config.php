<?php
// Application Configuration
define('APP_NAME', 'DonationHub');
define('APP_DESC', 'A platform for donating and receiving physical goods');
define('BASE_URL', 'http://localhost/donationhub');

// Database configuration
define('DB_HOST', 'sql105.infinityfree.com');
define('DB_NAME', 'if0_39360065_donation_db');
define('DB_USER', 'if0_39360065');
define('DB_PASS', 'WMQdgtirPztqNf');

// File upload configuration
define('UPLOAD_DIR', 'assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>