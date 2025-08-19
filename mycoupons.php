<?php
// Munkamenet és hitelesítés ellenőrzése
require_once 'session_config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// Adatbázis kapcsolat létrehozása
require_once 'config.php';

// Felhasználói adatok lekérése az adatbázisból
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user) {
    // Felhasználó található
} else {
    echo "Felhasználó nem található!";
    exit();
}

// Kuponok lekérdezése az új adatbázis struktúra alapján
$query = "SELECT id, code, is_active, type, created_at 
          FROM coupons 
          WHERE user_id = ? 
          ORDER BY created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$kuponok = $stmt->fetchAll();

$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Ha van bejelentkezett felhasználó és vannak beállításai, akkor onnan is lekérhetjük
if (isset($_SESSION['user_id']) && isset($userSettings['dark_mode'])) {
    $darkMode = $userSettings['dark_mode'] == 1;
}

$monogram = generateMonogram($user['name']);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuponjaim</title>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </a>
                        <a href="logout.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
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
                            <a href="profile_orders.php" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Rendeléseim
                            </a>
                        </li>
                        <li>
                            <a href="mycoupons.php" class="flex items-center p-3 bg-primary-lightest text-primary-darkest rounded-lg font-medium">
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
                <h1 class="text-2xl font-bold mb-6 text-primary-darkest">Kuponjaim</h1>
                
                <!-- Értesítés doboz -->
                <div id="notification" class="hidden mb-6 p-4 rounded"></div>
                
                <?php if (empty($kuponok)): ?>
                    <div class="bg-primary-lightest p-6 rounded-lg text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-primary-dark mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        <h2 class="text-xl font-semibold text-primary-dark mb-2">Jelenleg nincs aktív kuponod</h2>
                        <p class="text-primary-darkest mb-4">A rendeléseid és akciók során szerezhetsz kuponokat, melyeket itt fogsz látni.</p>
                        <a href="index.php" class="btn btn-primary inline-block">Vásárlás most</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($kuponok as $kupon): ?>
                            <?php 
                                // Kupon típusához megfelelő színek meghatározása
                                $cardClass = "border-l-4 rounded-lg shadow-sm p-4 ";
                                $headerClass = "font-bold text-lg ";
                                
                                if (!$kupon['is_active']) {
                                    $cardClass .= "bg-gray-50 border-gray-300";
                                    $headerClass .= "text-gray-500";
                                } else {
                                    switch ($kupon['type']) {
                                        case 'fixed':
                                            $cardClass .= "bg-primary-lightest border-primary-dark";
                                            $headerClass .= "text-primary-dark";
                                            break;
                                        case 'shipping':
                                            $cardClass .= "bg-green-50 border-green-500";
                                            $headerClass .= "text-green-700";
                                            break;
                                        case 'special':
                                            $cardClass .= "bg-yellow-50 border-yellow-500";
                                            $headerClass .= "text-yellow-700";
                                            break;
                                        default: // 'percentage'
                                            $cardClass .= "bg-primary-lightest border-primary-dark";
                                            $headerClass .= "text-primary-dark";
                                    }
                                }

                                // Típus szerinti megjelenítés
                                $typeDescription = '';
                                switch ($kupon['type']) {
                                    case 'fixed':
                                        $typeDescription = 'Fix összegű kedvezmény';
                                        break;
                                    case 'shipping':
                                        $typeDescription = 'Ingyenes szállítás';
                                        break;
                                    case 'special':
                                        $typeDescription = 'Speciális ajánlat';
                                        break;
                                    default: // 'percentage'
                                        $typeDescription = 'Százalékos kedvezmény';
                                }

                                // Létrehozás dátuma
                                $created_at = new DateTime($kupon['created_at']);
                            ?>
                            <div class="<?php echo $cardClass; ?>">
                                <div class="flex justify-between items-start">
                                    <h3 class="<?php echo $headerClass; ?>"><?php echo htmlspecialchars($typeDescription); ?></h3>
                                    <?php if (!$kupon['is_active']): ?>
                                        <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded">Inaktív</span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Aktív</span>
                                    <?php endif; ?>
                                </div>
                                <div class="my-2 text-gray-600">
                                    <p>Kupon típusa: <?php echo htmlspecialchars($typeDescription); ?></p>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="flex justify-between items-center">
                                        <div class="bg-gray-100 px-3 py-1 rounded font-mono text-sm"><?php echo htmlspecialchars($kupon['code']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <span>Létrehozva: <?php echo $created_at->format('Y.m.d'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>