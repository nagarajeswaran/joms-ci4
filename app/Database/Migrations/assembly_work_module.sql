CREATE TABLE IF NOT EXISTS `finished_goods_master` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `tamil_name` VARCHAR(150) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assembly_work` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `work_number` VARCHAR(30) NOT NULL,
  `karigar_id` INT NOT NULL,
  `status` ENUM('draft','in_progress','finished','completed') DEFAULT 'draft',
  `notes` TEXT DEFAULT NULL,
  `making_charge_cash` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `making_charge_fine` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `finished_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`karigar_id`) REFERENCES `karigar`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assembly_work_order` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `assembly_work_id` INT NOT NULL,
  `order_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assembly_work_id`) REFERENCES `assembly_work`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assembly_work_issue` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `assembly_work_id` INT NOT NULL,
  `part_batch_id` INT NOT NULL,
  `part_id` INT NOT NULL,
  `stamp_id` INT DEFAULT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `piece_weight_g` DECIMAL(10,4) DEFAULT NULL,
  `pcs` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `created_by_user_id` INT DEFAULT NULL,
  `created_by_username` VARCHAR(150) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `issued_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assembly_work_id`) REFERENCES `assembly_work`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`part_batch_id`) REFERENCES `part_batch`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`part_id`) REFERENCES `part`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`stamp_id`) REFERENCES `stamp`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assembly_work_receive` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `assembly_work_id` INT NOT NULL,
  `receive_type` ENUM('finished_good','returned_part','by_product','kacha') NOT NULL,
  `part_id` INT DEFAULT NULL,
  `part_batch_id` INT DEFAULT NULL,
  `byproduct_type_id` INT DEFAULT NULL,
  `kacha_lot_id` INT DEFAULT NULL,
  `stamp_id` INT DEFAULT NULL,
  `batch_number` VARCHAR(50) NOT NULL,
  `weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `piece_weight_g` DECIMAL(10,4) DEFAULT NULL,
  `pcs` DECIMAL(12,4) DEFAULT 0.0000,
  `touch_pct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `finished_goods_id` INT DEFAULT NULL,
  `created_by_user_id` INT DEFAULT NULL,
  `created_by_username` VARCHAR(150) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `received_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assembly_work_id`) REFERENCES `assembly_work`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`part_id`) REFERENCES `part`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`part_batch_id`) REFERENCES `part_batch`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`byproduct_type_id`) REFERENCES `byproduct_type`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`finished_goods_id`) REFERENCES `finished_goods_master`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`stamp_id`) REFERENCES `stamp`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `assembly_work_summary` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `assembly_work_id` INT NOT NULL,
  `department_group_id` INT NOT NULL,
  `issue_weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `receive_weight_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `issue_touch_pct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `receive_touch_pct` DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
  `issue_fine_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `receive_fine_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `difference_fine_g` DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`assembly_work_id`) REFERENCES `assembly_work`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_group_id`) REFERENCES `department_group`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;