-- Product categories tábla létrehozása (kompatibilitás miatt)
-- Ez a tábla ugyanaz mint a categories tábla, csak más néven

CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT,
    slug VARCHAR(100),
    image_url VARCHAR(255),
    img VARCHAR(255), -- Kompatibilitás miatt
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adatok másolása a categories táblából (ha létezik és van benne adat)
INSERT IGNORE INTO product_categories (id, name, description, parent_id, slug, image_url, is_active, created_at, updated_at)
SELECT id, name, description, parent_id, slug, image_url, is_active, created_at, updated_at 
FROM categories; 