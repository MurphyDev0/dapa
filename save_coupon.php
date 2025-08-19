<?php
header('Content-Type: application/json');

// Include database config file
require_once 'session_config.php';
require_once 'config.php';

try {
    // Get POST data
    $coupon = isset($_POST['coupon']) ? $_POST['coupon'] : '';
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    
    // Convert segment name to type value
    $typeValue = convertSegmentNameToType($type);
    
    // Get user ID from session (adjust based on your authentication system)
    session_start();
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    // Current timestamp
    $createdAt = date('Y-m-d H:i:s');
    
    // Create database connection using the imported $db from config.php
    // or use your existing connection variable from config.php
    
    // Prepare and execute SQL statement
    $stmt = $db->prepare("
        INSERT INTO coupons (code, is_active, type, created_at, user_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $isActive = 1; // Active by default
    $stmt->execute([$coupon, $isActive, $typeValue, $createdAt, $userId]);
    
    // Get the last insert ID
    $lastId = $db->lastInsertId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Kupon sikeresen mentve',
        'coupon' => $coupon,
        'type' => $typeValue,
        'id' => $lastId
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Hiba történt',
        'error' => $e->getMessage()
    ]);
}

/**
 * Convert segment name to database type value
 */
function convertSegmentNameToType($segmentName) {
    switch ($segmentName) {
        case 'Ajándék termék':
            return 'gift_product';
        case 'Pörgess újra':
            return 'spin_again';
        case 'Ingyenes szállítás':
            return 'free_shipping';
        case '15% kedvezmény':
            return 'discount15';
        case '10% kedvezmény':
            return 'discount10';
        case '30% kedvezmény':
            return 'discount30';
        default:
            return 'unknown';
    }
}
?>