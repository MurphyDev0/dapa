<?php
// Session konfiguráció betöltése
require_once 'session_config.php';

// Adatbázis kapcsolat beállításai
define('DB_HOST', 'localhost');
define('DB_NAME', 'webshop_engine');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // PDO kapcsolat létrehozása
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Adatbázis kapcsolódási hiba: " . $e->getMessage());
}

// Időzóna beállítása
date_default_timezone_set('Europe/Budapest');

// Hibaüzenetek beállítása
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Globális függvények
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format((float)$price, 0, ',', ' ') . ' Ft';
}

function formatDate($date) {
    if (empty($date)) {
        return date('Y-m-d H:i:s');
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date('Y-m-d H:i:s', $timestamp);
}

// Adatbázis kapcsolat globális elérhetővé tétele
global $db;

// Monogram generálása a teljes névből
function generateMonogram($fullName) {
    if (empty($fullName)) return "?";
    
    $parts = explode(" ", $fullName);
    if (count($parts) >= 2) {
        return mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr($parts[1], 0, 1, 'UTF-8');
    } else {
        return mb_substr($fullName, 0, 2, 'UTF-8');
    }
}

?>