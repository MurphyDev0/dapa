<?php
require_once 'config.php';
require_once 'auth.php';

// Biztosítjuk, hogy a session el van indítva
require_once 'session_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $auth = new Auth($db);
    $user = $auth->login($username, $password);
    
    if ($user) {
        $_SESSION['is_logged'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];
        header('Location: index.php');
        Notification::add('Sikeres bejelentkezés!', 'success');
    } else {
        header('Location: login.php?error=invalid_credentials');
        Notification::add('Hibás email cím vagy jelszó.', 'failure');
    }
}
?>