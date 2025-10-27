-- setup_sales.sql
-- Cria tabelas de Orçamentos e Pedidos (idempotente) e adiciona permissões relacionadas

-- Orçamentos
CREATE TABLE IF NOT EXISTS quotes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  number VARCHAR(30) NOT NULL,
  title VARCHAR(255) NOT NULL,
  status ENUM('draft','awaiting','approved','not_approved','customer_notified') NOT NULL DEFAULT 'draft',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  addition_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  approved_at TIMESTAMP NULL,
  notified_at TIMESTAMP NULL,
  not_approved_at TIMESTAMP NULL,
  notes VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_quotes_tenant_number (tenant_id, number),
  KEY idx_quotes_tenant (tenant_id),
  CONSTRAINT fk_quotes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quote_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  quote_id BIGINT UNSIGNED NOT NULL,
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
  KEY idx_qi_tenant (tenant_id),
  KEY idx_qi_quote (quote_id),
  CONSTRAINT fk_qi_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_qi_quote FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
  CONSTRAINT fk_qi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pedidos
CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  client_id BIGINT UNSIGNED NOT NULL,
  number VARCHAR(30) NOT NULL,
  title VARCHAR(255) NOT NULL,
  status ENUM('open','fulfilled','canceled') NOT NULL DEFAULT 'open',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  addition_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_tenant_number (tenant_id, number),
  KEY idx_orders_tenant (tenant_id),
  CONSTRAINT fk_orders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NOT NULL,
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
  KEY idx_oi_tenant (tenant_id),
  KEY idx_oi_order (order_id),
  CONSTRAINT fk_oi_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissões
INSERT INTO permissions (name, slug, description, created_at, updated_at) VALUES
  ('Ver orçamentos', 'quotes.view', 'Listar/visualizar orçamentos', NOW(), NOW()),
  ('Criar orçamentos', 'quotes.create', 'Criar orçamentos', NOW(), NOW()),
  ('Editar orçamentos', 'quotes.edit', 'Editar orçamentos', NOW(), NOW()),
  ('Excluir orçamentos', 'quotes.delete', 'Excluir orçamentos', NOW(), NOW()),
  ('Aprovar orçamentos', 'quotes.approve', 'Aprovar orçamentos', NOW(), NOW()),
  ('Notificar cliente orçamento', 'quotes.notify', 'Marcar cliente avisado', NOW(), NOW()),
  ('Reprovar orçamentos', 'quotes.reject', 'Reprovar orçamentos', NOW(), NOW()),
  ('Converter orçamento em pedido', 'quotes.convert', 'Converter orçamento em pedido', NOW(), NOW()),
  ('Auditar orçamentos', 'quotes.audit', 'Visualizar auditoria de orçamentos', NOW(), NOW()),
  ('Ver pedidos', 'orders.view', 'Listar/visualizar pedidos', NOW(), NOW()),
  ('Criar pedidos', 'orders.create', 'Criar pedidos', NOW(), NOW()),
  ('Editar pedidos', 'orders.edit', 'Editar pedidos', NOW(), NOW()),
  ('Excluir pedidos', 'orders.delete', 'Excluir pedidos', NOW(), NOW()),
  ('Auditar pedidos', 'orders.audit', 'Visualizar auditoria de pedidos', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();

-- Concessão de permissões
-- Admin: todas
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW() FROM permissions p JOIN roles r ON r.slug='admin'
WHERE p.slug IN (
  'quotes.view','quotes.create','quotes.edit','quotes.delete','quotes.approve','quotes.notify','quotes.reject','quotes.convert','quotes.audit',
  'orders.view','orders.create','orders.edit','orders.delete','orders.audit'
) ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Manager: orçamentos e pedidos completos
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW() FROM permissions p JOIN roles r ON r.slug='manager'
WHERE p.slug IN (
  'quotes.view','quotes.create','quotes.edit','quotes.delete','quotes.approve','quotes.notify','quotes.reject','quotes.convert','quotes.audit',
  'orders.view','orders.create','orders.edit','orders.delete','orders.audit'
) ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Technician: pode criar/editar orçamentos, notificar; pedidos apenas ver/criar
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW() FROM permissions p JOIN roles r ON r.slug='technician'
WHERE p.slug IN (
  'quotes.view','quotes.create','quotes.edit','quotes.notify',
  'orders.view','orders.create'
) ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Operator: ver/criar orçamentos e pedidos

-- Relatórios e Calendário: permissões
INSERT INTO permissions (name, slug, description, created_at, updated_at) VALUES
 ('Ver relatórios', 'reports.view', 'Acessar tela de relatórios', NOW(), NOW()),
 ('Ver calendário', 'calendar.view', 'Acessar calendário', NOW(), NOW()),
 ('Criar evento calendário', 'calendar.create', 'Criar eventos no calendário', NOW(), NOW()),
 ('Excluir evento calendário', 'calendar.delete', 'Excluir eventos do calendário', NOW(), NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), updated_at=NOW();

-- Conceder ao admin
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p JOIN roles r ON r.slug='admin'
WHERE p.slug IN ('reports.view','calendar.view','calendar.create','calendar.delete')
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Conceder ao manager (somente ver)
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW()
FROM permissions p JOIN roles r ON r.slug='manager'
WHERE p.slug IN ('reports.view','calendar.view')
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Tabela de eventos do calendário
CREATE TABLE IF NOT EXISTS calendar_events (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  start_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_cal_tenant (tenant_id),
  CONSTRAINT fk_cal_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notícias (Novidades)
CREATE TABLE IF NOT EXISTS news (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  body TEXT NULL,
  image_url VARCHAR(500) NULL,
  link_url VARCHAR(500) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  published_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeds iniciais de notícias
INSERT INTO news (title, body, image_url, link_url, active, published_at, created_at, updated_at) VALUES
('Novo módulo de Calendário', 'Agenda integrada com A Receber/A Pagar e eventos.', NULL, NULL, 1, NOW(), NOW(), NOW())
,('Relatórios com impressão', 'Relatórios por período com versão para imprimir.', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at=NOW();

-- Assinaturas (billing básico)
CREATE TABLE IF NOT EXISTS subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  status ENUM('active','past_due','suspended','canceled') NOT NULL DEFAULT 'active',
  current_period_start DATE NULL,
  current_period_end DATE NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sub_tenant (tenant_id),
  CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  tenant_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('pending','paid','canceled') NOT NULL DEFAULT 'pending',
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_inv_tenant (tenant_id),
  KEY idx_inv_sub (subscription_id),
  CONSTRAINT fk_inv_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_sub FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_id BIGINT UNSIGNED NOT NULL,
  method ENUM('pix','card','boleto','manual') NOT NULL DEFAULT 'manual',
  status ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
  amount DECIMAL(10,2) NOT NULL,
  paid_at TIMESTAMP NULL,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_pay_invoice (invoice_id),
  CONSTRAINT fk_pay_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO permission_role (permission_id, role_id, created_at, updated_at)
SELECT p.id, r.id, NOW(), NOW() FROM permissions p JOIN roles r ON r.slug='operator'
WHERE p.slug IN (
  'quotes.view','quotes.create','quotes.edit',
  'orders.view','orders.create'
) ON DUPLICATE KEY UPDATE updated_at=NOW();


