CREATE TABLE IF NOT EXISTS `#__aaj_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `asin` varchar(20) NOT NULL DEFAULT '',
  `title` text, `url` text, `image_ids` text, `features` longtext, `attributes` longtext,
  `availability` varchar(255) DEFAULT '', `currency` varchar(10) DEFAULT '',
  `price` varchar(20) DEFAULT '', `savings` varchar(20) DEFAULT '',
  `savings_percentage` float DEFAULT 0, `savings_basis` varchar(20) DEFAULT '',
  `salesrank` float DEFAULT 0, `is_prime` tinyint(1) DEFAULT 0,
  `is_amazon_fulfilled` tinyint(1) DEFAULT 0,
  `rating` varchar(10) DEFAULT '', `reviews` float DEFAULT 0,
  `date_created` datetime DEFAULT NULL, `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), UNIQUE KEY `asin` (`asin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__aaj_lists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT '', `list_key` varchar(255) DEFAULT '',
  `country` varchar(20) DEFAULT '', `category` varchar(255) DEFAULT '',
  `products` longtext, `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`id`), KEY `list_key` (`list_key`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;