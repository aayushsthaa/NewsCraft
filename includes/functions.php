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

// Ad content sanitization function
function sanitizeAdContent($content) {
    if (empty($content)) {
        return '';
    }
    
    // Define allowed HTML tags and their attributes
    $allowed_tags = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'a' => ['href', 'title', 'target'],
        'img' => ['src', 'alt', 'width', 'height', 'class'],
        'div' => ['class'],
        'span' => ['class']
    ];
    
    // Remove all script tags and their content first
    $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
    
    // Remove javascript: and data: protocols from all attributes
    $content = preg_replace('/\s*(?:javascript|data|vbscript):[^"\']*["\']?/i', '', $content);
    
    // Remove on* event handlers (onclick, onload, etc.)
    $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']?/i', '', $content);
    
    // Build allowed tags string for strip_tags
    $allowed_tags_string = '<' . implode('><', array_keys($allowed_tags)) . '>';
    
    // Strip all tags except allowed ones
    $content = strip_tags($content, $allowed_tags_string);
    
    // Parse and validate remaining tags and attributes
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//*');
    
    foreach ($nodes as $node) {
        $tag_name = strtolower($node->nodeName);
        
        // Remove unknown tags
        if (!array_key_exists($tag_name, $allowed_tags)) {
            $node->parentNode->removeChild($node);
            continue;
        }
        
        // Check attributes
        $allowed_attrs = $allowed_tags[$tag_name];
        $attributes_to_remove = [];
        
        foreach ($node->attributes as $attr) {
            $attr_name = strtolower($attr->name);
            
            if (!in_array($attr_name, $allowed_attrs)) {
                $attributes_to_remove[] = $attr_name;
            } else {
                // Additional validation for specific attributes
                if ($attr_name === 'href' || $attr_name === 'src') {
                    $url = trim($attr->value);
                    
                    // Only allow http, https, and relative URLs
                    if (!preg_match('/^(https?:\/\/|\/|\.\/|[a-zA-Z0-9])/i', $url)) {
                        $attributes_to_remove[] = $attr_name;
                    }
                    
                    // Reject javascript:, data:, vbscript: protocols
                    if (preg_match('/^\s*(?:javascript|data|vbscript):/i', $url)) {
                        $attributes_to_remove[] = $attr_name;
                    }
                }
            }
        }
        
        // Remove invalid attributes
        foreach ($attributes_to_remove as $attr_name) {
            $node->removeAttribute($attr_name);
        }
    }
    
    // Get clean content
    $clean_content = $dom->saveHTML();
    
    // Remove the XML declaration that DOMDocument adds
    $clean_content = preg_replace('/^<!DOCTYPE.+?>/', '', $clean_content);
    $clean_content = str_replace(['<html>', '</html>', '<body>', '</body>'], '', $clean_content);
    
    return trim($clean_content);
}

// Enhanced function to get active ads by position with proper date filtering
function getActiveAdsByPosition($position) {
    $db = Database::getInstance();
    
    $current_date = date('Y-m-d');
    
    return $db->fetchAll("
        SELECT * FROM ads 
        WHERE position = :position 
        AND is_active = true 
        AND (start_date IS NULL OR start_date <= :current_date)
        AND (end_date IS NULL OR end_date >= :current_date)
        ORDER BY created_at DESC
    ", [
        ':position' => $position,
        ':current_date' => $current_date
    ]);
}

// Secure file deletion function
function deleteAdImage($image_url) {
    if (empty($image_url)) {
        return true;
    }
    
    // Convert URL to relative path if it's a full URL
    if (strpos($image_url, UPLOAD_URL) === 0) {
        $relative_path = str_replace(UPLOAD_URL, '', $image_url);
    } else {
        $relative_path = $image_url;
    }
    
    // Ensure the path is within the uploads directory
    $full_path = UPLOAD_PATH . ltrim($relative_path, '/');
    $real_path = realpath($full_path);
    $upload_real_path = realpath(UPLOAD_PATH);
    
    // Security check: ensure file is within upload directory
    if ($real_path === false || $upload_real_path === false || strpos($real_path, $upload_real_path) !== 0) {
        return false;
    }
    
    if (file_exists($real_path) && is_file($real_path)) {
        return unlink($real_path);
    }
    
    return true;
}
?>