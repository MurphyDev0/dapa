<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';

// Admin jogosultság ellenőrzése
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
//     header('Location: login.php');
//     exit;
// }

echo "<h1>Coupons adattábla frissítés</h1>";

try {
    // Ellenőrizzük a jelenlegi tábla struktúrát
    $stmt = $db->prepare("DESCRIBE coupons");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Jelenlegi oszlopok:</h3>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Törlendő oszlopok
    $columns_to_drop = ['start_date', 'end_date', 'discount_value'];
    
    foreach ($columns_to_drop as $column_name) {
        // Ellenőrizzük, létezik-e az oszlop, és ha igen, töröljük
        $column_exists = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $column_name) {
                $column_exists = true;
                break;
            }
        }
        
        if ($column_exists) {
            $db->exec("ALTER TABLE coupons DROP COLUMN $column_name");
            echo "$column_name oszlop törölve.<br>";
        }
    }
    
    // Oszlop létezések ellenőrzése
    $value_exists = false;
    $type_exists = false;
    $valid_from_exists = false;
    $valid_to_exists = false;
    $min_purchase_exists = false;
    $usage_limit_exists = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'value') $value_exists = true;
        if ($column['Field'] === 'type') $type_exists = true;
        if ($column['Field'] === 'valid_from') $valid_from_exists = true;
        if ($column['Field'] === 'valid_to') $valid_to_exists = true;
        if ($column['Field'] === 'min_purchase') $min_purchase_exists = true;
        if ($column['Field'] === 'usage_limit') $usage_limit_exists = true;
    }
    
    // Ha nem léteznek, hozzáadjuk őket
    if (!$type_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'percentage'");
        echo "Type oszlop hozzáadva.<br>";
    }
    
    if (!$value_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN value DECIMAL(10,2) NOT NULL DEFAULT 0");
        echo "Value oszlop hozzáadva.<br>";
    }
    
    if (!$valid_from_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN valid_from DATETIME NULL");
        echo "Valid_from oszlop hozzáadva.<br>";
    }
    
    if (!$valid_to_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN valid_to DATETIME NULL");
        echo "Valid_to oszlop hozzáadva.<br>";
    }
    
    if (!$min_purchase_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0");
        echo "Min_purchase oszlop hozzáadva.<br>";
    }
    
    if (!$usage_limit_exists) {
        $db->exec("ALTER TABLE coupons ADD COLUMN usage_limit INT NOT NULL DEFAULT 0");
        echo "Usage_limit oszlop hozzáadva.<br>";
    }
    
    // Ellenőrizzük újra a tábla struktúrát
    $stmt = $db->prepare("DESCRIBE coupons");
    $stmt->execute();
    $columns_after = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Frissített oszlopok:</h3>";
    echo "<pre>";
    print_r($columns_after);
    echo "</pre>";
    
    echo "<p>A coupons tábla sikeresen frissítve!</p>";
    echo "<p>Átirányítás 10 másodperc múlva az <a href='admin_coupons.php'>admin kuponok</a> oldalra...</p>";
    echo "<script>setTimeout(function(){ window.location.href = 'admin_coupons.php'; }, 10000);</script>";
} catch (Exception $e) {
    echo "Hiba történt: " . $e->getMessage();
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
?> 