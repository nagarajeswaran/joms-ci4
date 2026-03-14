CREATE TABLE IF NOT EXISTS `stock_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `stock_location` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'SALEM', 'SLM', 1, NOW()),
(2, 'NRPT', 'NRPT', 1, NOW()),
(3, 'MBNR', 'MBNR', 1, NOW());

CREATE TABLE IF NOT EXISTS `product_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `pattern_id` int(11) NOT NULL,
  `variation_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `min_qty` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stock` (`product_id`,`pattern_id`,`variation_id`,`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('in','out','adjustment','transfer_in','transfer_out') NOT NULL,
  `product_id` int(11) NOT NULL,
  `pattern_id` int(11) NOT NULL,
  `variation_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `ref_transfer_id` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'system',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_location_id` int(11) NOT NULL,
  `to_location_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'system',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transfer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `pattern_id` int(11) NOT NULL,
  `variation_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
