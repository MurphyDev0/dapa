<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';

// Hibakeresési mód bekapcsolása
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=cart.php');
    exit();
}

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// Ellenőrizzük, hogy van-e kosár adat
if (!isset($_POST['cart_data']) || empty($_POST['cart_data'])) {
    header('Location: cart.php');
    exit();
}

// Kosár adatok dekódolása
$cartData = json_decode($_POST['cart_data'], true);

// Hibakezelés
if (!$cartData || !isset($cartData['items']) || empty($cartData['items'])) {
    header('Location: cart.php');
    exit();
}

// Felhasználói adatok lekérése az adatbázisból
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: cart.php');
    exit();
}

// Rendelés feldolgozása
$orderSuccess = false;
$orderId = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    try {
        $db->beginTransaction();
        
        // Rendelés létrehozása
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $cartData['total']]);
        $order_id = $db->lastInsertId();
        
        // Rendelési tételek hozzáadása
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, size_id, price) 
                            SELECT ?, product_id, quantity, size_id, price FROM cart_items WHERE user_id = ?");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        
        // Kosár kiürítése
        $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        $db->commit();
        
        Notification::add('A rendelés sikeresen leadva!', 'success');
        header("Location: order_history.php");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        Notification::add('Hiba történt a rendelés feldolgozása során: ' . $e->getMessage(), 'failure');
    }
}

// Oldal megjelenítése
$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fizetés - Webshop</title>
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
                        <span id="cart-count" class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">0</span>
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
                    <a href="profile.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-lightest text-primary-dark">Profil</a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Kijelentkezés</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php if ($orderSuccess): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-6 rounded" role="alert">
                <strong class="font-bold">Sikeres rendelés!</strong>
                <span class="block sm:inline"> Köszönjük a vásárlást! Rendelési azonosító: <?php echo $orderId; ?></span>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <svg class="w-24 h-24 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <h2 class="text-2xl font-bold text-primary-darkest mb-2">Rendelését sikeresen feldolgoztuk!</h2>
                <p class="text-primary-dark mb-6">A rendelés részleteiről emailben értesítjük. Köszönjük, hogy nálunk vásárolt!</p>
                <div class="flex flex-col space-y-4 justify-center items-center">
                    <a href="profile_orders.php" class="btn btn-primary">Rendeléseim megtekintése</a>
                    <a href="index.php" class="btn btn-outline">Visszatérés a főoldalra</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-6 rounded" role="alert">
                <strong class="font-bold">Hiba!</strong>
                <span class="block sm:inline"> <?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <h1 class="text-3xl font-bold mb-6 text-primary-darkest">Fizetési adatok</h1>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Fizetési űrlap -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow overflow-hidden p-6">
                        <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Szállítási cím</h2>
                        
                        <form method="post" action="checkout.php" id="checkout-form">
                            <input type="hidden" name="cart_data" value='<?php echo htmlspecialchars($_POST['cart_data']); ?>'>
                            <input type="hidden" name="place_order" value="1">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="name" class="form-label">Teljes név</label>
                                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-control" required>
                                </div>
                                <div>
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-control" required>
                                </div>
                                <div>
                                    <label for="phone" class="form-label">Telefonszám</label>
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-control" required>
                                </div>
                                <div>
                                    <label for="address" class="form-label">Számlázási cím</label>
                                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="form-control" required>
                                </div>
                                <div>
                                    <label for="city" class="form-label">Város (számlázási)</label>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['town'] ?? ''); ?>" class="form-control" required>
                                </div>
                                <div>
                                    <label for="zip" class="form-label">Irányítószám (számlázási)</label>
                                    <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($user['postalCode'] ?? ''); ?>" class="form-control" required>
                                </div>
                            </div>
                            
                            <!-- Szállítási cím opcióval -->
                            <div class="mb-6">
                                <div class="flex items-center mb-3">
                                    <input type="checkbox" id="different-shipping" name="different_shipping" class="mr-2">
                                    <label for="different-shipping">A szállítási cím eltér a számlázási címtől</label>
                                </div>
                            </div>
                            
                            <!-- Szállítási cím mezők (alapértelmezetten rejtett) -->
                            <div id="shipping-address-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 hidden">
                                <div>
                                    <label for="shipping_address" class="form-label">Szállítási cím</label>
                                    <input type="text" id="shipping_address" name="shipping_address" class="form-control">
                                </div>
                                <div>
                                    <label for="shipping_city" class="form-label">Város (szállítási)</label>
                                    <input type="text" id="shipping_city" name="shipping_city" class="form-control">
                                </div>
                                <div>
                                    <label for="shipping_postal_code" class="form-label">Irányítószám (szállítási)</label>
                                    <input type="text" id="shipping_postal_code" name="shipping_postal_code" class="form-control">
                                </div>
                                <div>
                                    <label for="shipping_country" class="form-label">Ország</label>
                                    <select id="shipping_country" name="shipping_country" class="form-control">
                                        <option value="Magyarország" selected>Magyarország</option>
                                        <option value="Ausztria">Ausztria</option>
                                        <option value="Németország">Németország</option>
                                        <option value="Szlovákia">Szlovákia</option>
                                        <option value="Románia">Románia</option>
                                        <option value="Ukrajna">Ukrajna</option>
                                        <option value="Horvátország">Horvátország</option>
                                        <option value="Szerbia">Szerbia</option>
                                        <option value="Szlovénia">Szlovénia</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Kuponkód mező -->
                            <div class="mb-6">
                                <label for="coupon_code" class="form-label">Kuponkód (ha van)</label>
                                <div class="flex">
                                    <input type="text" id="coupon_code" name="coupon_code" class="form-control" placeholder="Adja meg a kuponkódot">
                                    <button type="button" id="apply-coupon" class="btn btn-outline ml-2">Beváltás</button>
                                </div>
                                <div id="coupon-message" class="mt-2 text-sm"></div>
                            </div>
                            
                            <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Fizetési mód</h2>
                            <div class="mb-6">
                                <div class="flex items-center mb-3">
                                    <input type="radio" id="payment-cash" name="payment_method" value="cash" class="mr-2" checked>
                                    <label for="payment-cash">Utánvét</label>
                                </div>
                                <div class="flex items-center mb-3">
                                    <input type="radio" id="payment-card" name="payment_method" value="card" class="mr-2">
                                    <label for="payment-card">Bankkártya</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="payment-transfer" name="payment_method" value="transfer" class="mr-2">
                                    <label for="payment-transfer">Banki átutalás</label>
                                </div>
                            </div>
                            
                            <!-- Bankkártya adatok (opcionális, csak ha bankkártya van kiválasztva) -->
                            <div id="card-details" class="hidden mb-6 p-4 bg-lightblue-50 rounded">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label for="card-number" class="form-label">Kártyaszám</label>
                                        <input type="text" id="card-number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                                    </div>
                                    <div>
                                        <label for="card-name" class="form-label">Kártyabirtokos neve</label>
                                        <input type="text" id="card-name" name="card_name" class="form-control">
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="card-expiry" class="form-label">Lejárati dátum</label>
                                            <input type="text" id="card-expiry" name="card_expiry" class="form-control" placeholder="MM/ÉÉ">
                                        </div>
                                        <div>
                                            <label for="card-cvc" class="form-label">CVC</label>
                                            <input type="text" id="card-cvc" name="card_cvc" class="form-control" placeholder="123">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-8">
                                <button type="submit" class="btn btn-primary w-full py-3 text-center">
                                    Rendelés véglegesítése
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Összesítés -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Rendelés összesítése</h2>
                        
                        <div class="mb-4">
                            <h3 class="font-medium text-primary-dark mb-2">Termékek (<?php echo count($cartData['items']); ?> db)</h3>
                            <div class="space-y-3">
                                <?php foreach($cartData['items'] as $item): ?>
                                <div class="flex justify-between">
                                    <div>
                                        <span class="text-primary-darkest"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="text-sm text-gray-500"> x <?php echo $item['quantity']; ?></span>
                                    </div>
                                    <span class="font-medium"><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> Ft</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="border-t border-primary-lightest pt-4 mt-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-primary-dark">Részösszeg:</span>
                                <span class="font-semibold text-primary-darkest"><?php echo number_format($cartData['subtotal'], 0, ',', ' '); ?> Ft</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-primary-dark">Szállítási díj:</span>
                                <span class="font-semibold text-primary-darkest">
                                    <?php echo $cartData['shipping'] > 0 ? number_format($cartData['shipping'], 0, ',', ' ') . ' Ft' : 'Ingyenes'; ?>
                                </span>
                            </div>
                            <?php if ($cartData['discount'] > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Kedvezmények:</span>
                                <span class="font-semibold"><?php echo number_format($cartData['discount'], 0, ',', ' '); ?> Ft</span>
                            </div>
                            <?php endif; ?>
                            <div class="pt-4 mt-4 border-t border-primary-lightest">
                                <div class="flex justify-between">
                                    <span class="text-lg font-bold text-primary-darkest">Összesen:</span>
                                    <span class="text-lg font-bold text-primary-darkest"><?php echo number_format($cartData['total'], 0, ',', ' '); ?> Ft</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
        
        // Fizetési mód választás kezelése
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'card') {
                    document.getElementById('card-details').classList.remove('hidden');
                } else {
                    document.getElementById('card-details').classList.add('hidden');
                }
            });
        });
        
        // Szállítási cím megjelenítése vagy elrejtése
        document.getElementById('different-shipping').addEventListener('change', function() {
            const shippingFields = document.getElementById('shipping-address-fields');
            if (this.checked) {
                shippingFields.classList.remove('hidden');
                
                // Ha nincs kitöltve a szállítási cím, akkor a számlázási cím adatai kerülnek beállításra
                if (!document.getElementById('shipping_address').value) {
                    document.getElementById('shipping_address').value = document.getElementById('address').value;
                    document.getElementById('shipping_city').value = document.getElementById('city').value;
                    document.getElementById('shipping_postal_code').value = document.getElementById('zip').value;
                }
            } else {
                shippingFields.classList.add('hidden');
            }
        });
        
        // Kuponkód alkalmazása
        document.getElementById('apply-coupon').addEventListener('click', function() {
            const couponCode = document.getElementById('coupon_code').value.trim();
            const couponMessage = document.getElementById('coupon-message');
            
            if (!couponCode) {
                couponMessage.innerHTML = '<span class="text-red-600">Kérjük, adjon meg egy kuponkódot.</span>';
                return;
            }
            
            // AJAX kérés a kuponkód ellenőrzésére (egyszerű példa)
            couponMessage.innerHTML = '<span class="text-primary-dark">Kuponkód ellenőrzése...</span>';
            
            // Itt valójában egy AJAX kérés lenne a szerver felé
            // Jelen implementációban csak szimulálunk egy választ
            setTimeout(function() {
                // Kupon érvényesség ellenőrzése példa logika
                if (couponCode.toUpperCase() === 'TESZT10') {
                    couponMessage.innerHTML = '<span class="text-green-600">Kuponkód sikeresen alkalmazva! 10% kedvezmény.</span>';
                    // Itt állítanánk be a tényleges kedvezményt az összesítésben
                    // updateCartTotal(10);
                } else if (couponCode.toUpperCase() === 'INGYENSZALLITAS') {
                    couponMessage.innerHTML = '<span class="text-green-600">Kuponkód érvényes! Ingyenes szállítás.</span>';
                    // updateShipping(0);
                } else {
                    couponMessage.innerHTML = '<span class="text-red-600">Érvénytelen vagy lejárt kuponkód.</span>';
                }
            }, 1000);
        });
        
        // Ha sikeres a rendelés, töröljük a kosarat
        <?php if ($orderSuccess): ?>
        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem('cart');
            // Kosár számláló frissítése
            document.getElementById('cart-count').textContent = '0';
        }
        <?php endif; ?>
        
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