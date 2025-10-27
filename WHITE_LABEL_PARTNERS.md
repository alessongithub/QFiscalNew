## White-label + Parceiros (Contabilidades) com Subdomínios e Split

### Objetivo
Tornar o ERP white-label para contabilidades parceiras:
- Cada parceira acessa via subdomínio (ex.: `contabx.suaapp.com`).
- Branding por parceira (logo, cores, tema).
- Tenants criados no subdomínio ficam vinculados à parceira (`partner_id`).
- Faturamento registra comissionamento por parceira.
- Fase 1: comissionamento interno (repasse manual).
- Fase 2: split automático no Mercado Pago (Marketplace / application_fee).

---

### Fases de implementação
- Fase 1 (rápida):
  - White-label por subdomínio
  - Vincular tenants/invoices/payments a `partner_id`
  - Calcular e registrar comissão da parceira (relatórios e repasse manual)
- Fase 2 (evolução):
  - Conexão Mercado Pago por OAuth para cada parceira
  - Preferência/pagamento usando o token da parceira (collector = parceira)
  - Definir `application_fee` (nossa comissão) → split automático

---

### Modelagem de dados

#### Nova tabela: `partners`
Campos sugeridos:
- `id` (PK)
- `name` (string)
- `slug` (string, único) → subdomínio
- `domain` (string, único, opcional) → domínio dedicado
- `commission_percent` (decimal(5,4)) ex.: `0.3000` para 30%
- Branding:
  - `theme` (enum: `light`, `dark`)
  - `primary_color` (string, ex: `#0ea5e9`)
  - `secondary_color` (string)
  - `logo_path` (string)
- Estado: `active` (boolean)
- Marketplace (fase 2):
  - `mp_user_id` (string)
  - `mp_public_key` (string)
  - `mp_access_token` (encrypted)
  - `mp_refresh_token` (encrypted)
  - `mp_connected_at` (timestamp)
- Timestamps

Índices: `slug` único, `domain` único.

#### Ajustes em tabelas existentes
- `tenants`
  - `partner_id` (foreignId, index, nullable)
- `invoices`
  - `partner_id` (foreignId, index, nullable)
  - `application_fee_amount` (decimal(12,2), nullable) → nossa parte (fase 1 opcional; fase 2 usado no split)
- `payments`
  - `partner_id` (foreignId, index, nullable)
  - `application_fee_amount` (decimal(12,2), nullable)

Relacionamentos (Eloquent):
- `Partner` hasMany `Tenant`, `Invoice`, `Payment`
- `Tenant` belongsTo `Partner`
- `Invoice` belongsTo `Partner`
- `Payment` belongsTo `Partner`

Sugestão de nomes de migrations:
- `2025_xx_xx_000400_create_partners_table.php`
- `2025_xx_xx_000410_add_partner_id_to_tenants_table.php`
- `2025_xx_xx_000420_add_partner_id_to_invoices_table.php`
- `2025_xx_xx_000430_add_partner_id_to_payments_table.php`
- `2025_xx_xx_000440_add_application_fee_to_invoices_and_payments.php`

---

### Middleware: contexto do parceiro por subdomínio
`PartnerMiddleware`
- Extrair subdomínio de `request()->getHost()` (ex.: `contabx.suaapp.com` → `contabx`).
- Carregar `Partner` por `slug` (ou `domain` quando for domínio dedicado).
- Injetar no container: `app()->instance('partner', $partner)`.
- Compartilhar em views: `view()->share('partner', $partner)`.
- Fallback: parceiro “default” quando no domínio raiz.

Aplicação do middleware:
- Rotas públicas (landing, registro, checkout) e rotas autenticadas (painel) para manter o contexto.

Considerações:
- Tratar ambiente local (ex.: `contabx.localhost` ou `contabx.local.suaapp.test`).
- Em produção, usar wildcard DNS `*.suaapp.com` apontando para o app.

---

### Branding (logo, cores, tema)
- Injetar variáveis CSS no layout (ex.: `layouts/app.blade.php`):
  ```html
  <style>
    :root {
      --brand-primary: {{ $partner->primary_color ?? '#2563eb' }};
      --brand-secondary: {{ $partner->secondary_color ?? '#0ea5e9' }};
    }
    .brand-primary { color: var(--brand-primary); }
    .bg-brand-primary { background-color: var(--brand-primary); }
  </style>
  ```
- Usar `{{ asset($partner->logo_path) }}` para logo.
- Tema: se o usuário não tiver preferência, usar `partner.theme` para alternar `dark`/`light` no `<body>`.

---

### Fluxos principais

#### 1) Cadastro da contabilidade parceira
- Rotas/Admin:
  - `GET /admin/partners` (listar)
  - `GET /admin/partners/create` | `POST /admin/partners` (criar, validar `slug`/subdomínio)
  - `GET /admin/partners/{id}/edit` | `PUT /admin/partners/{id}` (dados gerais e comissão)
  - `GET /admin/partners/{id}/branding` | `PUT ...` (logo, cores, tema)
  - (Fase 2) `GET /admin/partners/{id}/billing` (conectar MP via OAuth)

#### 2) Registro de tenant sob o subdomínio
- No fluxo atual de registro:
  - Se houver `partner` no middleware, ao criar `Tenant` setar `tenant.partner_id = partner.id`.
  - Exibir branding da parceira (logo/cores) no funil completo: landing → registro → checkout.

#### 3) Checkout e comissionamento (Fase 1)
- `CheckoutController@createPreference` / criação de `Invoice`:
  - Definir `invoice.partner_id = partner.id` (quando houver).
  - Calcular comissão: `partner_commission = amount * partner.commission_percent`.
  - Persistir campos auxiliares:
    - `invoices.application_fee_amount` (nossa comissão) OU gravar `partner_commission` em metadados para relatório.
- `MercadoPagoWebhookController@handle`:
  - Ao aprovar pagamento, criar `Payment` com `partner_id` e `application_fee_amount`.
  - Atualizar `Tenant` e `Invoice` como já fazemos.
- Relatórios:
  - Extratos por `partner_id`: total faturado, comissão do parceiro, nossa comissão, pagamentos aprovados por período.
  - Repasse manual (PIX/transferência) seguindo relatório.

#### 4) Split automático (Fase 2 — MP Marketplace)
- Cada parceira conecta a própria conta MP via OAuth:
  - Armazenar `mp_access_token`/`mp_refresh_token` por `partner`.
  - Salvar `mp_user_id` para auditoria/validação.
- Preferência/Pagamento:
  - Quando `partner` tiver token MP ativo: criar a preferência/cobrança usando o token da parceira (collector = parceira).
  - Definir `application_fee` (nossa comissão) no payload para split automático (consultar endpoint/versão MP adequada: Payments API geralmente aceita `application_fee`/`marketplace_fee`).
- Webhook:
  - Identificar `invoice.partner_id` e usar o token adequado para consultar o pagamento: token global (coletor = nossa conta) ou token da parceira (coletor = parceira).
- Observação:
  - Habilitar “Marketplace” e permissões na conta MP da plataforma antes de ir para produção.

---

### Rotas e Controllers a criar/ajustar
- Novos controllers:
  - `PartnerController` (CRUD)
  - `PartnerBrandingController` (upload de logo, cores, tema)
  - `PartnerBillingController` (comissão, OAuth MP)
- Ajustes existentes:
  - `TenantController` (setar `partner_id` no registro quando contexto existir)
  - `CheckoutController` (associar `invoice.partner_id`, calcular comissão)
  - `MercadoPagoWebhookController` (preencher `payments.partner_id`, `application_fee_amount`)
- Middleware:
  - `PartnerMiddleware` aplicado às rotas públicas e autenticadas

Exemplo de rotas (resumo):
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::resource('partners', PartnerController::class);
    Route::get('partners/{partner}/branding', [PartnerBrandingController::class, 'edit'])->name('partners.branding.edit');
    Route::put('partners/{partner}/branding', [PartnerBrandingController::class, 'update'])->name('partners.branding.update');
    Route::get('partners/{partner}/billing', [PartnerBillingController::class, 'edit'])->name('partners.billing.edit');
    Route::put('partners/{partner}/billing', [PartnerBillingController::class, 'update'])->name('partners.billing.update');
});
```

---

### ACL / Permissões
- Nova role: `partner_admin` (acesso às telas da própria parceria e seus tenants).
- Novas permissões:
  - `partners.view`, `partners.create`, `partners.update`, `partners.delete`
  - `partners.branding.update`, `partners.billing.update`
- Guardar associação usuário ↔ parceiro quando necessário (ex.: usuários da contabilidade).

---

### Infraestrutura (DNS / SSL / URLs)
- DNS: configurar wildcard `*.suaapp.com` → servidor do app (Hostinger suporta).
- SSL: wildcard (Let’s Encrypt via DNS challenge) ou Cloudflare (proxy + certificado universal).
- `APP_URL`: manter domínio raiz. Para gerar URLs, preferir `request()->getSchemeAndHttpHost()` no momento do checkout/`back_urls`/`notification_url`.
- Uploads de logo: `storage:link` e limites de tamanho adequados.

---

### Checklist de implantação (Fase 1)
1) Criar migrations e rodar `php artisan migrate`.
2) Criar model `Partner` e relacionamentos em `Tenant`, `Invoice`, `Payment`.
3) Implementar `PartnerMiddleware` e aplicar nas rotas.
4) Criar CRUD básico de parceiros (admin) + telas de Branding.
5) Ajustar fluxo de registro: setar `tenant.partner_id` pelo contexto.
6) Ajustar `CheckoutController`/`Webhook`: associar `partner_id`, calcular e salvar comissões.
7) Implementar relatórios de comissionamento (admin + parceiro).
8) Configurar DNS wildcard e SSL.

### Checklist de implantação (Fase 2)
1) Habilitar Marketplace na conta MP da plataforma.
2) Implementar OAuth para cada parceira (telas de conexão/renovação token).
3) Na criação de preferência/pagamento, usar o token da parceira e `application_fee` para split.
4) Adequar Webhook para variar o token conforme o collector.
5) Testes ponta-a-ponta (sandbox) com cobrança e validação de split.

---

### Riscos e considerações
- MP Marketplace: exige homologação/escopos corretos; revisar termos e taxas.
- Multi-tenant + subdomínio: garantir isolamento de dados por `tenant_id` e checagens adicionais por `partner_id` nos relatórios e telas administrativas.
- Branding: preservar contrastes/acessibilidade ao aceitar cores arbitrárias.
- Legal/fiscal: contratos de parceria e notas/refaturamento das comissões.

---

### Roadmap sugerido
- Semana 1: DB + Middleware + CRUD Partner + Branding + Registro de tenants com `partner_id`.
- Semana 2: Ajustes Checkout/Webhook + Relatórios de comissão (Fase 1) + DNS/SSL.
- Semana 3-4: OAuth MP + Split (Fase 2) + Testes sandbox + Documentação operacional.

---

### Glossário
- Parceira (Partner): contabilidade que opera o subdomínio e indica clientes.
- Tenant: cliente final atendido pela parceira (empresa usuária do ERP).
- Commission Percent: % do valor do plano destinado à parceira.
- Application Fee: valor que a plataforma retém automaticamente no split (Fase 2).
