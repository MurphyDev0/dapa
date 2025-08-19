<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    switch ($action) {
        case 'update_profile':
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';

            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Profil sikeresen frissítve!";
            } else {
                $_SESSION['error'] = "Hiba történt a profil frissítése során.";
            }
            break;

        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Az új jelszavak nem egyeznek!";
                break;
            }

            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Jelszó sikeresen megváltoztatva!";
                } else {
                    $_SESSION['error'] = "Hiba történt a jelszó megváltoztatása során.";
                }
            } else {
                $_SESSION['error'] = "Hibás jelenlegi jelszó!";
            }
            break;

        case 'update_notifications':
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
            $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;

            $stmt = $conn->prepare("UPDATE users SET email_notifications = ?, sms_notifications = ?, push_notifications = ? WHERE id = ?");
            $stmt->bind_param("iiii", $email_notifications, $sms_notifications, $push_notifications, $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Értesítési beállítások sikeresen frissítve!";
            } else {
                $_SESSION['error'] = "Hiba történt az értesítési beállítások frissítése során.";
            }
            break;
    }

    header('Location: settings.php');
    exit();
}
?> 