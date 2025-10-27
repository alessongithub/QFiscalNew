-- Inserir planos na tabela plans
INSERT INTO plans (name, slug, description, price, active, created_at, updated_at) VALUES
('Plano Gratuito', 'free', 'Plano gratuito com recursos básicos', 0.00, 1, NOW(), NOW()),
('Plano Básico', 'basic', 'Plano básico com recursos essenciais', 49.90, 1, NOW(), NOW()),
('Plano Profissional', 'professional', 'Plano profissional com recursos avançados', 99.90, 1, NOW(), NOW()),
('Plano Enterprise', 'enterprise', 'Plano empresarial com recursos ilimitados', 199.90, 1, NOW(), NOW());
