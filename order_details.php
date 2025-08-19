<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// Üzenetek kezelése
$notification = '';
$notificationType = '';

// Ellenőrizzük, hogy van-e megadva rendelés azonosító
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profile_orders.php');
    exit();
}

$orderId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Rendelés lekérdezése - csak a felhasználó saját rendelése (vagy admin esetén bármelyik)
$orderQuery = "SELECT o.*, 
             (SELECT status_name FROM order_statuses WHERE id = o.status) as status_name,
             (SELECT status_color FROM order_statuses WHERE id = o.status) as status_color
             FROM orders o 
             WHERE o.id = ? AND (o.user_id = ? OR ? = 1)";
$orderStmt = $db->prepare($orderQuery);
$orderStmt->execute([$orderId, $userId, $is_admin]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Ha nem találta a rendelést vagy nincs jogosultság
if (!$order) {
    header('Location: profile_orders.php');
    exit();
}

// Rendelési tételek lekérdezése
$itemsQuery = "SELECT oi.*, p.name as product_name
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = ?";
$itemsStmt = $db->prepare($itemsQuery);
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Rendelés dátuma
$dateField = null;
if (isset($order['order_date']) && !empty($order['order_date'])) {
    $dateField = $order['order_date'];
} else if (isset($order['created_at']) && !empty($order['created_at'])) {
    $dateField = $order['created_at'];
} else {
    $dateField = date('Y-m-d H:i:s');
}
$orderDate = new DateTime($dateField);

// Felhasználói adatok lekérése az adatbázisból
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "Felhasználó nem található!";
    exit();
}

$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Ha van bejelentkezett felhasználó és vannak beállításai, akkor onnan is lekérhetjük
if (isset($_SESSION['user_id']) && isset($userSettings['dark_mode'])) {
    $darkMode = $userSettings['dark_mode'] == 1;
}

$monogram = generateMonogram($user['name']);

// Státusz szín osztály meghatározása
$statusColor = !empty($order['status_color']) ? $order['status_color'] : 'gray';
$statusClass = '';
switch($statusColor) {
    case 'green':
        $statusClass = 'bg-green-100 text-green-800';
        break;
    case 'blue':
        $statusClass = 'bg-blue-100 text-blue-800';
        break;
    case 'red':
        $statusClass = 'bg-red-100 text-red-800';
        break;
    case 'yellow':
        $statusClass = 'bg-yellow-100 text-yellow-800';
        break;
    default:
        $statusClass = 'bg-gray-100 text-gray-800';
}

// Végösszeg számítása
$totalPrice = 0;
foreach ($orderItems as $item) {
    $totalPrice += $item['quantity'] * $item['price'];
}

// Rendelés státuszának frissítése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $orderId]);
        
        Notification::add('A rendelés státusza sikeresen frissítve!', 'success');
        header("Location: order_details.php?id=" . $orderId);
        exit;
    } catch (Exception $e) {
        Notification::add('Hiba történt a státusz frissítése során: ' . $e->getMessage(), 'failure');
    }
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendelés részletei - #<?php echo $orderId; ?></title>
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
                        },
                        lightblue: {
                            50: '#f0f7fa',
                            100: '#e1eef5',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pattern">
    <?php echo Notification::display(); ?>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-primary-darkest">Webshop</h1>
                    </div>
                    <div class="hidden md:block ml-10">
                        <div class="flex items-center space-x-4">
                            <a href="index.php" class="nav-link">Kezdőlap</a>
                            <a href="#" class="nav-link">Termékek</a>
                            <a href="#" class="nav-link">Kategóriák</a>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (!$is_logged): ?>
                        <a href="login.php" class="btn btn-outline">Bejelentkezés</a>
                        <a href="register.php" class="btn btn-primary">Regisztráció</a>
                    <?php else: ?>
                        <?php if ($is_admin == 1): ?>
                        <!-- Admin ikon -->
                        <a href="admin.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <a href="profile.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </a>
                        <a href="logout.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </a>
                    <?php endif; ?>
                    <a href="cart.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300 relative">
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">0</span>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button p-2 rounded-md text-primary-dark hover:text-primary-light focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path class="mobile-menu-icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div class="hidden mobile-menu md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 border-t border-primary-lightest">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Kezdőlap</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Termékek</a>
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Kategóriák</a>
                
                <?php if (!$is_logged): ?>
                    <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Bejelentkezés</a>
                    <a href="register.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-light text-white">Regisztráció</a>
                <?php else: ?>
                    <a href="profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Profil</a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Kijelentkezés</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Menu -->
            <div class="w-full md:w-64 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-center md:justify-start mb-8">
                    <div style="width: 64px; height: 64px; background-color: #00868a; border-radius: 32px; display: table; text-align: center">
                        <span style="display: table-cell; vertical-align: middle; color: white; font-weight: bold; font-size: 24px;"><?php echo htmlspecialchars($monogram); ?></span>
                    </div>
                    <div class="ml-4">
                        <h2 class="font-bold text-xl text-primary-darkest"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h2>
                        <p class="text-primary-dark text-sm"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    </div>
                </div>
                
                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="profile.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Profilom
                            </a>
                        </li>
                        <li>
                            <a href="profile_orders.php" class="flex items-center p-3 bg-primary-lightest text-primary-darkest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Rendeléseim
                            </a>
                        </li>
                        <li>
                            <a href="mycoupons.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                                Kuponjaim
                            </a>
                        </li>
                        <li>
                            <a href="profile_wishlist.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                </svg>
                                Kívánságlistám
                            </a>
                        </li>
                        <li>
                            <a href="settings.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Beállítások
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" class="flex items-center p-3 text-red-600 hover:bg-red-50 rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Kilépés
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1 bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-primary-darkest">Rendelés részletei</h1>
                        <p class="text-primary-dark">Rendelés #<?php echo $orderId; ?></p>
                    </div>
                    <a href="profile_orders.php" class="btn btn-outline-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Vissza a rendelésekhez
                    </a>
                </div>
                
                <?php if (!empty($notification)): ?>
                    <div id="notification" class="mb-6 p-4 <?php echo $notificationType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded">
                        <p><?php echo htmlspecialchars($notification); ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Rendelés állapota -->
                <div class="mb-6 p-4 bg-primary-lightest rounded-lg">
                    <div class="flex flex-wrap justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold text-primary-darkest mb-1">Rendelési információk</h2>
                            <p class="text-primary-dark text-sm">Dátum: <?php echo $orderDate->format('Y.m.d. H:i'); ?></p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <span class="px-3 py-1.5 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($order['status_name'] ?? 'Folyamatban'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Rendelési tételek -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-primary-darkest mb-4">Rendelt termékek</h2>
                    
                    <div class="overflow-hidden border border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Termék</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mennyiség</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ár</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Összesen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded">
                                                <div class="h-10 w-10 rounded flex items-center justify-center text-gray-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-primary-darkest">
                                                    <?php echo htmlspecialchars($item['product_name'] ?? 'Ismeretlen termék'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    ID: <?php echo $item['product_id']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-primary-dark"><?php echo $item['quantity']; ?> db</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-primary-dark"><?php echo number_format($item['price'], 0, ',', ' '); ?> Ft</div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="text-sm font-medium text-primary-darkest"><?php echo number_format($item['quantity'] * $item['price'], 0, ',', ' '); ?> Ft</div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-500">Végösszeg:</td>
                                    <td class="px-6 py-4 text-right text-base font-bold text-primary-darkest"><?php echo number_format($totalPrice, 0, ',', ' '); ?> Ft</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Szállítási és fizetési információk -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Szállítási adatok -->
                    <div>
                        <h2 class="text-lg font-semibold text-primary-darkest mb-3">Szállítási információk</h2>
                        <div class="bg-gray-50 p-4 rounded border border-gray-200">
                            <p class="mb-2 font-medium text-primary-darkest"><?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
                            <p class="text-sm text-primary-dark mb-1">
                                <?php echo htmlspecialchars($order['shipping_address'] ?? $order['address'] ?? ''); ?>
                            </p>
                            <p class="text-sm text-primary-dark mb-1">
                                <?php echo htmlspecialchars($order['shipping_postal_code'] ?? ''); ?> 
                                <?php echo htmlspecialchars($order['shipping_city'] ?? ''); ?>, 
                                <?php echo htmlspecialchars($order['shipping_country'] ?? 'Magyarország'); ?>
                            </p>
                            <p class="text-sm text-primary-dark mb-1">
                                Tel: <?php echo htmlspecialchars($order['phone'] ?? $user['phone'] ?? ''); ?>
                            </p>
                            <p class="text-sm text-primary-dark">
                                Email: <?php echo htmlspecialchars($order['email'] ?? $user['email'] ?? ''); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Fizetési adatok -->
                    <div>
                        <h2 class="text-lg font-semibold text-primary-darkest mb-3">Fizetési információk</h2>
                        <div class="bg-gray-50 p-4 rounded border border-gray-200">
                            <p class="text-sm text-primary-dark mb-2">
                                <span class="font-medium">Fizetési mód:</span> 
                                <?php 
                                    $paymentMethod = $order['payment_method'] ?? '';
                                    switch($paymentMethod) {
                                        case 'card':
                                            echo 'Bankkártya';
                                            break;
                                        case 'transfer':
                                            echo 'Banki átutalás';
                                            break;
                                        case 'cash':
                                        default:
                                            echo 'Utánvét';
                                    }
                                ?>
                            </p>
                            <?php if (!empty($order['coupon_code'])): ?>
                            <p class="text-sm text-green-600 mb-2">
                                <span class="font-medium">Felhasznált kupon:</span> <?php echo htmlspecialchars($order['coupon_code']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex justify-between mb-1 text-sm">
                                    <span class="text-primary-dark">Részösszeg:</span>
                                    <span class="text-primary-darkest"><?php echo number_format($totalPrice, 0, ',', ' '); ?> Ft</span>
                                </div>
                                <div class="flex justify-between mb-1 text-sm">
                                    <span class="text-primary-dark">Szállítási díj:</span>
                                    <span class="text-primary-darkest"><?php echo isset($order['shipping_fee']) ? (number_format($order['shipping_fee'], 0, ',', ' ') . ' Ft') : 'Ingyenes'; ?></span>
                                </div>
                                <?php if (isset($order['discount']) && $order['discount'] > 0): ?>
                                <div class="flex justify-between mb-1 text-sm text-green-600">
                                    <span>Kedvezmény:</span>
                                    <span><?php echo number_format($order['discount'], 0, ',', ' '); ?> Ft</span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between mt-2 pt-2 border-t border-gray-200">
                                    <span class="font-bold text-primary-darkest">Összesen:</span>
                                    <span class="font-bold text-primary-darkest">
                                        <?php 
                                        $finalTotal = $totalPrice;
                                        if (isset($order['shipping_fee'])) $finalTotal += $order['shipping_fee'];
                                        if (isset($order['discount'])) $finalTotal -= $order['discount'];
                                        echo number_format($finalTotal, 0, ',', ' '); ?> Ft
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="footer-title text-lg">Webshop</h3>
                    <p class="text-sm opacity-75 mt-2">
                        A legjobb webshop az Ön igényeire szabva. Kiváló minőségű termékek, gyors kiszállítás.
                    </p>
                </div>
                
                <div>
                    <h3 class="footer-title">Kategóriák</h3>
                    <a href="#" class="footer-link">Akciós termékek</a>
                    <a href="#" class="footer-link">Újdonságok</a>
                    <a href="#" class="footer-link">Legkelendőbb termékek</a>
                </div>
                
                <div>
                    <h3 class="footer-title">Információk</h3>
                    <a href="#" class="footer-link">Rólunk</a>
                    <a href="#" class="footer-link">Kapcsolat</a>
                    <a href="#" class="footer-link">GYIK</a>
                    <a href="#" class="footer-link">Szállítási információk</a>
                </div>
                
                <div>
                    <h3 class="footer-title">Fiók</h3>
                    <a href="#" class="footer-link">Bejelentkezés</a>
                    <a href="#" class="footer-link">Regisztráció</a>
                    <a href="#" class="footer-link">Rendeléseim</a>
                    <a href="#" class="footer-link">Kosár</a>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-primary-dark text-center text-sm opacity-75">
                <p>&copy; 2023 Webshop. Minden jog fenntartva.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
        
        // Automatikus értesítés eltüntetése
        setTimeout(function() {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.style.display = 'none';
            }
        }, 5000);
        
        // Kosár számláló frissítése az oldal betöltésekor
        document.addEventListener('DOMContentLoaded', function() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                const cart = localStorage.getItem('cart');
                const items = cart ? JSON.parse(cart) : [];
                const totalItems = items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
                cartCount.textContent = totalItems;
            }
        });
    </script>
</body>
</html> 