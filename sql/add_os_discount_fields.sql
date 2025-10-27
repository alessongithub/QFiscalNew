-- Adiciona campos de desconto/acréscimo em OS e Itens de OS, de forma idempotente
-- Tabelas: service_orders (discount_total, addition_total)
--          service_order_items (discount_value, addition_value)

-- service_orders.discount_total
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_orders'
    AND COLUMN_NAME = 'discount_total'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_orders ADD COLUMN discount_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- service_orders.addition_total
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_orders'
    AND COLUMN_NAME = 'addition_total'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_orders ADD COLUMN addition_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER discount_total;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- service_order_items.discount_value
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_order_items'
    AND COLUMN_NAME = 'discount_value'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_order_items ADD COLUMN discount_value DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER unit_price;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- service_order_items.addition_value
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_order_items'
    AND COLUMN_NAME = 'addition_value'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_order_items ADD COLUMN addition_value DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER discount_value;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


