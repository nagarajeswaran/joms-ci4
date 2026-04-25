-- Add modules column to user table for per-user module access control
-- Run this once in your database
ALTER TABLE `user` ADD COLUMN `modules` TEXT NULL AFTER `role`;
