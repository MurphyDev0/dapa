<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';
require_once 'category_functions.php';

// Admin jogosultság ellenőrzése
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    Notification::add('Nincs jogosultsága az admin panel eléréséhez!', 'failure');
    header('Location: login.php');
    exit;
}

$categoryManager = new CategoryManager($db);

// Dark mode beállítás lekérése
$darkMode = isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === '1';

// Készlet értesítés küszöbérték lekérése
try {
    $stmt = $db->query("SELECT settings_json FROM shop_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings && !empty($settings['settings_json'])) {
        $settingsJson = json_decode($settings['settings_json'], true);
        $lowStockThreshold = isset($settingsJson['low_stock_threshold']) ? (int)$settingsJson['low_stock_threshold'] : 5;
    } else {
        $lowStockThreshold = 5;
    }
} catch (Exception $e) {
    error_log("Hiba a beállítások lekérése során: " . $e->getMessage());
    $lowStockThreshold = 5;
}

// Méretek lekérése
try {
    $stmt = $db->query("SELECT * FROM product_sizes ORDER BY id");
    $sizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Hiba a méretek lekérése során: " . $e->getMessage());
    $sizes = [];
}

// Szűrési paraméterek lekérése
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;
$status_filter = isset($_GET['status']) ? $_GET['status'] : null;
$search_filter = isset($_GET['search']) ? $_GET['search'] : null;
$price_min = isset($_GET['price_min']) ? $_GET['price_min'] : '';
$price_max = isset($_GET['price_max']) ? $_GET['price_max'] : '';
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : null;

// Kategóriák lekérése - JAVÍTVA: categories tábla használata
try {
    $categories = $categoryManager->getAllCategoriesHierarchical();
} catch (Exception $e) {
    error_log("Hiba a kategóriák lekérése során: " . $e->getMessage());
    $categories = [];
}

// Termékek lekérése szűréssel - JAVÍTVA
try {
    $query = "SELECT p.*, c.name as category_name,
              GROUP_CONCAT(
                CONCAT(s.name, ':', COALESCE(ps.stock, 0)) 
                ORDER BY s.id 
                SEPARATOR '|'
              ) as size_stocks,
              COALESCE(SUM(ps.stock), 0) as total_stock
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              LEFT JOIN product_size_stock ps ON p.id = ps.product_id
              LEFT JOIN product_sizes s ON ps.size_id = s.id
              WHERE p.is_active = 1";
    $params = [];

    if ($category_filter) {
        $query .= " AND p.category_id = ?";
        $params[] = $category_filter;
    }

    if ($status_filter) {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    if ($search_filter) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search_filter%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if ($price_min !== '') {
        $query .= " AND p.price >= ?";
        $params[] = $price_min;
    }

    if ($price_max !== '') {
        $query .= " AND p.price <= ?";
        $params[] = $price_max;
    }

    if ($stock_filter) {
        switch ($stock_filter) {
            case 'low':
                $query .= " AND EXISTS (SELECT 1 FROM product_size_stock ps2 WHERE ps2.product_id = p.id AND ps2.stock <= ?)";
                $params[] = $lowStockThreshold;
                break;
            case 'out':
                $query .= " AND NOT EXISTS (SELECT 1 FROM product_size_stock ps2 WHERE ps2.product_id = p.id AND ps2.stock > 0)";
                break;
            case 'in':
                $query .= " AND EXISTS (SELECT 1 FROM product_size_stock ps2 WHERE ps2.product_id = p.id AND ps2.stock > ?)";
                $params[] = $lowStockThreshold;
                break;
        }
    }

    $query .= " GROUP BY p.id ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Méretek feldolgozása - JAVÍTVA
    foreach ($products as &$product) {
        $product['sizes'] = [];
        $product['stock'] = (int)$product['total_stock'];
        
        if (!empty($product['size_stocks'])) {
            $sizeStocks = explode('|', $product['size_stocks']);
            foreach ($sizeStocks as $sizeStock) {
                if (strpos($sizeStock, ':') !== false) {
                    list($size, $stock) = explode(':', $sizeStock);
                    $product['sizes'][$size] = (int)$stock;
                }
            }
        }
        unset($product['size_stocks'], $product['total_stock']);
    }
    
    // Debug információ
    error_log("SQL Query: " . $query);
    error_log("Parameters: " . print_r($params, true));
    error_log("Number of products: " . count($products));
} catch (Exception $e) {
    error_log("Hiba a termékek lekérése során: " . $e->getMessage());
    $products = [];
}

// Képfeltöltés kezelése
function handleImageUpload($file, $type) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Érvénytelen fájl paraméter.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return null;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('A fájl mérete túl nagy.');
        default:
            throw new RuntimeException('Ismeretlen hiba.');
    }

    if ($file['size'] > 10000000) {
        throw new RuntimeException('A fájl mérete nem lehet nagyobb 10MB-nál.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ];

    if (!in_array($mime_type, $allowed_types)) {
        throw new RuntimeException('Csak JPG, PNG, GIF és WEBP formátumú képek engedélyezettek.');
    }

    $upload_dir = 'uploads/' . $type . 's/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = sprintf(
        '%s.%s',
        sha1_file($file['tmp_name']),
        $file_extension
    );

    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new RuntimeException('Hiba történt a fájl feltöltése során.');
    }

    return $file_path;
}

// Új termék létrehozása - JAVÍTVA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    $sizes = $_POST['sizes'] ?? [];
    
    try {
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_url = handleImageUpload($_FILES['image'], 'product');
        }
        
        $db->beginTransaction();
        
        // Termék létrehozása
        $stmt = $db->prepare("INSERT INTO products (name, description, price, category_id, status, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $description, $price, $category_id, $status, $image_url]);
        $product_id = $db->lastInsertId();
        
        // Kategória ellenőrzése - ruházat kategória automatikus méret hozzárendelés
        $category = $categoryManager->getCategoryDetails($category_id);
        $isClothingCategory = false;
        
        if ($category) {
            // Ellenőrizzük, hogy ruházat kategória-e (név alapján)
            $categoryName = strtolower($category['name']);
            $parentName = $category['parent_name'] ? strtolower($category['parent_name']) : '';
            
            $clothingKeywords = ['ruházat', 'ruha', 'clothing', 'férfi', 'női', 'gyermek', 'cipő'];
            foreach ($clothingKeywords as $keyword) {
                if (strpos($categoryName, $keyword) !== false || strpos($parentName, $keyword) !== false) {
                    $isClothingCategory = true;
                    break;
                }
            }
        }
        
        // Méretek és készletek mentése
        if ($isClothingCategory && empty(array_filter($sizes))) {
            // Automatikus méret hozzárendelés ruházat kategóriához
            $defaultClothingSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            foreach ($sizes as $size_id => $size_name) {
                $stmt = $db->prepare("SELECT name FROM product_sizes WHERE id = ?");
                $stmt->execute([$size_id]);
                $sizeName = $stmt->fetchColumn();
                
                if (in_array($sizeName, $defaultClothingSizes)) {
                    $defaultStock = 10; // Alapértelmezett készlet
                    $stmt = $db->prepare("INSERT INTO product_size_stock (product_id, size_id, stock) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $size_id, $defaultStock]);
                }
            }
        } else {
            // Manuálisan megadott méretek mentése
            foreach ($sizes as $size_id => $stock) {
                if ($stock > 0) {
                    $stmt = $db->prepare("INSERT INTO product_size_stock (product_id, size_id, stock) VALUES (?, ?, ?)");
                    $stmt->execute([$product_id, $size_id, $stock]);
                }
            }
        }
        
        $db->commit();
        Notification::add('A termék sikeresen létrehozva!', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        Notification::add('Hiba történt a termék létrehozása során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_products.php");
    exit;
}

// Új kategória létrehozása - JAVÍTVA a categories tábla használatához
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
    try {
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_url = handleImageUpload($_FILES['image'], 'category');
        }
        
        $categoryId = $categoryManager->createCategory($name, $description, null, $image_url);
        Notification::add('A kategória sikeresen létrehozva!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kategória létrehozása során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_products.php");
    exit;
}

// Termék törlése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    try {
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        Notification::add('A termék sikeresen törölve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a termék törlése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_products.php");
    exit;
}

// Termék szerkesztése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $status = $_POST['status'];
    $sizes = $_POST['sizes'] ?? [];
    
    try {
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $image_url = handleImageUpload($_FILES['image'], 'product');
        }
        
        $db->beginTransaction();
        
        // Termék frissítése
        if ($image_url) {
            $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, status = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $category_id, $status, $image_url, $product_id]);
        } else {
            $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $category_id, $status, $product_id]);
        }
        
        // Régi méretek törlése
        $stmt = $db->prepare("DELETE FROM product_size_stock WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Új méretek és készletek mentése
        foreach ($sizes as $size_id => $stock) {
            if ($stock > 0) {
                $stmt = $db->prepare("INSERT INTO product_size_stock (product_id, size_id, stock) VALUES (?, ?, ?)");
                $stmt->execute([$product_id, $size_id, $stock]);
            }
        }
        
        $db->commit();
        Notification::add('A termék sikeresen frissítve!', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        Notification::add('Hiba történt a termék frissítése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termékek - Admin Panel</title>
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
                    <a href="admin_categories.php" class="flex items-center p-2 <?php echo basename($_SERVER['PHP_SELF']) === 'admin_categories.php' ? 'bg-primary-light' : 'hover:bg-primary-dark'; ?> rounded text-white">
                        <span class="mr-2">📂</span>
                        <span>Kategóriák</span>
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
                <h1 class="text-xl font-bold text-primary-darkest">Termékek kezelése</h1>
                <div class="flex space-x-4">
                    <button onclick="showCreateCategoryModal()" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Új kategória
                    </button>
                    <button onclick="showCreateProductModal()" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Új termék
                        </button>
                    </div>
                </div>
            </header>

            <main class="p-4">
            <!-- Termékek táblázat -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-primary-lightest">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Név</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Kategória</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Méretek</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Ár</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Készlet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Műveletek</th>
                                </tr>
                            </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="h-10 w-10 rounded-full object-cover mr-3">
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-primary-darkest"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="text-xs text-primary-dark"><?php echo htmlspecialchars($product['description']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php 
                                        if (empty($product['sizes'])) {
                                            echo 'Nincs méret';
                                        } else {
                                            $sizeTexts = [];
                                            foreach ($product['sizes'] as $size => $stock) {
                                                if ($stock > 0) {
                                                    $sizeTexts[] = "$size: $stock db";
                                                }
                                            }
                                            echo implode(', ', $sizeTexts);
                                        }
                                        ?>
                                    </div>
                                    </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest"><?php echo number_format($product['price'], 0, ',', ' ') . ' Ft'; ?></div>
                                    </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php 
                                        if ($product['stock'] == 0) {
                                            echo '<span class="text-red-600">Nincs készleten</span>';
                                        } elseif ($product['stock'] <= $lowStockThreshold) {
                                            echo '<span class="text-yellow-600">Alacsony készlet (' . $product['stock'] . ' db)</span>';
                                        } else {
                                            echo $product['stock'] . ' db';
                                        }
                                        ?>
                                    </div>
                                    </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo isset($product['status']) && $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo isset($product['status']) && $product['status'] === 'active' ? 'Aktív' : 'Inaktív'; ?>
                                    </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-primary-light hover:text-primary-dark mr-3" onclick="toggleEditForm(<?php echo $product['id']; ?>)">
                                        Szerkesztés
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Biztosan törli ezt a terméket?');">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="delete_product" value="1">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            Törlés
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            <tr id="editForm_<?php echo $product['id']; ?>" class="hidden">
                                <td colspan="7" class="px-6 py-4 bg-gray-50">
                                    <form method="POST" class="space-y-4" enctype="multipart/form-data">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Név</label>
                                                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Ár</label>
                                                <input type="number" name="price" value="<?php echo $product['price']; ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Készlet</label>
                                                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required class="form-control">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Kategória</label>
                                                <select name="category_id" required class="form-control">
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Méretek és készlet</label>
                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                                    <?php foreach ($sizes as $size): ?>
                                            <div>
                                                            <label class="block text-sm font-medium text-primary-darkest mb-1"><?php echo htmlspecialchars($size['name']); ?></label>
                                                            <input type="number" name="sizes[<?php echo $size['id']; ?>]" 
                                                                   value="<?php echo $product['sizes'][$size['name']] ?? 0; ?>" 
                                                                   min="0" class="form-control">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Leírás</label>
                                                <textarea name="description" required class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Státusz</label>
                                                <select name="status" required class="form-control">
                                                    <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Aktív</option>
                                                    <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inaktív</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-primary-darkest mb-1">Kép</label>
                                                <?php if (!empty($product['image_url'])): ?>
                                                <div class="mb-2">
                                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Jelenlegi kép" class="h-20 w-20 object-cover rounded">
                                                </div>
                                                <?php endif; ?>
                                                <input type="file" name="image" accept="image/*" class="form-control">
                                                <p class="text-xs text-gray-500 mt-1">Maximális méret: 10MB. Engedélyezett formátumok: JPG, PNG, GIF, WEBP</p>
                                            </div>
                                        </div>
                                        <div class="flex justify-end space-x-3">
                                            <button type="button" onclick="toggleEditForm(<?php echo $product['id']; ?>)" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                                                Mégse
                                            </button>
                                            <button type="submit" name="edit_product" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                                                    Mentés
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
        </main>
    </div>

    <!-- Új termék modal -->
    <div id="createProductModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-primary-darkest mb-4">Új termék létrehozása</h3>
                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Név</label>
                            <input type="text" name="name" required class="form-control" placeholder="Termék neve">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Ár</label>
                            <input type="number" name="price" required class="form-control" placeholder="0">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Leírás</label>
                            <textarea name="description" required class="form-control" rows="2" placeholder="Termék leírása"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Kategória</label>
                            <select name="category_id" id="categorySelect" required class="form-control" onchange="handleCategoryChange()">
                                <option value="">Válassz kategóriát</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars(strtolower($category['name'])); ?>"
                                            data-parent="<?php echo htmlspecialchars(strtolower($category['parent_name'] ?? '')); ?>">
                                        <?php 
                                        if ($category['parent_id']) {
                                            echo '&nbsp;&nbsp;&nbsp;↳ ';
                                        }
                                        echo htmlspecialchars($category['name']); 
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Státusz</label>
                            <select name="status" required class="form-control">
                                <option value="active">Aktív</option>
                                <option value="inactive">Inaktív</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-primary-darkest mb-1">
                                Méretek és készlet
                                <small class="text-gray-500">(Ruházat kategóriánál automatikusan kitöltődik)</small>
                            </label>
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-3" id="sizesContainer">
                                <?php foreach ($sizes as $size): ?>
                                    <div class="size-input-group">
                                        <label class="block text-xs font-medium text-primary-darkest mb-1"><?php echo htmlspecialchars($size['name']); ?></label>
                                        <input type="number" name="sizes[<?php echo $size['id']; ?>]" 
                                               value="0" min="0" class="form-control text-sm"
                                               data-size-name="<?php echo htmlspecialchars($size['name']); ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <button type="button" onclick="setClothingSizes()" class="text-primary-light hover:text-primary-dark text-sm">
                                    Ruházat méretek automatikus kitöltése
                                </button>
                                <span class="mx-2 text-gray-400">|</span>
                                <button type="button" onclick="clearAllSizes()" class="text-red-600 hover:text-red-800 text-sm">
                                    Összes méret törlése
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Kép</label>
                            <input type="file" name="image" accept="image/*" class="form-control">
                            <p class="text-xs text-gray-500 mt-1">Max: 10MB. JPG, PNG, GIF, WEBP</p>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="hideCreateProductModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Mégse
                        </button>
                        <button type="submit" name="create_product" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Létrehozás
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Új kategória modal -->
    <div id="createCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-primary-darkest mb-4">Új kategória létrehozása</h3>
                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Név</label>
                        <input type="text" name="name" required class="form-control" placeholder="Kategória neve">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Leírás</label>
                        <textarea name="description" required class="form-control" rows="3" placeholder="Kategória leírása"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Kép</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <p class="text-xs text-gray-500 mt-1">Maximális méret: 10MB. Engedélyezett formátumok: JPG, PNG, GIF, WEBP</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideCreateCategoryModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Mégse
                        </button>
                        <button type="submit" name="create_category" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Létrehozás
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Termék szerkesztés modal -->
    <div id="editProductModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-primary-dark rounded-lg shadow-xl w-full max-w-2xl mx-4">
            <div class="bg-gradient-to-r from-primary to-primary-dark rounded-t-lg p-4">
                <h2 class="text-xl font-semibold text-white">Termék szerkesztése</h2>
            </div>
            <div class="p-6">
                <form method="POST" class="space-y-4" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Név</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-control">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Ár</label>
                            <input type="number" name="price" value="<?php echo $product['price']; ?>" required class="form-control">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Készlet</label>
                            <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required class="form-control">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Kategória</label>
                            <select name="category_id" required class="form-control">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Méretek és készlet</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($sizes as $size): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-primary-darkest mb-1"><?php echo htmlspecialchars($size['name']); ?></label>
                                        <input type="number" name="sizes[<?php echo $size['id']; ?>]" 
                                               value="<?php echo $product['sizes'][$size['name']] ?? 0; ?>" 
                                               min="0" class="form-control">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Leírás</label>
                            <textarea name="description" required class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Státusz</label>
                            <select name="status" required class="form-control">
                                <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Aktív</option>
                                <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inaktív</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-primary-darkest mb-1">Kép</label>
                            <?php if (!empty($product['image_url'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Jelenlegi kép" class="h-20 w-20 object-cover rounded">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" accept="image/*" class="form-control">
                            <p class="text-xs text-gray-500 mt-1">Maximális méret: 10MB. Engedélyezett formátumok: JPG, PNG, GIF, WEBP</p>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideEditProductModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Mégse
                        </button>
                        <button type="submit" name="edit_product" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                            Mentés
                        </button>
                    </div>
                </form>
                </div>
        </div>
    </div>

    <script>
        let openEditForm = null;

        function toggleEditForm(productId) {
            const form = document.getElementById(`editForm_${productId}`);
            
            // Ha már van nyitott form, bezárjuk
            if (openEditForm && openEditForm !== form) {
                openEditForm.classList.add('hidden');
                openEditForm.style.maxHeight = '0';
            }
            
            // Ha a kattintott form nyitva van, bezárjuk
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                form.style.maxHeight = form.scrollHeight + 'px';
                openEditForm = form;
            } else {
                form.classList.add('hidden');
                form.style.maxHeight = '0';
                openEditForm = null;
            }
        }

        function showCreateProductModal() {
            document.getElementById('createProductModal').classList.remove('hidden');
        }

        function hideCreateProductModal() {
            document.getElementById('createProductModal').classList.add('hidden');
        }

        function showCreateCategoryModal() {
            document.getElementById('createCategoryModal').classList.remove('hidden');
        }

        function hideCreateCategoryModal() {
            document.getElementById('createCategoryModal').classList.add('hidden');
        }

        function hideEditProductModal() {
            document.getElementById('editProductModal').classList.add('hidden');
        }

        // Kategória változás kezelése
        function handleCategoryChange() {
            const categorySelect = document.getElementById('categorySelect');
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            
            if (selectedOption) {
                const categoryName = selectedOption.getAttribute('data-name') || '';
                const parentName = selectedOption.getAttribute('data-parent') || '';
                
                // Ruházat kategória ellenőrzése
                const clothingKeywords = ['ruházat', 'ruha', 'clothing', 'férfi', 'női', 'gyermek', 'cipő'];
                let isClothingCategory = false;
                
                for (const keyword of clothingKeywords) {
                    if (categoryName.includes(keyword) || parentName.includes(keyword)) {
                        isClothingCategory = true;
                        break;
                    }
                }
                
                if (isClothingCategory) {
                    setClothingSizes();
                    // Értesítés megjelenítése
                    showCategoryNotification('Ruházat kategória kiválasztva! A standard méretek automatikusan kitöltésre kerültek.');
                }
            }
        }

        // Ruházat méretek automatikus kitöltése
        function setClothingSizes() {
            const clothingSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            
            clothingSizes.forEach(sizeName => {
                const input = document.querySelector(`input[data-size-name="${sizeName}"]`);
                if (input) {
                    input.value = 10; // Alapértelmezett készlet
                    input.style.backgroundColor = '#ade4e5'; // Kiemelés
                    
                    // Kiemelés eltávolítása 2 másodperc után
                    setTimeout(() => {
                        input.style.backgroundColor = '';
                    }, 2000);
                }
            });
        }

        // Összes méret törlése
        function clearAllSizes() {
            const sizeInputs = document.querySelectorAll('#sizesContainer input[type="number"]');
            sizeInputs.forEach(input => {
                input.value = 0;
                input.style.backgroundColor = '#ffcccc'; // Piros kiemelés
                
                // Kiemelés eltávolítása 1 másodperc után
                setTimeout(() => {
                    input.style.backgroundColor = '';
                }, 1000);
            });
        }

        // Kategória értesítés megjelenítése
        function showCategoryNotification(message) {
            // Létrehozunk egy ideiglenes értesítést
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-primary-light text-white px-4 py-2 rounded-lg shadow-lg z-50 transform transition-transform duration-300 translate-x-full';
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Megjelenítés animációval
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Eltávolítás 3 másodperc után
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Modal háttérre kattintás kezelése
        document.getElementById('createProductModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCreateProductModal();
            }
        });

        document.getElementById('createCategoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCreateCategoryModal();
            }
        });
    </script>

    <style>
        #editForm_<?php echo $product['id']; ?> {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
    </style>
</body>
</html> 