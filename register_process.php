<?php
require_once 'config.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        header('Location: register.php?error=passwords_dont_match');
        exit;
    }
    
    $auth = new Auth($db);
    if ($auth->register($username, $fullname,  $password, $email)) {
        header('Location: login.php?success=registration_complete');
    } else {
        header('Location: register.php?error=registration_failed');
    }
}
?>