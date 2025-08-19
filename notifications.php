<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Notification {
    private static $notifications = [];

    public static function add($message, $type = 'info') {
        self::$notifications[] = [
            'message' => $message,
            'type' => $type
        ];
        $_SESSION['notifications'] = self::$notifications;
    }

    public static function get() {
        $notifications = $_SESSION['notifications'] ?? [];
        unset($_SESSION['notifications']);
        return $notifications;
    }

    public static function display() {
        $notifications = self::get();
        if (empty($notifications)) {
            return '';
        }

        $html = '<div class="notification-container"><script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    const notifications = document.querySelectorAll(".notification");
                    notifications.forEach(function(notification) {
                        notification.classList.add("removing");
                        setTimeout(function() {
                            notification.remove();
                        }, 300);
                    });
                }, 5000);
            });
        </script>';
        foreach ($notifications as $notification) {
            $html .= sprintf(
                '<div class="notification notification-%s">
                    <div class="notification_body">
                        <p>%s</p>
                    </div>
                    <div class="notification_progress"></div>
                </div>',
                htmlspecialchars($notification['type']),
                htmlspecialchars($notification['message'])
            );
        }
        $html .= '</div>';

        return $html;
    }
}

// Példa használat:
// Notification::add('Sikeres mentés!', 'success');
// Notification::add('Hiba történt!', 'failure');
// Notification::add('Figyelmeztetés!', 'warning');
// Notification::add('Információ!', 'info');
?> 