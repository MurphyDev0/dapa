-- Teszt felhasználók beszúrása
INSERT IGNORE INTO users (id, username, email, password, name, created_at) VALUES
(1, 'nagyjanos', 'nagy.janos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nagy János', '2024-01-15 10:00:00'),
(2, 'kisseva', 'kiss.eva@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kiss Éva', '2024-01-16 11:00:00'),
(3, 'szabopeter', 'szabo.peter@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Szabó Péter', '2024-01-17 12:00:00'),
(4, 'tothanna', 'toth.anna@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tóth Anna', '2024-01-18 13:00:00'),
(5, 'kovacsistvan', 'kovacs.istvan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kovács István', '2024-01-19 14:00:00'),
(6, 'molnarzsuzsa', 'molnar.zsuzsa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Molnár Zsuzsa', '2024-01-20 15:00:00'),
(7, 'baloghferenc', 'balogh.ferenc@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Balogh Ferenc', '2024-01-21 16:00:00'); 