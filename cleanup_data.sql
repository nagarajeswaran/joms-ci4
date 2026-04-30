-- CLEANUP SCRIPT: Reset transactional data for fresh real-time entry
-- Database: psboffic1_psboffi1_joms
-- Run with: mysql -u root psboffic1_psboffi1_joms < cleanup_data.sql

SET FOREIGN_KEY_CHECKS = 0;

-- ===== KARIGAR LEDGER =====
TRUNCATE TABLE karigar_ledger_conversion;
TRUNCATE TABLE karigar_ledger;

-- ===== ASSEMBLY WORK =====
TRUNCATE TABLE assembly_work_summary;
TRUNCATE TABLE assembly_work_receive;
TRUNCATE TABLE assembly_work_issue;
TRUNCATE TABLE assembly_work_order;
TRUNCATE TABLE assembly_work;

-- ===== PART BATCHES & PART ORDERS =====
TRUNCATE TABLE part_batch_stock_log;
TRUNCATE TABLE part_order_receive;
TRUNCATE TABLE part_order_issue;
TRUNCATE TABLE part_batch;
TRUNCATE TABLE part_batch_sequence;
TRUNCATE TABLE part_order;

-- ===== GATTI STOCK =====
TRUNCATE TABLE gatti_stock_log;
TRUNCATE TABLE melt_job_receive;
TRUNCATE TABLE melt_job_input;
TRUNCATE TABLE gatti_stock;
TRUNCATE TABLE melt_job;

-- ===== RAW MATERIAL BATCHES =====
TRUNCATE TABLE raw_material_batch_log;
TRUNCATE TABLE raw_material_batch;

-- ===== BYPRODUCTS =====
TRUNCATE TABLE byproduct_stock;

-- ===== KACHA =====
TRUNCATE TABLE kacha_lot;

-- ===== ORDERS =====
TRUNCATE TABLE order_touch;
TRUNCATE TABLE order_item_qty;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;

-- ===== CLIENTS =====
TRUNCATE TABLE client;

-- ===== STAMPS =====
TRUNCATE TABLE stamp;

-- ===== PRODUCT STOCK =====
TRUNCATE TABLE stock_transfer_item;
TRUNCATE TABLE stock_transfer;
TRUNCATE TABLE stock_transaction;
TRUNCATE TABLE product_stock;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'All transactional data cleaned successfully!' AS result;
