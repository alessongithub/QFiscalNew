# Análise de Integração - Celcoin/Galax Pay para Pagamentos Recorrentes

## 📋 Resumo Executivo

A Celcoin oferece soluções para pagamentos recorrentes através da plataforma **Galax Pay**, que permite cobranças automáticas via:
- Cartão de crédito
- Boleto bancário
- Pix Automático

Esta análise detalha como integrar o Galax Pay ao ERP para cobrar assinaturas recorrentes dos clientes (tenants).

---

## 🏗️ Estrutura Atual do Sistema

### Modelos Existentes

1. **`GatewayConfig`** - Configuração de gateway (atualmente só Mercado Pago)
   - Campos: `provider`, `mode`, `access_token_sandbox/production`, `public_key_sandbox/production`
   - Método: `GatewayConfig::current()`

2. **`Subscription`** - Assinaturas de tenants
   - Campos: `tenant_id`, `plan_id`, `status`, `current_period_start`, `current_period_end`
   - Relacionamentos: `tenant()`, `plan()`

3. **`Tenant`** - Clientes do ERP
   - Campos: `name`, `plan_id`, `plan_expires_at`, `status`, `active`
   - Relacionamento: `plan()`, `subscription()` (se existir)

4. **`Plan`** - Planos de assinatura
   - Campos: `name`, `slug`, `price`, `features`, `active`

### Fluxo Atual (Mercado Pago)

1. Configuração em `/admin/gateway`
2. Geração de PIX/Boleto via `ReceivableController`
3. Webhook em `/webhooks/mercadopago` para atualizar status

---

## 🔌 Integração Celcoin/Galax Pay

### 1. Autenticação

**Baseado em pesquisas e padrões de APIs similares:**

```
POST https://api.galaxpay.com.br/token
Headers:
  Authorization: Basic {base64(client_id:client_secret)}
  Content-Type: application/json

Response:
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expires_in": 3600
}
```

### 2. Criar Assinatura (Subscription)

**Endpoint esperado (baseado em padrões comuns):**

```
POST https://api.galaxpay.com.br/subscriptions
Headers:
  Authorization: Bearer {token}
  Content-Type: application/json

Body:
{
  "myId": "subscription-{tenant_id}-{plan_id}",
  "customer": {
    "myId": "customer-{tenant_id}",
    "name": "Nome do Tenant",
    "document": "12345678901234",
    "emails": ["email@tenant.com"],
    "phones": [11999999999]
  },
  "plan": {
    "myId": "plan-{plan_id}",
    "name": "Nome do Plano",
    "periodicity": "monthly", // monthly, quarterly, yearly
    "quantity": 1,
    "value": 99.90
  },
  "paymentMethod": {
    "type": "creditCard", // creditCard, boleto, pix
    "card": {
      "number": "4111111111111111",
      "holder": "NOME DO PORTADOR",
      "expiresAt": "12/2025",
      "cvv": "123"
    }
  },
  "firstPayDayDate": "2025-01-15"
}
```

**Response esperado:**

```json
{
  "id": "galaxpay-subscription-id",
  "myId": "subscription-{tenant_id}-{plan_id}",
  "status": "active",
  "customer": {...},
  "plan": {...},
  "creditCard": {...}
}
```

### 3. Webhooks (Notificações)

**Endpoint para receber notificações:**

```
POST /webhooks/galaxpay
Headers:
  X-GalaxPay-Signature: {signature}
  Content-Type: application/json

Body:
{
  "event": "subscription.paymentReceived",
  "subscription": {
    "myId": "subscription-{tenant_id}-{plan_id}",
    "id": "galaxpay-subscription-id",
    "status": "active"
  },
  "payment": {
    "id": "galaxpay-payment-id",
    "status": "received",
    "value": 99.90,
    "paidAt": "2025-01-15T10:30:00Z"
  }
}
```

**Eventos esperados:**
- `subscription.created` - Assinatura criada
- `subscription.paymentReceived` - Pagamento recebido
- `subscription.paymentFailed` - Pagamento falhou
- `subscription.canceled` - Assinatura cancelada
- `subscription.reactivated` - Assinatura reativada

---

## 📝 Implementação Necessária

### 1. Migration: Adicionar campos Celcoin no `GatewayConfig`

```php
Schema::table('gateway_configs', function (Blueprint $table) {
    // Celcoin/Galax Pay credentials
    $table->string('celcoin_client_id')->nullable();
    $table->string('celcoin_client_secret')->nullable();
    $table->string('celcoin_webhook_secret')->nullable();
    $table->enum('provider', ['mercadopago', 'celcoin'])->default('mercadopago')->change();
});
```

### 2. Migration: Adicionar campos na tabela `subscriptions`

```php
Schema::table('subscriptions', function (Blueprint $table) {
    $table->string('galaxpay_subscription_id')->nullable(); // ID da assinatura no Galax Pay
    $table->string('galaxpay_customer_id')->nullable(); // ID do cliente no Galax Pay
    $table->string('payment_method')->nullable(); // creditCard, boleto, pix
    $table->json('galaxpay_metadata')->nullable(); // Dados adicionais do Galax Pay
    $table->timestamp('last_payment_at')->nullable();
    $table->timestamp('next_payment_at')->nullable();
    $table->integer('failed_payment_attempts')->default(0);
});
```

### 3. Service: `CelcoinService` ou `GalaxPayService`

**Responsabilidades:**
- Autenticação (obter token)
- Criar assinatura
- Consultar assinatura
- Cancelar assinatura
- Processar webhooks

**Exemplo de estrutura:**

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\GatewayConfig;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;

class GalaxPayService
{
    private $baseUrl = 'https://api.galaxpay.com.br';
    private $config;
    
    public function __construct()
    {
        $this->config = GatewayConfig::current();
    }
    
    public function authenticate()
    {
        // Obter token de autenticação
    }
    
    public function createSubscription(Tenant $tenant, Plan $plan, array $paymentData)
    {
        // Criar assinatura no Galax Pay
    }
    
    public function cancelSubscription(Subscription $subscription)
    {
        // Cancelar assinatura
    }
    
    public function getSubscription(string $galaxpayId)
    {
        // Consultar assinatura
    }
    
    public function handleWebhook(array $payload)
    {
        // Processar webhook
    }
}
```

### 4. Controller: `SubscriptionController`

**Endpoints necessários:**

- `POST /subscriptions` - Criar assinatura
- `GET /subscriptions/{id}` - Consultar assinatura
- `DELETE /subscriptions/{id}` - Cancelar assinatura
- `POST /subscriptions/{id}/reactivate` - Reativar assinatura

### 5. Webhook Controller: `GalaxPayWebhookController`

**Lógica:**
1. Validar assinatura (webhook_secret)
2. Identificar evento (`subscription.paymentReceived`, etc.)
3. Atualizar `Subscription` no banco
4. Atualizar `Tenant.plan_expires_at`
5. Registrar pagamento (se necessário)

### 6. Atualizar `GatewayController`

Adicionar campos para configuração do Celcoin:
- `celcoin_client_id`
- `celcoin_client_secret`
- `celcoin_webhook_secret`
- Seletor de provider (`mercadopago` ou `celcoin`)

### 7. View: Formulário de assinatura

Quando um tenant assina um plano:
1. Coletar dados de pagamento (cartão, boleto ou Pix)
2. Chamar `GalaxPayService::createSubscription()`
3. Criar registro em `subscriptions`
4. Atualizar `Tenant.plan_id` e `Tenant.plan_expires_at`

---

## 🔄 Fluxo de Cobrança Recorrente

### Fluxo Inicial (Criação da Assinatura)

```
1. Tenant acessa página de planos
2. Seleciona plano e método de pagamento
3. Sistema cria assinatura via Galax Pay API
4. Galax Pay processa primeiro pagamento
5. Webhook recebe confirmação
6. Sistema atualiza Tenant.plan_expires_at
7. Sistema cria Subscription com status='active'
```

### Fluxo Recorrente (Mensal)

```
1. Galax Pay gera cobrança automaticamente (no vencimento)
2. Galax Pay processa pagamento (cartão, boleto ou Pix)
3. Webhook é enviado para /webhooks/galaxpay
4. Sistema atualiza Subscription.last_payment_at
5. Sistema atualiza Tenant.plan_expires_at (+30 dias)
6. Sistema registra pagamento (se necessário)
```

### Fluxo de Falha de Pagamento

```
1. Galax Pay tenta cobrar e falha
2. Webhook com evento 'subscription.paymentFailed'
3. Sistema incrementa Subscription.failed_payment_attempts
4. Após X tentativas, sistema pode:
   - Bloquear acesso do tenant
   - Enviar notificação
   - Cancelar assinatura automaticamente
```

---

## ⚠️ Pontos de Atenção

### 1. **Documentação Oficial Necessária**

A documentação completa da API do Galax Pay não está disponível publicamente. É necessário:
- Contatar suporte da Celcoin para obter credenciais de desenvolvimento
- Solicitar documentação completa da API
- Obter acesso ao ambiente sandbox

### 2. **Autenticação**

Precisa confirmar:
- Tipo de autenticação (Basic Auth, OAuth2, Bearer Token)
- Como obter `client_id` e `client_secret`
- Validade do token

### 3. **Webhooks**

Precisa confirmar:
- Como validar assinatura do webhook
- Formato exato dos eventos
- URL de callback configurável

### 4. **Métodos de Pagamento**

- **Cartão de Crédito**: Requer PCI-DSS compliance (dados do cartão não devem passar pelo ERP)
- **Boleto**: Gerado automaticamente pelo Galax Pay
- **Pix**: Pix Automático da Celcoin

### 5. **Segurança**

- Nunca armazenar dados de cartão de crédito
- Usar tokens do Galax Pay quando possível
- Validar webhooks com assinatura
- Usar HTTPS para todas as comunicações

---

## 📊 Próximos Passos

1. **Contatar Celcoin/Galax Pay**
   - Solicitar credenciais de desenvolvimento
   - Obter documentação completa da API
   - Acessar ambiente sandbox

2. **Implementar Prova de Conceito (POC)**
   - Criar autenticação básica
   - Testar criação de assinatura
   - Configurar webhook local (usando ngrok ou similar)

3. **Desenvolvimento**
   - Implementar `GalaxPayService`
   - Criar migrations
   - Implementar controllers
   - Criar views de assinatura

4. **Testes**
   - Testar criação de assinatura
   - Testar webhooks
   - Testar falhas de pagamento
   - Testar cancelamento

5. **Produção**
   - Configurar credenciais de produção
   - Configurar webhook de produção
   - Monitorar primeiras assinaturas

---

## 🔗 Referências

- [Galax Pay - Receber Pagamentos Online](https://galaxpay.celcoin.com.br/receber-pagamentos-online/)
- [Celcoin - Gateway de Pagamento](https://www.celcoin.com.br/gateway-de-pagamento)
- [Celcoin - Pagamento Recorrente](https://www.celcoin.com.br/news/pagamento-recorrente/)
- [Celcoin - Suporte Técnico](https://suporte.celcoin.com.br/hc/pt-br/sections/31739860339867-Integra%C3%A7%C3%A3o-do-gateway-de-pagamento)

---

## 📝 Observações

Esta análise foi baseada em:
- Informações públicas disponíveis sobre Celcoin/Galax Pay
- Padrões comuns de APIs de pagamento recorrente
- Estrutura atual do sistema ERP

**É necessário obter a documentação oficial da API do Galax Pay para implementação completa.**

