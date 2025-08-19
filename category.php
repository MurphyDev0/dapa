<?php
    require_once 'session_config.php';
    include 'config.php';
    require_once 'auth.php';
    require_once 'notifications.php';
    require_once 'category_functions.php';

    $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
    $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
    
    // Check if category ID is provided
    if(!isset($_GET['id'])) {
        header('Location: index.php');
        exit();
    }
    
    $category_id = $_GET['id'];
    
    // Kategória kezelő inicializálása
    $categoryManager = new CategoryManager($db);

    // Get category details
    $category = $categoryManager->getCategoryDetails($category_id);
    
    if(!$category) {
        header('Location: index.php');
        exit();
    }
    
    // Breadcrumb útvonal generálása
    $breadcrumb = $categoryManager->getBreadcrumb($category_id);

    // Alkategóriák lekérése
    $subcategories = $categoryManager->getSubcategories($category_id);

    // Get products for this category (including subcategories)
    $subcategoryIds = array_column($subcategories, 'id');
    $allCategoryIds = array_merge([$category_id], $subcategoryIds);
    $placeholders = str_repeat('?,', count($allCategoryIds) - 1) . '?';

    $sqlProducts = "SELECT p.*, c.name as category_name,
                           COALESCE(p.image_url, 'img/placeholder.jpg') as image_url
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.category_id IN ($placeholders) AND p.is_active = 1 
                   ORDER BY p.created_at DESC";
    $stmtProducts = $db->prepare($sqlProducts);
    $stmtProducts->execute($allCategoryIds);
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

    // Termék hozzáadása a kosárhoz
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $size_id = $_POST['size_id'];
        
        try {
            // Ellenőrizzük a készletet
            $stmt = $db->prepare("SELECT stock FROM product_size_stock WHERE product_id = ? AND size_id = ?");
            $stmt->execute([$product_id, $size_id]);
            $stock = $stmt->fetchColumn();
            
            if ($stock >= $quantity) {
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'quantity' => $quantity,
                        'size_id' => $size_id
                    ];
                }
                
                Notification::add('A termék sikeresen hozzáadva a kosárhoz!', 'success');
            } else {
                Notification::add('Nincs elég készlet a kiválasztott méretből!', 'failure');
            }
        } catch (Exception $e) {
            Notification::add('Hiba történt a termék kosárba helyezése során: ' . $e->getMessage(), 'failure');
        }
        
        header("Location: category.php?id=" . $_GET['id']);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategória</title>
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
    <style>
        .product-card .btn {
            height: auto;
            min-height: 2.5rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-card .btn svg {
            flex-shrink: 0;
        }
    </style>
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
                            <a href="#" class="nav-link active">Kategóriák</a>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (!$is_logged): ?>
                        <a href="login.php" class="btn btn-outline">Bejelentkezés</a>
                        <a href="register.php" class="btn btn-primary">Regisztráció</a>
                    <?php else: ?>
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
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-primary-dark bg-primary-lightest">Kategóriák</a>
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
    
    <!-- Category Header -->
    <div class="bg-primary-lightest py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-4">
                <ol class="flex items-center space-x-2 text-sm">
                    <li><a href="index.php" class="text-primary-dark hover:text-primary-darkest">Kezdőlap</a></li>
                    <?php foreach ($breadcrumb as $index => $crumb): ?>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mx-2 text-primary-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            <?php if ($index === count($breadcrumb) - 1): ?>
                                <span class="text-primary-darkest font-medium"><?php echo htmlspecialchars($crumb['name']); ?></span>
                            <?php else: ?>
                                <a href="category.php?id=<?php echo $crumb['id']; ?>" class="text-primary-dark hover:text-primary-darkest">
                                    <?php echo htmlspecialchars($crumb['name']); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            
            <div class="md:flex md:items-center md:justify-between">
                <div class="md:w-2/3">
                    <h1 class="text-3xl font-bold text-primary-darkest"><?php echo htmlspecialchars($category['name']); ?></h1>
                    <p class="mt-2 text-primary-dark"><?php echo htmlspecialchars($category['description']); ?></p>
                    <div class="mt-2 text-sm text-primary-dark">
                        <?php echo $category['product_count']; ?> termék ebben a kategóriában
                    </div>
                </div>
                <div class="mt-4 md:mt-0 md:w-1/3">
                    <div class="flex justify-end">
                        <div class="relative">
                            <select class="form-control appearance-none pr-8" onchange="sortProducts(this.value)">
                                <option value="newest">Legújabb</option>
                                <option value="price_asc">Ár szerint növekvő</option>
                                <option value="price_desc">Ár szerint csökkenő</option>
                                <option value="name_asc">Név szerint A-Z</option>
                                <option value="name_desc">Név szerint Z-A</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-primary-dark">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Alkategóriák megjelenítése -->
        <?php if (!empty($subcategories)): ?>
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-primary-darkest mb-6">Alkategóriák</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($subcategories as $subcategory): ?>
                <a href="category.php?id=<?php echo $subcategory['id']; ?>" class="block">
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                        <?php if (!empty($subcategory['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($subcategory['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($subcategory['name']); ?>" 
                             class="w-full h-32 object-cover">
                        <?php else: ?>
                        <div class="w-full h-32 bg-primary-lightest flex items-center justify-center">
                            <span class="text-primary-darkest text-lg font-medium">
                                <?php echo htmlspecialchars(substr($subcategory['name'], 0, 2)); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-primary-darkest mb-2">
                                <?php echo htmlspecialchars($subcategory['name']); ?>
                            </h3>
                            <p class="text-sm text-primary-dark mb-2">
                                <?php echo htmlspecialchars($subcategory['description']); ?>
                            </p>
                            <div class="text-xs text-primary-dark">
                                <?php echo $subcategory['product_count']; ?> termék
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Termékek -->
        <div class="mb-6 flex justify-between items-center">
            <h2 class="text-2xl font-bold text-primary-darkest">
                <?php echo !empty($subcategories) ? 'Összes termék' : 'Termékek'; ?>
            </h2>
            <div class="text-sm text-primary-dark">
                <?php echo count($products); ?> termék találva
            </div>
        </div>

        <?php if (empty($products)): ?>
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-lightest text-primary-darkest mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-primary-darkest mb-2">Nincsenek termékek</h3>
            <p class="text-primary-dark">Ebben a kategóriában jelenleg nincsenek elérhető termékek.</p>
        </div>
        <?php else: ?>
        <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
                    <div class="product-card-content">
                        <h3><?php echo $product['name']; ?></h3>
                        <div class="product-card-price"><?php echo number_format($product['price'], 0, ',', ' '); ?> Ft</div>
                        <div class="product-card-footer">
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary text-center">
                                    Részletek
                                </a>
                                <button onclick="addToCart(<?php echo $product['id']; ?>, 1)" class="btn btn-outline flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    Kosárba
                                </button>
                            </div>
                            <button onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn btn-light w-full text-center">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                Kívánságlistához
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
        // Mobile menu
        document.querySelector('.mobile-menu-button').addEventListener('click', function() {
            document.querySelector('.mobile-menu').classList.toggle('hidden');
        });
        
        // Add to cart function
        function addToCart(productId, quantity = 1) {
            // Ha a kosár objektum elérhető, használjuk azt
            productId = parseInt(productId);
            if (typeof window.cart !== 'undefined') {
                window.cart.addItem(productId, quantity).then(success => {
                    if (success) {
                        alert('Termék hozzáadva a kosárhoz!');
                    }
                });
            } else {
                // AJAX kérés a termék kosárhoz adásához
                fetch('cart.php?action=add&id=' + productId + '&quantity=' + quantity)
                .then(response => response.json())
                .then(product => {
                    // Frissítjük a kosár számláló megjelenítését
                    const cart = localStorage.getItem('cart') ? JSON.parse(localStorage.getItem('cart')) : [];
                    const existingItem = cart.find(item => item.id === parseInt(product.id));
                    
                    if (existingItem) {
                        existingItem.quantity += parseInt(quantity);
                    } else {
                        cart.push({
                            id: parseInt(product.id),
                            name: product.name,
                            price: product.discount_price || product.price,
                            image: product.image_url,
                            quantity: parseInt(quantity)
                        });
                    }
                    
                    localStorage.setItem('cart', JSON.stringify(cart));
                    
                    // Frissítjük a kosár számláló megjelenítését
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                        cartCount.textContent = totalItems;
                    }
                    
                    alert('Termék hozzáadva a kosárhoz!');
                })
                .catch(error => {
                    console.error('Hiba:', error);
                    alert('Hiba történt a termék kosárhoz adása közben.');
                });
            }
        }
        
        // Add to wishlist function
        function addToWishlist(productId) {
            // Az AJAX kéréshez szükséges adatok összeállítása
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('action', 'add_to_wishlist');
            
            // AJAX kérés küldése
            fetch('wishlist_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Termék hozzáadva a kívánságlistához!');
                } else {
                    if (data.error === 'not_logged_in') {
                        if (confirm('A kívánságlista használatához be kell jelentkezni. Átirányítjuk a bejelentkezési oldalra?')) {
                            window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                        }
                    } else {
                        alert(data.message || 'Hiba történt a kívánságlistához adás közben.');
                    }
                }
            })
            .catch(error => {
                console.error('Hiba:', error);
                alert('Hiba történt a kérés feldolgozása során.');
            });
        }
        
        // Sort products function
        function sortProducts(sortBy) {
            const grid = document.getElementById('products-grid');
            const products = Array.from(grid.children);
            
            products.sort((a, b) => {
                switch(sortBy) {
                    case 'price_asc':
                        const priceA = parseInt(a.querySelector('.product-card-price').textContent.replace(/\D/g, ''));
                        const priceB = parseInt(b.querySelector('.product-card-price').textContent.replace(/\D/g, ''));
                        return priceA - priceB;
                    
                    case 'price_desc':
                        const priceDescA = parseInt(a.querySelector('.product-card-price').textContent.replace(/\D/g, ''));
                        const priceDescB = parseInt(b.querySelector('.product-card-price').textContent.replace(/\D/g, ''));
                        return priceDescB - priceDescA;
                    
                    case 'name_asc':
                        const nameA = a.querySelector('h3').textContent.toLowerCase();
                        const nameB = b.querySelector('h3').textContent.toLowerCase();
                        return nameA.localeCompare(nameB);
                    
                    case 'name_desc':
                        const nameDescA = a.querySelector('h3').textContent.toLowerCase();
                        const nameDescB = b.querySelector('h3').textContent.toLowerCase();
                        return nameDescB.localeCompare(nameDescA);
                    
                    case 'newest':
                    default:
                        // Alapértelmezett sorrend (már be van állítva)
                        return 0;
                }
            });
            
            // Grid újrarendezése
            products.forEach(product => grid.appendChild(product));
        }
    </script>
</body>
</html>