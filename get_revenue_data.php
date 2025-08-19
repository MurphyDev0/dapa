<?php
require_once 'config.php';

$period = $_GET['period'] ?? 'daily';

function getRevenueData($db, $period) {
    switch($period) {
        case 'daily':
            $query = $db->prepare("
                SELECT DATE(order_date) as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(order_date)
                ORDER BY date
            ");
            break;
            
        case 'weekly':
            $query = $db->prepare("
                SELECT DATE_FORMAT(order_date, '%Y-%u') as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
                GROUP BY DATE_FORMAT(order_date, '%Y-%u')
                ORDER BY date
            ");
            break;
            
        case 'monthly':
            $query = $db->prepare("
                SELECT DATE_FORMAT(order_date, '%Y-%m') as date, SUM(total_amount) as revenue 
                FROM orders 
                WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY date
            ");
            break;
            
        default:
            return ['labels' => [], 'revenue' => []];
    }
    
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
    // Formázza a dátumokat a megjelenítéshez
    $formattedLabels = array_map(function($date) use ($period) {
        switch($period) {
            case 'daily':
                return date('M d', strtotime($date));
            case 'weekly':
                return 'Hét ' . date('W', strtotime($date));
            case 'monthly':
                return date('M Y', strtotime($date));
            default:
                return $date;
        }
    }, array_column($results, 'date'));
    
    return [
        'labels' => $formattedLabels,
        'revenue' => array_map('floatval', array_column($results, 'revenue'))
    ];
}

header('Content-Type: application/json');
echo json_encode(getRevenueData($db, $period)); 