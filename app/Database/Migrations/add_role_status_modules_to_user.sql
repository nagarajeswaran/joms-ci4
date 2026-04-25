ALTER TABLE `user`
    ADD COLUMN IF NOT EXISTS `name` VARCHAR(150) NULL AFTER `username`,
    ADD COLUMN IF NOT EXISTS `role` VARCHAR(50) NOT NULL DEFAULT 'user' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NOT NULL DEFAULT 'active' AFTER `role`,
    ADD COLUMN IF NOT EXISTS `modules` TEXT NULL AFTER `status`;

UPDATE `user` SET `role` = 'admin', `status` = 'active' WHERE `id` = (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM `user`) t);
UPDATE `user` SET `status` = 'active' WHERE `status` = '';
