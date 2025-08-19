<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';

echo "<h1>Teszt kupon létrehozása</h1>";

try {
    // Ellenőrizzük a tábla struktúrát
    $stmt = $db->prepare("DESCRIBE coupons");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Tábla oszlopok:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Teszt kupon létrehozása
    $code = "TESZT" . rand(1000, 9999);
    $type = "percentage";
    $value = 10;
    $valid_from = date('Y-m-d H:i:s');
    $valid_to = date('Y-m-d H:i:s', strtotime('+30 days'));
    $min_purchase = 5000;
    $usage_limit = 5;
    
    // Készítsünk egy dinamikus INSERT utasítást az oszlopok alapján
    $insert_columns = [];
    $placeholders = [];
    $params = [];
    
    foreach ($columns as $column) {
        $field = $column['Field'];
        
        // ID mezőt kihagyjuk
        if ($field === 'id') continue;
        
        $insert_columns[] = $field;
        $placeholders[] = '?';
        
        // Megfelelő értéket adjunk a mezőkhöz
        switch ($field) {
            case 'code':
                $params[] = $code;
                break;
            case 'type':
                $params[] = $type;
                break;
            case 'value':
                $params[] = $value;
                break;
            case 'valid_from':
                $params[] = $valid_from;
                break;
            case 'valid_to':
                $params[] = $valid_to;
                break;
            case 'min_purchase':
                $params[] = $min_purchase;
                break;
            case 'usage_limit':
                $params[] = $usage_limit;
                break;
            case 'is_active':
                $params[] = 1;
                break;
            case 'created_at':
                $params[] = date('Y-m-d H:i:s');
                break;
            case 'user_id':
                $params[] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                break;
            default:
                $params[] = null;
                break;
        }
    }
    
    // SQL utasítás összeállítása
    $insert_sql = "INSERT INTO coupons (" . implode(", ", $insert_columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
    
    echo "<h3>SQL lekérdezés:</h3>";
    echo "<pre>" . $insert_sql . "</pre>";
    
    echo "<h3>Paraméterek:</h3>";
    echo "<pre>";
    print_r($params);
    echo "</pre>";
    
    // Végrehajtás
    $stmt = $db->prepare($insert_sql);
    $stmt->execute($params);
    $last_id = $db->lastInsertId();
    
    echo "<div style='background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-top: 20px;'>";
    echo "Teszt kupon sikeresen létrehozva! ID: " . $last_id . ", Kód: " . $code;
    echo "</div>";
    
    echo "<p><a href='admin_coupons.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Vissza a Kuponok oldalra</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-top: 20px;'>";
    echo "<strong>Hiba:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Kód:</strong> " . $e->getCode() . "<br>";
    echo "<strong>Hely:</strong> " . $e->getFile() . ":" . $e->getLine();
    echo "</div>";
}
?> 