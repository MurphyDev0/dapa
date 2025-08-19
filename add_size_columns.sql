-- Méretek tábla létrehozása
CREATE TABLE IF NOT EXISTS product_sizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Alapértelmezett méretek beszúrása
INSERT INTO product_sizes (name, description) VALUES
('XS', 'Extra Small'),
('S', 'Small'),
('M', 'Medium'),
('L', 'Large'),
('XL', 'Extra Large'),
('XXL', 'Double Extra Large');

-- Termék méretek tábla létrehozása (a termékek és méretek kapcsolata)
CREATE TABLE IF NOT EXISTS product_size_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (size_id) REFERENCES product_sizes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size_id)
);

-- Méret oszlop hozzáadása a termékek táblához
ALTER TABLE products ADD COLUMN size_id INT DEFAULT NULL;
ALTER TABLE products ADD FOREIGN KEY (size_id) REFERENCES product_sizes(id); 