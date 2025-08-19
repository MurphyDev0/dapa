-- Először ellenőrizzük és töröljük a meglévő foreign key-eket (ha vannak)
SET @database = 'webshop_engine';
SET @table = 'sales_data';

-- Töröljük a customer_id foreign key-t (ha létezik)
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = @database 
    AND TABLE_NAME = @table 
    AND COLUMN_NAME = 'customer_id' 
    AND REFERENCED_TABLE_NAME = 'users'
);
SET @sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE sales_data DROP FOREIGN KEY ', @constraint_name),
    'SELECT "No existing foreign key for customer_id"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Töröljük a region_id foreign key-t (ha létezik)
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = @database 
    AND TABLE_NAME = @table 
    AND COLUMN_NAME = 'region_id' 
    AND REFERENCED_TABLE_NAME = 'regions'
);
SET @sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE sales_data DROP FOREIGN KEY ', @constraint_name),
    'SELECT "No existing foreign key for region_id"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Most hozzáadjuk az új foreign key-eket
ALTER TABLE sales_data
ADD CONSTRAINT fk_sales_customer
FOREIGN KEY (customer_id) REFERENCES users(id),
ADD CONSTRAINT fk_sales_region
FOREIGN KEY (region_id) REFERENCES regions(id); 