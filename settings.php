<?php
// Munkamenet és hitelesítés ellenőrzése
require_once 'session_config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Adatbázis kapcsolat létrehozása
require_once 'config.php';
require_once 'auth.php';

// Felhasználói adatok lekérése az adatbázisból
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Alapértelmezett beállítások
$defaultSettings = [
    'dark_mode' => 0,
    'notifications' => 1,
    'newsletter' => 1,
    'language' => 'hu',
    'currency' => 'HUF',
    'items_per_page' => 12
];

// Beállítások mentése
$messageType = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Form adatok feldolgozása
    $darkMode = isset($_POST['dark_mode']) ? 1 : 0;
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $language = isset($_POST['language']) ? $_POST['language'] : 'hu';
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'HUF';
    $itemsPerPage = isset($_POST['items_per_page']) ? intval($_POST['items_per_page']) : 12;
    
    // Validálás
    if ($itemsPerPage < 4 || $itemsPerPage > 48) {
        $itemsPerPage = 12;
    }
    
    // Beállítások összeállítása
    $settings = [
        'dark_mode' => $darkMode,
        'notifications' => $notifications,
        'newsletter' => $newsletter,
        'language' => $language,
        'currency' => $currency,
        'items_per_page' => $itemsPerPage
    ];
    
    // Beállítások konvertálása pontosvesszővel elválasztott formátumba
    $settingsString = '';
    foreach ($settings as $key => $value) {
        $settingsString .= $key . '=' . $value . ';';
    }
    $settingsString = rtrim($settingsString, ';');
    
    // Beállítások mentése az adatbázisba
    $updateQuery = "UPDATE users SET settings = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([$settingsString, $userId]);
    
    if ($updateStmt->rowCount() > 0) {
        $messageType = 'success';
        $message = 'A beállítások sikeresen mentve!';
        
        // Frissítjük a session-t is
        $_SESSION['settings'] = $settings;
        
        // Cookie beállítása a dark mode-hoz (30 napig érvényes)
        setcookie('dark_mode', $darkMode, time() + (30 * 24 * 60 * 60), '/');
    } else {
        $messageType = 'error';
        $message = 'Hiba történt a beállítások mentése közben!';
    }
}

// Felhasználó adatainak és beállításainak lekérdezése
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Beállítások feldolgozása
$userSettings = $defaultSettings;
if (!empty($user['settings'])) {
    $settingsParts = explode(';', $user['settings']);
    foreach ($settingsParts as $part) {
        if (strpos($part, '=') !== false) {
            list($key, $value) = explode('=', $part);
            if (array_key_exists($key, $userSettings)) {
                $userSettings[$key] = $value;
            }
        }
    }
}

$monogram = generateMonogram($user['name']);

// Beállítások mentése a munkamenetbe
$_SESSION['settings'] = $userSettings;

// Cookie beállítása, ha még nem létezik
if (!isset($_COOKIE['dark_mode']) && isset($userSettings['dark_mode'])) {
    setcookie('dark_mode', $userSettings['dark_mode'], time() + (30 * 24 * 60 * 60), '/');
}

$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Ha van bejelentkezett felhasználó és vannak beállításai, akkor onnan is lekérhetjük
if (isset($_SESSION['user_id']) && isset($userSettings['dark_mode'])) {
    $darkMode = $userSettings['dark_mode'] == 1;
}

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beállítások</title>
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
<body class="bg-pattern">
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
        <!-- Értesítés doboz -->
        <?php if (!empty($message)): ?>
            <div id="notification" class="mb-6 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        
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
                            <a href="profile_orders.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
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
                            <a href="settings.php" class="flex items-center p-3 bg-primary-lightest text-primary-darkest rounded-lg font-medium">
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
                <h1 class="text-2xl font-bold mb-6 text-primary-darkest">Beállítások</h1>
                
                <form method="POST" action="settings.php">
                    <!-- Megjelenítési beállítások -->
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold mb-4 text-primary-dark">Megjelenítési beállítások</h2>
                        <div class="space-y-4">
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="language" class="form-label">Nyelv</label>
                                    <select id="language" name="language" class="form-control">
                                        <option value="hu" <?php echo $userSettings['language'] === 'hu' ? 'selected' : ''; ?>>Magyar</option>
                                        <option value="en" <?php echo $userSettings['language'] === 'en' ? 'selected' : ''; ?>>English</option>
                                        <option value="de" <?php echo $userSettings['language'] === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="currency" class="form-label">Pénznem</label>
                                    <select id="currency" name="currency" class="form-control">
                                        <option value="HUF" <?php echo $userSettings['currency'] === 'HUF' ? 'selected' : ''; ?>>Forint (HUF)</option>
                                        <option value="EUR" <?php echo $userSettings['currency'] === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                        <option value="USD" <?php echo $userSettings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="items_per_page" class="form-label">Termékek oldalanként</label>
                                    <select id="items_per_page" name="items_per_page" class="form-control">
                                        <option value="8" <?php echo $userSettings['items_per_page'] == 8 ? 'selected' : ''; ?>>8</option>
                                        <option value="12" <?php echo $userSettings['items_per_page'] == 12 ? 'selected' : ''; ?>>12</option>
                                        <option value="24" <?php echo $userSettings['items_per_page'] == 24 ? 'selected' : ''; ?>>24</option>
                                        <option value="36" <?php echo $userSettings['items_per_page'] == 36 ? 'selected' : ''; ?>>36</option>
                                        <option value="48" <?php echo $userSettings['items_per_page'] == 48 ? 'selected' : ''; ?>>48</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Értesítési beállítások -->
                    <div class="mb-10">
                        <h2 class="text-xl font-semibold mb-4 text-primary-dark">Értesítési beállítások</h2>
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="notifications" name="notifications" <?php echo $userSettings['notifications'] == 1 ? 'checked' : ''; ?> class="w-4 h-4 text-primary-dark">
                                <label for="notifications" class="ml-2 text-primary-darkest">Értesítések rendelés státuszáról</label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="newsletter" name="newsletter" <?php echo $userSettings['newsletter'] == 1 ? 'checked' : ''; ?> class="w-4 h-4 text-primary-dark">
                                <label for="newsletter" class="ml-2 text-primary-darkest">Hírlevél feliratkozás</label>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn btn-primary">Beállítások mentése</button>
                    </div>
                </form>
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
    </script>
</body>
</html>