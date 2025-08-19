-- Teszt adatok beszúrása a riport táblákhoz
-- Ez a fájl biztonságosan beszúrja az adatokat anélkül, hogy törölné a meglévőket

-- Régiók beszúrása
INSERT IGNORE INTO regions (id, name, country_code, is_active) VALUES 
(1, 'Budapest', 'HU', 1),
(2, 'Pest megye', 'HU', 1),
(3, 'Debrecen', 'HU', 1),
(4, 'Szeged', 'HU', 1),
(5, 'Pécs', 'HU', 1),
(6, 'Bécs', 'AT', 1),
(7, 'Pozsony', 'SK', 1),
(8, 'Zágráb', 'HR', 1);

-- Kategóriák beszúrása
INSERT INTO categories (name, description, slug, is_active) VALUES
('Elektronika', 'Elektronikai termékek', 'elektronika', 1),
('Ruházat', 'Ruházati termékek', 'ruhazat', 1),
('Könyvek', 'Könyvek és kiadványok', 'konyvek', 1),
('Sport', 'Sportfelszerelések', 'sport', 1),
('Játékok', 'Játékok és szórakozás', 'jatekok', 1);

-- Marketing kampányok beszúrása
INSERT IGNORE INTO marketing_campaigns (campaign_name, campaign_type, start_date, end_date, budget, total_revenue, conversion_rate, roi) VALUES
('Tavaszi vásár', 'seasonal', '2024-03-01', '2024-03-31', 500000, 1500000, 3.5, 200),
('Húsvéti akció', 'holiday', '2024-04-01', '2024-04-10', 300000, 900000, 4.2, 300),
('Nyári kiárusítás', 'seasonal', '2024-06-01', '2024-06-30', 800000, 2400000, 3.8, 250),
('Email kampány', 'email', '2024-03-15', '2024-03-20', 100000, 450000, 4.5, 350),
('Social Media', 'social', '2024-03-10', '2024-03-25', 200000, 800000, 4.0, 300);

-- Weboldal látogatottsági adatok
INSERT IGNORE INTO website_analytics (page_url, visit_date, pageviews, unique_visitors, bounce_rate, avg_time_on_page, device_type) VALUES
('/', CURDATE(), 1500, 800, 35.5, 180, 'desktop'),
('/termekek', CURDATE(), 2500, 1200, 28.3, 240, 'desktop'),
('/akciok', CURDATE(), 1800, 950, 32.1, 160, 'mobile'),
('/blog', CURDATE(), 500, 300, 45.2, 120, 'tablet'),
('/kapcsolat', CURDATE(), 300, 250, 25.8, 90, 'mobile'),
('/', CURDATE() - INTERVAL 1 DAY, 1400, 750, 38.5, 175, 'desktop'),
('/termekek', CURDATE() - INTERVAL 1 DAY, 2300, 1100, 30.3, 230, 'desktop'),
('/akciok', CURDATE() - INTERVAL 1 DAY, 1600, 850, 34.1, 150, 'mobile');

-- Vásárlói demográfia
INSERT IGNORE INTO customer_demographics (customer_id, age_group, gender, location, loyalty_points, total_purchases, first_purchase_date, last_purchase_date) VALUES
(1, '18-25', 'Férfi', 'Budapest', 1500, 12, '2023-01-15', NOW()),
(2, '26-35', 'Nő', 'Debrecen', 2500, 20, '2023-02-20', NOW()),
(3, '36-45', 'Férfi', 'Szeged', 1800, 15, '2023-03-10', NOW()),
(4, '46-55', 'Nő', 'Pécs', 3000, 25, '2023-04-05', NOW()),
(5, '26-35', 'Férfi', 'Budapest', 2000, 18, '2023-05-15', NOW()),
(6, '18-25', 'Nő', 'Bécs', 1000, 8, '2024-01-15', NOW()),
(7, '36-45', 'Férfi', 'Pozsony', 500, 5, '2024-02-01', NOW()),
(8, '26-35', 'Nő', 'Zágráb', 800, 7, '2024-02-15', NOW());

-- Sales data beszúrása részletesebb adatokkal
INSERT IGNORE INTO sales_data (category_id, quantity, price, total_amount, order_date, payment_method, region_id, customer_id) VALUES
(1, 2, 150000, 300000, NOW() - INTERVAL 1 DAY, 'card', 1, 1),
(2, 3, 45000, 135000, NOW() - INTERVAL 2 DAY, 'cash', 2, 2),
(3, 1, 12000, 12000, NOW() - INTERVAL 3 DAY, 'transfer', 3, 3),
(4, 4, 25000, 100000, NOW() - INTERVAL 4 DAY, 'card', 4, 4),
(5, 2, 35000, 70000, NOW() - INTERVAL 5 DAY, 'cash', 5, 5),
(1, 1, 180000, 180000, NOW() - INTERVAL 1 DAY, 'card', 6, 6),
(2, 2, 55000, 110000, NOW() - INTERVAL 2 DAY, 'paypal', 7, 7),
(3, 3, 15000, 45000, NOW() - INTERVAL 3 DAY, 'card', 8, 8),
-- Visszatérő vásárlók tranzakciói
(1, 1, 120000, 120000, NOW() - INTERVAL 10 DAY, 'card', 1, 1),
(2, 2, 35000, 70000, NOW() - INTERVAL 15 DAY, 'cash', 2, 2),
(3, 1, 18000, 18000, NOW() - INTERVAL 20 DAY, 'transfer', 3, 3);

-- Customer service reports bővített adatokkal
INSERT IGNORE INTO customer_service_reports (customer_id, issue_type, satisfaction_rating, resolution_time, created_at, resolved_at) VALUES
(1, 'Szállítási késés', 4, 120, NOW() - INTERVAL 1 DAY, NOW()),
(2, 'Termék minőség', 5, 60, NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 1 DAY),
(3, 'Méretprobléma', 3, 180, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY),
(4, 'Garanciális ügy', 4, 240, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY),
(5, 'Visszatérítés', 5, 30, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 4 DAY),
(6, 'Termék információ', 4, 45, NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 5 DAY),
(7, 'Szállítási cím módosítás', 5, 15, NOW() - INTERVAL 7 DAY, NOW() - INTERVAL 6 DAY),
(8, 'Számlázási probléma', 3, 90, NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 7 DAY);

-- Returns (visszaküldések) részletes adatokkal
INSERT IGNORE INTO returns (order_id, customer_id, product_id, return_reason, return_date, refund_amount, status) VALUES
(1, 1, 1, 'Méret nem megfelelő', NOW() - INTERVAL 5 DAY, 150000, 'Elfogadva'),
(2, 2, 2, 'Minőségi probléma', NOW() - INTERVAL 6 DAY, 45000, 'Elfogadva'),
(3, 3, 3, 'Nem tetszik', NOW() - INTERVAL 7 DAY, 12000, 'Elutasítva'),
(4, 4, 4, 'Sérült termék', NOW() - INTERVAL 8 DAY, 25000, 'Elfogadva'),
(5, 5, 5, 'Más terméket kaptam', NOW() - INTERVAL 9 DAY, 35000, 'Folyamatban');

-- Email marketing statisztikák bővített adatokkal
INSERT IGNORE INTO email_marketing_stats (campaign_id, send_date, emails_sent, opened, clicked, unsubscribed, bounced) VALUES
(1, NOW() - INTERVAL 1 DAY, 5000, 2500, 800, 10, 50),
(2, NOW() - INTERVAL 2 DAY, 3000, 1500, 450, 5, 30),
(3, NOW() - INTERVAL 3 DAY, 4000, 2000, 600, 8, 40),
(4, NOW() - INTERVAL 4 DAY, 6000, 3000, 900, 12, 60),
(5, NOW() - INTERVAL 5 DAY, 3500, 1750, 525, 7, 35);

-- Közösségi média konverziók
INSERT IGNORE INTO social_media_conversions (platform, conversion_date, clicks, impressions, conversions, revenue, ad_spend) VALUES
('Facebook', CURDATE(), 1200, 15000, 45, 225000, 50000),
('Instagram', CURDATE(), 800, 10000, 30, 150000, 35000),
('TikTok', CURDATE(), 1500, 20000, 60, 300000, 75000),
('Pinterest', CURDATE(), 600, 8000, 25, 125000, 30000),
('LinkedIn', CURDATE(), 400, 5000, 15, 75000, 20000);

-- Kupon használat
INSERT IGNORE INTO coupon_usage (coupon_code, discount_amount, used_date) VALUES
('TAVASZ24', 5000, NOW() - INTERVAL 1 DAY),
('NYAR24', 3000, NOW() - INTERVAL 2 DAY),
('HUSVET24', 2000, NOW() - INTERVAL 3 DAY),
('SZULINAP24', 4000, NOW() - INTERVAL 4 DAY),
('UJVEVO24', 1000, NOW() - INTERVAL 5 DAY); 