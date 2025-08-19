<?php
require_once 'session_config.php';
include 'config.php';

// Ellenőrizzük, hogy a felhasználó már be van-e jelentkezve
if (isset($_SESSION['is_logged']) && $_SESSION['is_logged'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Form feldolgozása
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);
    
    // Mezők ellenőrzése
    if (empty($name) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Kérjük, töltse ki az összes mezőt.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Kérjük, adjon meg egy érvényes email címet.';
    } elseif ($password !== $password_confirm) {
        $error = 'A két jelszó nem egyezik.';
    } elseif (strlen($password) < 6) {
        $error = 'A jelszónak legalább 6 karakter hosszúnak kell lennie.';
    } else {
        // Ellenőrizzük, hogy nem létezik-e már a felhasználó
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = 'Ez az email cím már regisztrálva van.';
        } else {
            // Jelszó hashelése
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Felhasználó létrehozása
            $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, 0)");
            $insert_stmt->bindParam(':name', $name);
            $insert_stmt->bindParam(':email', $email);
            $insert_stmt->bindParam(':password', $hashed_password);
            
            if ($insert_stmt->execute()) {
                $success = 'Sikeres regisztráció! Most már bejelentkezhet.';
                // Ha automatikus bejelentkezést szeretnénk, itt meg lehetne tenni
            } else {
                $error = 'Hiba történt a regisztráció során. Kérjük, próbálja újra később.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció | Webshop</title>
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
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-pattern">
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
                            <a href="#" class="nav-link">Kategóriák</a>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="login.php" class="btn btn-outline">Bejelentkezés</a>
                    <a href="register.php" class="nav-link active">Regisztráció</a>
                    <a href="cart.php" class="p-1 rounded-full text-primary-dark hover:text-primary-light transition duration-300 relative">
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">0</span>
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
                <a href="#" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Kategóriák</a>
                <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-darkest hover:bg-primary-lightest hover:text-primary-dark">Bejelentkezés</a>
                <a href="register.php" class="block px-3 py-2 rounded-md text-base font-medium text-primary-dark bg-primary-lightest">Regisztráció</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="max-w-md mx-auto">
            <div class="card overflow-hidden">
                <div class="card-banner"></div>
                <div class="p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-primary-darkest">Regisztráció</h2>
                        <p class="text-primary-dark mt-2">Hozzon létre egy új fiókot</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p><?php echo $success; ?></p>
                            <a href="login.php" class="font-medium underline">Bejelentkezés</a>
                        </div>
                    <?php endif; ?>
                    
                    <form action="register.php" method="post">
                        <div class="mb-6">
                            <label for="name" class="form-label">Teljes név</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-6">
                            <label for="email" class="form-label">Email cím</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password" class="form-label">Jelszó</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   required minlength="6">
                            <p class="text-xs text-primary-dark mt-1">A jelszónak legalább 6 karakter hosszúnak kell lennie.</p>
                        </div>
                        
                        <div class="mb-6">
                            <label for="password_confirm" class="form-label">Jelszó megerősítése</label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                                   required minlength="6">
                        </div>
                        
                        <div class="flex items-center mb-6">
                            <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-primary-light focus:ring-primary-light border-gray-300 rounded" required>
                            <label for="terms" class="ml-2 block text-sm text-primary-dark">
                                Elfogadom az <a href="#" class="text-primary-light hover:text-primary-dark">Általános Szerződési Feltételeket</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full py-3">Regisztráció</button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-primary-dark">Már van fiókja? <a href="login.php" class="text-primary-light font-medium hover:text-primary-dark">Jelentkezzen be itt</a></p>
                    </div>
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
    </script>
</body>
</html>