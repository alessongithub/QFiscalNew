# Gateway Multi-Tenant - Mercado Pago

## 📋 Resumo Executivo

Este documento detalha as opções de implementação para suportar Mercado Pago em um sistema multi-tenant, permitindo que cada tenant tenha autonomia na gestão de seus pagamentos ou mantenha a conta centralizada.

## 🎯 Objetivos

- Permitir que tenants emitam boletos via Mercado Pago
- Oferecer flexibilidade: conta única (centralizada) ou conta por tenant (autônoma)
- Suportar OAuth para conexão de contas próprias dos tenants
- Manter compatibilidade com implementação atual

---

## 📊 Opções de Implementação

### Opção 1: Conta Única (Centralizada) ✅ **RECOMENDADA INICIALMENTE**

**Como funciona:**
- Você (provedor do ERP) cadastra UMA conta Mercado Pago
- Todos os tenants usam a mesma conta
- Todos os recebimentos vão para sua conta
- Você faz repasse manual ou automático para os tenants (com taxa de 1%)

**Vantagens:**
- ✅ Implementação simples (já está quase assim)
- ✅ Controle total sobre recebimentos
- ✅ Facilita cobrança de taxas/assinaturas
- ✅ Menos complexidade técnica
- ✅ Menos pontos de falha

**Desvantagens:**
- ❌ Você concentra os recebimentos
- ❌ Tenant não tem acesso direto ao painel do MP
- ❌ Você precisa fazer repasse manual
- ❌ Taxa de 1% precisa ser calculada e descontada

**Estrutura Atual:**
```php
// app/Models/GatewayConfig.php
// Já existe e funciona assim:
GatewayConfig::current() // Retorna configuração global
```

**O que precisa mudar:**
- Manter estrutura atual
- Adicionar sistema de repasse com taxa de 1%
- Dashboard do tenant mostra saldo disponível (valor - taxa_mp - 1%)

---

### Opção 2: Conta por Tenant (Autônoma) 🔄 **FUTURO**

**Como funciona:**
- Cada tenant conecta sua própria conta Mercado Pago via OAuth
- Cada tenant recebe diretamente em sua conta
- Você pode cobrar taxa de marketplace (application_fee)
- Tenant tem acesso ao painel do MP

**Vantagens:**
- ✅ Cada tenant recebe direto
- ✅ Tenant tem autonomia
- ✅ Você pode cobrar taxa via marketplace (split automático)
- ✅ Menos responsabilidade de repasse

**Desvantagens:**
- ❌ Implementação mais complexa (OAuth)
- ❌ Validação de contas por tenant
- ❌ Webhooks precisam identificar qual tenant
- ❌ Mais pontos de falha

**Estrutura Necessária:**
```php
// Migration
Schema::create('tenant_gateway_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('provider')->default('mercadopago');
    $table->enum('mode', ['sandbox', 'production'])->default('sandbox');
    
    // OAuth (quando conectado)
    $table->string('mp_access_token')->nullable();
    $table->string('mp_public_key')->nullable();
    $table->string('mp_user_id')->nullable(); // ID do usuário no MP
    $table->string('mp_refresh_token')->nullable();
    $table->timestamp('mp_token_expires_at')->nullable();
    
    // Configurações
    $table->boolean('connected')->default(false);
    $table->timestamp('connected_at')->nullable();
    $table->timestamps();
    
    $table->unique(['tenant_id', 'provider']);
});
```

---

## 🔧 Implementação Detalhada

### Fase 1: Conta Única (Atual + Melhorias)

#### 1.1 Manter `GatewayConfig` Global

```php
// app/Models/GatewayConfig.php
// Já existe, não precisa mudar
// Usa GatewayConfig::current() em todos os lugares
```

#### 1.2 Adicionar Campos de Taxa (Opcional)

```php
// Migration
Schema::table('gateway_configs', function (Blueprint $table) {
    $table->decimal('platform_fee_percent', 5, 2)->default(1.00); // Taxa de 1%
    $table->boolean('auto_transfer_enabled')->default(false);
});
```

#### 1.3 Sistema de Repasse

Ver documento `SISTEMA_REPASSE_TAXA_1PORCENTO.md` para detalhes.

---

### Fase 2: Conta por Tenant (Futuro - OAuth)

#### 2.1 Migration para Tenant Gateway Configs

```php
// database/migrations/XXXX_XX_XX_create_tenant_gateway_configs_table.php
Schema::create('tenant_gateway_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('provider')->default('mercadopago');
    $table->enum('mode', ['sandbox', 'production'])->default('sandbox');
    
    // OAuth Credentials
    $table->string('mp_access_token')->nullable();
    $table->string('mp_public_key')->nullable();
    $table->string('mp_user_id')->nullable();
    $table->string('mp_refresh_token')->nullable();
    $table->timestamp('mp_token_expires_at')->nullable();
    
    // Status
    $table->boolean('connected')->default(false);
    $table->timestamp('connected_at')->nullable();
    $table->json('mp_account_info')->nullable(); // Nome, email, etc.
    
    $table->timestamps();
    $table->unique(['tenant_id', 'provider']);
});
```

#### 2.2 Model TenantGatewayConfig

```php
// app/Models/TenantGatewayConfig.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantGatewayConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'mode',
        'mp_access_token',
        'mp_public_key',
        'mp_user_id',
        'mp_refresh_token',
        'mp_token_expires_at',
        'connected',
        'connected_at',
        'mp_account_info',
    ];

    protected $casts = [
        'connected' => 'boolean',
        'mp_token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'mp_account_info' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getActiveAccessTokenAttribute(): ?string
    {
        // Verificar se token expirou e renovar se necessário
        if ($this->mp_token_expires_at && $this->mp_token_expires_at->isPast()) {
            $this->refreshToken();
        }
        return $this->mp_access_token;
    }

    private function refreshToken()
    {
        // Implementar renovação de token via OAuth
        // ...
    }
}
```

#### 2.3 Controller OAuth

```php
// app/Http/Controllers/Tenant/GatewayController.php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantGatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function connect()
    {
        $tenant = auth()->user()->tenant;
        
        // URL de autorização do Mercado Pago
        $clientId = config('services.mercadopago.client_id');
        $redirectUri = route('tenant.gateway.callback');
        $state = encrypt($tenant->id); // Segurança
        
        $authUrl = "https://auth.mercadopago.com/authorization" .
            "?client_id={$clientId}" .
            "&response_type=code" .
            "&platform_id=mp" .
            "&redirect_uri={$redirectUri}" .
            "&state={$state}";
        
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        $code = $request->get('code');
        $state = decrypt($request->get('state'));
        $tenantId = $state;
        
        // Trocar code por access_token
        $response = Http::post('https://api.mercadopago.com/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.mercadopago.client_id'),
            'client_secret' => config('services.mercadopago.client_secret'),
            'code' => $code,
            'redirect_uri' => route('tenant.gateway.callback'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            // Buscar informações do usuário
            $userInfo = Http::withToken($data['access_token'])
                ->get('https://api.mercadopago.com/users/me')
                ->json();

            $config = TenantGatewayConfig::updateOrCreate(
                ['tenant_id' => $tenantId, 'provider' => 'mercadopago'],
                [
                    'mp_access_token' => $data['access_token'],
                    'mp_refresh_token' => $data['refresh_token'] ?? null,
                    'mp_user_id' => $userInfo['id'] ?? null,
                    'mp_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 21600),
                    'connected' => true,
                    'connected_at' => now(),
                    'mp_account_info' => $userInfo,
                ]
            );

            return redirect()->route('tenant.settings')
                ->with('success', 'Conta Mercado Pago conectada com sucesso!');
        }

        return redirect()->route('tenant.settings')
            ->withErrors(['gateway' => 'Falha ao conectar conta Mercado Pago.']);
    }

    public function disconnect()
    {
        $tenant = auth()->user()->tenant;
        TenantGatewayConfig::where('tenant_id', $tenant->id)
            ->where('provider', 'mercadopago')
            ->delete();

        return redirect()->route('tenant.settings')
            ->with('success', 'Conta Mercado Pago desconectada.');
    }
}
```

#### 2.4 Atualizar ReceivableController para Usar Gateway do Tenant

```php
// app/Http/Controllers/ReceivableController.php
public function emitBoleto(Receivable $receivable, Request $request)
{
    // ...
    
    $tenant = auth()->user()->tenant;
    
    // Tentar usar gateway do tenant, senão usar global
    $tenantConfig = TenantGatewayConfig::where('tenant_id', $tenant->id)
        ->where('provider', 'mercadopago')
        ->where('connected', true)
        ->first();
    
    $accessToken = $tenantConfig 
        ? $tenantConfig->active_access_token
        : GatewayConfig::current()->active_access_token;
    
    // ... resto do código
}
```

#### 2.5 Atualizar Webhook para Identificar Tenant

```php
// app/Http/Controllers/Webhooks/MercadoPagoWebhookController.php
public function handle(Request $request)
{
    // ...
    
    // Identificar qual tenant (se usar conta própria)
    if ($isReceivable) {
        $receivable = Receivable::find($recId);
        if ($receivable) {
            // Verificar se tenant tem gateway próprio
            $tenantConfig = TenantGatewayConfig::where('tenant_id', $receivable->tenant_id)
                ->where('connected', true)
                ->first();
            
            // Se tiver, validar que o pagamento veio da conta correta
            if ($tenantConfig && $paymentJson['collector_id'] !== $tenantConfig->mp_user_id) {
                return response()->json(['status' => 'invalid_tenant'], 400);
            }
        }
    }
    
    // ... resto do código
}
```

---

## 🔐 Configuração OAuth Mercado Pago

### 1. Criar Aplicação no Mercado Pago

1. Acesse: https://www.mercadopago.com.br/developers/panel/app
2. Crie uma nova aplicação
3. Configure redirect URI: `https://seu-dominio.com/tenant/gateway/callback`
4. Anote `Client ID` e `Client Secret`

### 2. Configurar .env

```env
MERCADOPAGO_CLIENT_ID=seu_client_id
MERCADOPAGO_CLIENT_SECRET=seu_client_secret
```

### 3. Adicionar em config/services.php

```php
'mercadopago' => [
    'client_id' => env('MERCADOPAGO_CLIENT_ID'),
    'client_secret' => env('MERCADOPAGO_CLIENT_SECRET'),
    'redirect' => route('tenant.gateway.callback'),
],
```

---

## 📋 Rotas Necessárias

```php
// routes/web.php

// Fase 1: Conta Única (já existe)
Route::get('/admin/gateway', [Admin\GatewayController::class, 'edit'])->name('admin.gateway.edit');
Route::post('/admin/gateway', [Admin\GatewayController::class, 'update'])->name('admin.gateway.update');

// Fase 2: Conta por Tenant (futuro)
Route::middleware(['auth', 'tenant'])->prefix('tenant')->group(function () {
    Route::get('/gateway/connect', [Tenant\GatewayController::class, 'connect'])->name('tenant.gateway.connect');
    Route::get('/gateway/callback', [Tenant\GatewayController::class, 'callback'])->name('tenant.gateway.callback');
    Route::post('/gateway/disconnect', [Tenant\GatewayController::class, 'disconnect'])->name('tenant.gateway.disconnect');
});
```

---

## 🎯 Plano de Implementação

### Fase 1: Conta Única + Repasse (RECOMENDADA)
- ✅ Manter estrutura atual
- ✅ Implementar sistema de repasse com taxa de 1%
- ✅ Dashboard do tenant mostra saldo disponível
- ⏱️ Tempo estimado: 1-2 dias

### Fase 2: Conta por Tenant (Futuro)
- ⏳ Migration para `tenant_gateway_configs`
- ⏳ Model e Controller OAuth
- ⏳ Atualizar ReceivableController
- ⏳ Atualizar Webhook
- ⏳ Interface para conectar conta
- ⏱️ Tempo estimado: 3-5 dias

---

## 📝 Checklist

### Fase 1 (Conta Única)
- [ ] Criar documento `SISTEMA_REPASSE_TAXA_1PORCENTO.md`
- [ ] Criar migration para `tenant_balances` ou similar
- [ ] Criar model `TenantBalance`
- [ ] Atualizar webhook para calcular saldo disponível
- [ ] Criar interface na dashboard do tenant
- [ ] Criar controller para solicitar transferência
- [ ] Implementar lógica de desconto (taxa MP + 1%)

### Fase 2 (Conta por Tenant)
- [ ] Migration `tenant_gateway_configs`
- [ ] Model `TenantGatewayConfig`
- [ ] Controller OAuth
- [ ] Rotas OAuth
- [ ] Atualizar `ReceivableController`
- [ ] Atualizar `MercadoPagoWebhookController`
- [ ] Interface para conectar conta
- [ ] Testes OAuth

---

## 🔗 Referências

- [Mercado Pago OAuth](https://www.mercadopago.com.br/developers/pt/docs/security/oauth)
- [Mercado Pago Marketplace](https://www.mercadopago.com.br/developers/pt/docs/marketplace/overview)
- [Mercado Pago API Payments](https://www.mercadopago.com.br/developers/pt/reference/payments/_payments_id/get)

---

## 💡 Observações

- **Recomendação**: Começar com Fase 1 (conta única) e evoluir para Fase 2 quando houver demanda
- **Taxa de 1%**: Pode ser configurável por tenant ou plano
- **Marketplace**: Na Fase 2, pode usar `application_fee` para cobrar taxa automaticamente
- **Segurança**: Sempre validar OAuth state e verificar tokens expirados

