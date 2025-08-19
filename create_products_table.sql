-- Termékek tábla létrehozása
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    description TEXT,
    price DECIMAL(10,2),
    stock INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Termékek beszúrása
INSERT INTO products (name, category_id, description, price, stock) VALUES
('Samsung Galaxy S21', 1, 'Csúcskategóriás okostelefon', 299990, 50),
('iPhone 13 Pro', 1, 'Apple flagship telefon', 399990, 30),
('Nike Air Max', 2, 'Sportcipő', 44990, 100),
('Adidas póló', 2, 'Sportos póló', 9990, 200),
('Harry Potter teljes sorozat', 3, 'Könyvsorozat', 29990, 75),
('Az alapítvány', 3, 'Sci-fi könyv', 4990, 150),
('Fitnesz szett', 4, 'Súlyzókészlet', 24990, 40),
('Jóga matrac', 4, 'Professional matrac', 8990, 120),
('LEGO Star Wars', 5, 'Építőjáték', 39990, 60),
('Monopoly', 5, 'Társasjáték', 8990, 90); 