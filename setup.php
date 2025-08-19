<?php
// Debug mód ellenőrzése
$debugMode = isset($_GET['debug']) && $_GET['debug'] === 'true';

// Adatbázis kapcsolódási adatok
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'webshop_engine';

// Telepítési lépések
$steps = [
    1 => 'Adatbázis kapcsolat létrehozása',
    2 => 'Adatbázis létrehozása',
    3 => 'Alapvető táblák létrehozása',
    4 => 'Kiegészítő táblák létrehozása',
    5 => 'Tábla módosítások alkalmazása',
    6 => 'Példa adatok beszúrása',
    7 => 'Telepítés befejezése'
];

$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$messages = [];
$success = true;

// Debug információk gyűjtése
$debugInfo = [
    'PHP Információk' => [
        'PHP Verzió' => PHP_VERSION,
        'Szerver Szoftver' => $_SERVER['SERVER_SOFTWARE'],
        'Operációs Rendszer' => PHP_OS,
        'Maximális feltöltési méret' => ini_get('upload_max_filesize'),
        'Maximális post méret' => ini_get('post_max_size'),
        'Memória limit' => ini_get('memory_limit'),
        'Maximális végrehajtási idő' => ini_get('max_execution_time') . ' másodperc',
        'Időzóna' => date_default_timezone_get(),
        'Kódolás' => ini_get('default_charset'),
        'PHP Extensions' => get_loaded_extensions(),
        'PHP SAPI' => php_sapi_name(),
        'PHP Memory Usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
        'PHP Peak Memory' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB',
        'PHP Configuration File' => php_ini_loaded_file(),
        'PHP Display Errors' => ini_get('display_errors') ? 'Igen' : 'Nem',
        'PHP Error Reporting' => ini_get('error_reporting'),
        'PHP Session Status' => session_status(),
        'PHP Session Save Path' => session_save_path()
    ],
    'Adatbázis Információk' => [],
    'Tábla Információk' => [],
    'Fájlrendszer Információk' => [],
    'Rendszer Információk' => [
        'Szerver IP' => $_SERVER['SERVER_ADDR'],
        'Szerver Port' => $_SERVER['SERVER_PORT'],
        'Szerver Protokoll' => $_SERVER['SERVER_PROTOCOL'],
        'Szerver Admin' => $_SERVER['SERVER_ADMIN'] ?? 'Nincs beállítva',
        'Szerver Software' => $_SERVER['SERVER_SOFTWARE'],
        'Szerver Document Root' => $_SERVER['DOCUMENT_ROOT'],
        'Szerver Script Filename' => $_SERVER['SCRIPT_FILENAME'],
        'Szerver Script Name' => $_SERVER['SCRIPT_NAME'],
        'Szerver Request URI' => $_SERVER['REQUEST_URI'],
        'Szerver Request Method' => $_SERVER['REQUEST_METHOD'],
        'Szerver Remote Addr' => $_SERVER['REMOTE_ADDR'],
        'Szerver Remote Port' => $_SERVER['REMOTE_PORT'],
        'Szerver HTTP User Agent' => $_SERVER['HTTP_USER_AGENT'],
        'Szerver HTTP Accept Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Nincs beállítva',
        'Szerver HTTP Accept Encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Nincs beállítva',
        'Szerver HTTP Connection' => $_SERVER['HTTP_CONNECTION'] ?? 'Nincs beállítva'
    ],
    'Biztonsági Információk' => [
        'HTTPS' => isset($_SERVER['HTTPS']) ? 'Igen' : 'Nem',
        'SSL Version' => isset($_SERVER['SSL_PROTOCOL']) ? $_SERVER['SSL_PROTOCOL'] : 'Nincs SSL',
        'SSL Cipher' => isset($_SERVER['SSL_CIPHER']) ? $_SERVER['SSL_CIPHER'] : 'Nincs SSL',
        'SSL Cipher Bits' => isset($_SERVER['SSL_CIPHER_USEKEYSIZE']) ? $_SERVER['SSL_CIPHER_USEKEYSIZE'] : 'Nincs SSL',
        'SSL Session ID' => isset($_SERVER['SSL_SESSION_ID']) ? $_SERVER['SSL_SESSION_ID'] : 'Nincs SSL',
        'SSL Client Certificate' => isset($_SERVER['SSL_CLIENT_CERT']) ? 'Igen' : 'Nem',
        'SSL Client Verify' => isset($_SERVER['SSL_CLIENT_VERIFY']) ? $_SERVER['SSL_CLIENT_VERIFY'] : 'Nincs SSL'
    ]
];

try {
    // 1. lépés: Adatbázis kapcsolat létrehozása
    if ($currentStep >= 1) {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $messages[] = ['type' => 'success', 'text' => 'Adatbázis kapcsolat sikeresen létrejött'];
        
        // Debug: MySQL verzió és további információk lekérése
        $debugInfo['Adatbázis Információk'] = [
            'MySQL Verzió' => $pdo->query('select version()')->fetchColumn(),
            'MySQL Kapcsolat Státusz' => 'Aktív',
            'MySQL Kapcsolat ID' => $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(),
            'MySQL Karakterkészlet' => $pdo->query('SHOW VARIABLES LIKE "character_set_server"')->fetchColumn(),
            'MySQL Collation' => $pdo->query('SHOW VARIABLES LIKE "collation_server"')->fetchColumn(),
            'MySQL Max Connections' => $pdo->query('SHOW VARIABLES LIKE "max_connections"')->fetchColumn(),
            'MySQL Current Connections' => $pdo->query('SHOW STATUS LIKE "Threads_connected"')->fetchColumn(),
            'MySQL Uptime' => $pdo->query('SHOW STATUS LIKE "Uptime"')->fetchColumn() . ' másodperc'
        ];
    }

    // 2. lépés: Adatbázis létrehozása
    if ($currentStep >= 2) {
        $sql = "CREATE DATABASE IF NOT EXISTS $database";
        $pdo->exec($sql);
        $messages[] = ['type' => 'success', 'text' => 'Adatbázis sikeresen létrehozva'];
    }

    // 3. lépés: Alapvető táblák létrehozása
    if ($currentStep >= 3) {
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Alapvető táblák létrehozása a database.sql fájlból
        if (file_exists('database.sql')) {
            $sql = file_get_contents('database.sql');
            $pdo->exec($sql);
        }
        
        $messages[] = ['type' => 'success', 'text' => 'Alapvető táblák sikeresen létrehozva'];
    }

    // 4. lépés: Kiegészítő táblák létrehozása
    if ($currentStep >= 4) {
        // Order statuses tábla
        if (file_exists('create_order_statuses_table.sql')) {
            $sql = file_get_contents('create_order_statuses_table.sql');
            $pdo->exec($sql);
        }
        
        // Wishlist tábla
        if (file_exists('create_wishlist_table.sql')) {
            $sql = file_get_contents('create_wishlist_table.sql');
            $pdo->exec($sql);
        }
        
        // Shop settings tábla - csak akkor hozzuk létre, ha nem létezik
        try {
            $checkSettingsTable = $pdo->query("SELECT 1 FROM shop_settings LIMIT 1");
        } catch(PDOException $ex) {
            if (file_exists('create_settings_table.sql')) {
                $shopSettingsSql = file_get_contents('create_settings_table.sql');
                $pdo->exec($shopSettingsSql);
            }
        }
        
        // Reports táblák létrehozása
        if (file_exists('reports_tables.sql')) {
            $sql = file_get_contents('reports_tables.sql');
            $pdo->exec($sql);
        }
        
        // Order items tábla létrehozása
        if (file_exists('create_order_items_table.sql')) {
            $sql = file_get_contents('create_order_items_table.sql');
            $pdo->exec($sql);
        }
        
        // Kosár tábla létrehozása
        if (file_exists('create_cart_table.sql')) {
            $sql = file_get_contents('create_cart_table.sql');
            $pdo->exec($sql);
        }
        
        // Termék értékelések tábla létrehozása
        if (file_exists('create_product_reviews_table.sql')) {
            $sql = file_get_contents('create_product_reviews_table.sql');
            $pdo->exec($sql);
        }
        
        // Kupon használat tábla létrehozása
        if (file_exists('create_coupon_usage_table.sql')) {
            $sql = file_get_contents('create_coupon_usage_table.sql');
            $pdo->exec($sql);
        }
        
        // Termék képek tábla létrehozása
        if (file_exists('create_product_images_table.sql')) {
            $sql = file_get_contents('create_product_images_table.sql');
            $pdo->exec($sql);
        }
        
        // Értesítések tábla létrehozása
        if (file_exists('create_notifications_table.sql')) {
            $sql = file_get_contents('create_notifications_table.sql');
            $pdo->exec($sql);
        }
        
        // Szállítási zónák tábla létrehozása
        if (file_exists('create_shipping_zones_table.sql')) {
            $sql = file_get_contents('create_shipping_zones_table.sql');
            $pdo->exec($sql);
        }
        
        // Product categories tábla létrehozása (kompatibilitás miatt)
        if (file_exists('create_product_categories_table.sql')) {
            $sql = file_get_contents('create_product_categories_table.sql');
            $pdo->exec($sql);
        }
        
        $messages[] = ['type' => 'success', 'text' => 'Kiegészítő táblák sikeresen létrehozva'];
    }

    // 5. lépés: Tábla módosítások alkalmazása
    if ($currentStep >= 5) {
        // Méret oszlopok hozzáadása
        if (file_exists('add_size_columns.sql')) {
            try {
                $sql = file_get_contents('add_size_columns.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek az oszlopok, nem probléma
            }
        }
        
        // Kép oszlopok hozzáadása
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT NULL");
            $pdo->exec("ALTER TABLE categories ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT NULL");
        } catch(PDOException $e) {
            // Ha már léteznek az oszlopok, nem probléma
        }
        
        // Státusz oszlop hozzáadása
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active'");
        } catch(PDOException $e) {
            // Ha már létezik az oszlop, nem probléma
        }
        
        // Kupon tábla frissítése
        try {
            // Ellenőrizzük a jelenlegi oszlopokat
            $stmt = $pdo->prepare("DESCRIBE coupons");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Szükséges oszlopok hozzáadása
            if (!in_array('value', $columns)) {
                $pdo->exec("ALTER TABLE coupons ADD COLUMN value DECIMAL(10,2) NOT NULL DEFAULT 0");
            }
            if (!in_array('valid_from', $columns)) {
                $pdo->exec("ALTER TABLE coupons ADD COLUMN valid_from DATETIME NULL");
            }
            if (!in_array('valid_to', $columns)) {
                $pdo->exec("ALTER TABLE coupons ADD COLUMN valid_to DATETIME NULL");
            }
            if (!in_array('min_purchase', $columns)) {
                $pdo->exec("ALTER TABLE coupons ADD COLUMN min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0");
            }
            if (!in_array('usage_limit', $columns)) {
                $pdo->exec("ALTER TABLE coupons ADD COLUMN usage_limit INT NOT NULL DEFAULT 0");
            }
        } catch(PDOException $e) {
            // Ha hiba van, nem kritikus
        }
        
        // Külső kulcs ellenőrzések kikapcsolása az adatok beszúrása előtt
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $messages[] = ['type' => 'success', 'text' => 'Tábla módosítások sikeresen alkalmazva'];
    }

    // 6. lépés: Példa adatok beszúrása
    if ($currentStep >= 6) {
        // Teszt felhasználók beszúrása
        if (file_exists('test_users.sql')) {
            try {
                $sql = file_get_contents('test_users.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek a felhasználók, nem probléma
            }
        }
        
        // Teszt rendelések beszúrása
        if (file_exists('test_orders.sql')) {
            try {
                $sql = file_get_contents('test_orders.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek a rendelések, nem probléma
            }
        }
        
        // Teszt rendelési tételek beszúrása (a rendelések után)
        if (file_exists('test_order_items.sql')) {
            try {
                $sql = file_get_contents('test_order_items.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek a rendelési tételek, nem probléma
            }
        }
        
        // Teszt adatok beszúrása (riportokhoz)
        if (file_exists('test_data.sql')) {
            try {
                $sql = file_get_contents('test_data.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek az adatok, nem probléma
            }
        }
        
        // Teszt termék értékelések beszúrása (a felhasználók és termékek után)
        if (file_exists('test_product_reviews.sql')) {
            try {
                $sql = file_get_contents('test_product_reviews.sql');
                $pdo->exec($sql);
            } catch(PDOException $e) {
                // Ha már léteznek az értékelések, nem probléma
            }
        }
        
        // Admin felhasználó létrehozása (ha nem létezik)
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
            $stmt->execute();
            $adminCount = $stmt->fetchColumn();
            
            if ($adminCount == 0) {
                $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email, is_admin) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute(['admin', $adminPassword, 'Rendszergazda', 'admin@webshop.hu', 1]);
            }
        } catch(PDOException $e) {
            // Ha hiba van, nem kritikus
        }
        
        $messages[] = ['type' => 'success', 'text' => 'Példa adatok sikeresen beszúrva'];
    }

    // 7. lépés: Telepítés befejezése
    if ($currentStep >= 7) {
        // Debug: Táblák és további információk lekérése
        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
                $indexes = $pdo->query("SHOW INDEX FROM $table")->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['Tábla Információk'][$table] = [
                    'Sorok száma' => $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn(),
                    'Oszlopok' => array_column($columns, 'Field'),
                    'Oszlop típusok' => array_column($columns, 'Type'),
                    'Indexek' => array_column($indexes, 'Key_name'),
                    'Tábla mérete' => $pdo->query("SELECT data_length + index_length FROM information_schema.tables WHERE table_schema = '$database' AND table_name = '$table'")->fetchColumn() / 1024 . ' KB'
                ];
            }
        } catch(PDOException $e) {
            // Ha hiba van a debug információk lekérésében, nem kritikus
        }
        
        // Külső kulcs ellenőrzések visszakapcsolása
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        $messages[] = ['type' => 'success', 'text' => 'A telepítés sikeresen befejeződött!'];
        $messages[] = ['type' => 'info', 'text' => 'Admin belépés: felhasználónév: admin, jelszó: admin123'];
    }

} catch(PDOException $e) {
    $messages[] = ['type' => 'error', 'text' => 'Hiba történt: ' . $e->getMessage()];
    $success = false;
}

// Debug: Fájlrendszer információk bővítése
$debugInfo['Fájlrendszer Információk'] = [
    'Munkamappa jogosultságok' => substr(sprintf('%o', fileperms('.')), -4),
    'PHP fájlok száma' => count(glob('*.php')),
    'CSS fájlok száma' => count(glob('*.css')),
    'JavaScript fájlok száma' => count(glob('*.js')),
    'Képfájlok száma' => count(glob('images/*.{jpg,jpeg,png,gif}', GLOB_BRACE)),
    'Mappák száma' => count(glob('*/', GLOB_ONLYDIR)),
    'Munkamappa mérete' => formatBytes(getDirectorySize('.')),
    'Munkamappa tartalma' => getDirectoryContents('.'),
    'PHP fájlok listája' => glob('*.php'),
    'CSS fájlok listája' => glob('*.css'),
    'JavaScript fájlok listája' => glob('*.js'),
    'Mappák listája' => glob('*/', GLOB_ONLYDIR)
];

// Segédfüggvények
function getDirectorySize($path) {
    $size = 0;
    $files = glob($path . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $size += filesize($file);
        } else if (is_dir($file)) {
            $size += getDirectorySize($file);
        }
    }
    return $size;
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getDirectoryContents($path) {
    $contents = [];
    $files = glob($path . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            $contents[basename($file)] = 'fájl';
        } else if (is_dir($file)) {
            $contents[basename($file)] = 'mappa';
        }
    }
    return $contents;
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webshop Telepítő</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ade4e5;
            border-top: 5px solid #3ca7aa;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #ade4e5;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        .progress-bar-fill {
            height: 100%;
            background-color: #3ca7aa;
            width: 0%;
            transition: width 0.5s ease-in-out;
        }
        .step-icon {
            transition: all 0.3s ease;
        }
        .step-icon.active {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(60, 167, 170, 0.5);
        }
        :root {
            --primary-light: #ade4e5;
            --primary: #3ca7aa;
            --primary-dark: #00868a;
            --primary-darker: #003f41;
        }
    </style>
</head>
<body class="bg-[#ade4e5]">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-[#003f41]">Webshop Telepítő</h1>
                <a href="?debug=<?php echo $debugMode ? 'false' : 'true'; ?>" 
                   class="bg-[#3ca7aa] text-white px-4 py-2 rounded-lg hover:bg-[#00868a] transition-colors">
                    <?php echo $debugMode ? 'Telepítő' : 'Debug'; ?> mód
                </a>
            </div>

            <?php if (!$debugMode): ?>
                <!-- Telepítési lépések -->
                <div class="mb-8">
                    <div class="flex justify-between mb-4">
                        <?php foreach ($steps as $step => $name): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center step-icon
                                    <?php echo $step <= $currentStep ? 'bg-[#3ca7aa] text-white active' : 'bg-[#ade4e5] text-[#003f41]'; ?>">
                                    <?php echo $step; ?>
                                </div>
                                <?php if ($step < count($steps)): ?>
                                    <div class="w-full h-1 mx-2 
                                        <?php echo $step < $currentStep ? 'bg-[#3ca7aa]' : 'bg-[#ade4e5]'; ?>">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center text-[#003f41]">
                        <?php echo $steps[$currentStep]; ?>
                    </div>

                    <!-- Progress bar -->
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: <?php echo ($currentStep / count($steps)) * 100; ?>%"></div>
                    </div>

                    <!-- Loading spinner -->
                    <?php if ($currentStep < count($steps)): ?>
                        <div class="loading-spinner"></div>
                    <?php endif; ?>
                </div>

                <!-- Üzenetek -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <?php foreach ($messages as $message): ?>
                        <div class="mb-4 p-4 rounded-lg 
                            <?php echo $message['type'] === 'success' ? 'bg-[#ade4e5] text-[#003f41]' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $message['text']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Navigációs gombok -->
                <div class="flex justify-between">
                    <?php if ($currentStep > 1): ?>
                        <a href="?step=<?php echo $currentStep - 1; ?>" 
                           class="bg-[#00868a] text-white px-6 py-2 rounded-lg hover:bg-[#003f41] transition-colors">
                            Vissza
                        </a>
                    <?php endif; ?>

                    <?php if ($currentStep < count($steps) && $success): ?>
                        <a href="?step=<?php echo $currentStep + 1; ?>" 
                           class="bg-[#3ca7aa] text-white px-6 py-2 rounded-lg hover:bg-[#00868a] transition-colors ml-auto">
                            Következő
                        </a>
                    <?php elseif ($currentStep === count($steps)): ?>
                        <a href="index.php" 
                           class="bg-[#3ca7aa] text-white px-6 py-2 rounded-lg hover:bg-[#00868a] transition-colors ml-auto">
                            Vissza a főoldalra
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Debug Információk -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <?php foreach ($debugInfo as $section => $items): ?>
                        <div class="mb-8">
                            <h2 class="text-xl font-bold mb-4 text-[#003f41]"><?php echo $section; ?></h2>
                            <div class="bg-[#ade4e5] rounded-lg p-4">
                                <?php foreach ($items as $key => $value): ?>
                                    <div class="mb-2">
                                        <span class="font-semibold text-[#003f41]"><?php echo $key; ?>:</span>
                                        <?php if (is_array($value)): ?>
                                            <pre class="mt-1 text-sm text-[#003f41]"><?php print_r($value); ?></pre>
                                        <?php else: ?>
                                            <span class="text-[#003f41]"><?php echo $value; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 