-- Atualiza limites e capacidades dinâmicas (features) dos planos por slug

-- Plano Gratuito
UPDATE plans SET features = '{
  "max_clients": 50,
  "max_users": 1,
  "has_api_access": false,
  "has_emissor": false,
  "has_erp": true,
  "support_type": "email",
  "display_features": [
    "Até 50 clientes",
    "ERP básico",
    "Suporte por email",
    "Backup automático"
  ]
}'
WHERE slug = 'gratuito';

-- Plano Emissor Fiscal
UPDATE plans SET features = '{
  "max_clients": 100,
  "max_users": 2,
  "has_api_access": false,
  "has_emissor": true,
  "has_erp": false,
  "support_type": "priority",
  "display_features": [
    "Emissor NFe",
    "Emissor NFCe",
    "Emissor CFe-SAT",
    "Suporte prioritário"
  ]
}'
WHERE slug = 'emissor-fiscal';

-- Plano Básico
UPDATE plans SET features = '{
  "max_clients": 500,
  "max_users": 3,
  "has_api_access": false,
  "has_emissor": true,
  "has_erp": true,
  "support_type": "priority",
  "display_features": [
    "Até 500 clientes",
    "ERP completo",
    "Emissor fiscal",
    "Suporte prioritário",
    "Relatórios avançados"
  ]
}'
WHERE slug = 'basico';

-- Plano Profissional
UPDATE plans SET features = '{
  "max_clients": -1,
  "max_users": 10,
  "has_api_access": true,
  "has_emissor": true,
  "has_erp": true,
  "support_type": "24/7",
  "display_features": [
    "Clientes ilimitados",
    "ERP completo",
    "Emissor fiscal avançado",
    "Suporte 24/7",
    "API personalizada",
    "Múltiplos usuários"
  ]
}'
WHERE slug = 'profissional';


