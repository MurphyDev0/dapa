<?php
require_once 'session_config.php';
require_once 'config.php';

/**
 * Segédfunkciók a felhasználói beállítások kezeléséhez
 */

/**
 * Felhasználói beállítások ellenőrzése és lekérése
 * Ha a munkamenetben nincs beállítás, az alapértelmezetteket adja vissza
 */
function getUserSettings() {
    // Alapértelmezett beállítások
    $defaultSettings = [
        'dark_mode' => 0,
        'notifications' => 1,
        'newsletter' => 1,
        'language' => 'hu',
        'currency' => 'HUF',
        'items_per_page' => 12
    ];
    
    // Ellenőrizzük, hogy a munkamenetben vannak-e beállítások
    if (isset($_SESSION['settings']) && is_array($_SESSION['settings'])) {
        return $_SESSION['settings'];
    }
    
    return $defaultSettings;
}

/**
 * Beállítások betöltése az adatbázisból
 */
function loadSettings() {
    global $db;
    
    $sql = "SELECT settings_json FROM shop_settings ORDER BY updated_at DESC LIMIT 1";
    $result = $db->query($sql);
    
    if ($result && $result->rowCount() > 0) {
        $row = $result->fetch();
        if ($row && !empty($row['settings_json'])) {
            $decoded = json_decode($row['settings_json'], true);
            return $decoded !== null ? $decoded : getDefaultSettings();
        }
    }
    
    return getDefaultSettings();
}

/**
 * Alapértelmezett beállítások
 */
function getDefaultSettings() {
    return [
        'basic' => [
            'default_language' => 'hu',
            'default_currency' => 'HUF',
            'timezone' => 'Europe/Budapest'
        ],
        'company' => [
            'name' => '',
            'tax_number' => '',
            'email' => ['info' => '', 'support' => '', 'sales' => ''],
            'phones' => [],
            'address' => ''
        ],
        'social_media' => [
            'facebook' => '',
            'instagram' => '',
            'twitter' => '',
            'linkedin' => ''
        ],
        'payment' => [
            'methods' => [],
            'gateway' => '',
            'vat' => ['default_rate' => 27, 'eu_vat' => 0]
        ],
        'shipping' => [
            'methods' => [],
            'zones' => [],
            'free_shipping_limit' => 0,
            'weight_unit' => 'kg',
            'size_unit' => 'cm'
        ],
        'stock' => [
            'enabled' => 0,
            'low_stock_threshold' => 5,
            'show_stock_status' => 0,
            'auto_update' => 0
        ],
        'integrations' => [
            'google_analytics' => '',
            'facebook_pixel' => '',
            'api_key' => ''
        ],
        'discounts' => [
            'bulk_discounts' => 0,
            'loyalty_program' => 0,
            'seasonal_discounts' => 0
        ]
    ];
}

/**
 * Beállítások mentése az adatbázisba
 */
function saveSettings($settings) {
    global $db;
    
    $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
    
    try {
        // Ellenőrizzük, hogy van-e már beállítás az adatbázisban
        $checkSql = "SELECT id FROM shop_settings LIMIT 1";
        $checkResult = $db->query($checkSql);
        
        if ($checkResult && $checkResult->rowCount() > 0) {
            // Ha már van beállítás, frissítjük
            $sql = "UPDATE shop_settings SET settings_json = ?, updated_at = NOW() WHERE id = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$settingsJson]);
        } else {
            // Ha még nincs beállítás, beszúrunk egy új rekordot
            $sql = "INSERT INTO shop_settings (id, settings_json) VALUES (1, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$settingsJson]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Hiba a beállítások mentésekor: " . $e->getMessage());
        return false;
    }
}

/**
 * Beállítás érték lekérése
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    if ($settings === null) {
        $settings = loadSettings();
    }
    
    $keys = explode('.', $key);
    $value = $settings;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Beállítás érték mentése
 */
function setSetting($key, $value) {
    $settings = loadSettings();
    $keys = explode('.', $key);
    $lastKey = array_pop($keys);
    $current = &$settings;
    
    foreach ($keys as $k) {
        if (!isset($current[$k])) {
            $current[$k] = [];
        }
        $current = &$current[$k];
    }
    
    $current[$lastKey] = $value;
    
    return saveSettings($settings);
}

/**
 * Pénznemek kezelése és formázása
 */
function formatCurrency($amount, $currency = null) {
    if ($currency === null) {
        $currency = getSetting('currency', 'HUF');
    }
    
    switch ($currency) {
        case 'EUR':
            return number_format($amount, 2, ',', ' ') . ' €';
        case 'USD':
            return '$' . number_format($amount, 2, '.', ',');
        case 'HUF':
        default:
            return number_format($amount, 0, ',', ' ') . ' Ft';
    }
}

/**
 * Lapozás beállítása terméklistákhoz
 */
function getItemsPerPage() {
    return (int) getSetting('items_per_page', 12);
}

/**
 * Nyelvi beállítások kezelése
 */
function getCurrentLanguage() {
    return getSetting('language', 'hu');
}

/**
 * Sötét mód beállítás ellenőrzése
 */
function isDarkModeEnabled() {
    return (bool) getSetting('dark_mode', 0);
}

/**
 * Nyelvi fordításokat kezelő funkció
 * (egyszerű példa, valós környezetben összetettebb megoldás javasolt)
 */
function translate($key, $replacements = []) {
    $lang = getCurrentLanguage();
    
    // Itt valójában egy teljes fordítási rendszert használnánk
    // ez csak egy egyszerű példa
    $translations = [
        'hu' => [
            'save' => 'Mentés',
            'cancel' => 'Mégse',
            'welcome' => 'Üdvözöljük, {name}!',
        ],
        'en' => [
            'save' => 'Save',
            'cancel' => 'Cancel',
            'welcome' => 'Welcome, {name}!',
        ],
        'de' => [
            'save' => 'Speichern',
            'cancel' => 'Abbrechen',
            'welcome' => 'Willkommen, {name}!',
        ],
    ];
    
    // Ha a kulcs létezik az adott nyelvben
    if (isset($translations[$lang][$key])) {
        $text = $translations[$lang][$key];
        
        // Helyettesítések végrehajtása
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', $value, $text);
        }
        
        return $text;
    }
    
    // Fallback az alapértelmezett nyelvre
    if ($lang !== 'hu' && isset($translations['hu'][$key])) {
        return $translations['hu'][$key];
    }
    
    // Ha sehol nincs, visszaadjuk a kulcsot
    return $key;
}

/**
 * Pénznem árfolyamok lekérése API-ról
 */
function getCurrencyRates($baseCurrency = 'HUF') {
    // Demo adatok visszaadása külső API-hívás helyett
    $demoRates = [
        'HUF' => 1,
        'EUR' => 0.0026,
        'USD' => 0.0028,
        'GBP' => 0.0022
    ];
    
    return $demoRates;
}

/**
 * Időzónák listájának lekérése
 */
function getTimezones() {
    return DateTimeZone::listIdentifiers();
}

/**
 * Nyelvek listájának lekérése
 */
function getLanguages() {
    return [
        'hu' => 'Magyar',
        'en' => 'English',
        'de' => 'Deutsch'
    ];
}

/**
 * Pénznemek listájának lekérése
 */
function getCurrencies() {
    return [
        'HUF' => 'Magyar Forint',
        'EUR' => 'Euro',
        'USD' => 'US Dollar',
        'GBP' => 'British Pound'
    ];
}

/**
 * Fizetési módok listájának lekérése
 */
function getPaymentMethods() {
    return [
        'cash' => 'Készpénz',
        'card' => 'Bankkártya',
        'transfer' => 'Átutalás'
    ];
}

/**
 * Szállítási módok listájának lekérése
 */
function getShippingMethods() {
    return [
        'personal' => 'Személyes átvétel',
        'courier' => 'Futárszolgálat',
        'post' => 'Postai kézbesítés'
    ];
}

/**
 * Payment gateway-ek listájának lekérése
 */
function getPaymentGateways() {
    return [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'barion' => 'Barion'
    ];
}

/**
 * Súly mértékegységek listájának lekérése
 */
function getWeightUnits() {
    return [
        'kg' => 'Kilogramm (kg)',
        'g' => 'Gramm (g)'
    ];
}

/**
 * Méret mértékegységek listájának lekérése
 */
function getSizeUnits() {
    return [
        'cm' => 'Centiméter (cm)',
        'm' => 'Méter (m)'
    ];
}

/**
 * Beállítások validálása
 */
function validateSettings($settings) {
    $errors = [];
    
    // Email címek validálása
    foreach ($settings['company']['email'] as $type => $email) {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Érvénytelen {$type} email cím formátum";
        }
    }
    
    // URL-ek validálása
    foreach ($settings['social_media'] as $platform => $url) {
        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = "Érvénytelen {$platform} URL formátum";
        }
    }
    
    // Számértékek validálása
    if ($settings['payment']['vat']['default_rate'] < 0 || $settings['payment']['vat']['default_rate'] > 100) {
        $errors[] = "Az ÁFA kulcsnak 0 és 100 között kell lennie";
    }
    
    if ($settings['shipping']['free_shipping_limit'] < 0) {
        $errors[] = "Az ingyenes szállítás limitje nem lehet negatív";
    }
    
    if ($settings['stock']['low_stock_threshold'] < 0) {
        $errors[] = "A minimum készletszint nem lehet negatív";
    }
    
    return $errors;
}

/**
 * Beállítások exportálása
 */
function exportSettings() {
    $settings = loadSettings();
    return json_encode($settings, JSON_PRETTY_PRINT);
}

/**
 * Beállítások importálása
 */
function importSettings($jsonString) {
    try {
        if (empty($jsonString)) {
            throw new Exception("Üres JSON string");
        }
        
        $settings = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Érvénytelen JSON formátum");
        }
        
        if ($settings === null) {
            throw new Exception("JSON dekódolás sikertelen");
        }
        
        $errors = validateSettings($settings);
        
        if (!empty($errors)) {
            throw new Exception(implode("\n", $errors));
        }
        
        return saveSettings($settings);
    } catch (Exception $e) {
        error_log("Hiba a beállítások importálásakor: " . $e->getMessage());
        return false;
    }
}