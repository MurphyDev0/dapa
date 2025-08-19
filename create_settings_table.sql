-- Bolt beállítások tábla
DROP TABLE IF EXISTS shop_settings;
CREATE TABLE shop_settings (
    id INT PRIMARY KEY,
    settings_json JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alapértelmezett beállítások beszúrása
INSERT INTO shop_settings (id, settings_json) VALUES
(1, '{
    "basic": {
        "default_language": "hu",
        "default_currency": "HUF",
        "timezone": "Europe/Budapest"
    },
    "company": {
        "name": "Webshop Kft.",
        "tax_number": "12345678-1-41",
        "email": {
            "info": "info@webshop.hu",
            "support": "support@webshop.hu",
            "sales": "sales@webshop.hu"
        },
        "phones": [],
        "address": "1234 Budapest, Példa utca 1."
    },
    "social_media": {
        "facebook": "https://facebook.com/webshop",
        "instagram": "https://instagram.com/webshop",
        "twitter": "",
        "linkedin": ""
    },
    "payment": {
        "methods": ["cash", "card", "transfer"],
        "gateway": "stripe",
        "vat": {
            "default_rate": 27,
            "eu_vat": 0
        }
    },
    "shipping": {
        "methods": ["personal", "courier"],
        "zones": [],
        "free_shipping_limit": 15000,
        "weight_unit": "kg",
        "size_unit": "cm"
    },
    "stock": {
        "enabled": 1,
        "low_stock_threshold": 5,
        "show_stock_status": 1,
        "auto_update": 0
    },
    "integrations": {
        "google_analytics": "",
        "facebook_pixel": "",
        "api_key": ""
    },
    "discounts": {
        "bulk_discounts": 0,
        "loyalty_program": 1,
        "seasonal_discounts": 0
    }
}'); 