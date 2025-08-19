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

// Rendelés ID ellenőrzése
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_orders.php');
    exit;
}

$order_id = $_GET['id'] ?? 0;

// Rendelés adatainak lekérése
try {
    $order = $db->prepare("
        SELECT o.*, 
               u.name as customer_name, 
               u.email as customer_email,
               u.phone as customer_phone,
               u.address as shipping_address,
               u.town as shipping_city,
               u.postalCode as shipping_zip
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $order->execute([$order_id]);
    $order = $order->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: admin_orders.php');
        exit;
    }
    
    // Rendelés tételek lekérése
    $items = $db->prepare("
        SELECT oi.*, 
               p.name as product_name,
               s.name as size_name
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN sizes s ON oi.size_id = s.id
        WHERE oi.order_id = ?
    ");
    $items->execute([$order_id]);
    $order_items = $items->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Hiba a rendelés adatainak lekérése során: " . $e->getMessage());
    header('Location: admin_orders.php');
    exit;
}

// Rendelés státuszának módosítása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        Notification::add('A rendelés státusza sikeresen frissítve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a rendelés státuszának frissítése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_order_details.php?id=" . $order_id);
    exit;
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendelés részletei - Admin Panel</title>
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
</head>
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
    <?php echo Notification::display(); ?>
    <!-- Sidebar -->
    <div class="w-full md:w-64 bg-primary-darkest text-white flex flex-col fixed md:h-screen">
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
    <div class="flex-grow md:ml-64">
        <header class="bg-white shadow p-4 sticky top-0 z-10">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold text-primary-darkest">Rendelés részletei</h1>
                <a href="admin_orders.php" class="text-primary-dark hover:text-primary-light">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
            </div>
        </header>

        <main class="p-4">
            <!-- Rendelés adatai -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-primary-darkest mb-4">Rendelő adatai</h3>
                        <div class="space-y-2">
                            <p><span class="font-medium">Név:</span> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                            <p><span class="font-medium">Telefon:</span> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p><span class="font-medium">Szállítási cím:</span> <?php echo htmlspecialchars($order['shipping_address'] . ', ' . $order['shipping_city'] . ' ' . $order['shipping_zip']); ?></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-primary-darkest mb-4">Rendelés adatai</h3>
                        <div class="space-y-2">
                            <p><span class="font-medium">Rendelés azonosító:</span> #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            <p><span class="font-medium">Dátum:</span> <?php echo date('Y.m.d H:i', strtotime($order['created_at'])); ?></p>
                            <p><span class="font-medium">Státusz:</span> 
                                <form method="POST" class="inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="form-control text-sm">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Függőben</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Feldolgozás alatt</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Szállítás alatt</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Kiszállítva</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Törölve</option>
                                    </select>
                                </form>
                            </p>
                            <p><span class="font-medium">Fizetési mód:</span> <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rendelt termékek -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold text-primary-darkest mb-4">Rendelt termékek</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary-lightest">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Termék</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Méret</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Mennyiség</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Ár</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Összesen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-primary-darkest"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-primary-dark"><?php echo htmlspecialchars($item['size_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-primary-darkest"><?php echo $item['quantity']; ?> db</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-primary-darkest"><?php echo number_format($item['product_price'], 0, ',', ' '); ?> Ft</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-primary-darkest"><?php echo number_format($item['product_price'] * $item['quantity'], 0, ',', ' '); ?> Ft</div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-primary-lightest">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-medium">Részösszeg:</td>
                                <td class="px-6 py-4"><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> Ft</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-medium">Szállítási díj:</td>
                                <td class="px-6 py-4"><?php echo number_format($order['shipping_cost'], 0, ',', ' '); ?> Ft</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-medium">Kedvezmény:</td>
                                <td class="px-6 py-4">-<?php echo number_format($order['discount'], 0, ',', ' '); ?> Ft</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-medium">Végösszeg:</td>
                                <td class="px-6 py-4 font-bold"><?php echo number_format($order['total_amount'] + $order['shipping_cost'] - $order['discount'], 0, ',', ' '); ?> Ft</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 