# Migração de Gateway: Mercado Pago → Iugu

## 📋 Resumo

Este documento descreve a estrutura atual e o que precisa ser feito para migrar de Mercado Pago para Iugu como gateway de pagamento, mantendo compatibilidade com ambos.

---

## ✅ Estrutura Atual (Já Implementada)

### 1. Model: `GatewayConfig`

**Localização**: `app/Models/GatewayConfig.php`

**Campos disponíveis**:
- `provider` (já existe) - Identifica qual gateway usar (`mercadopago` ou `iugu`)
- `mode` - Sandbox ou Production
- `public_key_sandbox` / `public_key_production`
- `access_token_sandbox` / `access_token_production`
- `client_id_production` / `client_secret_production` (para OAuth se necessário)
- `webhook_secret` - Para validar webhooks
- `block_login_after_days` - Política de bloqueio após vencimento

**Accessors úteis**:
- `$config->active_public_key` - Retorna a chave pública do modo atual
- `$config->active_access_token` - Retorna o access token do modo atual

### 2. Interface Admin

**Rota**: `/admin/gateway`  
**Controller**: `app/Http/Controllers/Admin/GatewayController.php`  
**View**: `resources/views/admin/gateway.blade.php`

**Status atual**: ✅ Funcional, mas hardcoded para Mercado Pago

---

## 🔄 Mudanças Necessárias para Suportar Iugu

### 1. Atualizar Migration (se necessário)

Verificar se a tabela `gateway_configs` precisa de campos adicionais para Iugu:

```php
// Iugu usa API Token ao invés de Access Token
// Pode usar o mesmo campo ou criar campo específico
// Sugestão: usar access_token para API Token do Iugu também (funciona)
```

**Campos Iugu típicos**:
- `api_token` - Token de autenticação (similar ao access_token)
- `account_id` - ID da conta (opcional, pode ir em metadata)
- Campos específicos de webhook (se necessário)

### 2. Atualizar Model `GatewayConfig`

**Adicionar método helper para Iugu**:

```php
// app/Models/GatewayConfig.php

/**
 * Retorna o API Token ativo (Iugu usa "API Token" ao invés de "Access Token")
 */
public function getActiveApiTokenAttribute(): ?string
{
    if ($this->mode === 'production') {
        return $this->access_token_production ?: ($this->access_token_sandbox ?: null);
    }
    return $this->access_token_sandbox ?: ($this->access_token_production ?: null);
}

/**
 * Verifica se é Iugu
 */
public function isIugu(): bool
{
    return $this->provider === 'iugu';
}

/**
 * Verifica se é Mercado Pago
 */
public function isMercadoPago(): bool
{
    return $this->provider === 'mercadopago';
}
```

### 3. Criar Service/Interface para Abstração

**Estratégia**: Criar uma interface e implementações específicas para cada gateway.

#### Interface:

```php
// app/Contracts/PaymentGatewayInterface.php

<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Cria uma preferência/pedido de pagamento
     */
    public function createPreference(array $data): array;
    
    /**
     * Processa webhook do gateway
     */
    public function processWebhook(array $payload): array;
    
    /**
     * Cria um boleto (específico Iugu)
     */
    public function createBoleto(array $data): array;
    
    /**
     * Consulta status de pagamento
     */
    public function getPaymentStatus(string $paymentId): array;
}
```

#### Implementação Mercado Pago:

```php
// app/Services/Gateways/MercadoPagoService.php

<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\GatewayConfig;
use Illuminate\Support\Facades\Http;

class MercadoPagoService implements PaymentGatewayInterface
{
    protected GatewayConfig $config;
    
    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
    }
    
    public function createPreference(array $data): array
    {
        $accessToken = $this->config->active_access_token;
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://api.mercadopago.com/checkout/preferences', $data);
        
        if (!$response->successful()) {
            throw new \Exception('Falha ao criar preferência: ' . $response->body());
        }
        
        return $response->json();
    }
    
    public function processWebhook(array $payload): array
    {
        // Lógica atual do MercadoPagoWebhookController
        // ...
    }
    
    public function createBoleto(array $data): array
    {
        // Mercado Pago não usa boletos dessa forma
        throw new \Exception('Mercado Pago não suporta criação de boletos via esta API');
    }
    
    public function getPaymentStatus(string $paymentId): array
    {
        $accessToken = $this->config->active_access_token;
        
        $response = Http::withToken($accessToken)
            ->get('https://api.mercadopago.com/v1/payments/' . $paymentId);
        
        if (!$response->successful()) {
            throw new \Exception('Falha ao consultar pagamento');
        }
        
        return $response->json();
    }
}
```

#### Implementação Iugu:

```php
// app/Services/Gateways/IuguService.php

<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\GatewayConfig;
use Illuminate\Support\Facades\Http;

class IuguService implements PaymentGatewayInterface
{
    protected GatewayConfig $config;
    protected string $baseUrl;
    
    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        // Iugu usa URL única, diferenciação por token
        $this->baseUrl = 'https://api.iugu.com/v1';
    }
    
    public function createPreference(array $data): array
    {
        $apiToken = $this->config->active_api_token;
        
        $response = Http::withBasicAuth($apiToken, '') // Iugu usa Basic Auth
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/charge', $data);
        
        if (!$response->successful()) {
            throw new \Exception('Falha ao criar cobrança: ' . $response->body());
        }
        
        return $response->json();
    }
    
    public function processWebhook(array $payload): array
    {
        // Processar webhook do Iugu
        // Iugu envia eventos diferentes: invoice.created, invoice.status_changed, etc.
        // ...
    }
    
    public function createBoleto(array $data): array
    {
        $apiToken = $this->config->active_api_token;
        
        // Iugu cria boletos via API de Invoices
        $response = Http::withBasicAuth($apiToken, '')
            ->withHeaders([
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/invoices', $data);
        
        if (!$response->successful()) {
            throw new \Exception('Falha ao criar boleto: ' . $response->body());
        }
        
        return $response->json();
    }
    
    public function getPaymentStatus(string $paymentId): array
    {
        $apiToken = $this->config->active_api_token;
        
        $response = Http::withBasicAuth($apiToken, '')
            ->get($this->baseUrl . '/invoices/' . $paymentId);
        
        if (!$response->successful()) {
            throw new \Exception('Falha ao consultar pagamento');
        }
        
        return $response->json();
    }
}
```

#### Factory/Resolver:

```php
// app/Services/GatewayServiceFactory.php

<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\GatewayConfig;
use App\Services\Gateways\MercadoPagoService;
use App\Services\Gateways\IuguService;

class GatewayServiceFactory
{
    public static function make(GatewayConfig $config): PaymentGatewayInterface
    {
        return match($config->provider) {
            'iugu' => new IuguService($config),
            'mercadopago' => new MercadoPagoService($config),
            default => throw new \Exception("Gateway '{$config->provider}' não suportado"),
        };
    }
    
    public static function current(): PaymentGatewayInterface
    {
        $config = GatewayConfig::current();
        return self::make($config);
    }
}
```

### 4. Atualizar Controllers

#### `CheckoutController`:

```php
// app/Http/Controllers/CheckoutController.php

use App\Services\GatewayServiceFactory;
use App\Models\GatewayConfig;

public function createPreference(Request $request)
{
    $config = GatewayConfig::current();
    $gateway = GatewayServiceFactory::make($config);
    
    // Preparar dados conforme o provider
    $preferenceData = $config->isIugu() 
        ? $this->prepareIuguData($request)
        : $this->prepareMercadoPagoData($request);
    
    $response = $gateway->createPreference($preferenceData);
    
    // Processar resposta conforme o provider
    // ...
}
```

#### `MercadoPagoWebhookController` → `PaymentWebhookController`:

```php
// app/Http/Controllers/Webhooks/PaymentWebhookController.php

public function handle(Request $request)
{
    $config = GatewayConfig::current();
    $gateway = GatewayServiceFactory::make($config);
    
    $result = $gateway->processWebhook($request->all());
    
    return response()->json($result);
}
```

**Rota**:
```php
// routes/web.php
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment');
```

#### `ReceivableController` (Boletos):

```php
// app/Http/Controllers/ReceivableController.php

public function createBoleto(Request $request, Receivable $receivable)
{
    $config = GatewayConfig::current();
    
    if (!$config->isIugu()) {
        return back()->withErrors(['gateway' => 'Boletos disponíveis apenas com Iugu']);
    }
    
    $gateway = GatewayServiceFactory::make($config);
    
    $data = [
        'email' => $receivable->client->email,
        'items' => [[
            'description' => $receivable->description,
            'quantity' => 1,
            'price_cents' => (int)($receivable->amount * 100),
        ]],
        'due_date' => $receivable->due_date,
        // ...
    ];
    
    $result = $gateway->createBoleto($data);
    
    // Salvar dados do boleto no receivable
    $receivable->external_id = $result['id'];
    $receivable->external_barcode = $result['bank_slip']['digitable_line'] ?? null;
    $receivable->external_url = $result['secure_url'] ?? null;
    $receivable->save();
    
    return back()->with('success', 'Boleto gerado com sucesso!');
}
```

### 5. Atualizar Interface Admin (`/admin/gateway`)

#### Adicionar Select de Provider:

```blade
<!-- resources/views/admin/gateway.blade.php -->

<div class="border rounded p-4 space-y-4">
    <div>
        <label class="block text-sm mb-1">Gateway</label>
        <select name="provider" class="w-full border rounded px-3 py-2" onchange="toggleProviderFields(this.value)">
            <option value="mercadopago" {{ $config->provider === 'mercadopago' ? 'selected' : '' }}>
                Mercado Pago
            </option>
            <option value="iugu" {{ $config->provider === 'iugu' ? 'selected' : '' }}>
                Iugu
            </option>
        </select>
    </div>

    <div>
        <label class="block text-sm mb-1">Modo</label>
        <select name="mode" class="w-full border rounded px-3 py-2">
            <option value="sandbox" {{ $config->mode === 'sandbox' ? 'selected' : '' }}>Sandbox/Test</option>
            <option value="production" {{ $config->mode === 'production' ? 'selected' : '' }}>Produção</option>
        </select>
    </div>

    <!-- Campos Mercado Pago -->
    <div id="mercadopago-fields" style="display: {{ $config->provider === 'mercadopago' ? 'block' : 'none' }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium mb-2">Sandbox</h3>
                <label class="block text-xs mb-1">Public Key</label>
                <input type="text" name="public_key_sandbox" value="{{ old('public_key_sandbox', $config->public_key_sandbox) }}" class="w-full border rounded px-3 py-2">
                <label class="block text-xs mb-1 mt-3">Access Token</label>
                <input type="text" name="access_token_sandbox" value="{{ old('access_token_sandbox', $config->access_token_sandbox) }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <h3 class="text-sm font-medium mb-2">Produção</h3>
                <label class="block text-xs mb-1">Public Key</label>
                <input type="text" name="public_key_production" value="{{ old('public_key_production', $config->public_key_production) }}" class="w-full border rounded px-3 py-2">
                <label class="block text-xs mb-1 mt-3">Access Token</label>
                <input type="text" name="access_token_production" value="{{ old('access_token_production', $config->access_token_production) }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>
    </div>

    <!-- Campos Iugu -->
    <div id="iugu-fields" style="display: {{ $config->provider === 'iugu' ? 'block' : 'none' }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="text-sm font-medium mb-2">Test</h3>
                <label class="block text-xs mb-1">API Token</label>
                <input type="text" name="access_token_sandbox" value="{{ old('access_token_sandbox', $config->access_token_sandbox) }}" class="w-full border rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Token de teste do Iugu</p>
            </div>
            <div>
                <h3 class="text-sm font-medium mb-2">Produção</h3>
                <label class="block text-xs mb-1">API Token</label>
                <input type="text" name="access_token_production" value="{{ old('access_token_production', $config->access_token_production) }}" class="w-full border rounded px-3 py-2">
                <p class="text-xs text-gray-500 mt-1">Token de produção do Iugu</p>
            </div>
        </div>
    </div>

    <!-- Webhook Secret (ambos) -->
    <div>
        <label class="block text-xs mb-1">Assinatura Secreta (Webhooks)</label>
        <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $config->webhook_secret) }}" class="w-full border rounded px-3 py-2">
        <p class="text-xs text-gray-500 mt-1">
            @if($config->provider === 'mercadopago')
                Configure no painel do Mercado Pago para validar as notificações.
            @else
                Configure no painel do Iugu para validar as notificações.
            @endif
        </p>
    </div>

    <!-- Bloqueio login -->
    <div>
        <label class="block text-xs mb-1">Dias para bloquear login após vencimento</label>
        <input type="number" min="0" max="30" name="block_login_after_days" value="{{ old('block_login_after_days', $config->block_login_after_days ?? 3) }}" class="w-full border rounded px-3 py-2">
    </div>
</div>

<script>
function toggleProviderFields(provider) {
    document.getElementById('mercadopago-fields').style.display = provider === 'mercadopago' ? 'block' : 'none';
    document.getElementById('iugu-fields').style.display = provider === 'iugu' ? 'block' : 'none';
}
</script>
```

#### Atualizar `GatewayController`:

```php
// app/Http/Controllers/Admin/GatewayController.php

public function update(Request $request)
{
    $validated = $request->validate([
        'provider' => 'required|in:mercadopago,iugu', // ✅ NOVO
        'mode' => 'required|in:sandbox,production',
        'public_key_sandbox' => 'nullable|string',
        'access_token_sandbox' => 'nullable|string',
        'public_key_production' => 'nullable|string',
        'access_token_production' => 'nullable|string',
        'client_id_production' => 'nullable|string',
        'client_secret_production' => 'nullable|string',
        'webhook_secret' => 'nullable|string',
        'block_login_after_days' => 'required|integer|min:0|max:30',
    ]);

    $config = GatewayConfig::query()->first();
    if (!$config) {
        $config = new GatewayConfig();
    }
    $config->fill($validated); // ✅ Remover hardcode de 'mercadopago'
    $config->save();

    return redirect()->route('admin.gateway.edit')->with('success', 'Configurações do gateway atualizadas.');
}
```

---

## 📝 Checklist de Implementação

### Fase 1: Preparação
- [ ] Atualizar `GatewayConfig` model com métodos helper (`isIugu()`, `active_api_token`)
- [ ] Criar `PaymentGatewayInterface`
- [ ] Criar `MercadoPagoService` (extrair lógica atual)
- [ ] Criar `IuguService` (nova implementação)
- [ ] Criar `GatewayServiceFactory`

### Fase 2: Atualizar Controllers
- [ ] Refatorar `CheckoutController` para usar `GatewayServiceFactory`
- [ ] Renomear `MercadoPagoWebhookController` → `PaymentWebhookController`
- [ ] Refatorar `ReceivableController` para criar boletos via Iugu
- [ ] Atualizar rotas de webhook

### Fase 3: Interface Admin
- [ ] Adicionar select de provider em `/admin/gateway`
- [ ] Adicionar JavaScript para mostrar/ocultar campos por provider
- [ ] Atualizar `GatewayController@update` para aceitar `provider`

### Fase 4: Testes
- [ ] Testar criação de preferência com Mercado Pago (regressão)
- [ ] Testar criação de preferência com Iugu
- [ ] Testar webhooks de ambos
- [ ] Testar geração de boletos com Iugu
- [ ] Testar processamento de pagamentos aprovados

---

## 🔄 Estrutura de Dados Iugu vs Mercado Pago

### Mercado Pago
- **Autenticação**: Bearer Token (`Authorization: Bearer {access_token}`)
- **Preferência**: `/checkout/preferences`
- **Pagamentos**: `/v1/payments/{id}`
- **Webhook**: Envia `type=payment` e `data.id`
- **External Reference**: String simples (ex: invoice ID)

### Iugu
- **Autenticação**: Basic Auth (`Authorization: Basic {base64(api_token:)}`)
- **Cobrança**: `/charge`
- **Faturas/Boleto**: `/invoices`
- **Webhook**: Envia eventos (`invoice.created`, `invoice.status_changed`, etc.)
- **External Reference**: Campo `custom_variables` ou `identifier`

---

## 💡 Notas Importantes

1. **Boletos**: Iugu suporta geração de boletos nativa. Mercado Pago usa outro fluxo (PIX/cartão).

2. **Webhooks**: Cada gateway tem estrutura diferente. Necessário normalizar no service.

3. **Compatibilidade**: Manter suporte a ambos por período de transição.

4. **URLs Dinâmicas**: Atualizar `notification_url` e `back_urls` conforme o provider.

5. **Campos de Banco**: Revisar se `Invoice` e `Receivable` precisam de campos adicionais para Iugu (ex: `iugu_invoice_id`).

---

## 📚 Documentação de Referência

- **Iugu API**: https://iugu.com/referencias/api
- **Mercado Pago API**: https://www.mercadopago.com.br/developers/pt/docs

---

## 🎯 Prioridade de Implementação

**Alta**: Interface Admin (permitir seleção de provider)  
**Alta**: Service Factory e Interfaces  
**Média**: Implementação Iugu  
**Média**: Refatoração Controllers  
**Baixa**: Otimizações e melhorias

---

**Status**: Documento preparado ✅  
**Próximo passo**: Implementar quando necessário

