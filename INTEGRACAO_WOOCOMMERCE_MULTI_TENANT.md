# Integração WooCommerce Multi-Tenant - QFiscal ERP

## 📋 Resumo Executivo

Este documento detalha como integrar o ERP QFiscal com WooCommerce em uma arquitetura multi-tenant, permitindo que cada tenant tenha sua própria loja virtual.

## 🎯 Objetivo

- Integrar ERP QFiscal com WooCommerce
- Suporte multi-tenant (cada tenant = uma loja)
- Sincronização bidirecional de produtos e pedidos
- Emissão automática de NFe para pedidos online

## 🏗️ Arquitetura

### Cenário 1: Subdomínios
```
tenant-a.loja.com
tenant-b.loja.com
tenant-c.loja.com
```

### Cenário 2: Domínios Próprios
```
loja-tenant-a.com.br
loja-tenant-b.com.br
loja-tenant-c.com.br
```

### Cenário 3: Híbrido (Recomendado)
```
tenant-a.loja.com (padrão)
loja-tenant-a.com.br (customizado)
```

## 🔧 Implementação Técnica

### 1. Estrutura de Banco de Dados

```sql
-- Adicionar campos ao modelo Tenant
ALTER TABLE tenants ADD COLUMN slug VARCHAR(100) UNIQUE;
ALTER TABLE tenants ADD COLUMN custom_domain VARCHAR(255) NULL UNIQUE;
ALTER TABLE tenants ADD COLUMN use_custom_domain BOOLEAN DEFAULT FALSE;
ALTER TABLE tenants ADD COLUMN woocommerce_domain VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN woocommerce_api_key VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN woocommerce_consumer_key VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN woocommerce_consumer_secret VARCHAR(255) NULL;
ALTER TABLE tenants ADD COLUMN woocommerce_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE tenants ADD COLUMN domain_settings JSON NULL;
```

### 2. Model Tenant Atualizado

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name', 'slug', 'custom_domain', 'use_custom_domain',
        'woocommerce_domain', 'woocommerce_api_key',
        'woocommerce_consumer_key', 'woocommerce_consumer_secret',
        'woocommerce_enabled', 'domain_settings'
    ];
    
    protected $casts = [
        'use_custom_domain' => 'boolean',
        'woocommerce_enabled' => 'boolean',
        'domain_settings' => 'array',
    ];
    
    public function getStoreUrlAttribute()
    {
        if ($this->use_custom_domain && $this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }
        
        return 'https://' . $this->slug . '.loja.com';
    }
}
```

### 3. Middleware de Resolução de Tenant

```php
<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;

class TenantResolver
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();
        
        // Tentar resolver por domínio próprio primeiro
        $tenant = Tenant::where('custom_domain', $host)
            ->where('use_custom_domain', true)
            ->first();
            
        // Se não encontrar, tentar por subdomínio
        if (!$tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('slug', $subdomain)->first();
        }
        
        if (!$tenant) {
            abort(404, 'Tenant não encontrado');
        }
        
        app()->instance('current_tenant', $tenant);
        return $next($request);
    }
}
```

### 4. Rotas Multi-Tenant

```php
<?php
// routes/web.php

// Rotas para subdomínios
Route::domain('{tenant}.loja.com')->middleware('tenant.resolver')->group(function () {
    Route::get('/', [WooCommerceController::class, 'showStore']);
    Route::prefix('api')->group(function () {
        Route::get('/products', [WooCommerceController::class, 'getProducts']);
        Route::post('/orders', [WooCommerceController::class, 'createOrder']);
        Route::get('/orders/{id}', [WooCommerceController::class, 'getOrder']);
        Route::put('/orders/{id}/status', [WooCommerceController::class, 'updateOrderStatus']);
    });
});

// Rotas para domínios próprios
Route::domain('{domain}')->middleware('tenant.resolver')->group(function () {
    Route::get('/', [WooCommerceController::class, 'showStore']);
    Route::prefix('api')->group(function () {
        Route::get('/products', [WooCommerceController::class, 'getProducts']);
        Route::post('/orders', [WooCommerceController::class, 'createOrder']);
        Route::get('/orders/{id}', [WooCommerceController::class, 'getOrder']);
        Route::put('/orders/{id}/status', [WooCommerceController::class, 'updateOrderStatus']);
    });
});

// Webhooks
Route::post('/webhooks/woocommerce/order', [WooCommerceWebhookController::class, 'handleOrder']);
```

### 5. Controller Principal

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;

class WooCommerceController extends Controller
{
    public function showStore()
    {
        $tenant = app('current_tenant');
        $products = Product::where('tenant_id', $tenant->id)
            ->where('active', true)
            ->get();
            
        return view('woocommerce.store', compact('tenant', 'products'));
    }
    
    public function getProducts(Request $request)
    {
        $tenant = app('current_tenant');
        
        $products = Product::where('tenant_id', $tenant->id)
            ->where('active', true)
            ->get();
            
        return response()->json([
            'tenant' => $tenant->name,
            'products' => $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'stock_quantity' => $this->getStock($product->id),
                    'images' => $this->getProductImages($product->id),
                    'categories' => $product->category ? [$product->category->name] : [],
                    'meta_data' => [
                        ['key' => 'ncm', 'value' => $product->ncm],
                        ['key' => 'ean', 'value' => $product->ean],
                        ['key' => 'tenant_id', 'value' => $product->tenant_id],
                    ]
                ];
            })
        ]);
    }
    
    public function createOrder(Request $request)
    {
        $tenant = app('current_tenant');
        $wooOrder = $request->all();
        
        // Criar pedido no ERP
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'client_id' => $this->findOrCreateClient($wooOrder['billing'], $tenant),
            'number' => 'WOO-' . $wooOrder['id'],
            'title' => 'Pedido WooCommerce #' . $wooOrder['id'],
            'status' => $this->mapWooStatus($wooOrder['status']),
            'total_amount' => $wooOrder['total'],
        ]);
        
        // Criar itens do pedido
        foreach ($wooOrder['line_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $this->findProductBySku($item['sku'], $tenant),
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'line_total' => $item['total'],
            ]);
        }
        
        return response()->json(['success' => true, 'order_id' => $order->id]);
    }
    
    private function findOrCreateClient($billingData, $tenant)
    {
        $client = Client::where('tenant_id', $tenant->id)
            ->where('email', $billingData['email'])
            ->first();
            
        if (!$client) {
            $client = Client::create([
                'tenant_id' => $tenant->id,
                'name' => $billingData['first_name'] . ' ' . $billingData['last_name'],
                'email' => $billingData['email'],
                'phone' => $billingData['phone'],
                'address' => $billingData['address_1'],
                'city' => $billingData['city'],
                'state' => $billingData['state'],
                'zip_code' => $billingData['postcode'],
            ]);
        }
        
        return $client->id;
    }
    
    private function findProductBySku($sku, $tenant)
    {
        $product = Product::where('tenant_id', $tenant->id)
            ->where('sku', $sku)
            ->first();
            
        return $product ? $product->id : null;
    }
    
    private function mapWooStatus($wooStatus)
    {
        $mapping = [
            'pending' => 'open',
            'processing' => 'open',
            'completed' => 'fulfilled',
            'cancelled' => 'canceled',
        ];
        
        return $mapping[$wooStatus] ?? 'open';
    }
    
    private function getStock($productId)
    {
        // Implementar lógica de estoque
        return 0;
    }
    
    private function getProductImages($productId)
    {
        // Implementar lógica de imagens
        return [];
    }
}
```

### 6. Webhook Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Tenant;

class WooCommerceWebhookController extends Controller
{
    public function handleOrder(Request $request)
    {
        // Resolver tenant pelo header ou subdomínio
        $tenant = $this->resolveTenant($request);
        
        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }
        
        $wooOrder = $request->all();
        
        // Criar pedido no contexto do tenant
        $order = Order::create([
            'tenant_id' => $tenant->id,
            'client_id' => $this->findOrCreateClient($wooOrder['billing'], $tenant),
            'number' => 'WOO-' . $wooOrder['id'],
            'title' => 'Pedido WooCommerce #' . $wooOrder['id'],
            'status' => $this->mapWooStatus($wooOrder['status']),
            'total_amount' => $wooOrder['total'],
        ]);
        
        // Criar itens do pedido
        foreach ($wooOrder['line_items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $this->findProductBySku($item['sku'], $tenant),
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'line_total' => $item['total'],
            ]);
        }
        
        return response()->json(['success' => true, 'tenant' => $tenant->name]);
    }
    
    private function resolveTenant(Request $request)
    {
        // Opção 1: Por subdomínio
        $subdomain = explode('.', $request->getHost())[0];
        if ($subdomain !== 'www') {
            return Tenant::where('slug', $subdomain)->first();
        }
        
        // Opção 2: Por header
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::find($tenantId);
        }
        
        // Opção 3: Por API Key
        $apiKey = $request->header('X-Tenant-API-Key');
        if ($apiKey) {
            return Tenant::where('woocommerce_api_key', $apiKey)->first();
        }
        
        return null;
    }
    
    private function findOrCreateClient($billingData, $tenant)
    {
        $client = Client::where('tenant_id', $tenant->id)
            ->where('email', $billingData['email'])
            ->first();
            
        if (!$client) {
            $client = Client::create([
                'tenant_id' => $tenant->id,
                'name' => $billingData['first_name'] . ' ' . $billingData['last_name'],
                'email' => $billingData['email'],
                'phone' => $billingData['phone'],
                'address' => $billingData['address_1'],
                'city' => $billingData['city'],
                'state' => $billingData['state'],
                'zip_code' => $billingData['postcode'],
            ]);
        }
        
        return $client->id;
    }
    
    private function findProductBySku($sku, $tenant)
    {
        $product = Product::where('tenant_id', $tenant->id)
            ->where('sku', $sku)
            ->first();
            
        return $product ? $product->id : null;
    }
    
    private function mapWooStatus($wooStatus)
    {
        $mapping = [
            'pending' => 'open',
            'processing' => 'open',
            'completed' => 'fulfilled',
            'cancelled' => 'canceled',
        ];
        
        return $mapping[$wooStatus] ?? 'open';
    }
}
```

### 7. Service de Sincronização

```php
<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Product;
use App\Models\StockMovement;

class WooCommerceSyncService
{
    protected $tenant;
    protected $wooClient;
    
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
        $this->wooClient = new WooCommerceClient(
            $tenant->woocommerce_domain,
            $tenant->woocommerce_consumer_key,
            $tenant->woocommerce_consumer_secret
        );
    }
    
    public function syncProducts()
    {
        $products = Product::where('tenant_id', $this->tenant->id)
            ->where('active', true)
            ->get();
            
        foreach ($products as $product) {
            $this->syncProduct($product);
        }
    }
    
    public function syncProduct(Product $product)
    {
        $wooProduct = [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock_quantity' => $this->getStock($product->id),
            'meta_data' => [
                ['key' => 'ncm', 'value' => $product->ncm],
                ['key' => 'ean', 'value' => $product->ean],
                ['key' => 'tenant_id', 'value' => $this->tenant->id],
            ]
        ];
        
        // Verificar se produto já existe no WooCommerce
        $existing = $this->wooClient->get('products', ['sku' => $product->sku]);
        
        if (empty($existing)) {
            // Criar novo produto
            $this->wooClient->post('products', $wooProduct);
        } else {
            // Atualizar produto existente
            $this->wooClient->put('products/' . $existing[0]['id'], $wooProduct);
        }
    }
    
    public function syncStock(Request $request)
    {
        $product = Product::findOrFail($request->product_id);
        $newStock = $request->stock_quantity;
        
        // Registrar movimento de estoque
        StockMovement::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => $newStock - $this->getCurrentStock($product->id),
            'note' => 'Sincronização WooCommerce'
        ]);
    }
    
    private function getStock($productId)
    {
        // Implementar lógica de estoque
        return 0;
    }
    
    private function getCurrentStock($productId)
    {
        // Implementar lógica de estoque atual
        return 0;
    }
}
```

## 🔧 Configuração

### 1. Configuração DNS

#### Para Subdomínios:
```
# DNS do domínio principal (loja.com)
*.loja.com    CNAME    servidor.loja.com
loja.com      A        192.168.1.100
```

#### Para Domínios Próprios:
```
# DNS do domínio do tenant
loja-tenant-a.com.br    A        192.168.1.100
www.loja-tenant-a.com.br    CNAME    loja-tenant-a.com.br
```

### 2. Configuração Nginx

```nginx
# Para subdomínios
server {
    listen 80;
    server_name *.loja.com;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Para domínios próprios
server {
    listen 80;
    server_name loja-tenant-a.com.br www.loja-tenant-a.com.br;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

### 3. Interface de Configuração

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantWooCommerceController extends Controller
{
    public function show()
    {
        $tenant = auth()->user()->tenant;
        return view('tenant.woocommerce.config', compact('tenant'));
    }
    
    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;
        
        $validated = $request->validate([
            'woocommerce_domain' => 'required|url',
            'woocommerce_consumer_key' => 'required|string',
            'woocommerce_consumer_secret' => 'required|string',
            'woocommerce_enabled' => 'boolean',
            'use_custom_domain' => 'boolean',
            'custom_domain' => 'nullable|string|unique:tenants,custom_domain,' . $tenant->id,
        ]);
        
        $tenant->update($validated);
        
        return back()->with('success', 'Configuração WooCommerce atualizada!');
    }
}
```

## 📊 Fluxo de Integração

### 1. Sincronização de Produtos
```
WooCommerce → API REST → ERP QFiscal
- Lista produtos ativos
- Sincroniza preços e estoque
- Atualiza informações fiscais
```

### 2. Processamento de Pedidos
```
WooCommerce → Webhook → ERP QFiscal
- Cria pedido automaticamente
- Baixa estoque
- Emite NFe (se configurado)
```

### 3. Atualização de Status
```
ERP QFiscal → API → WooCommerce
- Atualiza status do pedido
- Envia informações de rastreamento
```

## 🚀 Cronograma de Implementação

### Semana 1: Estrutura Base
- [ ] Migration para campos de domínio
- [ ] Middleware de resolução de tenant
- [ ] Rotas multi-tenant
- [ ] Controller básico

### Semana 2: Sincronização
- [ ] Service de sincronização
- [ ] API de produtos
- [ ] Webhook de pedidos
- [ ] Mapeamento de dados

### Semana 3: Interface e Testes
- [ ] Interface de configuração
- [ ] Testes de integração
- [ ] Ajustes e correções
- [ ] Documentação

### Semana 4: Deploy e Configuração
- [ ] Deploy em produção
- [ ] Configuração DNS
- [ ] Configuração Nginx
- [ ] Treinamento da equipe

## ✅ Vantagens

### 1. Isolamento Total
- Cada tenant tem sua própria loja
- Dados completamente separados
- Configurações independentes

### 2. Escalabilidade
- Fácil adicionar novos tenants
- Recursos compartilhados
- Manutenção centralizada

### 3. Flexibilidade
- Domínios próprios ou subdomínios
- Temas personalizados
- Integrações específicas

## 🔒 Segurança

### 1. Autenticação
- API Keys por tenant
- Validação de webhooks
- Rate limiting

### 2. Isolamento
- Dados por tenant
- Configurações separadas
- Logs independentes

### 3. Monitoramento
- Logs de sincronização
- Alertas de falhas
- Dashboard de status

## 📈 Monitoramento

### 1. Logs
```php
// Log de sincronização
Log::info('WooCommerce Sync', [
    'tenant_id' => $tenant->id,
    'action' => 'product_sync',
    'products_count' => $products->count()
]);
```

### 2. Alertas
```php
// Alertas de falha
if ($syncFailed) {
    Mail::to($tenant->admin_email)->send(new SyncFailedAlert($tenant));
}
```

### 3. Dashboard
```php
// Métricas por tenant
$metrics = [
    'products_synced' => $productsCount,
    'orders_received' => $ordersCount,
    'last_sync' => $lastSync,
    'status' => $status
];
```

## 🎯 Conclusão

A integração WooCommerce multi-tenant é viável e oferece:

- **Flexibilidade**: Subdomínios ou domínios próprios
- **Escalabilidade**: Fácil adicionar novos tenants
- **Isolamento**: Dados e configurações separados
- **Automação**: Sincronização bidirecional
- **Integração**: Emissão automática de NFe

**Tempo estimado**: 3-4 semanas
**Complexidade**: Média
**Benefício**: Alto

---

**Documento gerado em**: {{ date('d/m/Y H:i:s') }}
**Versão**: 1.0
**Autor**: Análise do Sistema QFiscal ERP
