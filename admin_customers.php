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

// Ügyfél törlése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $customer_id = $_POST['customer_id'];
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$customer_id]);
        $_SESSION['success_message'] = "Az ügyfél sikeresen törölve!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Hiba történt az ügyfél törlése során: " . $e->getMessage();
    }
    header("Location: admin_customers.php");
    exit();
}

// Ügyfél szerkesztése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_customer'])) {
    $customer_id = $_POST['customer_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $is_admin = $_POST['is_admin'];
    
    try {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, is_admin = ? WHERE id = ?");
        $stmt->execute([$name, $email, $phone, $address, $is_admin, $customer_id]);
        $_SESSION['success_message'] = "Az ügyfél adatai sikeresen módosítva!";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Hiba történt az ügyfél módosítása során: " . $e->getMessage();
    }
    header("Location: admin_customers.php");
    exit();
}

// Keresés és rendezés paramétereinek kezelése
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$order = $_GET['order'] ?? 'asc';

// SQL lekérdezés összeállítása
$sql = "SELECT u.*, 
               COUNT(DISTINCT o.id) as order_count,
               COUNT(DISTINCT c.id) as coupon_count
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        LEFT JOIN coupons c ON u.id = c.user_id
        WHERE (u.name LIKE :search_name 
           OR u.email LIKE :search_email 
           OR u.phone LIKE :search_phone)
        GROUP BY u.id
        ORDER BY ";

// Rendezés beállítása
switch($sort) {
    case 'email':
        $sql .= 'u.email';
        break;
    case 'phone':
        $sql .= 'u.phone';
        break;
    case 'orders':
        $sql .= 'order_count';
        break;
    case 'coupons':
        $sql .= 'coupon_count';
        break;
    default:
        $sql .= 'u.name';
        break;
}
$sql .= ' ' . ($order === 'desc' ? 'DESC' : 'ASC');

try {
    $stmt = $db->prepare($sql);
    $searchParam = "%$search%";
    $stmt->execute([
        'search_name' => $searchParam,
        'search_email' => $searchParam,
        'search_phone' => $searchParam
    ]);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Hiba történt az ügyfelek lekérdezése során: " . $e->getMessage();
    $customers = [];
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ügyfelek Kezelése - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="new_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
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
                <h1 class="text-xl font-bold text-primary-darkest">Ügyfelek Kezelése</h1>
                    <div class="flex items-center space-x-4">
                    <a href="index.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-14 0l2 2m0 0l7 7-7-7m14 0l-2-2m0 0l-7-7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </header>

            <main class="p-4">
            <!-- Filter & Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-wrap justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-primary-dark">Ügyfelek Keresése</h2>

                </div>
                
                <div class="flex flex-wrap gap-4">
                    <div class="w-full md:w-auto">
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Szűrés</label>
                        <select class="form-control" id="userTypeFilter">
                            <option value="all">Összes ügyfél</option>
                            <option value="admin">Adminisztrátorok</option>
                            <option value="regular">Normál felhasználók</option>
                            <option value="active">Aktív felhasználók</option>
                            <option value="inactive">Inaktív felhasználók</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Rendezés</label>
                        <select class="form-control" id="sortBy">
                            <option value="name">Név szerint</option>
                            <option value="email">Email szerint</option>
                            <option value="date">Regisztráció dátuma szerint</option>
                            <option value="orders">Rendelések száma szerint</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto">
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Keresés</label>
                        <div class="relative">
                            <input type="text" class="form-control pl-10" placeholder="Keresés név vagy email alapján...">
                            <div class="absolute left-3 top-2 text-primary-dark">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

            <!-- Customers Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-primary-lightest">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Felhasználó</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Elérhetőség</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Regisztráció</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Rendelések</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-primary-darkest uppercase tracking-wider">Műveletek</th>
                                </tr>
                            </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($customers as $customer): ?>
                        <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-primary-light rounded-full flex items-center justify-center text-white font-medium">
                                        <?php
                                        // Rövid név generálása monogramként
                                        $name_parts = explode(' ', trim($customer['name']));
                                        $initials = '';
                                        foreach ($name_parts as $part) {
                                            if (!empty($part)) {
                                                $initials .= mb_substr($part, 0, 1, 'UTF-8');
                                            }
                                            if (strlen($initials) >= 2) break;
                                        }
                                        echo htmlspecialchars($initials);
                                        ?>
                                                </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-primary-darkest"><?php echo htmlspecialchars($customer['name']); ?></div>
                                        <div class="text-sm text-primary-dark">ID: <?php echo $customer['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-primary-darkest"><?php echo htmlspecialchars($customer['email']); ?></div>
                                <div class="text-sm text-primary-dark"><?php echo htmlspecialchars($customer['phone'] ?? 'Nincs megadva'); ?></div>
                                    </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-primary-darkest"><?php echo date('Y.m.d', strtotime($customer['created_at'])); ?></div>
                                    </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-primary-darkest"><?php echo $customer['order_count']; ?> rendelés</div>
                                <?php if (isset($customer['total_spent'])): ?>
                                <div class="text-sm text-primary-dark"><?php echo number_format($customer['total_spent'], 0, ',', ' '); ?> Ft</div>
                                <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($customer['is_admin']): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Admin
                                </span>
                                <?php elseif ($customer['order_count'] > 0): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Aktív
                                </span>
                                <?php else: ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inaktív
                                        </span>
                                <?php endif; ?>
                                    </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button class="text-primary-light hover:text-primary-dark mr-3" onclick="viewCustomerDetails(<?php echo $customer['id']; ?>)">
                                    Részletek
                                </button>
                                <button class="text-indigo-600 hover:text-indigo-900 mr-3" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                    Szerkesztés
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Biztosan törli ezt az ügyfelet? Ez a művelet nem vonható vissza!');">
                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                    <input type="hidden" name="delete_customer" value="1">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        Törlés
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Ügyfél részletek sor -->
                        <tr id="detailsForm_<?php echo $customer['id']; ?>" class="hidden">
                            <td colspan="6" class="px-6 py-4 bg-gray-50">
                                <div class="bg-white rounded-lg shadow-sm p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <h3 class="text-lg font-medium text-primary-darkest mb-4">Személyes adatok</h3>
                                            <div class="space-y-2">
                                                <p><span class="font-medium">Név:</span> <span id="customer_name_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Email:</span> <span id="customer_email_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Telefon:</span> <span id="customer_phone_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Cím:</span> <span id="customer_address_<?php echo $customer['id']; ?>"></span></p>
                                            </div>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-medium text-primary-darkest mb-4">Rendelési adatok</h3>
                                            <div class="space-y-2">
                                                <p><span class="font-medium">Regisztráció dátuma:</span> <span id="customer_reg_date_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Rendelések száma:</span> <span id="customer_order_count_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Összes vásárlás:</span> <span id="customer_total_spent_<?php echo $customer['id']; ?>"></span></p>
                                                <p><span class="font-medium">Utolsó rendelés:</span> <span id="customer_last_order_<?php echo $customer['id']; ?>"></span></p>
                                            </div>
                                        </div>
                                        <div class="md:col-span-2">
                                            <h3 class="text-lg font-medium text-primary-darkest mb-4">Rendelések</h3>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead>
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Rendelés szám</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Dátum</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Összeg</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="customer_orders_<?php echo $customer['id']; ?>" class="divide-y divide-gray-200">
                                                        <!-- Itt jelennek meg a rendelések -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end mt-6">
                                        <button type="button" onclick="hideCustomerDetails(<?php echo $customer['id']; ?>)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                            Bezárás
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Ügyfél szerkesztés sor -->
                        <tr id="editForm_<?php echo $customer['id']; ?>" class="hidden">
                            <td colspan="6" class="px-6 py-4 bg-gray-50">
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-primary-darkest mb-1">Név</label>
                                            <input type="text" name="name" id="edit_name_<?php echo $customer['id']; ?>" required class="form-control">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-primary-darkest mb-1">Email</label>
                                            <input type="email" name="email" id="edit_email_<?php echo $customer['id']; ?>" required class="form-control">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-primary-darkest mb-1">Telefon</label>
                                            <input type="tel" name="phone" id="edit_phone_<?php echo $customer['id']; ?>" class="form-control">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-primary-darkest mb-1">Cím</label>
                                            <input type="text" name="address" id="edit_address_<?php echo $customer['id']; ?>" class="form-control">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-primary-darkest mb-1">Admin jogosultság</label>
                                            <select name="is_admin" id="edit_is_admin_<?php echo $customer['id']; ?>" class="form-control">
                                                <option value="0">Nem</option>
                                                <option value="1">Igen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="flex justify-end space-x-3">
                                        <button type="button" onclick="hideEditCustomer(<?php echo $customer['id']; ?>)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                            Mégse
                                        </button>
                                        <button type="submit" name="edit_customer" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                                            Mentés
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                                <?php endforeach; ?>
                        <?php if (count($customers) == 0): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-primary-dark">
                                Nem található ügyfél a megadott feltételekkel.
                            </td>
                        </tr>
                        <?php endif; ?>
                            </tbody>
                        </table>
                
                <!-- Pagination -->
                <div class="px-6 py-4 bg-white border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-primary-dark">
                            Mutatás: 1-<?php echo min(count($customers), 10); ?> / <?php echo count($customers); ?> ügyfél
                        </div>
                        <div class="flex space-x-2">
                            <a href="#" class="px-3 py-1 rounded border border-primary-light text-primary-dark hover:bg-primary-lightest">Előző</a>
                            <a href="#" class="px-3 py-1 rounded border border-primary-light bg-primary-light text-white">1</a>
                            <a href="#" class="px-3 py-1 rounded border border-primary-light text-primary-dark hover:bg-primary-lightest">2</a>
                            <a href="#" class="px-3 py-1 rounded border border-primary-light text-primary-dark hover:bg-primary-lightest">3</a>
                            <a href="#" class="px-3 py-1 rounded border border-primary-light text-primary-dark hover:bg-primary-lightest">Következő</a>
                        </div>
                    </div>
                                                </div>
                                            </div>
            </main>
    </div>

    <script>
    let openDetailsForm = null;
    let openEditForm = null;

    function viewCustomerDetails(customerId) {
        const form = document.getElementById(`detailsForm_${customerId}`);
        
        // Bezárunk minden nyitott űrlapot
        if (openEditForm) {
            openEditForm.classList.add('hidden');
            openEditForm.style.maxHeight = '0';
            openEditForm = null;
        }
        
        // Ha már van nyitott részletek űrlap, bezárjuk
        if (openDetailsForm && openDetailsForm !== form) {
            openDetailsForm.classList.add('hidden');
            openDetailsForm.style.maxHeight = '0';
        }
        
        // AJAX kérés az ügyfél adatainak lekéréséhez
        fetch(`get_customer_details.php?id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                // Adatok beállítása
                document.getElementById(`customer_name_${customerId}`).textContent = data.name;
                document.getElementById(`customer_email_${customerId}`).textContent = data.email;
                document.getElementById(`customer_phone_${customerId}`).textContent = data.phone || 'Nincs megadva';
                document.getElementById(`customer_address_${customerId}`).textContent = data.address || 'Nincs megadva';
                document.getElementById(`customer_reg_date_${customerId}`).textContent = new Date(data.created_at).toLocaleDateString('hu-HU');
                document.getElementById(`customer_order_count_${customerId}`).textContent = data.order_count;
                document.getElementById(`customer_total_spent_${customerId}`).textContent = new Intl.NumberFormat('hu-HU', {
                    style: 'currency',
                    currency: 'HUF'
                }).format(data.total_spent);
                document.getElementById(`customer_last_order_${customerId}`).textContent = data.last_order ? new Date(data.last_order).toLocaleDateString('hu-HU') : 'Nincs rendelés';
                
                // Rendelések táblázat feltöltése
                const ordersTable = document.getElementById(`customer_orders_${customerId}`);
                ordersTable.innerHTML = '';
                if (data.orders && data.orders.length > 0) {
                    data.orders.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap">${order.order_number}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${new Date(order.created_at).toLocaleDateString('hu-HU')}</td>
                            <td class="px-6 py-4 whitespace-nowrap">${new Intl.NumberFormat('hu-HU', {
                                style: 'currency',
                                currency: 'HUF'
                            }).format(order.total_amount)}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold ${getStatusClass(order.status)}">
                                    ${getStatusText(order.status)}
                                </span>
                            </td>
                        `;
                        ordersTable.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="4" class="px-6 py-4 text-center text-gray-500">Nincsenek rendelések</td>';
                    ordersTable.appendChild(row);
                }
                
                // Megjelenítjük a részletek űrlapot
                if (form.classList.contains('hidden')) {
                    form.classList.remove('hidden');
                    form.style.maxHeight = form.scrollHeight + 'px';
                    openDetailsForm = form;
                } else {
                    form.classList.add('hidden');
                    form.style.maxHeight = '0';
                    openDetailsForm = null;
                }
                
                // Görgetés az űrlaphoz
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(error => {
                console.error('Hiba:', error);
                alert('Hiba történt az ügyfél adatainak lekérése során.');
            });
    }

    function hideCustomerDetails(customerId) {
        const form = document.getElementById(`detailsForm_${customerId}`);
        form.classList.add('hidden');
        form.style.maxHeight = '0';
        openDetailsForm = null;
    }

    function editCustomer(customerId) {
        const form = document.getElementById(`editForm_${customerId}`);
        
        // Bezárunk minden nyitott űrlapot
        if (openDetailsForm) {
            openDetailsForm.classList.add('hidden');
            openDetailsForm.style.maxHeight = '0';
            openDetailsForm = null;
        }
        
        // Ha már van nyitott szerkesztés űrlap, bezárjuk
        if (openEditForm && openEditForm !== form) {
            openEditForm.classList.add('hidden');
            openEditForm.style.maxHeight = '0';
        }
        
        // AJAX kérés az ügyfél adatainak lekéréséhez
        fetch(`get_customer_details.php?id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                // Form adatok kitöltése
                document.getElementById(`edit_name_${customerId}`).value = data.name;
                document.getElementById(`edit_email_${customerId}`).value = data.email;
                document.getElementById(`edit_phone_${customerId}`).value = data.phone || '';
                document.getElementById(`edit_address_${customerId}`).value = data.address || '';
                document.getElementById(`edit_is_admin_${customerId}`).value = data.is_admin;
                
                // Megjelenítjük a szerkesztés űrlapot
                if (form.classList.contains('hidden')) {
                    form.classList.remove('hidden');
                    form.style.maxHeight = form.scrollHeight + 'px';
                    openEditForm = form;
                } else {
                    form.classList.add('hidden');
                    form.style.maxHeight = '0';
                    openEditForm = null;
                }
                
                // Görgetés az űrlaphoz
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            })
            .catch(error => {
                console.error('Hiba:', error);
                alert('Hiba történt az ügyfél adatainak lekérése során.');
            });
    }

    function hideEditCustomer(customerId) {
        const form = document.getElementById(`editForm_${customerId}`);
        form.classList.add('hidden');
        form.style.maxHeight = '0';
        openEditForm = null;
    }

    function getStatusClass(status) {
        const statusClasses = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'processing': 'bg-blue-100 text-blue-800',
            'shipped': 'bg-purple-100 text-purple-800',
            'delivered': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return statusClasses[status] || 'bg-gray-100 text-gray-800';
    }

    function getStatusText(status) {
        const statusTexts = {
            'pending': 'Függőben',
            'processing': 'Feldolgozás alatt',
            'shipped': 'Szállítás alatt',
            'delivered': 'Kiszállítva',
            'cancelled': 'Törölve'
        };
        return statusTexts[status] || status;
    }
    </script>

    <style>
    #detailsForm_<?php echo $customer['id']; ?>, #editForm_<?php echo $customer['id']; ?> {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    </style>
</body>
</html> 