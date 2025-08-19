<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'reports_functions.php';

// Admin jogosultság ellenőrzése
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    exit('Hozzáférés megtagadva');
}

// Dátum paraméterek ellenőrzése
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    // Adatok lekérése
    $data = [
        'salesData' => [
            'dates' => array_column(getSalesData($start_date, $end_date), 'date'),
            'revenues' => array_column(getSalesData($start_date, $end_date), 'revenue')
        ],
        'productSales' => [
            'products' => array_column(getProductSales($start_date, $end_date), 'name'),
            'quantities' => array_column(getProductSales($start_date, $end_date), 'quantity')
        ],
        'categorySales' => [
            'categories' => array_column(getCategorySales($start_date, $end_date), 'name'),
            'totals' => array_column(getCategorySales($start_date, $end_date), 'total')
        ],
        'customerTypes' => [
            'new' => getCustomerStats($start_date, $end_date)['new_customers'],
            'returning' => getCustomerStats($start_date, $end_date)['returning_customers']
        ],
        'campaigns' => [
            'campaigns' => array_column(getMarketingStats($start_date, $end_date), 'campaign_name'),
            'conversionRates' => array_column(getMarketingStats($start_date, $end_date), 'conversion_rate'),
            'roi' => array_column(getMarketingStats($start_date, $end_date), 'roi')
        ],
        'visitorStats' => [
            'dates' => array_column(getWebsiteStats($start_date, $end_date), 'visit_date'),
            'pageviews' => array_column(getWebsiteStats($start_date, $end_date), 'pageviews'),
            'uniqueVisitors' => array_column(getWebsiteStats($start_date, $end_date), 'unique_visitors')
        ],
        'bounceRates' => [
            'dates' => array_column(getWebsiteStats($start_date, $end_date), 'visit_date'),
            'bounceRates' => array_column(getWebsiteStats($start_date, $end_date), 'bounce_rate')
        ],
        'regionalSales' => [
            'regions' => array_column(getGeographicStats($start_date, $end_date), 'region'),
            'sales' => array_column(getGeographicStats($start_date, $end_date), 'total_sales')
        ],
        'satisfaction' => [
            'ratings' => [
                count(array_filter(getCustomerServiceStats($start_date, $end_date), function($rating) { return $rating == 1; })),
                count(array_filter(getCustomerServiceStats($start_date, $end_date), function($rating) { return $rating == 2; })),
                count(array_filter(getCustomerServiceStats($start_date, $end_date), function($rating) { return $rating == 3; })),
                count(array_filter(getCustomerServiceStats($start_date, $end_date), function($rating) { return $rating == 4; })),
                count(array_filter(getCustomerServiceStats($start_date, $end_date), function($rating) { return $rating == 5; }))
            ]
        ],
        'returns' => [
            'reasons' => array_column(getReturnsAnalysis($start_date, $end_date), 'return_reason'),
            'counts' => array_column(getReturnsAnalysis($start_date, $end_date), 'total_returns')
        ],
        'paymentMethods' => [
            'methods' => array_column(getPaymentMethodStats($start_date, $end_date), 'payment_method'),
            'amounts' => array_column(getPaymentMethodStats($start_date, $end_date), 'total_amount')
        ],
        'emailStats' => [
            'dates' => array_column(getEmailMarketingStats($start_date, $end_date), 'send_date'),
            'openRates' => array_column(getEmailMarketingStats($start_date, $end_date), 'open_rate'),
            'clickRates' => array_column(getEmailMarketingStats($start_date, $end_date), 'click_rate')
        ],
        'demographics' => [
            'ageGroups' => ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
            'male' => [120, 250, 180, 150, 90, 45],  // Példa adatok
            'female' => [150, 280, 200, 170, 100, 55]  // Példa adatok
        ],
        'internationalSales' => [
            'countries' => ['Magyarország', 'Románia', 'Szlovákia', 'Ausztria', 'Németország'],
            'sales' => [1500000, 800000, 600000, 450000, 350000]  // Példa adatok
        ],
        'financialData' => [
            'dates' => array_column(getSalesData($start_date, $end_date), 'date'),
            'revenues' => array_column(getSalesData($start_date, $end_date), 'revenue'),
            'expenses' => array_map(function($revenue) { 
                return $revenue * 0.7;  // Példa: költségek a bevétel 70%-a
            }, array_column(getSalesData($start_date, $end_date), 'revenue'))
        ]
    ];

    // JSON válasz küldése
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 