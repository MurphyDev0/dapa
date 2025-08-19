-- Teszt rendelések beszúrása
INSERT IGNORE INTO orders (user_id, user_name, order_date, status, total_amount, category) VALUES
(1, 'Nagy János', '2024-03-15 10:30:00', 'completed', 159900, 'Elektronika'),
(2, 'Kiss Éva', '2024-03-15 14:15:00', 'processing', 45900, 'Ruházat'),
(3, 'Szabó Péter', '2024-03-15 16:45:00', 'completed', 89900, 'Elektronika'),
(4, 'Tóth Anna', '2024-03-16 09:20:00', 'pending', 12900, 'Könyvek'),
(5, 'Kovács István', '2024-03-16 11:30:00', 'completed', 29900, 'Sport'),
(1, 'Nagy János', '2024-03-16 15:45:00', 'processing', 79900, 'Elektronika'),
(6, 'Molnár Zsuzsa', '2024-03-17 08:15:00', 'completed', 19900, 'Ruházat'),
(2, 'Kiss Éva', '2024-03-17 13:20:00', 'pending', 35900, 'Könyvek'),
(7, 'Balogh Ferenc', '2024-03-17 16:30:00', 'completed', 129900, 'Elektronika'),
(3, 'Szabó Péter', '2024-03-18 10:45:00', 'processing', 45900, 'Sport'); 