-- Order statuses tábla létrehozása
CREATE TABLE IF NOT EXISTS `order_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_color` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gray',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alap státuszok beszúrása
INSERT IGNORE INTO `order_statuses` (`id`, `status_name`, `status_color`, `sort_order`) VALUES
(1, 'Függőben', 'yellow', 1),
(2, 'Feldolgozás alatt', 'blue', 2),
(3, 'Fizetve', 'green', 3),
(4, 'Szállítás alatt', 'blue', 4),
(5, 'Kézbesítve', 'green', 5),
(6, 'Lemondva', 'red', 6),
(7, 'Visszaküldve', 'gray', 7); 