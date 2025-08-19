<?php
require_once 'session_config.php';
include 'config.php';

// Database connection
$pdo = null;

try {
    if (isset($pdo) && $pdo instanceof PDO) {
        // Already connected
    } elseif (isset($db) && $db instanceof PDO) {
        $pdo = $db;
    } elseif (isset($conn) && $conn instanceof PDO) {
        $pdo = $conn;
    } elseif (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } else {
        throw new Exception('Adatbázis konfiguráció nem található');
    }
} catch (Exception $e) {
    error_log('Search page DB error: ' . $e->getMessage());
    die('Adatbázis kapcsolódási hiba.');
}

// Get search parameters
$searchQuery = isset($_GET['search-query']) ? trim($_GET['search-query']) : '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// If no search query, redirect to homepage
if (empty($searchQuery)) {
    header('Location: index.php');
    exit;
}

$products = [];
$totalResults = 0;

try {
    // Count total results
    $countSql = "SELECT COUNT(*) FROM products 
                 WHERE (name LIKE :query OR description LIKE :query)
                 AND is_active = 1";
    $countStmt = $pdo->prepare($countSql);
    $searchTerm = '%' . $searchQuery . '%';
    $countStmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
    $countStmt->execute();
    $totalResults = (int)$countStmt->fetchColumn();

    // Get products for current page
    if ($totalResults > 0) {
        $sql = "SELECT id, name, price, description as short_desc, image_url as img, category_id
                FROM products 
                WHERE (name LIKE :query OR description LIKE :query)
                AND is_active = 1
                ORDER BY 
                    CASE WHEN name LIKE :prefix THEN 0 ELSE 1 END,
                    name ASC
                LIMIT :offset, :limit";
        
        $stmt = $pdo->prepare($sql);
        $prefixTerm = $searchQuery . '%';
        $stmt->bindParam(':query', $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(':prefix', $prefixTerm, PDO::PARAM_STR);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Search query error: ' . $e->getMessage());
    die('Keresési hiba történt.');
}

$totalPages = ceil($totalResults / $perPage);
$escapedQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');

$is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
$is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keresés: <?= $escapedQuery ?> - Webshop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="new_styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
<body class="bg-lightblue-50">
    <!-- Header -->
    <header class="header">
        <div class="container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="logo">
                <h1><a href="index.php">Dapa</a></h1>    
            </div>
            <!-- Search Bar -->
            <div class="search-bar">
                <form id="search-form" action="search.php" method="GET" class="flex items-center relative" autocomplete="off">
                    <input type="text" id="search-input" name="search-query" value="<?= $escapedQuery ?>" placeholder="Keresés..." class="search-input" aria-label="Keresés termékek között">
                    <button type="submit" id="search-submit" class="search-button" aria-label="Keresés">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="hidden md:block">
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
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Search Results Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-primary-darkest mb-2">
                Keresési eredmények
            </h1>
            <p class="text-lg text-primary-dark">
                "<?= $escapedQuery ?>" kifejezésre <?= $totalResults ?> találat
            </p>
            <?php if ($totalResults > 0): ?>
                <p class="text-sm text-gray-600 mt-1">
                    <?= $offset + 1 ?>-<?= min($offset + $perPage, $totalResults) ?>. találat megjelenítve
                </p>
            <?php endif; ?>
        </div>

        <?php if (empty($products)): ?>
            <!-- No Results -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-4">
                    <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Nincs találat</h2>
                <p class="text-gray-600 mb-6">
                    Sajnos nem találtunk terméket a "<?= $escapedQuery ?>" keresésre.
                </p>
                <div class="space-y-2 text-sm text-gray-500">
                    <p>Próbálja meg:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Ellenőrizze a helyesírást</li>
                        <li>Használjon általánosabb kifejezéseket</li>
                        <li>Próbáljon kevesebb szót használni</li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a href="index.php" class="btn btn-primary">Vissza a főoldalra</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Search Results -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($products as $product): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <?php if (!empty($product['img'])): ?>
                            <div class="aspect-w-1 aspect-h-1">
                                <img src="<?= htmlspecialchars($product['img']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" 
                                     class="w-full h-48 object-cover">
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-4">
                            <h3 class="font-semibold text-lg text-primary-darkest mb-2 line-clamp-2">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            
                            <?php if (!empty($product['short_desc'])): ?>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-3">
                                    <?= htmlspecialchars(mb_substr(strip_tags($product['short_desc']), 0, 120, 'UTF-8')) ?>
                                    <?= mb_strlen($product['short_desc'], 'UTF-8') > 120 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (isset($product['price']) && $product['price'] > 0): ?>
                                <div class="text-xl font-bold text-primary-dark mb-3">
                                    <?= number_format($product['price'], 0, ',', ' ') ?> Ft
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex justify-between items-center">
                                <a href="product.php?id=<?= (int)$product['id'] ?>" 
                                   class="btn btn-primary flex-1 mr-2 text-center">
                                    Részletek
                                </a>
                                <button onclick="addToCart(<?= (int)$product['id'] ?>)" 
                                        class="btn btn-secondary px-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-12 flex justify-center" aria-label="Pagination">
                    <div class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(['search-query' => $searchQuery, 'page' => $page - 1]) ?>" 
                               class="px-4 py-2 text-sm font-medium text-primary-dark bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Előző
                            </a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?<?= http_build_query(['search-query' => $searchQuery, 'page' => 1]) ?>" 
                               class="px-3 py-2 text-sm font-medium text-primary-dark bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="px-3 py-2 text-sm text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-2 text-sm font-medium text-white bg-primary-dark border border-primary-dark rounded-md">
                                    <?= $i ?>
                                </span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(['search-query' => $searchQuery, 'page' => $i]) ?>" 
                                   class="px-3 py-2 text-sm font-medium text-primary-dark bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="px-3 py-2 text-sm text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?<?= http_build_query(['search-query' => $searchQuery, 'page' => $totalPages]) ?>" 
                               class="px-3 py-2 text-sm font-medium text-primary-dark bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= http_build_query(['search-query' => $searchQuery, 'page' => $page + 1]) ?>" 
                               class="px-4 py-2 text-sm font-medium text-primary-dark bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                                Következő
                                <svg class="w-4 h-4 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-16">
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
                    <a href="login.php" class="footer-link">Bejelentkezés</a>
                    <a href="register.php" class="footer-link">Regisztráció</a>
                    <a href="#" class="footer-link">Rendeléseim</a>
                    <a href="cart.php" class="footer-link">Kosár</a>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-primary-dark text-center text-sm opacity-75">
                <p>&copy; 2023 Webshop. Minden jog fenntartva.</p>
            </div>
        </div>
    </footer>

    <script>
        // Cart functionality
        function addToCart(productId) {
            // Add to cart logic here
            console.log('Adding product to cart:', productId);
            // You can implement the actual cart functionality here
        }

        // Initialize cart count
        document.addEventListener('DOMContentLoaded', function() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                const cart = localStorage.getItem('cart');
                const items = cart ? JSON.parse(cart) : [];
                const totalItems = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                cartCount.textContent = totalItems;
            }
        });
    </script>
</body>
</html>