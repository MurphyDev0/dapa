<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// Kosár műveletek feldolgozása
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    // Ezt a szervert oldali műveletet csak AJAX-ból használjuk a termék adatainak lekéréséhez
    $productId = intval($_GET['id']);
    $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
    
    // Lekérjük a termék adatait
    $stmt = $db->prepare("SELECT id, name, price, discount_price, image_url FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Visszaküldjük JSON formátumban
        header('Content-Type: application/json');
        echo json_encode($product);
        exit;
    } else {
        // Hiba esetén
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Termék nem található']);
        exit;
    }
}

// Rendelés feldolgozása
$orderSuccess = false;
if (isset($_POST['checkout'])) {
    if (!$is_logged) {
        header('Location: login.php?redirect=cart.php');
        exit;
    }
    
    // Itt lehet implementálni a rendelés feldolgozását
    // ...
    
    $orderSuccess = true;
}

$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Kosár törlése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    try {
        $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        Notification::add('A kosár sikeresen kiürítve!', 'success');
        header("Location: cart.php");
        exit;
    } catch (Exception $e) {
        Notification::add('Hiba történt a kosár kiürítése során: ' . $e->getMessage(), 'failure');
    }
}

// Termék eltávolítása a kosárból
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    try {
        $cart_item_id = $_POST['cart_item_id'];
        $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$cart_item_id, $_SESSION['user_id']])) {
            Notification::add('A termék sikeresen eltávolítva a kosárból!', 'success');
        } else {
            Notification::add('Nem sikerült eltávolítani a terméket a kosárból!', 'failure');
        }
    } catch (Exception $e) {
        Notification::add('Hiba történt a termék eltávolítása során: ' . $e->getMessage(), 'failure');
    }
}

// Mennyiség módosítása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    try {
        $cart_item_id = $_POST['cart_item_id'];
        $quantity = $_POST['quantity'];
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$quantity, $cart_item_id, $_SESSION['user_id']])) {
            Notification::add('A mennyiség sikeresen frissítve!', 'success');
        } else {
            Notification::add('Nem sikerült frissíteni a mennyiséget!', 'failure');
        }
    } catch (Exception $e) {
        Notification::add('Hiba történt a mennyiség frissítése során: ' . $e->getMessage(), 'failure');
    }
}

// Kosár frissítése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if (isset($_SESSION['cart'][$product_id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
    
    Notification::add('A kosár sikeresen frissítve!', 'success');
    header("Location: cart.php");
    exit;
}

// Kupon alkalmazása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = $_POST['coupon_code'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' AND valid_from <= NOW() AND valid_to >= NOW()");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            $_SESSION['coupon'] = $coupon;
            Notification::add('A kupon sikeresen alkalmazva!', 'success');
        } else {
            Notification::add('Érvénytelen vagy lejárt kupon!', 'failure');
        }
    } catch (Exception $e) {
        Notification::add('Hiba történt a kupon ellenőrzése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: cart.php");
    exit;
}

// Kupon eltávolítása
if (isset($_GET['remove_coupon'])) {
    unset($_SESSION['coupon']);
    Notification::add('A kupon eltávolítva!', 'success');
    header("Location: cart.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kosár - Webshop</title>
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
                <span class="block sm:inline"> Köszönjük a vásárlást!</span>
            </div>
        <?php endif; ?>

        <h1 class="text-3xl font-bold mb-6 text-primary-darkest">Kosár</h1>
        
        <div id="cart-empty" class="bg-white rounded-lg shadow p-8 text-center hidden">
            <svg class="w-24 h-24 mx-auto text-primary-lightest mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h2 class="text-2xl font-bold text-primary-darkest mb-2">A kosár üres</h2>
            <p class="text-primary-dark mb-6">Még nem adott hozzá termékeket a kosárhoz.</p>
            <a href="index.php" class="btn btn-primary">Vásárlás folytatása</a>
        </div>
        
        <div id="cart-container" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kosár tartalma -->
            <div class="lg:col-span-2">
                <div id="cart-items" class="bg-white rounded-lg shadow overflow-hidden">
                    <!-- Itt jelennek meg a kosár elemei dinamikusan -->
                </div>
            </div>
            
            <!-- Összesítés -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Rendelés összesítése</h2>
                    
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between">
                            <span class="text-primary-dark">Részösszeg:</span>
                            <span id="subtotal" class="font-semibold text-primary-darkest">0 Ft</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-primary-dark">Szállítási díj:</span>
                            <span id="shipping" class="font-semibold text-primary-darkest">0 Ft</span>
                        </div>
                        <?php if ($is_logged): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Kedvezmények:</span>
                            <span id="discount" class="font-semibold">0 Ft</span>
                        </div>
                        <?php endif; ?>
                        <div class="pt-4 mt-4 border-t border-primary-lightest">
                            <div class="flex justify-between">
                                <span class="text-lg font-bold text-primary-darkest">Összesen:</span>
                                <span id="total" class="text-lg font-bold text-primary-darkest">0 Ft</span>
                            </div>
                        </div>
                    </div>
                    
                    <form id="checkout-form" method="post" action="checkout.php">
                        <?php if (!$is_logged): ?>
                            <div class="mb-4 p-4 bg-primary-lightest rounded text-primary-darkest">
                                A rendeléshez <a href="login.php?redirect=cart.php" class="font-semibold underline">jelentkezz be</a> vagy <a href="register.php" class="font-semibold underline">regisztrálj</a>!
                            </div>
                        <?php else: ?>
                            <!-- Ha be van jelentkezve, akkor megjelenik a rendelés gomb -->
                            <input type="hidden" name="cart_data" id="cart-data">
                            <button type="submit" id="checkout-button" class="btn btn-primary w-full py-3 text-center disabled:opacity-50 disabled:cursor-not-allowed">
                                Tovább a fizetéshez
                            </button>
                        <?php endif; ?>
                    </form>
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
        // Cart management with localStorage
        class Cart {
            constructor() {
                this.items = this.getCartItems();
                this.updateCartDisplay();
            }
            
            // Kosár elemeinek lekérése a localStorage-ból
            getCartItems() {
                const cart = localStorage.getItem('cart');
                return cart ? JSON.parse(cart) : [];
            }
            
            // Kosár tartalmának mentése localStorage-ba
            saveCart() {
                localStorage.setItem('cart', JSON.stringify(this.items));
                this.updateCartDisplay();
            }
            
            // Termék hozzáadása a kosárhoz
            async addItem(productId, quantity = 1) {
                // Ellenőrizzük, hogy a termék már a kosárban van-e
                productId = parseInt(productId);
                const existingItem = this.items.find(item => item.id === productId);
                
                if (existingItem) {
                    // Ha már a kosárban van, növeljük a mennyiséget
                    existingItem.quantity += parseInt(quantity);
                } else {
                    // Ha még nincs a kosárban, lekérjük a termék adatait
                    try {
                        const response = await fetch(`cart.php?action=add&id=${productId}`);
                        const product = await response.json();
                        
                        if (product.error) {
                            console.error('Hiba:', product.error);
                            return false;
                        }
                        
                        // Hozzáadjuk a kosárhoz
                        this.items.push({
                            id: parseInt(product.id),
                            name: product.name,
                            price: product.discount_price || product.price,
                            image: product.image_url,
                            quantity: parseInt(quantity)
                        });
                    } catch (error) {
                        console.error('Hiba:', error);
                        return false;
                    }
                }
                
                this.saveCart();
                return true;
            }
            
            // Termék eltávolítása a kosárból
            removeItem(productId) {
                productId = parseInt(productId);
                this.items = this.items.filter(item => item.id !== productId);
                this.saveCart();
            }
            
            // Termék mennyiségének módosítása
            updateQuantity(productId, quantity) {
                productId = parseInt(productId);
                const item = this.items.find(item => item.id === productId);
                if (item) {
                    item.quantity = parseInt(quantity);
                    // Ha a mennyiség 0 vagy kisebb, töröljük az elemet
                    if (item.quantity <= 0) {
                        this.removeItem(productId);
                    } else {
                        this.saveCart();
                    }
                }
            }
            
            // Kosár kiürítése
            clearCart() {
                this.items = [];
                this.saveCart();
            }
            
            // Kosár tartalmának megjelenítése
            updateCartDisplay() {
                const cartCountElement = document.getElementById('cart-count');
                const cartItemsElement = document.getElementById('cart-items');
                const cartEmptyElement = document.getElementById('cart-empty');
                const cartContainerElement = document.getElementById('cart-container');
                const subtotalElement = document.getElementById('subtotal');
                const shippingElement = document.getElementById('shipping');
                const discountElement = document.getElementById('discount');
                const totalElement = document.getElementById('total');
                const checkoutButton = document.getElementById('checkout-button');
                
                // Kosárban lévő termékek darabszámának megjelenítése
                const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
                cartCountElement.textContent = totalItems;
                
                if (this.items.length === 0) {
                    // Ha üres a kosár
                    if (cartEmptyElement) cartEmptyElement.classList.remove('hidden');
                    if (cartContainerElement) cartContainerElement.classList.add('hidden');
                    if (checkoutButton) checkoutButton.disabled = true;
                    return;
                }
                
                // Kosár tartalmának megjelenítése
                if (cartEmptyElement) cartEmptyElement.classList.add('hidden');
                if (cartContainerElement) cartContainerElement.classList.remove('hidden');
                if (checkoutButton) checkoutButton.disabled = false;
                
                // A kosár adatok hozzáadása a fizetéshez form-hoz
                const cartDataInput = document.getElementById('cart-data');
                if (cartDataInput) {
                    cartDataInput.value = JSON.stringify({
                        items: this.items,
                        subtotal: this.calculateSubtotal(),
                        shipping: (this.calculateSubtotal() > 0 && this.calculateSubtotal() < 15000) ? 1500 : 0,
                        discount: 0,
                        total: this.calculateSubtotal() + ((this.calculateSubtotal() > 0 && this.calculateSubtotal() < 15000) ? 1500 : 0)
                    });
                }
                
                if (cartItemsElement) {
                    cartItemsElement.innerHTML = '';
                    
                    // Kosár elemek megjelenítése
                    this.items.forEach(item => {
                        const itemElement = document.createElement('div');
                        itemElement.className = 'flex items-center p-4 border-b border-primary-lightest';
                        itemElement.innerHTML = `
                            <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded overflow-hidden mr-4">
                                <img src="${item.image || 'img/placeholder.jpg'}" alt="${item.name}" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-primary-darkest">${item.name}</h3>
                                <div class="flex justify-between items-center mt-2">
                                    <div class="flex items-center">
                                        <button class="cart-decrease p-1 rounded-full bg-gray-100" data-id="${item.id}">
                                            <svg class="w-4 h-4 text-primary-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <span class="mx-2 w-8 text-center">${item.quantity}</span>
                                        <button class="cart-increase p-1 rounded-full bg-gray-100" data-id="${item.id}">
                                            <svg class="w-4 h-4 text-primary-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="font-bold text-primary-dark mr-4">${this.formatPrice(item.price * item.quantity)} Ft</span>
                                        <button class="cart-remove text-red-500" data-id="${item.id}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        cartItemsElement.appendChild(itemElement);
                    });
                    
                    // Események hozzáadása a gombokhoz
                    document.querySelectorAll('.cart-decrease').forEach(button => {
                        button.addEventListener('click', () => {
                            const id = parseInt(button.dataset.id);
                            const item = this.items.find(item => item.id === id);
                            if (item && item.quantity > 1) {
                                this.updateQuantity(id, item.quantity - 1);
                            } else {
                                this.removeItem(id);
                            }
                        });
                    });
                    
                    document.querySelectorAll('.cart-increase').forEach(button => {
                        button.addEventListener('click', () => {
                            const id = parseInt(button.dataset.id);
                            const item = this.items.find(item => item.id === id);
                            if (item) {
                                this.updateQuantity(id, item.quantity + 1);
                            }
                        });
                    });
                    
                    document.querySelectorAll('.cart-remove').forEach(button => {
                        button.addEventListener('click', () => {
                            const id = parseInt(button.dataset.id);
                            this.removeItem(id);
                        });
                    });
                }
                
                // Összesítés frissítése
                const subtotal = this.calculateSubtotal();
                let shipping = 0;
                if (subtotal > 0 && subtotal < 15000) {
                    shipping = 1500;  // 15 000 Ft alatt 1500 Ft szállítási díj
                }
                const discount = 0;  // Itt lehetne számolni kedvezményt, ha van
                const total = subtotal + shipping - discount;
                
                if (subtotalElement) subtotalElement.textContent = this.formatPrice(subtotal) + ' Ft';
                if (shippingElement) shippingElement.textContent = shipping > 0 ? this.formatPrice(shipping) + ' Ft' : 'Ingyenes';
                if (discountElement) discountElement.textContent = this.formatPrice(discount) + ' Ft';
                if (totalElement) totalElement.textContent = this.formatPrice(total) + ' Ft';
            }
            
            // Részösszeg számítása
            calculateSubtotal() {
                return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            }
            
            // Ár formázása
            formatPrice(price) {
                return new Intl.NumberFormat('hu-HU').format(price);
            }
        }
        
        // Kosár inicializálása
        const cart = new Cart();
        // Exportáljuk a kosarat globálisan
        window.cart = cart;
        
        // Kosárhoz adás funkció a termékoldalakon való használathoz
        function addToCart(productId, quantity = 1) {
            cart.addItem(productId, quantity).then(success => {
                if (success) {
                    alert('Termék hozzáadva a kosárhoz!');
                }
            });
        }
        
        // Mobile menu toggle
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html> 