<?php

require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'settings_functions.php';
require_once 'notifications.php';

// Ellenőrizzük, hogy admin jogosultsággal rendelkezik-e
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    Notification::add('Nincs jogosultsága az admin panel eléréséhez!', 'failure');
    header('Location: login.php');
    exit;
}

// Ellenőrizzük, hogy létezik-e a shop_settings tábla, ha nem, létrehozzuk
function ensureSettingsTable() {
    global $db;
    try {
        // Ellenőrizzük a tábla létezését
        $tableExists = $db->query("SHOW TABLES LIKE 'shop_settings'")->rowCount() > 0;
        
        if (!$tableExists) {
            // Tábla létrehozása
            $sql = "CREATE TABLE shop_settings (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                settings_json LONGTEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $db->exec($sql);
            return true;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Hiba a beállítások tábla ellenőrzésekor: " . $e->getMessage());
        return false;
    }
}

// Beállítások tábla ellenőrzése
if (!ensureSettingsTable()) {
    $error_message = "Nem sikerült létrehozni vagy ellenőrizni a beállítások táblát.";
}

// Beállítások mentése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $db->beginTransaction();
        
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $db->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
            $stmt->execute([$value, $key]);
        }
        
        $db->commit();
        Notification::add('A beállítások sikeresen mentve!', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        Notification::add('Hiba történt a beállítások mentése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_settings.php");
    exit;
}

// Jelenlegi beállítások betöltése
$currentSettings = loadSettings();

// Listák betöltése
$timezones = getTimezones();
$languages = getLanguages();
$currencies = getCurrencies();
$paymentMethods = getPaymentMethods();
$shippingMethods = getShippingMethods();
$paymentGateways = getPaymentGateways();
$weightUnits = getWeightUnits();
$sizeUnits = getSizeUnits();

// Árfolyamok lekérése
$currencyRates = getCurrencyRates();

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webshop Beállítások</title>
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
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
    <?php echo Notification::display(); ?>
    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-primary-darkest">Webshop Beállítások</h1>
                <a href="admin.php" class="btn btn-primary flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Vissza a vezérlőpulthoz
                </a>
            </div>
            
            <form method="POST" action="admin_settings.php" class="space-y-6">
                <!-- Alapvető beállítások -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Alapvető beállítások</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Alapértelmezett nyelv</label>
                            <select name="default_language" class="form-control">
                                <?php foreach ($languages as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($currentSettings['basic']['default_language']) && $currentSettings['basic']['default_language'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Alapértelmezett pénznem</label>
                            <select name="default_currency" class="form-control">
                                <?php foreach ($currencies as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($currentSettings['basic']['default_currency']) && $currentSettings['basic']['default_currency'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars("$code - $name"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Időzóna</label>
                            <select name="timezone" class="form-control">
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?php echo $tz; ?>" <?php echo isset($currentSettings['basic']['timezone']) && $currentSettings['basic']['timezone'] === $tz ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tz); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Cég információk -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Cég információk</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Cégnév</label>
                            <input type="text" name="company_name" value="<?php echo isset($currentSettings['company']['name']) ? htmlspecialchars($currentSettings['company']['name']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Adószám</label>
                            <input type="text" name="tax_number" value="<?php echo isset($currentSettings['company']['tax_number']) ? htmlspecialchars($currentSettings['company']['tax_number']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Info Email</label>
                            <input type="email" name="info_email" value="<?php echo isset($currentSettings['company']['email']['info']) ? htmlspecialchars($currentSettings['company']['email']['info']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Support Email</label>
                            <input type="email" name="support_email" value="<?php echo isset($currentSettings['company']['email']['support']) ? htmlspecialchars($currentSettings['company']['email']['support']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Sales Email</label>
                            <input type="email" name="sales_email" value="<?php echo isset($currentSettings['company']['email']['sales']) ? htmlspecialchars($currentSettings['company']['email']['sales']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Postai cím</label>
                            <input type="text" name="address" value="<?php echo isset($currentSettings['company']['address']) ? htmlspecialchars($currentSettings['company']['address']) : ''; ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Közösségi média -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Közösségi média</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Facebook</label>
                            <input type="url" name="facebook" value="<?php echo isset($currentSettings['social_media']['facebook']) ? htmlspecialchars($currentSettings['social_media']['facebook']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Instagram</label>
                            <input type="url" name="instagram" value="<?php echo isset($currentSettings['social_media']['instagram']) ? htmlspecialchars($currentSettings['social_media']['instagram']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Twitter</label>
                            <input type="url" name="twitter" value="<?php echo isset($currentSettings['social_media']['twitter']) ? htmlspecialchars($currentSettings['social_media']['twitter']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo isset($currentSettings['social_media']['linkedin']) ? htmlspecialchars($currentSettings['social_media']['linkedin']) : ''; ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Fizetési beállítások -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Fizetési beállítások</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Fizetési módok</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="payment_methods[]" value="cash" <?php echo isset($currentSettings['payment']['methods']) && is_array($currentSettings['payment']['methods']) && in_array('cash', $currentSettings['payment']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Készpénz
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="payment_methods[]" value="card" <?php echo isset($currentSettings['payment']['methods']) && is_array($currentSettings['payment']['methods']) && in_array('card', $currentSettings['payment']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Bankkártya
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="payment_methods[]" value="transfer" <?php echo isset($currentSettings['payment']['methods']) && is_array($currentSettings['payment']['methods']) && in_array('transfer', $currentSettings['payment']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Átutalás
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Payment Gateway</label>
                            <select name="payment_gateway" class="form-control">
                                <option value="">Válassz...</option>
                                <?php foreach ($paymentGateways as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($currentSettings['payment']['gateway']) && $currentSettings['payment']['gateway'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Alapértelmezett ÁFA kulcs (%)</label>
                            <input type="number" name="default_vat_rate" value="<?php echo isset($currentSettings['payment']['vat']['default_rate']) ? htmlspecialchars($currentSettings['payment']['vat']['default_rate']) : '27'; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="flex items-center mt-8">
                                <input type="checkbox" name="eu_vat" <?php echo isset($currentSettings['payment']['vat']['eu_vat']) && $currentSettings['payment']['vat']['eu_vat'] ? 'checked' : ''; ?> class="mr-2">
                                EU ÁFA kezelés engedélyezése
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Szállítási beállítások -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Szállítási beállítások</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Szállítási módok</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="shipping_methods[]" value="personal" <?php echo isset($currentSettings['shipping']['methods']) && is_array($currentSettings['shipping']['methods']) && in_array('personal', $currentSettings['shipping']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Személyes átvétel
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="shipping_methods[]" value="courier" <?php echo isset($currentSettings['shipping']['methods']) && is_array($currentSettings['shipping']['methods']) && in_array('courier', $currentSettings['shipping']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Futárszolgálat
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="shipping_methods[]" value="post" <?php echo isset($currentSettings['shipping']['methods']) && is_array($currentSettings['shipping']['methods']) && in_array('post', $currentSettings['shipping']['methods']) ? 'checked' : ''; ?> class="mr-2">
                                    Postai kézbesítés
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Ingyenes szállítás limit (Ft)</label>
                            <input type="number" name="free_shipping_limit" value="<?php echo isset($currentSettings['shipping']['free_shipping_limit']) ? htmlspecialchars($currentSettings['shipping']['free_shipping_limit']) : '0'; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Súly mértékegység</label>
                            <select name="weight_unit" class="form-control">
                                <?php foreach ($weightUnits as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($currentSettings['shipping']['weight_unit']) && $currentSettings['shipping']['weight_unit'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Méret mértékegység</label>
                            <select name="size_unit" class="form-control">
                                <?php foreach ($sizeUnits as $code => $name): ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($currentSettings['shipping']['size_unit']) && $currentSettings['shipping']['size_unit'] === $code ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Készlet beállítások -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Készlet beállítások</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="stock_management" <?php echo isset($currentSettings['stock']['enabled']) && $currentSettings['stock']['enabled'] ? 'checked' : ''; ?> class="mr-2">
                                Készletkezelés engedélyezése
                            </label>
                        </div>
                        <div>
                            <label class="form-label">Minimum készletszint figyelmeztetés</label>
                            <input type="number" name="low_stock_threshold" value="<?php echo isset($currentSettings['stock']['low_stock_threshold']) ? htmlspecialchars($currentSettings['stock']['low_stock_threshold']) : '5'; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="show_stock_status" <?php echo isset($currentSettings['stock']['show_stock_status']) && $currentSettings['stock']['show_stock_status'] ? 'checked' : ''; ?> class="mr-2">
                                Készlet státusz megjelenítése
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="auto_stock_update" <?php echo isset($currentSettings['stock']['auto_update']) && $currentSettings['stock']['auto_update'] ? 'checked' : ''; ?> class="mr-2">
                                Automatikus készletfrissítés
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Integrációk -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Integrációk</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Google Analytics kód</label>
                            <input type="text" name="google_analytics" value="<?php echo isset($currentSettings['integrations']['google_analytics']) ? htmlspecialchars($currentSettings['integrations']['google_analytics']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Facebook Pixel kód</label>
                            <input type="text" name="facebook_pixel" value="<?php echo isset($currentSettings['integrations']['facebook_pixel']) ? htmlspecialchars($currentSettings['integrations']['facebook_pixel']) : ''; ?>" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">API kulcs</label>
                            <input type="text" name="api_key" value="<?php echo isset($currentSettings['integrations']['api_key']) ? htmlspecialchars($currentSettings['integrations']['api_key']) : ''; ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Kedvezmény beállítások -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4 text-primary-dark">Kedvezmény beállítások</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="bulk_discounts" <?php echo isset($currentSettings['discounts']['bulk_discounts']) && $currentSettings['discounts']['bulk_discounts'] ? 'checked' : ''; ?> class="mr-2">
                                Mennyiségi kedvezmények engedélyezése
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="loyalty_program" <?php echo isset($currentSettings['discounts']['loyalty_program']) && $currentSettings['discounts']['loyalty_program'] ? 'checked' : ''; ?> class="mr-2">
                                Hűségprogram engedélyezése
                            </label>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="seasonal_discounts" <?php echo isset($currentSettings['discounts']['seasonal_discounts']) && $currentSettings['discounts']['seasonal_discounts'] ? 'checked' : ''; ?> class="mr-2">
                                Szezonális akciók engedélyezése
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Mentés gomb -->
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        Beállítások mentése
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 