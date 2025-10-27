-- Limpar planos existentes
DELETE FROM plans;

-- Inserir planos na tabela plans com características corretas
INSERT INTO plans (name, slug, description, price, features, active, created_at, updated_at) VALUES
('Gratuito', 'gratuito', 'Plano gratuito para começar', 0.00, '["Até 50 clientes", "ERP básico", "Suporte por email", "Backup automático"]', 1, NOW(), NOW()),
('Emissor Fiscal', 'emissor-fiscal', 'Emissor fiscal completo', 39.90, '["Emissor NFe", "Emissor NFCe", "Emissor CFe-SAT", "Suporte prioritário"]', 1, NOW(), NOW()),
('Básico', 'basico', 'Plano básico com ERP completo', 97.00, '["Até 500 clientes", "ERP completo", "Emissor fiscal", "Suporte prioritário", "Relatórios avançados"]', 1, NOW(), NOW()),
('Profissional', 'profissional', 'Plano profissional com recursos ilimitados', 197.00, '["Clientes ilimitados", "ERP completo", "Emissor fiscal avançado", "Suporte 24/7", "API personalizada", "Múltiplos usuários"]', 1, NOW(), NOW());
