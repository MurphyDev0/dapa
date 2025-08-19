-- Szállítási zónák tábla létrehozása
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    countries TEXT,
    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    free_shipping_limit DECIMAL(10,2) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alapértelmezett szállítási zónák beszúrása
INSERT INTO shipping_zones (name, description, countries, shipping_cost, free_shipping_limit) VALUES
('Magyarország', 'Magyarországi szállítás', 'HU', 1500, 15000),
('EU országok', 'Európai Uniós országok', 'AT,SK,RO,HR,SI,CZ,PL,DE,FR,IT', 3500, 25000),
('Világ többi része', 'Nemzetközi szállítás', '*', 8500, 50000); 