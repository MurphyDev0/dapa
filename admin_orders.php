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

// Szűrők kezelése
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Rendelések lekérése az adatbázisból
try {
    $query = "SELECT o.*, 
              u.name as customer_name,
              u.email as customer_email,
              COUNT(oi.id) as item_count,
              GROUP_CONCAT(p.name) as product_names
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              LEFT JOIN products p ON oi.product_id = p.id
              WHERE 1=1";
              
    $params = [];
    
    if ($status_filter !== 'all') {
        $query .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    
    if ($date_from) {
        $query .= " AND o.created_at >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND o.created_at <= ?";
        $params[] = $date_to;
    }
    
    if ($search) {
        $query .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Hiba a rendelések lekérése során: " . $e->getMessage());
    $orders = [];
}

// Rendelés státusz módosítása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        Notification::add('A rendelés státusza sikeresen frissítve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a státusz frissítése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_orders.php");
    exit;
}

// Rendelés törlése
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db->beginTransaction();
        
        // Először töröljük a rendelés tételeit
        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$_GET['delete']]);
        
        // Majd töröljük magát a rendelést
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        
        $db->commit();
        Notification::add('A rendelés sikeresen törölve!', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        Notification::add('Hiba történt a rendelés törlése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_orders.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendelések - Admin Panel</title>
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
                <h1 class="text-xl font-bold text-primary-darkest">Rendelések kezelése</h1>
                </div>
            </header>

            <main class="p-4">
            <!-- Szűrők -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Státusz</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Összes</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Függőben</option>
                            <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Feldolgozás alatt</option>
                            <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Szállítva</option>
                            <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Kézbesítve</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Törölve</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Dátumtól</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Dátumig</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
                        </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Keresés</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rendelés szám, név vagy email" class="form-control">
                        </div>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Szűrés
                        </button>
                    </div>
                    </form>
                </div>

            <!-- Rendelések táblázat -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-primary-lightest">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Rendelés szám</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Vásárló</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Termékek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Összeg</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Dátum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Műveletek</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-primary-darkest">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-dark"><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                                    <div class="text-xs text-primary-dark"><?php echo htmlspecialchars($order['customer_email'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo $order['item_count'] ?? 0; ?> termék</div>
                                    <div class="text-xs text-primary-dark"><?php echo htmlspecialchars($order['product_names'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo number_format($order['total_amount'] ?? 0, 0, ',', ' '); ?> Ft</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo date('Y.m.d H:i', strtotime($order['created_at'] ?? 'now')); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="admin_order_details.php?id=<?php echo $order['id']; ?>" class="text-primary-dark hover:text-primary-light">
                                        <i class="fas fa-eye"></i> Részletek
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Rendelés részletek modal -->
    <div id="orderDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-primary-dark rounded-lg shadow-xl w-full max-w-4xl mx-4">
            <div class="bg-gradient-to-r from-primary to-primary-dark rounded-t-lg p-4">
                <h2 class="text-xl font-semibold text-white">Rendelés részletei</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-primary-darkest mb-4">Rendelő adatai</h3>
                        <div class="space-y-2">
                            <p><span class="font-medium">Név:</span> <span id="order_customer_name"></span></p>
                            <p><span class="font-medium">Email:</span> <span id="order_customer_email"></span></p>
                            <p><span class="font-medium">Telefon:</span> <span id="order_customer_phone"></span></p>
                            <p><span class="font-medium">Szállítási cím:</span> <span id="order_shipping_address"></span></p>
                                            </div>
                                        </div>
                                                <div>
                        <h3 class="text-lg font-medium text-primary-darkest mb-4">Rendelés adatai</h3>
                        <div class="space-y-2">
                            <p><span class="font-medium">Rendelés azonosító:</span> <span id="order_id"></span></p>
                            <p><span class="font-medium">Dátum:</span> <span id="order_date"></span></p>
                            <p><span class="font-medium">Státusz:</span> <span id="order_status"></span></p>
                            <p><span class="font-medium">Fizetési mód:</span> <span id="order_payment_method"></span></p>
                                                </div>
                                            </div>
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-medium text-primary-darkest mb-4">Rendelt termékek</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Termék</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Méret</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Mennyiség</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Ár</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Összesen</th>
                                </tr>
                                </thead>
                                <tbody id="order_items" class="divide-y divide-gray-200">
                                    <!-- Itt jelennek meg a termékek -->
                            </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-right font-medium">Részösszeg:</td>
                                        <td class="px-6 py-4" id="order_subtotal"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-right font-medium">Szállítási díj:</td>
                                        <td class="px-6 py-4" id="order_shipping_cost"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-right font-medium">Kedvezmény:</td>
                                        <td class="px-6 py-4" id="order_discount"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-right font-medium">Végösszeg:</td>
                                        <td class="px-6 py-4 font-bold" id="order_total"></td>
                                    </tr>
                                </tfoot>
                        </table>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button onclick="hideOrderDetailsModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                        Bezárás
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showOrderDetailsModal(order) {
        document.getElementById('order_customer_name').textContent = order.customer_name;
        document.getElementById('order_customer_email').textContent = order.customer_email;
        document.getElementById('order_customer_phone').textContent = order.customer_phone;
        document.getElementById('order_shipping_address').textContent = order.shipping_address;
        document.getElementById('order_id').textContent = order.id;
        document.getElementById('order_date').textContent = new Date(order.created_at).toLocaleString('hu-HU');
        document.getElementById('order_status').textContent = getStatusText(order.status);
        document.getElementById('order_payment_method').textContent = order.payment_method;
        
        // Termékek megjelenítése
        const orderItems = document.getElementById('order_items');
        orderItems.innerHTML = '';
        order.items.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">${item.product_name}</td>
                <td class="px-6 py-4 whitespace-nowrap">${item.size_name}</td>
                <td class="px-6 py-4 whitespace-nowrap">${item.quantity}</td>
                <td class="px-6 py-4 whitespace-nowrap">${formatPrice(item.price)}</td>
                <td class="px-6 py-4 whitespace-nowrap">${formatPrice(item.price * item.quantity)}</td>
            `;
            orderItems.appendChild(row);
        });
        
        document.getElementById('order_subtotal').textContent = formatPrice(order.subtotal);
        document.getElementById('order_shipping_cost').textContent = formatPrice(order.shipping_cost);
        document.getElementById('order_discount').textContent = formatPrice(order.discount);
        document.getElementById('order_total').textContent = formatPrice(order.total);
        
        document.getElementById('orderDetailsModal').classList.remove('hidden');
    }

    function hideOrderDetailsModal() {
        document.getElementById('orderDetailsModal').classList.add('hidden');
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('hu-HU', {
            style: 'currency',
            currency: 'HUF'
        }).format(price);
    }

    function getStatusText(status) {
        const statusMap = {
            'pending': 'Függőben',
            'processing': 'Feldolgozás alatt',
            'shipped': 'Szállítás alatt',
            'delivered': 'Kiszállítva',
            'cancelled': 'Törölve'
        };
        return statusMap[status] || status;
    }
    </script>
</body>
</html>    