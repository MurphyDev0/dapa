-- Teszt termék értékelések beszúrása
-- Ez a fájl csak akkor fut le, ha már léteznek felhasználók és termékek

-- Példa értékelések beszúrása biztonságosan
INSERT IGNORE INTO product_reviews (product_id, user_id, rating, review_text, is_verified_purchase) VALUES
(1, 1, 5, 'Kiváló telefon, nagyon elégedett vagyok vele!', TRUE),
(1, 2, 4, 'Jó minőség, de az ár kicsit magas.', TRUE),
(2, 3, 5, 'Fantasztikus laptop, gyors és megbízható.', TRUE),
(3, 1, 4, 'Kényelmes póló, jó anyagból készült.', TRUE),
(4, 2, 5, 'Nagyon kényelmes kanapé, ajánlom mindenkinek!', TRUE),
(5, 3, 4, 'Jó futócipő, de kicsit szűk.', TRUE),
(6, 1, 5, 'Szuper társasjáték, a család imádja!', TRUE); 