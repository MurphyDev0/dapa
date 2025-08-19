-- Teszt rendelési tételek beszúrása
-- Ez a fájl csak akkor fut le, ha már léteznek rendelések az orders táblában

-- Rendelési tételek beszúrása biztonságosan
INSERT IGNORE INTO order_items (order_id, product_id, product_name, quantity, price, total_price) VALUES
(1, 1, 'Okostelefon', 1, 299.99, 299.99),
(1, 3, 'Póló', 2, 29.99, 59.98),
(2, 2, 'Laptop', 1, 999.99, 999.99),
(3, 5, 'Futócipő', 1, 89.99, 89.99),
(3, 6, 'Társasjáték', 1, 39.99, 39.99); 