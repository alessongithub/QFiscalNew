# Planos, recursos e regras de funcionamento (ERP Laravel + Emissor Delphi)

Este documento consolida os planos disponíveis, o que já está implementado no ERP, como as restrições são aplicadas e o que precisamos implementar para o plano Emissor Fiscal.

## Planos e preços

- Plano Gratuito — R$ 0,00/mês
- Plano Emissor Fiscal — R$ 39,90/mês
- Plano Básico — R$ 49,90/mês
- Plano Profissional — R$ 99,90/mês
- Plano Enterprise — R$ 199,90/mês

Os valores podem ser ajustados no seeder ou via painel/admin no futuro.

## Matriz de recursos por plano

Chaves (features) usadas no sistema:
- max_users: número máximo de usuários (−1 = ilimitado)
- max_clients: número máximo de clientes (−1 = ilimitado)
- max_products: número máximo de produtos (−1 = ilimitado)
- has_api_access: acesso a API
- has_emissor: acesso/uso do Emissor Delphi
- has_erp: acesso ao ERP
- allow_issue_nfe: permitir emissão de NFe a partir do ERP
- allow_pos: permitir acesso ao PDV (POS)

Resumo por plano:

- Plano Gratuito
  - max_users: 1
  - max_clients: 50
  - max_products: 50
  - allow_issue_nfe: false
  - allow_pos: false
  - has_erp: true
  - has_emissor: false
  - Multiusuário: não

- Plano Emissor Fiscal (NOVO)
  - Preço: R$ 39,90/mês
  - Objetivo: Cliente usa o Emissor Delphi para emitir NFe; no ERP ele fica com acesso equivalente ao Plano Gratuito
  - ERP: em “modo free” (sem NFe/PDV), sem limites no Emissor
  - Recomendações de features para o plano:
    - has_emissor: true
    - has_erp: true (mas com erp_access_level: "free")
    - erp_access_level: "free" (sugerido para aplicar regras do Gratuito no ERP)
    - allow_issue_nfe: false
    - allow_pos: false
    - max_users: 1; max_clients: 50; max_products: 50 (no ERP). Emissor Delphi sem limites.
  - Autenticação: login e senha iguais ao cadastro do tenant; enviar senha por e-mail conforme fluxo atual

- Plano Básico
  - max_users: 1 (não multiusuário)
  - max_clients: 200
  - max_products: sem limite definido (null)
  - allow_issue_nfe: true
  - allow_pos: true
  - has_erp: true
  - has_emissor: false
  - Multiusuário: não

- Plano Profissional
  - max_users: 10 (multiusuário)
  - max_clients: 1000
  - max_products: sem limite definido (null)
  - allow_issue_nfe: true
  - allow_pos: true
  - has_erp: true
  - has_emissor: true
  - Multiusuário: sim

- Plano Enterprise
  - max_users: −1 (ilimitado)
  - max_clients: −1 (ilimitado)
  - max_products: −1 (ilimitado)
  - allow_issue_nfe: true
  - allow_pos: true
  - has_erp: true
  - has_emissor: true
  - Multiusuário: sim

## O que já está implementado no código

- Faturas pagas no menu de perfil (topo direito):
  - Mostra as 5 últimas faturas pagas (via `payments` aprovados, ordenados por `paid_at`)
  - Link “Ver todas” aponta para `/billing/invoices` com paginação
  - Arquivos:
    - `resources/views/layouts/app.blade.php`
    - `app/Http/Controllers/BillingController.php`
    - `resources/views/billing/invoices/index.blade.php`
    - `routes/web.php`

- Modo de expiração (“limited mode”):
  - Em vez de deslogar após a carência, o sistema entra em modo limitado (regras do plano gratuito) e mantém ativa a renovação
  - Aplicado em `app/Http/Middleware/TenantMiddleware.php` (seta `config('app.limited_mode')` e request attribute)

- Gate de recursos por plano (middleware):
  - `app/Http/Middleware/PlanFeatureMiddleware.php` valida chaves (ex.: `allow_issue_nfe`, `allow_pos`)
  - Exemplo de uso nas rotas (já aplicado): travas em emissão de NFe e acesso ao POS
  - Adaptação no futuro: podemos usar também para outras features

- Cohesão de features nas views:
  - `app/Providers/AppServiceProvider.php` injeta `planFeatures` e `limitedMode` em todas as views para facilitar banners/avisos/UX

- Seeders atualizados (parcial):
  - `database/seeders/PlanSeeder.php` ajustado para refletir:
    - Gratuito: sem NFe/PDV; 50 clientes/produtos; 1 usuário
    - Básico: não multiusuário; NFe/PDV habilitados
    - Profissional: multiusuário; NFe/PDV habilitados; has_emissor true
    - Enterprise: ilimitado em tudo; NFe/PDV habilitados; has_emissor true

## O que falta implementar para o Plano Emissor Fiscal

1) Inserir o plano no seeder (ou via painel)
- Sugestão de registro (pseudo-features):
  - slug: `emissor`
  - price: 39.90
  - features:
    - has_emissor: true
    - has_erp: true
    - erp_access_level: "free"
    - allow_issue_nfe: false
    - allow_pos: false
    - max_users: 1
    - max_clients: 50
    - max_products: 50

2) Gatilhos de UI e rotas para Emissor Fiscal
- Mostrar link “Baixar Emissor Fiscal” quando `(plan.slug == 'emissor' || planFeatures['has_emissor'] == true)`
- ERP continua operando como Gratuito (NFe/PDV desabilitados). Rotas já travadas pelo middleware.

3) Autenticação do Emissor Delphi
- O login/senha será o do tenant (como você definiu)
- Recomendação técnica:
  - Criar endpoint no ERP para o Emissor obter token (JWT) com escopo “emissor”
    - Ex.: `POST /api/emissor/auth { email, password }`
    - Retorna token apenas se o plano tiver `has_emissor: true` (ou slug `emissor`/`professional`/`enterprise` etc.)
  - Emissor usa esse token para:
    - Validar assinatura ativa
    - Emitir NFe sem limitação de itens (cadastro interno no Emissor)

4) Regras de ERP “acesso free” para Emissor Fiscal
- Como o ERP deve se comportar neste plano:
  - Aplicar as mesmas limitações do Plano Gratuito (sem NFe/PDV; limites de clientes/produtos)
  - Exibir banners e CTA para download do Emissor

5) Auditoria/Logs
- Sugerido: logar origem de emissão (ERP x Emissor), ainda que o Emissor emita por sua própria stack

## Como as restrições são aplicadas

- Contagem de limites
  - Clientes e produtos: verificação no momento do cadastro (ex.: `ProductController@store`) usando `plan.features`
  - Usuários: verificação no `UserManagementController`

- Recursos bloqueados por middleware
  - `PlanFeatureMiddleware:allow_issue_nfe` bloqueia rotas de emissão de NFe no ERP
  - `PlanFeatureMiddleware:allow_pos` bloqueia rotas do POS

- Modo limitado por expiração
  - `TenantMiddleware` detecta expiração + carência e ativa `limitedMode`; UI e middlewares passam a operar como “free”

## Sugestões de UI (Blade)

- Desabilitar botões conforme plano/limitedMode (exemplos):
```blade
@php $canIssue = ($planFeatures['allow_issue_nfe'] ?? false) && !$limitedMode; @endphp
<button class="btn" {{ $canIssue ? '' : 'disabled' }}>Emitir NFe</button>

@php $canPOS = ($planFeatures['allow_pos'] ?? false) && !$limitedMode; @endphp
<a href="{{ route('pos.index') }}" class="btn {{ $canPOS ? '' : 'opacity-50 pointer-events-none' }}">Abrir PDV</a>
```

- Mostrar CTA “Renovar” quando vencido (já existe no dashboard) e “Baixar Emissor” quando plan.has_emissor for true

## Fluxo de upgrade/checkout

- `PlanUpgradeController`:
  - Impede selecionar o mesmo plano; se vencido, redireciona para checkout
  - Para planos de preço 0, migra direto

- `CheckoutController` + Webhook MercadoPago:
  - Ao confirmar pagamento, estende `tenant.plan_expires_at` e libera recursos imediatamente

## Próximos passos

1) Adicionar Plano Emissor Fiscal ao seeder/admin com as features acima (slug `emissor`)
2) Criar endpoint `/api/emissor/auth` para o app Delphi autenticar e validar acesso
3) Adicionar link/área “Baixar Emissor Fiscal” quando `has_emissor`
4) (Opcional) Exibir contadores de limites (clientes/produtos/usuários) no dashboard por plano

---

Qualquer dúvida sobre regras de cada plano ou ajustes de limites, este documento serve como referência rápida para futuras evoluções.
