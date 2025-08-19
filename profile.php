<?php
require_once 'session_config.php';
require_once 'notifications.php';

// Ellenőrizzük, hogy a felhasználó be van-e jelentkezve
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Átirányítás a bejelentkezési oldalra, ha nincs bejelentkezve
    exit();
}

// Bejelentkezés státuszának beállítása
$is_logged = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;

// Adatbázis kapcsolat betöltése
require_once 'config.php';

// Felhasználói adatok lekérése az adatbázisból
$userId = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    Notification::add('Felhasználó nem található!', 'failure');
    header('Location: index.php');
    exit();
}

// Form beküldés feldolgozása
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Személyes adatok frissítése
    if (isset($_POST['action']) && $_POST['action'] === 'update_personal') {
        $fullName = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        
        if (empty($fullName) || empty($email)) {
            Notification::add('A név és az email kötelező mezők!', 'failure');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::add('Érvénytelen email formátum!', 'failure');
        } else {
            $emailCheckSql = "SELECT id FROM users WHERE email = ? AND id != ?";
            $emailCheckStmt = $db->prepare($emailCheckSql);
            $emailCheckStmt->execute([$email, $userId]);
            $emailCheckResult = $emailCheckStmt->fetch();
            
            if ($emailCheckResult) {
                Notification::add('Ez az email cím már használatban van!', 'failure');
            } else {
                $updateSql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([$fullName, $email, $phone, $userId]);
                
                if ($updateStmt->rowCount() > 0) {
                    Notification::add('Személyes adatok sikeresen frissítve!', 'success');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                } else {
                    Notification::add('Hiba történt a frissítés során!', 'failure');
                }
            }
        }
    }
    
    // Szállítási cím frissítése
    if (isset($_POST['action']) && $_POST['action'] === 'update_address') {
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $zip = $_POST['zip'] ?? '';
        
        if (empty($city) || empty($zip)) {
            Notification::add('A város és az irányítószám kötelező mezők!', 'failure');
        } else {
            $updateSql = "UPDATE users SET address = ?, town = ?, postalCode = ? WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$address, $city, $zip, $userId]);
            
            if ($updateStmt->rowCount() > 0) {
                Notification::add('Szállítási cím sikeresen frissítve!', 'success');
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                Notification::add('Hiba történt a frissítés során!', 'failure');
            }
        }
    }
    
    // Jelszó módosítása
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Notification::add('Minden jelszó mező kitöltése kötelező!', 'failure');
        } elseif (strlen($newPassword) < 8) {
            Notification::add('Az új jelszónak legalább 8 karakter hosszúnak kell lennie!', 'failure');
        } else {
            $passwordSql = "SELECT password FROM users WHERE id = ?";
            $passwordStmt = $db->prepare($passwordSql);
            $passwordStmt->execute([$userId]);
            $userData = $passwordStmt->fetch();
            
            if (!password_verify($currentPassword, $userData['password'])) {
                Notification::add('A jelenlegi jelszó nem megfelelő!', 'failure');
            } elseif ($newPassword !== $confirmPassword) {
                Notification::add('Az új jelszó és a megerősítés nem egyezik!', 'failure');
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePasswordSql = "UPDATE users SET password = ? WHERE id = ?";
                $updatePasswordStmt = $db->prepare($updatePasswordSql);
                $updatePasswordStmt->execute([$hashedPassword, $userId]);
                
                if ($updatePasswordStmt->rowCount() > 0) {
                    Notification::add('Jelszó sikeresen módosítva!', 'success');
                } else {
                    Notification::add('Hiba történt a jelszó módosítása során!', 'failure');
                }
            }
        }
    }
}

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
    <title>Felhasználói Profil</title>
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
        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar Menu -->
            <div class="w-full md:w-64 bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-center md:justify-start mb-8">
                    <!-- Egyszerű kör alakú monogram doboz -->
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
                            <a href="profile.php" class="flex items-center p-3 bg-primary-lightest text-primary-darkest rounded-lg font-medium">
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
                            <a href="#" class="flex items-center p-3 text-primary-darkest hover:bg-primary-lightest rounded-lg font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                <h1 class="text-2xl font-bold mb-6 text-primary-darkest">Profilom</h1>
                
                <!-- Personal Information -->
                <div class="mb-10">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Személyes adatok</h2>
                    <form action="profile.php" method="post" class="grid md:grid-cols-2 gap-6">
                        <input type="hidden" name="action" value="update_personal">
                        <div>
                            <label for="name" class="form-label">Teljes név</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-control">
                        </div>
                        <div>
                            <label for="email" class="form-label">Email cím</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-control">
                        </div>
                        <div>
                            <label for="phone" class="form-label">Telefonszám</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="md:col-span-2 mt-4">
                            <button type="submit" class="btn btn-primary">Mentés</button>
                        </div>
                    </form>
                </div>
                
                <!-- Shipping Address -->
                <div class="mb-10">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Szállítási cím</h2>
                    <form action="profile.php" method="post" class="grid md:grid-cols-2 gap-6">
                        <input type="hidden" name="action" value="update_address">
                        <div class="md:col-span-2">
                            <label for="address" class="form-label">Cím</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" class="form-control">
                        </div>
                        <div>
                            <label for="city" class="form-label">Város</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['town'] ?? ''); ?>" class="form-control">
                        </div>
                        <div>
                            <label for="zip" class="form-label">Irányítószám</label>
                            <input type="text" id="zip" name="zip" value="<?php echo htmlspecialchars($user['postalCode'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="md:col-span-2 mt-4">
                            <button type="submit" class="btn btn-primary">Mentés</button>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div>
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Jelszó módosítása</h2>
                    <form action="profile.php" method="post" class="grid md:grid-cols-2 gap-6">
                        <input type="hidden" name="action" value="update_password">
                        <div class="md:col-span-2">
                            <label for="current_password" class="form-label">Jelenlegi jelszó</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        <div>
                            <label for="new_password" class="form-label">Új jelszó</label>
                            <input type="password" id="new_password" name="new_password" class="form-control">
                        </div>
                        <div>
                            <label for="confirm_password" class="form-label">Új jelszó megerősítése</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                        </div>
                        <div class="md:col-span-2 mt-4">
                            <button type="submit" class="btn btn-primary">Jelszó módosítása</button>
                        </div>
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