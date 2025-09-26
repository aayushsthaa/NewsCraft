<?php
// Database configuration
$db_config = [
    'host' => $_ENV['PGHOST'] ?? 'localhost',
    'port' => $_ENV['PGPORT'] ?? '5432',
    'dbname' => $_ENV['PGDATABASE'] ?? 'postgres',
    'user' => $_ENV['PGUSER'] ?? 'postgres',
    'password' => $_ENV['PGPASSWORD'] ?? ''
];

// Site configuration
define('SITE_URL', 'http://localhost:5000');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>