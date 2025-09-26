<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Validate Content-Type for JSON requests
if (!isset($_SERVER['CONTENT_TYPE']) || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid content type']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate JSON parsing
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validate ad ID
$adId = (int) ($input['ad_id'] ?? 0);
if (!$adId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ad ID']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if ad exists and is active
    $ad = $db->fetch("
        SELECT id, is_active, start_date, end_date 
        FROM ads 
        WHERE id = :id
    ", [':id' => $adId]);
    
    if (!$ad) {
        http_response_code(404);
        echo json_encode(['error' => 'Advertisement not found']);
        exit;
    }
    
    // Check if ad is currently active
    $now = date('Y-m-d');
    $isActive = $ad['is_active'] && 
                (!$ad['start_date'] || $ad['start_date'] <= $now) && 
                (!$ad['end_date'] || $ad['end_date'] >= $now);
    
    if (!$isActive) {
        http_response_code(403);
        echo json_encode(['error' => 'Advertisement is not active']);
        exit;
    }
    
    // Atomic increment of click count using a single query
    $result = $db->query("
        UPDATE ads 
        SET click_count = click_count + 1, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = :id
    ", [':id' => $adId]);
    
    if ($result) {
        // Get updated click count
        $updated_ad = $db->fetch("SELECT click_count FROM ads WHERE id = :id", [':id' => $adId]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Click tracked successfully',
            'click_count' => $updated_ad['click_count'] ?? 0
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update click count']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    // Log error for debugging
    error_log("Ad tracking error: " . $e->getMessage());
}
?>