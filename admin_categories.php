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

// Képfeltöltés kezelése
function handleImageUpload($file, $type = 'category') {
    $uploadDir = 'uploads/' . $type . 's/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Csak JPEG, PNG, GIF és WebP képformátumok engedélyezettek!');
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('A kép mérete nem lehet nagyobb 5MB-nál!');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    } else {
        throw new Exception('Hiba történt a kép feltöltése során!');
    }
}

// Kategória létrehozása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $slug = trim($_POST['slug']);
    
    try {
        $imageUrl = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageUrl = handleImageUpload($_FILES['image'], 'category');
        }
        
        $categoryId = $categoryManager->createCategory($name, $description, $parentId, $imageUrl, $slug);
        Notification::add('A kategória sikeresen létrehozva!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kategória létrehozása során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_categories.php");
    exit;
}

// Kategória frissítése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
    $slug = trim($_POST['slug']);
    
    try {
        $imageUrl = $_POST['current_image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $imageUrl = handleImageUpload($_FILES['image'], 'category');
        }
        
        $categoryManager->updateCategory($id, $name, $description, $parentId, $imageUrl, $slug);
        Notification::add('A kategória sikeresen frissítve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kategória frissítése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_categories.php");
    exit;
}

// Kategória törlése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = $_POST['category_id'];
    
    try {
        $categoryManager->deleteCategory($id);
        Notification::add('A kategória sikeresen törölve!', 'success');
    } catch (Exception $e) {
        Notification::add('Hiba történt a kategória törlése során: ' . $e->getMessage(), 'failure');
    }
    
    header("Location: admin_categories.php");
    exit;
}

// Kategóriák lekérése
$categories = $categoryManager->getAllCategoriesHierarchical();
$mainCategories = $categoryManager->getMainCategories();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategóriák Kezelése - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="new_styles.css">
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
<body class="bg-pattern flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <div class="w-full md:w-64 bg-primary-darkest text-white flex flex-col md:h-screen">
        <div class="p-4 bg-primary-dark">
            <h2 class="text-2xl font-bold">AdminPanel</h2>
        </div>
        <nav class="flex-grow p-4">
            <ul class="space-y-2">
                <li>
                    <a href="admin.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">🏠</span>
                        <span>Vezérlőpult</span>
                    </a>
                </li>
                <li>
                    <a href="admin_products.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">🛒</span>
                        <span>Termékek</span>
                    </a>
                </li>
                <li>
                    <a href="admin_categories.php" class="flex items-center p-2 bg-primary-light rounded text-white">
                        <span class="mr-2">📂</span>
                        <span>Kategóriák</span>
                    </a>
                </li>
                <li>
                    <a href="admin_orders.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">📦</span>
                        <span>Rendelések</span>
                    </a>
                </li>
                <li>
                    <a href="admin_customers.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">👥</span>
                        <span>Ügyfelek</span>
                    </a>
                </li>
                <li>
                    <a href="admin_coupons.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">🎟️</span>
                        <span>Kuponok</span>
                    </a>
                </li>
                <li>
                    <a href="admin_reports.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
                        <span class="mr-2">📊</span>
                        <span>Jelentések</span>
                    </a>
                </li>
                <li>
                    <a href="admin_settings.php" class="flex items-center p-2 hover:bg-primary-dark rounded text-white">
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
                <h1 class="text-xl font-bold text-primary-darkest">Kategóriák Kezelése</h1>
                <div class="flex space-x-4">
                    <button onclick="showCreateCategoryModal()" class="bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        Új kategória
                    </button>
                </div>
            </div>
        </header>

        <main class="p-4">
            <?php echo Notification::display(); ?>
            
            <!-- Kategóriák táblázat -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-primary-darkest">Kategóriák listája</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary-lightest">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Kategória</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Szülő kategória</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Termékek száma</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-primary-darkest uppercase tracking-wider">Státusz</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-primary-darkest uppercase tracking-wider">Műveletek</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                            <tr class="<?php echo $category['parent_id'] ? 'bg-gray-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($category['parent_id']): ?>
                                            <div class="ml-4 mr-2 text-gray-400">↳</div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($category['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                 class="h-10 w-10 rounded-lg object-cover mr-3">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-lg bg-primary-lightest flex items-center justify-center mr-3">
                                                <span class="text-primary-darkest font-medium">
                                                    <?php echo htmlspecialchars(substr($category['name'], 0, 2)); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div class="text-sm font-medium text-primary-darkest">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                            <div class="text-sm text-primary-dark">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </div>
                                            <?php if (!empty($category['slug'])): ?>
                                                <div class="text-xs text-gray-500">
                                                    Slug: <?php echo htmlspecialchars($category['slug']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php echo $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '-'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-primary-darkest">
                                        <?php echo $category['product_count']; ?> termék
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($category['is_active']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Aktív
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Inaktív
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="editCategory(<?php echo $category['id']; ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        Szerkesztés
                                    </button>
                                    <form method="POST" class="inline" onsubmit="return confirm('Biztosan törli ezt a kategóriát? Ez a művelet nem vonható vissza!');">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <input type="hidden" name="delete_category" value="1">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            Törlés
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Új kategória modal -->
    <div id="createCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
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
                        <textarea name="description" class="form-control" rows="3" placeholder="Kategória leírása"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Szülő kategória</label>
                        <select name="parent_id" class="form-control">
                            <option value="">Nincs (főkategória)</option>
                            <?php foreach ($mainCategories as $mainCategory): ?>
                                <option value="<?php echo $mainCategory['id']; ?>">
                                    <?php echo htmlspecialchars($mainCategory['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Slug (URL-barát név)</label>
                        <input type="text" name="slug" class="form-control" placeholder="kategoriaSlug">
                        <div class="text-xs text-gray-500 mt-1">Üresen hagyva automatikusan generálódik</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-primary-darkest mb-1">Kép</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
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

    <script>
        function showCreateCategoryModal() {
            document.getElementById('createCategoryModal').classList.remove('hidden');
        }

        function hideCreateCategoryModal() {
            document.getElementById('createCategoryModal').classList.add('hidden');
        }

        function editCategory(id) {
            // Itt implementálható a szerkesztési funkció
            alert('Szerkesztési funkció még nem implementálva kategória ID: ' + id);
        }

        // Modal bezárása háttérre kattintáskor
        document.getElementById('createCategoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideCreateCategoryModal();
            }
        });
    </script>
</body>
</html> 