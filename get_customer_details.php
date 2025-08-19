<?php
session_start();
require_once 'config.php';

// Ellenőrizzük, hogy az admin be van-e jelentkezve
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    die('Nincs jogosultság');
}

// Ellenőrizzük, hogy van-e ID
if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Hiányzó ID');
}

$customer_id = (int)$_GET['id'];

try {
    // Ügyfél alapadatainak lekérése
    $stmt = $db->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT o.id) as order_count,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            MAX(o.created_at) as last_order
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.id = ?
        GROUP BY u.id
    ");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        http_response_code(404);
        die('Ügyfél nem található');
    }

    // Ügyfél rendeléseinek lekérése
    $stmt = $db->prepare("
        SELECT 
            id as order_number,
            created_at,
            total_amount,
            status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$customer_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Adatok összeállítása
    $response = [
        'id' => $customer['id'],
        'name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'address' => $customer['address'],
        'is_admin' => $customer['is_admin'],
        'created_at' => $customer['created_at'],
        'order_count' => $customer['order_count'],
        'total_spent' => $customer['total_spent'],
        'last_order' => $customer['last_order'],
        'orders' => $orders
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Adatbázis hiba: ' . $e->getMessage());
    http_response_code(500);
    die('Adatbázis hiba történt. Kérjük, próbálja később.');
} 