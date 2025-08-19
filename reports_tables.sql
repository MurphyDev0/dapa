-- Régiók tábla
CREATE TABLE IF NOT EXISTS regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    country_code CHAR(2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Kategóriák tábla
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT,
    description TEXT,
    slug VARCHAR(100),
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Értékesítési adatok tábla
CREATE TABLE IF NOT EXISTS sales_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    category_id INT,
    quantity INT,
    price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    customer_id INT,
    order_date DATETIME,
    payment_method VARCHAR(50),
    is_refunded BOOLEAN DEFAULT FALSE,
    region_id INT,
    shipping_zone_id INT,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (region_id) REFERENCES regions(id)
);

-- Vásárlói demográfia tábla
CREATE TABLE IF NOT EXISTS customer_demographics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    age_group VARCHAR(20),
    gender VARCHAR(20),
    location VARCHAR(100),
    loyalty_points INT DEFAULT 0,
    total_purchases INT DEFAULT 0,
    first_purchase_date DATE,
    last_purchase_date DATE,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Marketing kampányok tábla
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(100),
    campaign_type VARCHAR(50),
    start_date DATE,
    end_date DATE,
    budget DECIMAL(10,2),
    total_revenue DECIMAL(10,2),
    conversion_rate DECIMAL(5,2),
    roi DECIMAL(5,2)
);

-- Kuponkód használat tábla
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(50),
    customer_id INT,
    order_id INT,
    discount_amount DECIMAL(10,2),
    used_date DATETIME,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Weboldal látogatottsági statisztikák
CREATE TABLE IF NOT EXISTS website_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(255),
    visit_date DATE,
    pageviews INT,
    unique_visitors INT,
    bounce_rate DECIMAL(5,2),
    avg_time_on_page INT,
    device_type VARCHAR(50)
);

-- Vevőszolgálati jelentések
CREATE TABLE IF NOT EXISTS customer_service_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    issue_type VARCHAR(100),
    satisfaction_rating INT,
    resolution_time INT,
    created_at DATETIME,
    resolved_at DATETIME,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);

-- Visszaküldések tábla
CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    customer_id INT,
    product_id INT,
    return_reason VARCHAR(255),
    return_date DATETIME,
    refund_amount DECIMAL(10,2),
    status VARCHAR(50),
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Email marketing statisztikák
CREATE TABLE IF NOT EXISTS email_marketing_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    send_date DATETIME,
    emails_sent INT,
    opened INT,
    clicked INT,
    unsubscribed INT,
    bounced INT,
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id)
);

-- Közösségi média konverziók
CREATE TABLE IF NOT EXISTS social_media_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(50),
    conversion_date DATE,
    clicks INT,
    impressions INT,
    conversions INT,
    revenue DECIMAL(10,2),
    ad_spend DECIMAL(10,2)
); 