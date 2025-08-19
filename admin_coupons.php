<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';

// Admin jogosultság ellenőrzése
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    Notification::add('Nincs jogosultsága az admin panel eléréséhez!', 'failure');
    header('Location: login.php');
    exit;
}

// Dark mode beállítás lekérése
$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Szűrési paraméterek
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$valid_from = isset($_GET['valid_from']) ? $_GET['valid_from'] : '';
$valid_to = isset($_GET['valid_to']) ? $_GET['valid_to'] : '';

// Kuponok lekérése szűréssel
try {
    // Ellenőrizzük, hogy vannak-e kuponok
    $stmt = $db->prepare("SELECT COUNT(*) AS count FROM coupons");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $query = "SELECT c.*, 
                  COUNT(o.id) as usage_count,
                  COALESCE(SUM(o.discount_amount), 0) as total_discount
                  FROM coupons c
                  LEFT JOIN orders o ON c.code = o.coupon_code
                  WHERE 1=1";
        $params = [];

        if ($type_filter) {
            $query .= " AND c.type = ?";
            $params[] = $type_filter;
        }

        if ($status_filter) {
            switch ($status_filter) {
                case 'active':
                    $query .= " AND c.valid_from <= CURRENT_DATE AND (c.valid_to IS NULL OR c.valid_to >= CURRENT_DATE)";
                    break;
                case 'expired':
                    $query .= " AND c.valid_to < CURRENT_DATE";
                    break;
                case 'future':
                    $query .= " AND c.valid_from > CURRENT_DATE";
                    break;
            }
        }

        if ($search_filter) {
            $query .= " AND c.code LIKE ?";
            $search_param = "%$search_filter%";
            $params[] = $search_param;
        }

        if ($valid_from) {
            $query .= " AND c.valid_from >= ?";
            $params[] = $valid_from;
        }

        if ($valid_to) {
            $query .= " AND c.valid_to <= ?";
            $params[] = $valid_to;
        }

        $query .= " GROUP BY c.id ORDER BY c.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $coupons = [];
    }
} catch (Exception $e) {
    error_log("Hiba a kuponok lekérése során: " . $e->getMessage());
    echo "<div style='color:red; background: #ffeeee; border: 1px solid red; padding: 10px; margin: 10px;'>Adatbázis hiba: " . $e->getMessage() . "</div>";
    $coupons = [];
}

// Kupon létrehozása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    $code = $_POST['code'];
    $discount = $_POST['discount'];
    $valid_from = $_POST['valid_from'];
    $valid_to = $_POST['valid_to'];
    $min_order = $_POST['min_order'];
    $max_uses = $_POST['max_uses'];
    $status = $_POST['status'];
    
    try {
        $stmt = $db->prepare("INSERT INTO coupons (code, discount, valid_from, valid_to, min_order, max_uses, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $discount, $valid_from, $valid_to, $min_order, $max_uses, $status]);
        Notification::add('A kupon sikeresen létrehozva!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kupon létrehozása során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_coupons.php");
    exit;
}

// Kupon törlése
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        Notification::add('A kupon sikeresen törölve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kupon törlése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_coupons.php");
    exit;
}

// Kupon szerkesztése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_coupon'])) {
    $coupon_id = $_POST['coupon_id'];
    $code = $_POST['code'];
    $type = $_POST['type'];
    $value = $_POST['value'];
    $valid_from = $_POST['valid_from'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_from'])) : null;
    $valid_to = $_POST['valid_to'] ? date('Y-m-d H:i:s', strtotime($_POST['valid_to'])) : null;
    $min_purchase = isset($_POST['min_purchase']) ? $_POST['min_purchase'] : 0;
    $usage_limit = isset($_POST['usage_limit']) ? $_POST['usage_limit'] : 0;
    
    try {
        $stmt = $db->prepare("UPDATE coupons SET code = ?, type = ?, value = ?, valid_from = ?, valid_to = ?, min_purchase = ?, usage_limit = ? WHERE id = ?");
        $stmt->execute([$code, $type, $value, $valid_from, $valid_to, $min_purchase, $usage_limit, $coupon_id]);
        Notification::add('A kupon sikeresen frissítve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kupon frissítése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_coupons.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuponok - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="new_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="notifications/notifications.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            lightest: '#ade4e5',
                            light: '#3ca7aa',
                            dark: '#00868a',
                            darkest: '#003f41',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
        }
        .hidden {
            display: none;
        }
        tr[id^="editForm_"] {
            transition: all 0.3s ease-in-out;
            max-height: 0;
            overflow: hidden;
        }
        tr[id^="editForm_"]:not(.hidden) {
            max-height: 1000px;
        }
    </style>
</head>
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
    <?php echo Notification::display(); ?>
    <!-- Sidebar -->
    <div class="w-full md:w-64 bg-primary-darkest text-white flex flex-col md:h-screen">
        <div class="p-4 bg-primary-dark">
            <h2 class="text-2xl font-bold">AdminPanel</h2>
        </div>
        <nav class="flex-grow p-4">
            <ul class="space-y-2">
                <li>
                    <a href="admin.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">🏠</span>
                        <span>Vezérlőpult</span>
                    </a>
                </li>
                <li>
                    <a href="admin_products.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_products.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">🛒</span>
                        <span>Termékek</span>
                    </a>
                </li>
                <li>
                    <a href="admin_orders.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_orders.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">📦</span>
                        <span>Rendelések</span>
                    </a>
                </li>
                <li>
                    <a href="admin_customers.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_customers.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">👥</span>
                        <span>Ügyfelek</span>
                    </a>
                </li>
                <li>
                    <a href="admin_coupons.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_coupons.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">🎟️</span>
                        <span>Kuponok</span>
                    </a>
                </li>
                <li>
                    <a href="admin_reports.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_reports.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">📊</span>
                        <span>Jelentések</span>
                    </a>
                </li>
                <li>
                    <a href="admin_settings.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_settings.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">⚙️</span>
                        <span>Beállítások</span>
                    </a>
                </li>
                <li>
                    <a href="index.php" class="flex items-center p-2 hover:bg-red-700 text-white rounded">
                        <span class="mr-2">🚪</span>
                        <span>Kilépés</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow">
        <header class="bg-white shadow p-4">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-primary-darkest">Kuponok kezelése</h1>
                <div class="flex gap-2">
                    <a href="update_coupon_table.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        DB frissítés
                    </a>
                    <a href="create_test_coupon.php" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        Teszt kupon
                    </a>
                    <button onclick="showCreateCouponModal()" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        <span class="mt-2">+</span> Új kupon
                    </button>
                </div>
            </div>
        </header>

        <main class="p-4">
            <?php
            // Hibaüzenetek megjelenítése
            if (isset($_SESSION['error_message'])) {
                echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">';
                echo '<p>' . $_SESSION['error_message'] . '</p>';
                echo '</div>';
                unset($_SESSION['error_message']);
            }
            
            // Sikeres műveletek megjelenítése
            if (isset($_SESSION['success_message'])) {
                echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">';
                echo '<p>' . $_SESSION['success_message'] . '</p>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }
            ?>

            <?php
            // Debug információk
            if (empty($coupons)) {
                echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
                echo "<h3 class='text-lg font-medium text-primary-darkest mb-4'>Debug információk</h3>";
                
                try {
                    // Tábla szerkezet lekérdezése
                    $stmt = $db->prepare("DESCRIBE coupons");
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<div class='mb-4'>";
                    echo "<h4 class='text-md font-medium text-primary-darkest mb-2'>Tábla oszlopok:</h4>";
                    echo "<pre class='bg-gray-100 p-3 rounded'>";
                    print_r($columns);
                    echo "</pre>";
                    echo "</div>";
                    
                    // Adatok lekérdezése
                    $stmt = $db->prepare("SELECT * FROM coupons LIMIT 3");
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($data)) {
                        echo "<div>";
                        echo "<h4 class='text-md font-medium text-primary-darkest mb-2'>Adatok a táblában (max 3):</h4>";
                        echo "<pre class='bg-gray-100 p-3 rounded'>";
                        print_r($data);
                        echo "</pre>";
                        echo "</div>";
                    } else {
                        echo "<div class='bg-yellow-100 p-3 rounded'>";
                        echo "Nincsenek adatok a coupons táblában.";
                        echo "</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='bg-red-100 p-3 rounded'>";
                    echo "Hiba a tábla struktúra lekérdezése során: " . $e->getMessage();
                    echo "</div>";
                }
                
                echo "</div>";
            }
            ?>

            <!-- Szűrők -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Típus</label>
                        <select name="type" class="form-control">
                            <option value="">Összes típus</option>
                            <option value="percentage" <?php echo $type_filter === 'percentage' ? 'selected' : ''; ?>>Százalékos</option>
                            <option value="fixed" <?php echo $type_filter === 'fixed' ? 'selected' : ''; ?>>Fix összeg</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Státusz</label>
                        <select name="status" class="form-control">
                            <option value="">Összes státusz</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktív</option>
                            <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Lejárt</option>
                            <option value="future" <?php echo $status_filter === 'future' ? 'selected' : ''; ?>>Jövőbeli</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Érvényesség</label>
                        <div class="flex space-x-2">
                            <input type="date" name="valid_from" class="form-control" placeholder="Mettől" value="<?php echo htmlspecialchars($valid_from); ?>">
                            <input type="date" name="valid_to" class="form-control" placeholder="Meddig" value="<?php echo htmlspecialchars($valid_to); ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Keresés</label>
                        <input type="text" name="search" class="form-control" placeholder="Kód vagy leírás..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Szűrés
                        </button>
                        <a href="admin_coupons.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Törlés
                        </a>
                    </div>
                </form>
            </div>

            <!-- Kuponok táblázat -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <?php if (empty($coupons)): ?>
                    <div class="p-6 text-center">
                        <p class="text-gray-500 mb-4">Nincsenek kuponok az adatbázisban.</p>
                        <button onclick="showCreateCouponModal()" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            <span class="mt-2">+</span> Új kupon létrehozása
                        </button>
                    </div>
                <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-primary-lightest">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Kód</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Típus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Érték</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Érvényesség</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Min. vásárlás</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Használható</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Felhasználások</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Összes kedvezmény</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Műveletek</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-primary-darkest"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo $coupon['type'] === 'percentage' ? 'Százalékos' : 'Fix összeg'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php 
                                        if ($coupon['type'] === 'percentage') {
                                            echo $coupon['value'] . '%';
                                        } else {
                                            echo number_format($coupon['value'], 0, ',', ' ') . ' Ft';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php 
                                        $from_date = !empty($coupon['valid_from']) ? date('Y.m.d', strtotime($coupon['valid_from'])) : '-';
                                        $to_date = !empty($coupon['valid_to']) ? date('Y.m.d', strtotime($coupon['valid_to'])) : '-';
                                        echo $from_date . ' - ' . $to_date;
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo number_format($coupon['min_purchase'], 0, ',', ' ') . ' Ft'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php 
                                        if ($coupon['usage_limit'] > 0) {
                                            echo $coupon['usage_limit'] . ' alkalommal';
                                        } else {
                                            echo 'Korlátlan';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo $coupon['usage_count']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo number_format($coupon['total_discount'], 0, ',', ' ') . ' Ft'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $now = new DateTime();
                                    
                                    if (!empty($coupon['valid_from']) && !empty($coupon['valid_to'])) {
                                        try {
                                            $start = new DateTime($coupon['valid_from']);
                                            $end = new DateTime($coupon['valid_to']);
                                            
                                            if ($now < $start) {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Közelgő</span>';
                                            } elseif ($now > $end) {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Lejárt</span>';
                                            } else {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktív</span>';
                                            }
                                        } catch (Exception $e) {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Hibás dátum</span>';
                                        }
                                    } else {
                                        echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Nincs dátum</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $now = new DateTime();
                                    
                                    if (!empty($coupon['valid_from']) && !empty($coupon['valid_to'])) {
                                        try {
                                            $start = new DateTime($coupon['valid_from']);
                                            $end = new DateTime($coupon['valid_to']);
                                            
                                            if ($now < $start) {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Közelgő</span>';
                                            } elseif ($now > $end) {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Lejárt</span>';
                                            } else {
                                                echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktív</span>';
                                            }
                                        } catch (Exception $e) {
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Hibás dátum</span>';
                                        }
                                    } else {
                                        echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Nincs dátum</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="toggleEditForm(<?php echo $coupon['id']; ?>)" class="text-primary-light hover:text-primary-dark mr-3">
                                        Szerkesztés
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Biztosan törölni szeretnéd ezt a kupont?');">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <button type="submit" name="delete_coupon" class="text-red-600 hover:text-red-900">
                                            Törlés
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <!-- Szerkesztési űrlap sor -->
                            <tr id="editForm_<?php echo $coupon['id']; ?>" class="hidden">
                                <td colspan="10" class="px-6 py-4 bg-gray-50">
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Kupon kód</label>
                                                <input type="text" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Típus</label>
                                                <select name="type" required class="form-control">
                                                    <option value="percentage" <?php echo $coupon['type'] === 'percentage' ? 'selected' : ''; ?>>Százalékos</option>
                                                    <option value="fixed" <?php echo $coupon['type'] === 'fixed' ? 'selected' : ''; ?>>Fix összeg</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Érték</label>
                                                <input type="number" name="value" value="<?php echo $coupon['value']; ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Minimális vásárlás (Ft)</label>
                                                <input type="number" name="min_purchase" value="<?php echo $coupon['min_purchase']; ?>" min="0" class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Használati korlát (0 = korlátlan)</label>
                                                <input type="number" name="usage_limit" value="<?php echo $coupon['usage_limit']; ?>" min="0" class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Érvényesség kezdete</label>
                                                <input type="datetime-local" name="valid_from" value="<?php echo $coupon['valid_from'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_from'])) : ''; ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Érvényesség vége</label>
                                                <input type="datetime-local" name="valid_to" value="<?php echo $coupon['valid_to'] ? date('Y-m-d\TH:i', strtotime($coupon['valid_to'])) : ''; ?>" required class="form-control">
                                            </div>
                                        </div>
                                        <div class="flex justify-end space-x-3">
                                            <button type="button" onclick="toggleEditForm(<?php echo $coupon['id']; ?>)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                                Mégse
                                            </button>
                                            <button type="submit" name="edit_coupon" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                                                Mentés
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Új kupon modal -->
    <div id="createCouponModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-primary-darkest mb-4">Új kupon létrehozása</h3>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Kupon kód</label>
                        <input type="text" name="code" required class="form-control" placeholder="pl. SUMMER2024">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Típus</label>
                        <select name="type" required class="form-control">
                            <option value="percentage">Százalékos</option>
                            <option value="fixed">Fix összeg</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Érték</label>
                        <input type="number" name="value" required class="form-control" placeholder="pl. 10 vagy 1000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Érvényesség kezdete</label>
                        <input type="date" name="valid_from" required class="form-control">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Érvényesség vége</label>
                        <input type="date" name="valid_to" required class="form-control">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Minimális vásárlás (Ft)</label>
                        <input type="number" name="min_purchase" class="form-control" placeholder="Minimális vásárlás összege" min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Használati korlát (0 = korlátlan)</label>
                        <input type="number" name="usage_limit" class="form-control" placeholder="Hányszor használható" min="0">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideCreateCouponModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Mégse
                        </button>
                        <button type="submit" name="create_coupon" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Létrehozás
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showCreateCouponModal() {
            document.getElementById('createCouponModal').classList.remove('hidden');
        }

        function hideCreateCouponModal() {
            document.getElementById('createCouponModal').classList.add('hidden');
        }

        function toggleEditForm(couponId) {
            const editForm = document.getElementById(`editForm_${couponId}`);
            if (editForm) {
                editForm.classList.toggle('hidden');
            }
        }
    </script>
</body>
</html> 