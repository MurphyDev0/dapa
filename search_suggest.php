<?php
require_once 'session_config.php';
include 'config.php';

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, must-revalidate');

// CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Error handling
function sendError($message = 'Hiba történt') {
    http_response_code(500);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendEmptyResult() {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get and validate query parameter
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query) || mb_strlen($query) < 2) {
    sendEmptyResult();
}

// Database connection
$pdo = null;

try {
    // Try to use existing connection from config
    if (isset($pdo) && $pdo instanceof PDO) {
        // Already connected
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    } elseif (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        // Create new connection using constants
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } else {
        throw new Exception('Adatbázis konfiguráció nem található');
    }
} catch (Exception $e) {
    error_log('Search suggest DB error: ' . $e->getMessage());
    sendError('Adatbázis kapcsolódási hiba');
}

if (!$pdo) {
    sendError('Adatbázis kapcsolat nem elérhető');
}

try {
    // Prepare search query with proper escaping
    $searchTerm = '%' . $query . '%';
    $prefixTerm = $query . '%';
    
    // SQL query to search products
    $sql = "SELECT id, name, img, short_desc, price 
            FROM products 
            WHERE (name LIKE :search OR short_desc LIKE :search)
            AND active = 1
            ORDER BY 
                CASE WHEN name LIKE :prefix THEN 0 ELSE 1 END,
                name ASC
            LIMIT 8";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $stmt->bindParam(':prefix', $prefixTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll();
    
    // Format results
    $suggestions = [];
    foreach ($results as $row) {
        $snippet = '';
        if (!empty($row['short_desc'])) {
            $snippet = mb_substr(strip_tags($row['short_desc']), 0, 80);
            if (mb_strlen($row['short_desc']) > 80) {
                $snippet .= '...';
            }
        }
        
        $suggestions[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'img' => $row['img'] ?: '',
            'snippet' => $snippet,
            'price' => isset($row['price']) ? (float)$row['price'] : null
        ];
    }
    
    echo json_encode($suggestions, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Search suggest query error: ' . $e->getMessage());
    sendError('Keresési hiba');
}
?>