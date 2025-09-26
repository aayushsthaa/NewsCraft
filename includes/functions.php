<?php
require_once 'database.php';

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function login($username, $password) {
    $db = Database::getInstance();
    
    $user = $db->fetch(
        "SELECT * FROM users WHERE username = :username AND role = 'admin'", 
        [':username' => $username]
    );
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'];
        return true;
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// CSRF Protection functions
function generateCSRFToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        return generateCSRFToken();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    $isValid = hash_equals($_SESSION['csrf_token'], $token);
    
    // Regenerate token after validation for added security
    if ($isValid) {
        generateCSRFToken();
    }
    
    return $isValid;
}

// Utility functions
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    // Basic slug generation - replace with more sophisticated version if needed
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

// Image upload function
function uploadImage($file, $directory = 'posts', $maxSize = 5242880) { // 5MB default limit
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    // Check file size limit
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    $uploadDir = UPLOAD_PATH . $directory . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Use finfo to get the real MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Allowed MIME types and corresponding extensions
    $allowedTypes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp']
    ];
    
    if (!array_key_exists($realMimeType, $allowedTypes)) {
        return false;
    }
    
    // Validate file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes[$realMimeType])) {
        return false;
    }
    
    // Generate secure random filename
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Ensure filename is unique
    while (file_exists($targetPath)) {
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $uploadDir . $filename;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $directory . '/' . $filename;
    }
    
    return false;
}

// Content functions
function getPosts($limit = null, $offset = 0, $categoryId = null, $status = 'published') {
    $db = Database::getInstance();
    
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug, u.username as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.status = :status
    ";
    
    $params = [':status' => $status];
    
    if ($categoryId) {
        $sql .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryId;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    return $db->queryWithLimitOffset($sql, $params, $limit, $offset)->fetchAll();
}

function getPostBySlug($slug) {
    $db = Database::getInstance();
    
    return $db->fetch("
        SELECT p.*, c.name as category_name, c.slug as category_slug, u.username as author_name
        FROM posts p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN users u ON p.author_id = u.id
        WHERE p.slug = :slug AND p.status = 'published'
    ", [':slug' => $slug]);
}

function getCategories($parentId = null) {
    $db = Database::getInstance();
    
    $sql = "SELECT * FROM categories WHERE is_active = true";
    $params = [];
    
    if ($parentId === null) {
        $sql .= " AND parent_id IS NULL";
    } else {
        $sql .= " AND parent_id = :parent_id";
        $params[':parent_id'] = $parentId;
    }
    
    $sql .= " ORDER BY sort_order ASC, name ASC";
    
    return $db->fetchAll($sql, $params);
}

function getCategoryBySlug($slug) {
    $db = Database::getInstance();
    return $db->fetch("SELECT * FROM categories WHERE slug = :slug AND is_active = true", [':slug' => $slug]);
}

function getActiveAds($position) {
    $db = Database::getInstance();
    
    return $db->fetchAll("
        SELECT * FROM ads 
        WHERE position = :position 
        AND is_active = true 
        AND (start_date IS NULL OR start_date <= CURRENT_DATE)
        AND (end_date IS NULL OR end_date >= CURRENT_DATE)
        ORDER BY created_at DESC
    ", [':position' => $position]);
}

function getSiteSetting($key, $default = '') {
    $db = Database::getInstance();
    
    $setting = $db->fetch("SELECT setting_value FROM site_settings WHERE setting_key = :key", [':key' => $key]);
    
    return $setting ? $setting['setting_value'] : $default;
}

function updateSiteSetting($key, $value) {
    $db = Database::getInstance();
    
    // Check if setting exists
    $exists = $db->fetch("SELECT id FROM site_settings WHERE setting_key = :key", [':key' => $key]);
    
    if ($exists) {
        return $db->update('site_settings', 
            ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
            'setting_key = :key', 
            [':key' => $key]
        );
    } else {
        return $db->insert('site_settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
?>