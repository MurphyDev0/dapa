-- Termék méretek inicializálása
-- Ez a script létrehozza az alapértelmezett méreteket a product_sizes táblában

-- Ellenőrizzük, hogy létezik-e a product_sizes tábla
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Töröljük az esetleges duplikátumokat
DELETE FROM product_sizes WHERE name IN ('XS', 'S', 'M', 'L', 'XL', 'XXL', 'One Size', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46');

-- Ruházat méretek beszúrása
INSERT INTO product_sizes (name, description, sort_order, is_active) VALUES
('XS', 'Extra Small - Ruházat', 1, 1),
('S', 'Small - Ruházat', 2, 1),
('M', 'Medium - Ruházat', 3, 1),
('L', 'Large - Ruházat', 4, 1),
('XL', 'Extra Large - Ruházat', 5, 1),
('XXL', 'Extra Extra Large - Ruházat', 6, 1);

-- Cipő méretek beszúrása
INSERT INTO product_sizes (name, description, sort_order, is_active) VALUES
('36', 'Cipőméret 36', 10, 1),
('37', 'Cipőméret 37', 11, 1),
('38', 'Cipőméret 38', 12, 1),
('39', 'Cipőméret 39', 13, 1),
('40', 'Cipőméret 40', 14, 1),
('41', 'Cipőméret 41', 15, 1),
('42', 'Cipőméret 42', 16, 1),
('43', 'Cipőméret 43', 17, 1),
('44', 'Cipőméret 44', 18, 1),
('45', 'Cipőméret 45', 19, 1),
('46', 'Cipőméret 46', 20, 1);

-- Egyéb méretek
INSERT INTO product_sizes (name, description, sort_order, is_active) VALUES
('One Size', 'Univerzális méret', 30, 1),
('Mini', 'Mini méret', 31, 1),
('Midi', 'Közepes méret', 32, 1),
('Maxi', 'Nagy méret', 33, 1);

-- Ellenőrizzük, hogy létezik-e a product_size_stock tábla
CREATE TABLE IF NOT EXISTS product_size_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    reserved_stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES product_sizes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size_id)
);

-- Index létrehozása a jobb teljesítmény érdekében
CREATE INDEX idx_product_size_stock_product ON product_size_stock(product_id);
CREATE INDEX idx_product_size_stock_size ON product_size_stock(size_id);
CREATE INDEX idx_product_sizes_active ON product_sizes(is_active, sort_order);

-- Státusz
SELECT 'Termék méretek sikeresen inicializálva!' as status; 