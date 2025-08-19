<?php
    require_once 'session_config.php';
    require_once 'config.php';
    require_once 'category_functions.php';

    $is_logged = isset($_SESSION['is_logged']) ? $_SESSION['is_logged'] : false;

    // Kategória kezelő inicializálása
    $categoryManager = new CategoryManager($db);

    // Főkategóriák lekérése (alkategóriákkal együtt a hierarchikus megjelenítéshez)
    $categories = $categoryManager->getMainCategories();

    // Minden kategória lekérése hierarchikus szerkezetben (admin célokra)
    $allCategories = $categoryManager->getAllCategoriesHierarchical();

    // Termékek lekérése
    try {
        $sqlProducts = "SELECT p.*, c.name as category_name, 
                               COALESCE(p.image_url, 'img/placeholder.jpg') as image_url
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.is_active = 1 
                       ORDER BY p.created_at DESC";
        $resultProducts = $db->query($sqlProducts);
        $products = $resultProducts->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Hiba a termékek lekérése során: " . $e->getMessage());
        $products = [];
    }

    // Alapértelmezett képek a kategóriákhoz, ha nincs kép beállítva
    foreach ($categories as &$category) {
        if (empty($category['image_url'])) {
            // Alapértelmezett kép a kategória neve alapján
            $defaultImages = [
                'elektronika' => 'img/categories/electronics.jpg',
                'ruházat' => 'img/categories/clothing.jpg',
                'otthon' => 'img/categories/home.jpg',
                'sport' => 'img/categories/sports.jpg',
                'játékok' => 'img/categories/toys.jpg',
                'könyvek' => 'img/categories/books.jpg',
                'autó' => 'img/categories/automotive.jpg',
                'kert' => 'img/categories/garden.jpg'
            ];
            
            $categoryNameLower = strtolower($category['name']);
            $category['img'] = $defaultImages[$categoryNameLower] ?? 'img/categories/default.jpg';
        } else {
            $category['img'] = $category['image_url'];
        }
    }

?>