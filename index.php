<?php
    require_once 'session_config.php';
    include 'config.php';

    require_once 'get_products.php';

    $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;
    $is_admin = isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : 0; // Admin jogosultság ellenőrzése
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webshop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="new_styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    <?php 
    require_once 'notifications.php';
    echo Notification::display(); 
    ?>

    <button id="scroll-to-top"><i class="fa-solid fa-arrow-up"></i></button>

    <header class="header">
        <div class="container max-w-7xl mx-auto px-4 sm:px-6 lg:px8 py-4" bis_skin_checked="1">
            <div class="logo" bis_skin_checked="1">
                <h1>
                    Dapa 
                </h1>    
            </div>
            <div class="search-bar" bis_skin_checked="1">
                <form action="search.php" method="GET" class="flex items-center">
                    <input type="text" id="search-input" name="search-query" placeholder="Keresés..." class="search-input" required>
                    <div id="search-suggestions" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-b-lg shadow-lg z-50 hidden max-h-96 overflow-y-auto"></div>
                </form>
                <button type="button" class="search-button">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
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
                            <a href="index.php" class="nav-link active">Kezdőlap</a>
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
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-dark bg-primary-lightest">Kezdőlap</a>
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

    <!-- Hero Section -->
    <div class="bg-primary-lightest py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-primary-darkest sm:text-4xl">
                    Üdvözöljük Webshopunkban!
                </h2>
                <p class="mt-4 max-w-2xl mx-auto text-xl text-primary-dark">
                    Fedezze fel kiváló termékeinket, amiket gondosan válogattunk össze az Ön számára.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="#categories" class="btn btn-primary mx-2">Kategóriák</a>
                    <a href="#" class="btn btn-secondary mx-2">Akciós termékek</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 bg-lightblue-50">
        <h2 id="categories" class="section-title text-3xl sm:text-4xl mb-12">Kategóriák</h2>
        
        <!-- Kategóriák megjelenítése -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <?php foreach($categories as $category): ?>
                <div class="category-card-wrapper">
                    <a href="category.php?id=<?php echo $category['id']; ?>" class="block">
                        <div class="category-card">
                            <img src="<?php echo htmlspecialchars($category['img']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                            <div class="category-card-content">
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo htmlspecialchars($category['description']); ?></p>
                                <div class="category-stats">
                                    <span class="text-sm text-primary-dark">
                                        <?php echo $category['product_count']; ?> termék
                                        <?php if ($category['subcategory_count'] > 0): ?>
                                            • <?php echo $category['subcategory_count']; ?> alkategória
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Alkategóriák megjelenítése (ha vannak) -->
                    <?php 
                    $subcategories = $categoryManager->getSubcategories($category['id']);
                    if (!empty($subcategories) && count($subcategories) <= 4): 
                    ?>
                    <div class="subcategories mt-3">
                        <div class="text-sm font-medium text-primary-darkest mb-2">Alkategóriák:</div>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($subcategories as $subcategory): ?>
                                <a href="category.php?id=<?php echo $subcategory['id']; ?>" 
                                   class="inline-block px-3 py-1 text-xs bg-primary-lightest text-primary-darkest rounded-full hover:bg-primary-light hover:text-white transition-colors">
                                    <?php echo htmlspecialchars($subcategory['name']); ?>
                                    <span class="text-gray-500">(<?php echo $subcategory['product_count']; ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        
        
        <!-- Features Section -->
        <div class="mt-20">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-lightest text-primary-darkest mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 text-primary-darkest">Pénzvisszafizetési garancia</h3>
                    <p class="text-primary-dark">30 napos pénzvisszafizetési garanciát vállalunk.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-lightest text-primary-darkest mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 text-primary-darkest">Ingyenes kiszállítás</h3>
                    <p class="text-primary-dark">15 000 Ft feletti rendelés esetén ingyenes kiszállítást biztosítunk.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-lightest text-primary-darkest mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold mb-2 text-primary-darkest">Biztonságos fizetés</h3>
                    <p class="text-primary-dark">Minden fizetési mód titkosított és biztonságos.</p>
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
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Kosár inicializálása, ha létezik 
        document.addEventListener('DOMContentLoaded', function() {
            // Ha a cart.php oldal már betöltődött, és létezik a Cart objektum
            if (typeof window.cart === 'undefined') {
                // Ha nem létezik globális Cart objektum, csak a számláló frissítjük
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    const cart = localStorage.getItem('cart');
                    const items = cart ? JSON.parse(cart) : [];
                    const totalItems = items.reduce((sum, item) => sum + (item.quantity || 0), 0);
                    cartCount.textContent = totalItems;
                }
            }
        });

        const searchBtn = document.querySelector(".search-button");
        const searchInput = document.querySelector("#search-input");
        const searchSuggestions = document.getElementById("search-suggestions");
        let searchTimeout;
        let currentFocus = -1;

        searchBtn.addEventListener("click", () => {
            searchInput.classList.toggle("active");
            if (searchInput.classList.contains("active")) {
                searchInput.focus();
            } else {
                hideSuggestions();
            }
        });

        // Keresési javaslatok funkció
        searchInput.addEventListener("input", function() {
            const query = this.value.trim();
            
            // Töröljük az előző timeout-ot
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                hideSuggestions();
                return;
            }
            
            // 300ms késleltetés a túl gyakori kérések elkerülésére
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        // Billentyűzet navigáció a javaslatokban
        searchInput.addEventListener("keydown", function(e) {
            const suggestions = searchSuggestions.querySelectorAll("li");
            
            if (e.key === "ArrowDown") {
                e.preventDefault();
                currentFocus++;
                if (currentFocus >= suggestions.length) currentFocus = 0;
                setActiveSuggestion(suggestions);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                currentFocus--;
                if (currentFocus < 0) currentFocus = suggestions.length - 1;
                setActiveSuggestion(suggestions);
            } else if (e.key === "Enter") {
                e.preventDefault();
                if (currentFocus > -1 && suggestions[currentFocus]) {
                    suggestions[currentFocus].click();
                } else {
                    // Ha nincs kiválasztott javaslat, küldje el a formot
                    this.closest('form').submit();
                }
            } else if (e.key === "Escape") {
                hideSuggestions();
            }
        });

        // Javaslatok lekérése
        function fetchSuggestions(query) {
            fetch(`search_suggest.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displaySuggestions(data);
                })
                .catch(error => {
                    console.error('Keresési javaslatok hiba:', error);
                    hideSuggestions();
                });
        }

        // Javaslatok megjelenítése
        function displaySuggestions(suggestions) {
            if (!suggestions || suggestions.length === 0) {
                hideSuggestions();
                return;
            }
            
            let html = '';
            suggestions.forEach((item, index) => {
                const price = item.price ? `${new Intl.NumberFormat('hu-HU').format(item.price)} Ft` : '';
                const image = item.img ? `<img src="${item.img}" alt="${item.name}" class="w-10 h-10 object-cover rounded">` : 
                             '<div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center"><svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>';
                
                html += `
                    <li class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" 
                        onclick="selectSuggestion('${item.name}', ${item.id})">
                        <div class="flex items-center space-x-3">
                            ${image}
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 truncate">${item.name}</div>
                                ${item.snippet ? `<div class="text-sm text-gray-500 truncate">${item.snippet}</div>` : ''}
                            </div>
                            ${price ? `<div class="text-sm font-medium text-primary-dark">${price}</div>` : ''}
                        </div>
                    </li>
                `;
            });
            
            searchSuggestions.innerHTML = `<ul class="py-1">${html}</ul>`;
            searchSuggestions.classList.remove("hidden");
            currentFocus = -1;
        }

        // Javaslat kiválasztása
        function selectSuggestion(name, productId) {
            if (productId) {
                // Ha van termék ID, irányítsuk át a termék oldalára
                window.location.href = `product.php?id=${productId}`;
            } else {
                // Egyébként töltsük ki a keresőmezőt és küldjük el a formot
                searchInput.value = name;
                searchInput.closest('form').submit();
            }
        }

        // Aktív javaslat beállítása
        function setActiveSuggestion(suggestions) {
            suggestions.forEach((item, index) => {
                if (index === currentFocus) {
                    item.classList.add("focused", "bg-gray-100");
                } else {
                    item.classList.remove("focused", "bg-gray-100");
                }
            });
        }

        // Javaslatok elrejtése
        function hideSuggestions() {
            searchSuggestions.classList.add("hidden");
            currentFocus = -1;
        }

        // Kattintás a dokumentumon kívül - javaslatok elrejtése
        document.addEventListener("click", function(e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target) && !searchBtn.contains(e.target)) {
                hideSuggestions();
            }
        });

        // Form submit kezelése
        searchInput.closest('form').addEventListener('submit', function(e) {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                e.preventDefault();
                alert('Kérjük, adjon meg legalább 2 karaktert a kereséshez.');
                return false;
            }
            hideSuggestions();
        });

        const scrollBtn = document.getElementById("scroll-to-top");

        window.addEventListener("scroll", () => {
        if (window.scrollY > 100) {
            scrollBtn.classList.add("show");
        } else {
            scrollBtn.classList.remove("show");
        }
        });

        scrollBtn.addEventListener("click", () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
        });

        // Globális függvények elérhetővé tétele
        window.selectSuggestion = selectSuggestion;
    </script>
</body>
</html>