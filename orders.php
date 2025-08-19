<?php
require_once 'session_config.php';
require_once 'config.php';
require_once 'auth.php';
require_once 'notifications.php';

// ... existing code ...

// Rendelés törlése
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    try {
        $order_id = $_POST['order_id'];
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$order_id, $_SESSION['user_id']])) {
            Notification::add('A rendelés sikeresen törölve!', 'success');
        } else {
            Notification::add('Nem sikerült törölni a rendelést!', 'failure');
        }
    } catch (Exception $e) {
        Notification::add('Hiba történt a rendelés törlése során: ' . $e->getMessage(), 'failure');
    }
}

?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rendeléseim</title>
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
// ... existing code ... 