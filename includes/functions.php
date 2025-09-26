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
function uploadImage($file, $directory = 'posts') {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $uploadDir = UPLOAD_PATH . $directory . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $file['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
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
    
    if ($limit) {
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
    }
    
    return $db->fetchAll($sql, $params);
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