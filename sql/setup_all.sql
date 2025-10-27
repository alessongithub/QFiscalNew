-- Setup completo: RBAC (papéis/permissões), atualização de planos (features),
-- tabela de produtos e movimentos de estoque

-- 1) Tabelas de Papéis e Permissões (RBAC)
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permissions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS role_user (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_role_user (role_id, user_id),
  PRIMARY KEY (id),
  CONSTRAINT fk_role_user_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permission_user (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  permission_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_permission_user (permission_id, user_id),
  PRIMARY KEY (id),
  CONSTRAINT fk_permission_user_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_permission_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permission_role (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  permission_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_permission_role (permission_id, role_id),
  PRIMARY KEY (id),
  CONSTRAINT fk_permission_role_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
  CONSTRAINT fk_permission_role_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeds de Papéis
INSERT INTO roles (name, slug, description, created_at, updated_at) VALUES
('Administrador', 'admin', 'Acesso total ao sistema do tenant', NOW(), NOW()),
('Gestor', 'manager', 'Gerencia clientes, usuários e produtos', NOW(), NOW()),
('Operador', 'operator', 'Operações do dia a dia', NOW(), NOW()),
('Técnico', 'technician', 'Usuário técnico para assistência/OS', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();

-- Seeds de Permissões (Clientes, Usuários, Produtos, Estoque)
INSERT INTO permissions (name, slug, description, created_at, updated_at) VALUES
('Ver clientes', 'clients.view', 'Listar e ver clientes', NOW(), NOW()),
('Criar clientes', 'clients.create', 'Criar clientes', NOW(), NOW()),
('Editar clientes', 'clients.edit', 'Editar clientes', NOW(), NOW()),
('Excluir clientes', 'clients.delete', 'Excluir clientes', NOW(), NOW()),
('Ver usuários', 'users.view', 'Listar e ver usuários', NOW(), NOW()),
('Criar usuários', 'users.create', 'Criar usuários', NOW(), NOW()),
('Editar usuários', 'users.edit', 'Editar usuários', NOW(), NOW()),
('Excluir usuários', 'users.delete', 'Excluir usuários', NOW(), NOW()),
('Ver produtos', 'products.view', 'Listar/ver produtos', NOW(), NOW()),
('Criar produtos', 'products.create', 'Criar produtos', NOW(), NOW()),
('Editar produtos', 'products.edit', 'Editar produtos', NOW(), NOW()),
('Excluir produtos', 'products.delete', 'Excluir produtos', NOW(), NOW()),
('Ver estoque', 'stock.view', 'Ver saldos de estoque', NOW(), NOW()),
('Criar movimentos de estoque', 'stock.create', 'Lançar entradas/saídas/ajustes', NOW(), NOW())
 ,('Ver a receber', 'receivables.view', 'Listar/visualizar contas a receber', NOW(), NOW())
 ,('Criar a receber', 'receivables.create', 'Criar contas a receber', NOW(), NOW())
 ,('Editar a receber', 'receivables.edit', 'Editar contas a receber', NOW(), NOW())
 ,('Excluir a receber', 'receivables.delete', 'Excluir contas a receber', NOW(), NOW())
 ,('Baixar a receber', 'receivables.receive', 'Baixar como pago', NOW(), NOW())
 ,('Ver a pagar', 'payables.view', 'Listar/visualizar contas a pagar', NOW(), NOW())
 ,('Criar a pagar', 'payables.create', 'Criar contas a pagar', NOW(), NOW())
 ,('Editar a pagar', 'payables.edit', 'Editar contas a pagar', NOW(), NOW())
 ,('Excluir a pagar', 'payables.delete', 'Excluir contas a pagar', NOW(), NOW())
 ,('Pagar contas', 'payables.pay', 'Baixar como pago', NOW(), NOW())
  ,('Ver recibos', 'receipts.view', 'Listar/visualizar recibos', NOW(), NOW())
  ,('Criar recibos', 'receipts.create', 'Emitir novos recibos', NOW(), NOW())
  ,('Editar recibos', 'receipts.edit', 'Editar recibos', NOW(), NOW())
  ,('Excluir recibos', 'receipts.delete', 'Excluir recibos', NOW(), NOW())
  ,('Imprimir recibos', 'receipts.print', 'Imprimir/visualizar recibo emitido', NOW(), NOW())
  ,('Ver sangrias', 'cash.withdraw.view', 'Listar sangrias de caixa', NOW(), NOW())
  ,('Criar sangria', 'cash.withdraw.create', 'Criar nova sangria', NOW(), NOW())
  ,('Editar sangria', 'cash.withdraw.edit', 'Editar sangria', NOW(), NOW())
  ,('Excluir sangria', 'cash.withdraw.delete', 'Excluir sangria', NOW(), NOW())
 ,('Ver OS', 'service_orders.view', 'Listar/visualizar ordens de serviço', NOW(), NOW())
 ,('Criar OS', 'service_orders.create', 'Criar ordens de serviço', NOW(), NOW())
 ,('Editar OS', 'service_orders.edit', 'Editar ordens de serviço', NOW(), NOW())
 ,('Excluir OS', 'service_orders.delete', 'Excluir ordens de serviço', NOW(), NOW())
  ,('Aprovar OS', 'service_orders.approve', 'Aprovar orçamento/OS', NOW(), NOW())
  ,('Notificar cliente OS', 'service_orders.notify', 'Marcar cliente avisado', NOW(), NOW())
  ,('Reprovar OS', 'service_orders.reject', 'Marcar OS como não aprovada', NOW(), NOW())
  ,('Ver caixa', 'cash.view', 'Visualizar caixa do dia', NOW(), NOW())
  ,('Fechar caixa', 'cash.close', 'Fechar caixa do dia', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();

-- Concessões de Permissões aos Papéis
-- Admin: todas permissões
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'admin'
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Gestor: clientes (todas), produtos (ver, criar, editar), estoque (ver, criar)
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'manager'
WHERE p.slug IN (
  'clients.view','clients.create','clients.edit','clients.delete',
  'products.view','products.create','products.edit',
  'stock.view','stock.create',
  'receivables.view','receivables.create','receivables.edit','receivables.delete','receivables.receive',
  'payables.view','payables.create','payables.edit','payables.delete','payables.pay',
  'receipts.view','receipts.create','receipts.edit','receipts.delete','receipts.print',
  'service_orders.view','service_orders.create','service_orders.edit','service_orders.delete',
  'service_orders.approve','service_orders.notify','service_orders.reject',
  'cash.view','cash.close'
  ,'cash.withdraw.view','cash.withdraw.create','cash.withdraw.edit','cash.withdraw.delete'
)
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Operador: clientes (ver, criar, editar), produtos (ver), estoque (criar)
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'operator'
WHERE p.slug IN (
  'clients.view','clients.create','clients.edit',
  'products.view',
  'stock.create',
  'receivables.view','receivables.create','receivables.edit',
  'payables.view','payables.create','payables.edit',
  'receipts.view','receipts.create','receipts.edit','receipts.print',
  'service_orders.view','service_orders.create','service_orders.approve','service_orders.notify','service_orders.reject',
  'cash.view'
)
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- 2) Atualização de Planos (features com max_products)
-- Ajuste os valores conforme necessidade
UPDATE plans SET features = '{
  "max_clients": 50,
  "max_users": 1,
  "max_products": 50,
  "has_api_access": false,
  "has_emissor": false,
  "has_erp": true,
  "support_type": "email",
  "display_features": [
    "Até 50 clientes",
    "Até 50 produtos",
    "ERP básico",
    "Suporte por email",
    "Backup automático"
  ]
}'
WHERE slug = 'gratuito';

UPDATE plans SET features = '{
  "max_clients": 100,
  "max_users": 2,
  "max_products": 100,
  "has_api_access": false,
  "has_emissor": true,
  "has_erp": false,
  "support_type": "priority",
  "display_features": [
    "Até 100 clientes",
    "Até 100 produtos",
    "Emissor NFe",
    "Emissor NFCe",
    "Emissor CFe-SAT",
    "Suporte prioritário"
  ]
}'
WHERE slug = 'emissor-fiscal';

UPDATE plans SET features = '{
  "max_clients": 500,
  "max_users": 3,
  "max_products": 500,
  "has_api_access": false,
  "has_emissor": true,
  "has_erp": true,
  "support_type": "priority",
  "display_features": [
    "Até 500 clientes",
    "Até 500 produtos",
    "ERP completo",
    "Emissor fiscal",
    "Suporte prioritário",
    "Relatórios avançados"
  ]
}'
WHERE slug = 'basico';

UPDATE plans SET features = '{
  "max_clients": -1,
  "max_users": 10,
  "max_products": -1,
  "has_api_access": true,
  "has_emissor": true,
  "has_erp": true,
  "support_type": "24/7",
  "display_features": [
    "Clientes ilimitados",
    "Produtos ilimitados",
    "ERP completo",
    "Emissor fiscal avançado",
    "Suporte 24/7",
    "API personalizada",
    "Múltiplos usuários"
  ]
}'
WHERE slug = 'profissional';

-- 3) Tabela de Produtos
CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  sku VARCHAR(100) NULL,
  ean VARCHAR(14) NULL,
  unit VARCHAR(6) NOT NULL,
  ncm VARCHAR(8) NULL,
  cest VARCHAR(7) NULL,
  cfop VARCHAR(4) NULL,
  origin VARCHAR(2) NULL,
  csosn VARCHAR(3) NULL,
  cst_icms VARCHAR(3) NULL,
  cst_pis VARCHAR(2) NULL,
  cst_cofins VARCHAR(2) NULL,
  aliquota_icms DECIMAL(5,2) NULL,
  aliquota_pis DECIMAL(5,2) NULL,
  aliquota_cofins DECIMAL(5,2) NULL,
  price DECIMAL(10,2) NOT NULL,
  type ENUM('product','service') NOT NULL DEFAULT 'product',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  deleted_at TIMESTAMP NULL,
  KEY idx_products_tenant (tenant_id),
  CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Tabela de Movimentos de Estoque
CREATE TABLE IF NOT EXISTS stock_movements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NOT NULL,
  type ENUM('entry','exit','adjustment') NOT NULL,
  quantity DECIMAL(12,3) NOT NULL,
  unit_price DECIMAL(10,2) NULL,
  document VARCHAR(255) NULL,
  note VARCHAR(500) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_sm_tenant (tenant_id),
  KEY idx_sm_product (product_id),
  CONSTRAINT fk_sm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_sm_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Contas a Pagar e a Receber
CREATE TABLE IF NOT EXISTS receivables (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  service_order_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('open','partial','paid','canceled') NOT NULL DEFAULT 'open',
  received_at TIMESTAMP NULL,
  payment_method VARCHAR(50) NULL,
  document_number VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_rec_tenant (tenant_id),
  CONSTRAINT fk_rec_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payables (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  supplier_name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('open','partial','paid','canceled') NOT NULL DEFAULT 'open',
  paid_at TIMESTAMP NULL,
  payment_method VARCHAR(50) NULL,
  document_number VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_pay_tenant (tenant_id),
  CONSTRAINT fk_pay_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Ordens de Serviço (ligadas a Receber)
CREATE TABLE IF NOT EXISTS service_orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  number VARCHAR(30) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  status ENUM('open','in_progress','in_service','warranty','service_finished','no_repair','finished','canceled') NOT NULL DEFAULT 'open',
  is_warranty TINYINT(1) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  addition_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  warranty_days INT UNSIGNED NOT NULL DEFAULT 0,
  warranty_until DATE NULL,
  finalized_at TIMESTAMP NULL,
  issue_nfse TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_so_tenant_number (tenant_id, number),
  KEY idx_so_tenant (tenant_id),
  CONSTRAINT fk_so_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 7) Itens da Ordem de Serviço
-- Campos adicionais para assistência técnica e aprovação (idempotente)
ALTER TABLE service_orders
  ADD COLUMN IF NOT EXISTS equipment_brand VARCHAR(100) NULL AFTER description,
  ADD COLUMN IF NOT EXISTS equipment_model VARCHAR(100) NULL AFTER equipment_brand,
  ADD COLUMN IF NOT EXISTS equipment_serial VARCHAR(100) NULL AFTER equipment_model,
  ADD COLUMN IF NOT EXISTS equipment_description VARCHAR(255) NULL AFTER equipment_serial,
  ADD COLUMN IF NOT EXISTS defect_reported TEXT NULL AFTER equipment_description,
  ADD COLUMN IF NOT EXISTS diagnosis TEXT NULL AFTER defect_reported,
  ADD COLUMN IF NOT EXISTS budget_amount DECIMAL(10,2) NULL DEFAULT 0 AFTER diagnosis,
  ADD COLUMN IF NOT EXISTS approval_status ENUM('approved','customer_notified','not_approved') NULL AFTER budget_amount,
  ADD COLUMN IF NOT EXISTS approval_notes VARCHAR(255) NULL AFTER approval_status,
  ADD COLUMN IF NOT EXISTS received_by_user_id BIGINT UNSIGNED NULL AFTER approval_notes,
  ADD COLUMN IF NOT EXISTS internal_notes TEXT NULL AFTER status,
  ADD COLUMN IF NOT EXISTS technician_user_id BIGINT UNSIGNED NULL AFTER received_by_user_id,
  ADD CONSTRAINT IF NOT EXISTS fk_so_received_by_user FOREIGN KEY (received_by_user_id) REFERENCES users(id) ON DELETE SET NULL;
-- FK técnico (se não existir)
ALTER TABLE service_orders
  ADD CONSTRAINT IF NOT EXISTS fk_so_technician_user FOREIGN KEY (technician_user_id) REFERENCES users(id) ON DELETE SET NULL;
CREATE TABLE IF NOT EXISTS service_order_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  service_order_id BIGINT UNSIGNED NOT NULL,
  product_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(500) NULL,
  quantity DECIMAL(12,3) NOT NULL DEFAULT 1,
  unit VARCHAR(10) NULL,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  addition_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_soi_tenant (tenant_id),
  KEY idx_soi_order (service_order_id),
  CONSTRAINT fk_soi_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_soi_order FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_soi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10) Recibos (emissão de recibos por tenant)
CREATE TABLE IF NOT EXISTS receipts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  receivable_id BIGINT UNSIGNED NULL,
  number VARCHAR(30) NOT NULL,
  issue_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  notes VARCHAR(255) NULL,
  status ENUM('issued','canceled') NOT NULL DEFAULT 'issued',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipts_tenant_number (tenant_id, number),
  KEY idx_receipts_tenant (tenant_id),
  KEY idx_receipts_client (client_id),
  KEY idx_receipts_receivable (receivable_id),
  CONSTRAINT fk_receipts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_receipts_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  CONSTRAINT fk_receipts_receivable FOREIGN KEY (receivable_id) REFERENCES receivables(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- 8) Anexos da Ordem de Serviço
CREATE TABLE IF NOT EXISTS service_order_attachments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  service_order_id BIGINT UNSIGNED NOT NULL,
  path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NULL,
  size BIGINT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_soa_tenant (tenant_id),
  KEY idx_soa_order (service_order_id),
  CONSTRAINT fk_soa_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_soa_order FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9) Caixa do Dia
CREATE TABLE IF NOT EXISTS daily_cashes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  total_received DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
  net_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  notes VARCHAR(500) NULL,
  closed_by BIGINT UNSIGNED NULL,
  closed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dc_tenant_date (tenant_id, date),
  KEY idx_dc_tenant (tenant_id),
  CONSTRAINT fk_dc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_dc_user FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9.1) Sangrias de Caixa
CREATE TABLE IF NOT EXISTS cash_withdrawals (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_cw_tenant_date (tenant_id, date),
  CONSTRAINT fk_cw_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_cw_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10.1) Transportadoras (por tenant)
CREATE TABLE IF NOT EXISTS carriers (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  trade_name VARCHAR(255) NULL,
  cnpj VARCHAR(18) NULL,
  ie VARCHAR(30) NULL,
  street VARCHAR(255) NULL,
  number VARCHAR(30) NULL,
  complement VARCHAR(100) NULL,
  district VARCHAR(100) NULL,
  city VARCHAR(100) NULL,
  state CHAR(2) NULL,
  zip_code VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  vehicle_plate VARCHAR(10) NULL,
  vehicle_state CHAR(2) NULL,
  rntc VARCHAR(20) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_carriers_tenant (tenant_id),
  UNIQUE KEY uq_carriers_tenant_cnpj (tenant_id, cnpj),
  CONSTRAINT fk_carriers_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10.2) Campos de frete no Pedido
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS carrier_id BIGINT UNSIGNED NULL,
  ADD COLUMN IF NOT EXISTS freight_payer ENUM('company','buyer') NOT NULL DEFAULT 'company',
  ADD COLUMN IF NOT EXISTS freight_cost DECIMAL(10,2) NULL,
  ADD COLUMN IF NOT EXISTS freight_obs VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS freight_mode TINYINT UNSIGNED NOT NULL DEFAULT 9 AFTER carrier_id,
  ADD CONSTRAINT IF NOT EXISTS fk_orders_carrier FOREIGN KEY (carrier_id) REFERENCES carriers(id) ON DELETE SET NULL;

-- 10.3) Permissões de transportadoras e atribuição de frete
INSERT INTO permissions (name, slug, description, created_at, updated_at) VALUES
('Ver transportadoras', 'carriers.view', 'Listar/visualizar transportadoras', NOW(), NOW()),
('Criar transportadoras', 'carriers.create', 'Cadastrar transportadoras', NOW(), NOW()),
('Editar transportadoras', 'carriers.edit', 'Editar transportadoras', NOW(), NOW()),
('Excluir transportadoras', 'carriers.delete', 'Excluir transportadoras', NOW(), NOW()),
('Atribuir frete ao pedido', 'orders.freight.assign', 'Selecionar frete/transportadora ao finalizar pedido', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();

-- 10.4) Concessões
-- Admin: todas (já coberto por bloco global), replicamos para garantir atualização
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'admin'
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Manager
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'manager'
WHERE p.slug IN (
  'carriers.view','carriers.create','carriers.edit','carriers.delete',
  'orders.freight.assign'
)
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Operator
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p
JOIN roles r ON r.slug = 'operator'
WHERE p.slug IN (
  'carriers.view','carriers.create','carriers.edit',
  'orders.freight.assign'
)
ON DUPLICATE KEY UPDATE updated_at=NOW();

