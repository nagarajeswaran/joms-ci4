-- Karigar Manufacturing Module Migration

CREATE TABLE IF NOT EXISTS `karigar` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `tamil_name` VARCHAR(150) DEFAULT NULL,
  `department_id` INT DEFAULT NULL,
  `default_cash_rate` DECIMAL(10,2) DEFAULT 0.00,
  `default_fine_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `raw_material_type` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `default_touch_pct` DECIMAL(8,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `byproduct_type` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `kacha` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `raw_material_stock` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `material_type_id` INT NOT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `notes` TEXT DEFAULT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`material_type_id`) REFERENCES `raw_material_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `byproduct_stock` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `byproduct_type_id` INT NOT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `source_job_type` ENUM('melt','partorder') DEFAULT NULL,
  `source_job_id` INT DEFAULT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`byproduct_type_id`) REFERENCES `byproduct_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `melt_job` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `job_number` VARCHAR(20) NOT NULL,
  `karigar_id` INT NOT NULL,
  `status` ENUM('draft','posted') DEFAULT 'draft',
  `cash_rate_per_kg` DECIMAL(10,2) DEFAULT 0.00,
  `fine_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`karigar_id`) REFERENCES `karigar`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `melt_job_input` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `melt_job_id` INT NOT NULL,
  `input_type` ENUM('raw_material','kacha','byproduct','other') NOT NULL,
  `item_id` INT DEFAULT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `fine_g` DECIMAL(12,4) DEFAULT 0.0000,
  FOREIGN KEY (`melt_job_id`) REFERENCES `melt_job`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `gatti_stock` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `melt_job_id` INT DEFAULT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `qty_issued_g` DECIMAL(12,4) DEFAULT 0.0000,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`melt_job_id`) REFERENCES `melt_job`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `melt_job_receive` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `melt_job_id` INT NOT NULL,
  `receive_type` ENUM('gatti','touch_gatti','byproduct') NOT NULL,
  `gatti_stock_id` INT DEFAULT NULL,
  `byproduct_stock_id` INT DEFAULT NULL,
  `byproduct_type_id` INT DEFAULT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `batch_number` VARCHAR(30) DEFAULT NULL,
  `received_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`melt_job_id`) REFERENCES `melt_job`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`gatti_stock_id`) REFERENCES `gatti_stock`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`byproduct_type_id`) REFERENCES `byproduct_type`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_order` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(20) NOT NULL,
  `karigar_id` INT NOT NULL,
  `client_order_id` INT DEFAULT NULL,
  `status` ENUM('draft','posted') DEFAULT 'draft',
  `cash_rate_per_kg` DECIMAL(10,2) DEFAULT 0.00,
  `fine_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`karigar_id`) REFERENCES `karigar`(`id`),
  FOREIGN KEY (`client_order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_order_issue` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `part_order_id` INT NOT NULL,
  `gatti_stock_id` INT DEFAULT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `stamp_id` INT DEFAULT NULL,
  `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`part_order_id`) REFERENCES `part_order`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`gatti_stock_id`) REFERENCES `gatti_stock`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`stamp_id`) REFERENCES `stamp`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_batch_sequence` (
  `part_id` INT NOT NULL PRIMARY KEY,
  `last_sequence` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_batch` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `batch_number` VARCHAR(30) NOT NULL,
  `part_id` INT NOT NULL,
  `stamp_id` INT DEFAULT NULL,
  `piece_weight_g` DECIMAL(10,4) DEFAULT NULL,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `qty_in_stock` INT DEFAULT 0,
  `source_part_order_id` INT DEFAULT NULL,
  `received_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`part_id`) REFERENCES `part`(`id`),
  FOREIGN KEY (`stamp_id`) REFERENCES `stamp`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`source_part_order_id`) REFERENCES `part_order`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `part_order_receive` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `part_order_id` INT NOT NULL,
  `receive_type` ENUM('part','byproduct') NOT NULL,
  `part_id` INT DEFAULT NULL,
  `batch_number` VARCHAR(30) DEFAULT NULL,
  `part_batch_id` INT DEFAULT NULL,
  `byproduct_type_id` INT DEFAULT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `piece_weight_g` DECIMAL(10,4) DEFAULT NULL,
  `qty` INT DEFAULT 0,
  `touch_pct` DECIMAL(8,4) DEFAULT 0.0000,
  `received_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`part_order_id`) REFERENCES `part_order`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`part_id`) REFERENCES `part`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`part_batch_id`) REFERENCES `part_batch`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`byproduct_type_id`) REFERENCES `byproduct_type`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `karigar_ledger` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `karigar_id` INT NOT NULL,
  `source_type` ENUM('melt_job','part_order') NOT NULL,
  `source_id` INT NOT NULL,
  `account_type` ENUM('fine','cash') NOT NULL,
  `direction` ENUM('debit','credit') NOT NULL,
  `amount` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `narration` VARCHAR(500) DEFAULT NULL,
  `posted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`karigar_id`) REFERENCES `karigar`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `karigar_ledger_conversion` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `karigar_id` INT NOT NULL,
  `from_account` ENUM('fine','cash') NOT NULL,
  `to_account` ENUM('cash','fine') NOT NULL,
  `from_amount` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `to_amount` DECIMAL(14,4) NOT NULL DEFAULT 0.0000,
  `rate_per_kg` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `converted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`karigar_id`) REFERENCES `karigar`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
