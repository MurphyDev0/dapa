<?php
    require_once 'session_config.php';
    require_once 'config.php';
    require_once 'auth.php';
    require_once 'notifications.php';

    $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
    $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0;
    
    // Check if product ID is provided
    if(!isset($_GET['id'])) {
        header('Location: index.php');
        exit();
    }
    
    $product_id = $_GET['id'];
    
    // Get product details with its category
    $sqlProduct = "SELECT p.*, c.name as category_name, c.id as category_id,
                          COALESCE(p.image_url, 'img/placeholder.jpg') as image_url
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = :id";
    $stmtProduct = $db->prepare($sqlProduct);
    $stmtProduct->bindParam(':id', $product_id);
    $stmtProduct->execute();
    $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
    
    if(!$product) {
        header('Location: index.php');
        exit();
    }
    
    // Get related products from the same category
    $sqlRelated = "SELECT *, COALESCE(image_url, 'img/placeholder.jpg') as image_url 
                   FROM products 
                   WHERE category_id = :category_id AND id != :id AND is_active = 1 
                   LIMIT 4";
    $stmtRelated = $db->prepare($sqlRelated);
    $stmtRelated->bindParam(':category_id', $product['category_id']);
    $stmtRelated->bindParam(':id', $product_id);
    $stmtRelated->execute();
    $relatedProducts = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);

    // Termék kosárba helyezése
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
        try {
            // ... existing code ...
            
            if ($cart_success) {
                Notification::add('A termék sikeresen hozzáadva a kosárhoz!', 'success');
                header('Location: cart.php');
                exit;
            } else {
                Notification::add('Nem sikerült hozzáadni a terméket a kosárhoz!', 'failure');
            }
        } catch (Exception $e) {
            Notification::add('Hiba történt a termék kosárba helyezése során: ' . $e->getMessage(), 'failure');
        }
    }
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> | Webshop</title>
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
                            <a href="#" class="nav-link active">Termékek</a>
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
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-primary-dark bg-primary-lightest">Termékek</a>
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
    
    <!-- Breadcrumb -->
    <div class="bg-primary-lightest">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center space-x-2 text-sm">
                <a href="index.php" class="text-primary-dark hover:text-primary-darkest transition-colors">Kezdőlap</a>
                <span class="text-primary-dark">/</span>
                <a href="category.php?id=<?php echo $product['category_id']; ?>" class="text-primary-dark hover:text-primary-darkest transition-colors"><?php echo $product['category_name']; ?></a>
                <span class="text-primary-dark">/</span>
                <span class="text-primary-darkest font-medium"><?php echo $product['name']; ?></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="card p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Product Image -->
                <div>
                    <div class="relative rounded-lg overflow-hidden bg-white shadow">
                        <?php if(!empty($product['image_url'])): ?>
                            <img id="main-image" src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="w-full h-auto object-contain aspect-square">
                        <?php else: ?>
                            <div class="w-full h-96 flex items-center justify-center bg-primary-lightest">
                                <svg class="w-16 h-16 text-primary-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Product Details -->
                <div>
                    <div class="badge badge-secondary inline-block mb-2"><?php echo $product['category_name']; ?></div>
                    <h1 class="text-3xl font-bold mb-4 text-primary-darkest"><?php echo $product['name']; ?></h1>
                    
                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            <span>★</span><span>★</span><span>★</span><span>★</span><span class="text-gray-300">★</span>
                        </div>
                        <span class="text-sm ml-2 text-primary-dark">(4/5, 24 értékelés)</span>
                    </div>
                    
                    <?php if(isset($product['price'])): ?>
                        <div class="text-3xl font-bold text-primary-dark mb-6">
                            <?php echo number_format($product['price'], 0, ',', ' '); ?> Ft
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($product['description'])): ?>
                        <div class="mb-6 text-primary-darkest">
                            <p><?php echo nl2br($product['description']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Quantity Selector -->
                    <div class="mb-6">
                        <label class="form-label">Mennyiség:</label>
                        <div class="flex items-center">
                            <button class="counter-btn decrease">-</button>
                            <input type="number" min="1" value="1" class="counter-input mx-2">
                            <button class="counter-btn increase">+</button>
                        </div>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <div class="flex gap-2 mb-6">
                        <button class="btn btn-primary py-3 flex-grow" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Kosárba
                        </button>
                        <button class="btn btn-outline py-3" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            Kívánságlistához
                        </button>
                    </div>
                    
                    <!-- Product features -->
                    <div class="border-t border-primary-lightest pt-4">
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 mr-2 text-primary-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-primary-dark">Ingyenes kiszállítás 15.000 Ft felett</span>
                        </div>
                        <div class="flex items-center mb-3">
                            <svg class="w-5 h-5 mr-2 text-primary-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-primary-dark">30 napos pénzvisszafizetési garancia</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-primary-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-primary-dark">Biztonságos fizetés</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="mt-12">
            <div class="card">
                <div class="border-b border-primary-lightest">
                    <div class="flex">
                        <button class="tab-button active py-4 px-6 font-semibold text-primary-dark border-b-2 border-primary-light" data-tab="description">Leírás</button>
                        <button class="tab-button py-4 px-6 font-semibold text-primary-dark opacity-60" data-tab="reviews">Értékelések</button>
                        <button class="tab-button py-4 px-6 font-semibold text-primary-dark opacity-60" data-tab="shipping">Szállítás</button>
                    </div>
                </div>
                <div class="tab-content p-6" id="description-content">
                    <?php if(!empty($product['description'])): ?>
                        <p class="text-primary-darkest"><?php echo nl2br($product['description']); ?></p>
                    <?php else: ?>
                        <p class="text-primary-dark">Nincs részletes leírás a termékhez.</p>
                    <?php endif; ?>
                </div>
                <div class="tab-content hidden p-6" id="reviews-content">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-primary-darkest">Vásárlói értékelések</h3>
                        <button class="btn btn-outline" id="write-review-btn">Értékelés írása</button>
                    </div>
                    <div class="text-center text-primary-dark py-8">
                        <p>Jelenleg nincsenek értékelések.</p>
                    </div>
                </div>
                <div class="tab-content hidden p-6" id="shipping-content">
                    <h3 class="text-xl font-bold text-primary-darkest mb-4">Szállítási információk</h3>
                    <ul class="list-disc pl-5 text-primary-dark">
                        <li class="mb-2">Szállítási idő: 2-3 munkanap</li>
                        <li class="mb-2">Kiszállítás díja: 1 500 Ft</li>
                        <li class="mb-2">Ingyenes kiszállítás 15 000 Ft feletti rendelés esetén</li>
                        <li class="mb-2">Házhozszállítás futárszolgálattal</li>
                        <li>Átvehető csomagponton is</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(count($relatedProducts) > 0): ?>
        <div class="mt-20">
            <h2 class="section-title text-3xl mb-12">Kapcsolódó termékek</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach($relatedProducts as $related): ?>
                    <div class="product-card">
                        <img src="<?php echo $related['image_url']; ?>" alt="<?php echo $related['name']; ?>">
                        <div class="product-card-content">
                            <h3><?php echo $related['name']; ?></h3>
                            <div class="product-card-price"><?php echo number_format($related['price'], 0, ',', ' '); ?> Ft</div>
                            <div class="product-card-footer">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="btn btn-primary w-full">Részletek</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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
        
        // Product tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.tab-button').forEach(btn => {
                    btn.classList.remove('active', 'border-primary-light');
                    btn.classList.add('opacity-60');
                });
                
                // Add active class to clicked button
                this.classList.add('active', 'border-primary-light');
                this.classList.remove('opacity-60');
                
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show the content for the clicked tab
                document.getElementById(this.dataset.tab + '-content').classList.remove('hidden');
            });
        });
        
        // Quantity counter
        document.querySelector('.decrease').addEventListener('click', function() {
            let input = document.querySelector('.counter-input');
            let value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        });
        
        document.querySelector('.increase').addEventListener('click', function() {
            let input = document.querySelector('.counter-input');
            let value = parseInt(input.value);
            input.value = value + 1;
        });
        
        // Add to cart function
        function addToCart(productId) {
            const quantity = document.querySelector('.counter-input').value;
            // A globális kosár objektumot használjuk, ha létezik
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
                        const totalItems = cart.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
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
    </script>
    
    <script>
        // Kosár számláló frissítése az oldal betöltésekor
        document.addEventListener('DOMContentLoaded', function() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                const cart = localStorage.getItem('cart');
                const items = cart ? JSON.parse(cart) : [];
                const totalItems = items.reduce((sum, item) => sum + parseInt(item.quantity || 0), 0);
                cartCount.textContent = totalItems;
            }
        });
    </script>
</body>
</html>