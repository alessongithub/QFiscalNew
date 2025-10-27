-- Adiciona campos de garantia na tabela service_orders (idempotente)

-- service_orders.warranty_days
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_orders'
    AND COLUMN_NAME = 'warranty_days'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_orders ADD COLUMN warranty_days INT UNSIGNED NOT NULL DEFAULT 0 AFTER addition_total;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- service_orders.warranty_until
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_orders'
    AND COLUMN_NAME = 'warranty_until'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_orders ADD COLUMN warranty_until DATE NULL AFTER warranty_days;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- service_orders.finalized_at
SET @col_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_orders'
    AND COLUMN_NAME = 'finalized_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE service_orders ADD COLUMN finalized_at TIMESTAMP NULL AFTER warranty_until;',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


