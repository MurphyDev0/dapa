-- Alkategóriák létrehozása a meglévő főkategóriákhoz

-- Elektronika alkategóriák
INSERT INTO categories (name, description, parent_id, slug, is_active) VALUES
('Mobiltelefonok', 'Okostelefonok és mobiltelefonok', 
 (SELECT id FROM categories WHERE name = 'Elektronika' AND parent_id IS NULL LIMIT 1), 
 'mobiltelefonok', 1),
('Laptopok', 'Hordozható számítógépek', 
 (SELECT id FROM categories WHERE name = 'Elektronika' AND parent_id IS NULL LIMIT 1), 
 'laptopok', 1),
('Táblagépek', 'Tablet számítógépek', 
 (SELECT id FROM categories WHERE name = 'Elektronika' AND parent_id IS NULL LIMIT 1), 
 'tablagepek', 1),
('Fejhallgatók', 'Vezetékes és vezeték nélküli fejhallgatók', 
 (SELECT id FROM categories WHERE name = 'Elektronika' AND parent_id IS NULL LIMIT 1), 
 'fejhallgatok', 1);

-- Ruházat alkategóriák
INSERT INTO categories (name, description, parent_id, slug, is_active) VALUES
('Férfi ruházat', 'Férfi ruhadarabok', 
 (SELECT id FROM categories WHERE name = 'Ruházat' AND parent_id IS NULL LIMIT 1), 
 'ferfi-ruhazat', 1),
('Női ruházat', 'Női ruhadarabok', 
 (SELECT id FROM categories WHERE name = 'Ruházat' AND parent_id IS NULL LIMIT 1), 
 'noi-ruhazat', 1),
('Gyermek ruházat', 'Gyermek ruhadarabok', 
 (SELECT id FROM categories WHERE name = 'Ruházat' AND parent_id IS NULL LIMIT 1), 
 'gyermek-ruhazat', 1),
('Cipők', 'Különféle cipők és lábbelik', 
 (SELECT id FROM categories WHERE name = 'Ruházat' AND parent_id IS NULL LIMIT 1), 
 'cipok', 1);

-- Otthon alkategóriák
INSERT INTO categories (name, description, parent_id, slug, is_active) VALUES
('Bútorok', 'Lakásberendezési bútorok', 
 (SELECT id FROM categories WHERE name = 'Otthon' AND parent_id IS NULL LIMIT 1), 
 'butorok', 1),
('Konyhai eszközök', 'Konyhai felszerelések és eszközök', 
 (SELECT id FROM categories WHERE name = 'Otthon' AND parent_id IS NULL LIMIT 1), 
 'konyhai-eszkozok', 1),
('Dekoráció', 'Otthoni dekorációs tárgyak', 
 (SELECT id FROM categories WHERE name = 'Otthon' AND parent_id IS NULL LIMIT 1), 
 'dekoracio', 1),
('Tisztítószerek', 'Háztartási tisztítószerek', 
 (SELECT id FROM categories WHERE name = 'Otthon' AND parent_id IS NULL LIMIT 1), 
 'tisztitoszerek', 1);

-- Sport alkategóriák
INSERT INTO categories (name, description, parent_id, slug, is_active) VALUES
('Fitness', 'Fitness és edzőtermi felszerelések', 
 (SELECT id FROM categories WHERE name = 'Sport' AND parent_id IS NULL LIMIT 1), 
 'fitness', 1),
('Labdajátékok', 'Labdajátékokhoz szükséges eszközök', 
 (SELECT id FROM categories WHERE name = 'Sport' AND parent_id IS NULL LIMIT 1), 
 'labdajatekok', 1),
('Túrázás', 'Túrázáshoz és természetjáráshoz eszközök', 
 (SELECT id FROM categories WHERE name = 'Sport' AND parent_id IS NULL LIMIT 1), 
 'turazas', 1),
('Vízisportok', 'Vízisportokhoz szükséges felszerelések', 
 (SELECT id FROM categories WHERE name = 'Sport' AND parent_id IS NULL LIMIT 1), 
 'vizisportok', 1);

-- Játékok alkategóriák
INSERT INTO categories (name, description, parent_id, slug, is_active) VALUES
('Társasjátékok', 'Különféle társasjátékok', 
 (SELECT id FROM categories WHERE name = 'Játékok' AND parent_id IS NULL LIMIT 1), 
 'tarsasjatekok', 1),
('Építőjátékok', 'LEGO és egyéb építőjátékok', 
 (SELECT id FROM categories WHERE name = 'Játékok' AND parent_id IS NULL LIMIT 1), 
 'epitojatekok', 1),
('Babák és figurák', 'Babák és akciófigurák', 
 (SELECT id FROM categories WHERE name = 'Játékok' AND parent_id IS NULL LIMIT 1), 
 'babak-es-figurak', 1),
('Fejlesztő játékok', 'Oktatási és fejlesztő játékok', 
 (SELECT id FROM categories WHERE name = 'Játékok' AND parent_id IS NULL LIMIT 1), 
 'fejleszto-jatekok', 1);

-- Kategóriák képeinek frissítése alapértelmezett értékekkel
UPDATE categories SET image_url = 'img/categories/electronics.jpg' WHERE name = 'Elektronika';
UPDATE categories SET image_url = 'img/categories/clothing.jpg' WHERE name = 'Ruházat';
UPDATE categories SET image_url = 'img/categories/home.jpg' WHERE name = 'Otthon';
UPDATE categories SET image_url = 'img/categories/sports.jpg' WHERE name = 'Sport';
UPDATE categories SET image_url = 'img/categories/toys.jpg' WHERE name = 'Játékok';

-- Alkategóriák képeinek beállítása
UPDATE categories SET image_url = 'img/categories/phones.jpg' WHERE name = 'Mobiltelefonok';
UPDATE categories SET image_url = 'img/categories/laptops.jpg' WHERE name = 'Laptopok';
UPDATE categories SET image_url = 'img/categories/tablets.jpg' WHERE name = 'Táblagépek';
UPDATE categories SET image_url = 'img/categories/headphones.jpg' WHERE name = 'Fejhallgatók';

UPDATE categories SET image_url = 'img/categories/mens-clothing.jpg' WHERE name = 'Férfi ruházat';
UPDATE categories SET image_url = 'img/categories/womens-clothing.jpg' WHERE name = 'Női ruházat';
UPDATE categories SET image_url = 'img/categories/kids-clothing.jpg' WHERE name = 'Gyermek ruházat';
UPDATE categories SET image_url = 'img/categories/shoes.jpg' WHERE name = 'Cipők';

UPDATE categories SET image_url = 'img/categories/furniture.jpg' WHERE name = 'Bútorok';
UPDATE categories SET image_url = 'img/categories/kitchen.jpg' WHERE name = 'Konyhai eszközök';
UPDATE categories SET image_url = 'img/categories/decoration.jpg' WHERE name = 'Dekoráció';
UPDATE categories SET image_url = 'img/categories/cleaning.jpg' WHERE name = 'Tisztítószerek';

UPDATE categories SET image_url = 'img/categories/fitness.jpg' WHERE name = 'Fitness';
UPDATE categories SET image_url = 'img/categories/ball-games.jpg' WHERE name = 'Labdajátékok';
UPDATE categories SET image_url = 'img/categories/hiking.jpg' WHERE name = 'Túrázás';
UPDATE categories SET image_url = 'img/categories/water-sports.jpg' WHERE name = 'Vízisportok';

UPDATE categories SET image_url = 'img/categories/board-games.jpg' WHERE name = 'Társasjátékok';
UPDATE categories SET image_url = 'img/categories/building-toys.jpg' WHERE name = 'Építőjátékok';
UPDATE categories SET image_url = 'img/categories/dolls.jpg' WHERE name = 'Babák és figurák';
UPDATE categories SET image_url = 'img/categories/educational.jpg' WHERE name = 'Fejlesztő játékok'; 