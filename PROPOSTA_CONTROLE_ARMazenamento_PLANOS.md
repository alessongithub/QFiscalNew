# Proposta: Controle de Armazenamento por Plano

## 📋 Resumo

Esta proposta implementa controle de espaço em disco por plano, inspirado no modelo do Bling, dividindo o armazenamento em duas categorias:
- **Espaço de Dados (MB)**: Informações estruturadas no banco (clientes, produtos, vendas, estoque, etc.)
- **Espaço de Arquivos (GB)**: Arquivos físicos (XMLs de NF-e, imagens, documentos PDF, etc.)

## 🎯 Objetivos

1. Controlar uso de espaço por tenant/plano
2. Permitir monitoramento em tempo real no dashboard
3. Bloquear novas inserções quando limite for atingido
4. Oferecer upgrade de plano ou compra de espaço adicional
5. Exibir alertas quando próximo do limite

## 📊 Limites Propostos por Plano (Inspirado no Bling)

### Plano Gratuito
- **Dados**: 50 MB
- **Arquivos**: 500 MB (0.5 GB)

### Plano Emissor Fiscal
- **Dados**: 60 MB
- **Arquivos**: 1 GB

### Plano Básico
- **Dados**: 120 MB
- **Arquivos**: 2 GB

### Plano Profissional
- **Dados**: 240 MB
- **Arquivos**: 5 GB

### Plano Enterprise
- **Dados**: Ilimitado (-1)
- **Arquivos**: Ilimitado (-1)

### Espaço Adicional (Compra)
- **Dados**: +50 MB por R$ 9,90/mês
- **Arquivos**: +500 MB por R$ 9,90/mês

---

## 🗄️ Estrutura do Banco de Dados

### 1. Migration: Adicionar campos de storage aos planos

```php
// database/migrations/YYYY_MM_DD_add_storage_limits_to_plans.php
Schema::table('plans', function (Blueprint $table) {
    $table->integer('storage_data_mb')->default(50)->comment('Limite de dados em MB (-1 = ilimitado)');
    $table->integer('storage_files_mb')->default(500)->comment('Limite de arquivos em MB (-1 = ilimitado)');
    $table->decimal('additional_data_price', 10, 2)->default(9.90)->comment('Preço por 50MB adicionais de dados');
    $table->decimal('additional_files_price', 10, 2)->default(9.90)->comment('Preço por 500MB adicionais de arquivos');
});
```

### 2. Migration: Criar tabela de uso de storage por tenant

```php
// database/migrations/YYYY_MM_DD_create_tenant_storage_usage.php
Schema::create('tenant_storage_usage', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    
    // Uso atual (atualizado via trigger ou scheduled job)
    $table->bigInteger('data_size_bytes')->default(0)->comment('Tamanho dos dados em bytes');
    $table->bigInteger('files_size_bytes')->default(0)->comment('Tamanho dos arquivos em bytes');
    
    // Espaço adicional comprado (mesmo padrão do Bling)
    $table->integer('additional_data_mb')->default(0)->comment('MB adicionais comprados');
    $table->integer('additional_files_mb')->default(0)->comment('MB adicionais de arquivos comprados');
    
    // Cache da última atualização
    $table->timestamp('last_calculated_at')->nullable();
    
    $table->timestamps();
    
    $table->unique('tenant_id');
});
```

### 3. Migration: Criar tabela de compras de espaço adicional

```php
// database/migrations/YYYY_MM_DD_create_storage_addons.php
Schema::create('storage_addons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['data', 'files']);
    $table->integer('quantity_mb');
    $table->decimal('price', 10, 2);
    $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending');
    $table->date('expires_at')->nullable();
    $table->timestamps();
});
```

---

## 💻 Implementação em Código

### 1. Model: TenantStorageUsage

```php
// app/Models/TenantStorageUsage.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantStorageUsage extends Model
{
    protected $fillable = [
        'tenant_id',
        'data_size_bytes',
        'files_size_bytes',
        'additional_data_mb',
        'additional_files_mb',
        'last_calculated_at'
    ];

    protected $casts = [
        'data_size_bytes' => 'integer',
        'files_size_bytes' => 'integer',
        'additional_data_mb' => 'integer',
        'additional_files_mb' => 'integer',
        'last_calculated_at' => 'datetime'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Limite total de dados em MB (plano + adicional)
     */
    public function getTotalDataLimitMbAttribute(): int
    {
        $plan = $this->tenant->plan;
        if (!$plan || ($plan->features['storage_data_mb'] ?? -1) === -1) {
            return -1; // Ilimitado
        }
        return ($plan->features['storage_data_mb'] ?? 50) + $this->additional_data_mb;
    }

    /**
     * Limite total de arquivos em MB (plano + adicional)
     */
    public function getTotalFilesLimitMbAttribute(): int
    {
        $plan = $this->tenant->plan;
        if (!$plan || ($plan->features['storage_files_mb'] ?? -1) === -1) {
            return -1; // Ilimitado
        }
        return ($plan->features['storage_files_mb'] ?? 500) + $this->additional_files_mb;
    }

    /**
     * Uso atual de dados em MB
     */
    public function getDataUsageMbAttribute(): float
    {
        return round($this->data_size_bytes / 1024 / 1024, 2);
    }

    /**
     * Uso atual de arquivos em MB
     */
    public function getFilesUsageMbAttribute(): float
    {
        return round($this->files_size_bytes / 1024 / 1024, 2);
    }

    /**
     * Percentual de uso de dados
     */
    public function getDataUsagePercentAttribute(): float
    {
        $limit = $this->total_data_limit_mb;
        if ($limit === -1) return 0;
        return $limit > 0 ? min(100, ($this->data_usage_mb / $limit) * 100) : 0;
    }

    /**
     * Percentual de uso de arquivos
     */
    public function getFilesUsagePercentAttribute(): float
    {
        $limit = $this->total_files_limit_mb;
        if ($limit === -1) return 0;
        return $limit > 0 ? min(100, ($this->files_usage_mb / $limit) * 100) : 0;
    }

    /**
     * Verifica se pode adicionar mais dados
     */
    public function canAddData(int $sizeBytes): bool
    {
        $limit = $this->total_data_limit_mb;
        if ($limit === -1) return true;
        $newTotal = $this->data_size_bytes + $sizeBytes;
        $limitBytes = $limit * 1024 * 1024;
        return $newTotal <= $limitBytes;
    }

    /**
     * Verifica se pode adicionar mais arquivos
     */
    public function canAddFiles(int $sizeBytes): bool
    {
        $limit = $this->total_files_limit_mb;
        if ($limit === -1) return true;
        $newTotal = $this->files_size_bytes + $sizeBytes;
        $limitBytes = $limit * 1024 * 1024;
        return $newTotal <= $limitBytes;
    }
}
```

### 2. Service: StorageCalculator

```php
// app/Services/StorageCalculator.php
<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantStorageUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageCalculator
{
    /**
     * Calcula uso de dados do tenant (tamanho em bytes das tabelas)
     */
    public function calculateDataSize(Tenant $tenant): int
    {
        // Tabelas principais que contam como "dados"
        $tables = [
            'clients',
            'products',
            'orders',
            'quotes',
            'service_orders',
            'receivables',
            'payables',
            'invoices',
            'stock_movements',
            // ... outras tabelas
        ];

        $totalBytes = 0;
        foreach ($tables as $table) {
            $query = "SELECT 
                SUM(data_length + index_length) as size
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                AND table_name = '{$table}'";
            
            $result = DB::select($query);
            $totalBytes += (int) ($result[0]->size ?? 0);
        }

        return $totalBytes;
    }

    /**
     * Calcula uso de arquivos do tenant (storage/disk)
     */
    public function calculateFilesSize(Tenant $tenant): int
    {
        $totalBytes = 0;
        
        // Diretórios do tenant no storage
        $directories = [
            "tenants/{$tenant->id}/nfe/xml",
            "tenants/{$tenant->id}/nfe/danfe",
            "tenants/{$tenant->id}/products/images",
            "tenants/{$tenant->id}/documents",
            // ... outros diretórios
        ];

        foreach ($directories as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $files = Storage::disk('public')->allFiles($dir);
                foreach ($files as $file) {
                    $totalBytes += Storage::disk('public')->size($file);
                }
            }
        }

        return $totalBytes;
    }

    /**
     * Atualiza uso de storage do tenant
     */
    public function updateTenantUsage(Tenant $tenant): void
    {
        $dataSize = $this->calculateDataSize($tenant);
        $filesSize = $this->calculateFilesSize($tenant);

        TenantStorageUsage::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'data_size_bytes' => $dataSize,
                'files_size_bytes' => $filesSize,
                'last_calculated_at' => now()
            ]
        );
    }

    /**
     * Atualiza uso de todos os tenants (executar via schedule)
     */
    public function updateAllTenants(): void
    {
        Tenant::where('active', true)->each(function ($tenant) {
            $this->updateTenantUsage($tenant);
        });
    }
}
```

### 3. Middleware: StorageLimitMiddleware

```php
// app/Http/Middleware/StorageLimitMiddleware.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StorageLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $type = 'data')
    {
        $tenant = auth()->user()->tenant;
        $usage = $tenant->storageUsage;

        if (!$usage) {
            return $next($request);
        }

        $sizeBytes = $request->input('size_bytes', 0);
        
        if ($type === 'data' && !$usage->canAddData($sizeBytes)) {
            return back()->withErrors([
                'storage' => 'Limite de armazenamento de dados atingido. Faça upgrade ou compre espaço adicional.'
            ]);
        }

        if ($type === 'files' && !$usage->canAddFiles($sizeBytes)) {
            return back()->withErrors([
                'storage' => 'Limite de armazenamento de arquivos atingido. Faça upgrade ou compre espaço adicional.'
            ]);
        }

        return $next($request);
    }
}
```

### 4. Command: Atualizar Storage Usage

```php
// app/Console/Commands/UpdateStorageUsage.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StorageCalculator;

class UpdateStorageUsage extends Command
{
    protected $signature = 'storage:update-usage';
    protected $description = 'Atualiza uso de storage de todos os tenants';

    public function handle(StorageCalculator $calculator)
    {
        $this->info('Atualizando uso de storage...');
        $calculator->updateAllTenants();
        $this->info('Concluído!');
    }
}
```

### 5. Controller: Storage Management

```php
// app/Http/Controllers/StorageController.php
<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\StorageAddon;
use App\Services\StorageCalculator;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $usage = $tenant->storageUsage;
        $plan = $tenant->plan;

        if (!$usage) {
            $calculator = new StorageCalculator();
            $calculator->updateTenantUsage($tenant);
            $usage = $tenant->fresh()->storageUsage;
        }

        return view('storage.index', compact('usage', 'plan', 'tenant'));
    }

    public function purchaseAddon(Request $request)
    {
        $request->validate([
            'type' => 'required|in:data,files',
            'quantity_mb' => 'required|integer|min:50|in:50,500'
        ]);

        $tenant = auth()->user()->tenant;
        $plan = $tenant->plan;

        // Preços baseados no plano
        $priceData = $plan->features['additional_data_price'] ?? 9.90;
        $priceFiles = $plan->features['additional_files_price'] ?? 9.90;

        $price = $request->type === 'data' ? $priceData : $priceFiles;
        $totalPrice = $price; // Por 50MB ou 500MB

        // Criar addon e redirecionar para checkout
        $addon = StorageAddon::create([
            'tenant_id' => $tenant->id,
            'type' => $request->type,
            'quantity_mb' => $request->quantity_mb,
            'price' => $totalPrice,
            'status' => 'pending'
        ]);

        return redirect()->route('checkout.index', ['addon_id' => $addon->id]);
    }
}
```

---

## 📱 Interface do Usuário

### 1. Widget no Dashboard

```blade
<!-- resources/views/components/storage-widget.blade.php -->
@php
    $usage = auth()->user()->tenant->storageUsage;
    $plan = auth()->user()->tenant->plan;
@endphp

@if($usage)
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <h3 class="text-lg font-semibold mb-4">Armazenamento</h3>
        
        <!-- Dados -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Dados</span>
                <span class="text-sm font-medium">
                    {{ number_format($usage->data_usage_mb, 1) }} MB
                    @if($usage->total_data_limit_mb !== -1)
                        / {{ $usage->total_data_limit_mb }} MB
                    @else
                        / Ilimitado
                    @endif
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $usage->data_usage_percent >= 90 ? 'bg-red-500' : ($usage->data_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min(100, $usage->data_usage_percent) }}%"></div>
            </div>
            @if($usage->data_usage_percent >= 90)
                <p class="text-xs text-red-600 mt-1">⚠️ Limite quase atingido!</p>
            @endif
        </div>

        <!-- Arquivos -->
        <div class="mb-4">
            <div class="flex justify-between mb-1">
                <span class="text-sm text-gray-600">Arquivos</span>
                <span class="text-sm font-medium">
                    {{ number_format($usage->files_usage_mb, 1) }} MB
                    @if($usage->total_files_limit_mb !== -1)
                        / {{ $usage->total_files_limit_mb }} MB
                    @else
                        / Ilimitado
                    @endif
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $usage->files_usage_percent >= 90 ? 'bg-red-500' : ($usage->files_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                     style="width: {{ min(100, $usage->files_usage_percent) }}%"></div>
            </div>
            @if($usage->files_usage_percent >= 90)
                <p class="text-xs text-red-600 mt-1">⚠️ Limite quase atingido!</p>
            @endif
        </div>

        <!-- Ações -->
        <div class="flex gap-2">
            <a href="{{ route('storage.index') }}" class="flex-1 text-center px-3 py-2 bg-gray-100 rounded hover:bg-gray-200 text-sm">
                Ver Detalhes
            </a>
            @if($usage->data_usage_percent >= 75 || $usage->files_usage_percent >= 75)
                <a href="{{ route('storage.upgrade') }}" class="flex-1 text-center px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                    Comprar Espaço
                </a>
            @endif
        </div>
    </div>
@endif
```

---

## 🔄 Atualização do PLANOS_E_REGRAS.md

Adicionar ao campo `features` de cada plano:

```json
{
  "storage_data_mb": 50,        // ou -1 para ilimitado
  "storage_files_mb": 500,      // ou -1 para ilimitado
  "additional_data_price": 9.90, // preço por 50MB adicional
  "additional_files_price": 9.90 // preço por 500MB adicional
}
```

---

## 📅 Agendamento e Cron

### ✅ Laravel 11: Configuração no `routes/console.php`

**Já implementado**:
```php
// routes/console.php
Schedule::command('storage:update-usage')
    ->dailyAt('02:00')
    ->description('Atualizar uso de storage de todos os tenants');
```

### ⚙️ Configuração do Cron no Servidor (Produção)

**O Laravel NÃO executa tarefas agendadas automaticamente**. É necessário configurar um cron job no servidor Linux:

#### 1. Adicionar ao Crontab do Servidor

```bash
# Acessar o crontab
crontab -e

# Adicionar esta linha (roda a cada minuto para verificar tarefas agendadas)
* * * * * cd /caminho/para/qfiscal && php artisan schedule:run >> /dev/null 2>&1

# Salvar e sair
```

**Explicação**:
- `* * * * *` = executa a cada minuto
- `cd /caminho/para/qfiscal` = navega para o diretório do projeto
- `php artisan schedule:run` = executa o scheduler do Laravel (que verifica se há tarefas para rodar)
- `>> /dev/null 2>&1` = redireciona logs (opcional)

#### 2. Verificar se o Cron está Funcionando

```bash
# Verificar se o cron está rodando
crontab -l

# Verificar logs (se configurado)
tail -f storage/logs/laravel.log
```

#### 3. Execução Manual (Para Testes)

```bash
# Rodar o comando manualmente
php artisan storage:update-usage

# Verificar próximas execuções agendadas
php artisan schedule:list

# Testar o scheduler (simula execução)
php artisan schedule:run
```

### 🖥️ Desenvolvimento Local (Windows/XAMPP)

**Opcional - Configurar Task Scheduler do Windows**:

1. Abrir "Agendador de Tarefas" do Windows
2. Criar Tarefa Básica
3. Trigger: Diariamente às 2h
4. Ação: Iniciar programa
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `artisan schedule:run`
   - Iniciar em: `C:\xampp-novo\htdocs\emissor\qfiscal`

**Ou executar manualmente quando necessário**:
```bash
php artisan storage:update-usage
```

### ✅ Por que este é o Melhor Método?

**Vantagens**:
1. ✅ **Padrão Laravel**: Usa o sistema oficial de agendamento
2. ✅ **Flexível**: Fácil mudar horário, frequência, condições
3. ✅ **Confiável**: Processo testado e estável
4. ✅ **Escalável**: Funciona com múltiplos tenants
5. ✅ **Manutenível**: Tudo configurado em código (versionado no Git)
6. ✅ **Otimizado**: Roda apenas 1x/dia (economiza recursos)

**Alternativas consideradas**:
- ❌ **MySQL Events**: Menos flexível, código no banco (não versionado)
- ❌ **Verificar a cada requisição**: Muito lento e desnecessário
- ❌ **Jobs por tenant**: Mais complexo, não necessário para atualização diária

### 📋 Checklist de Configuração

#### ✅ Desenvolvimento (Já Funciona)
- [x] Comando criado: `storage:update-usage`
- [x] Agendamento no `routes/console.php`
- [x] Pode executar manualmente: `php artisan storage:update-usage`

#### ⚠️ Produção (Fazer na Implantação)
- [ ] Configurar cron job no servidor Linux
- [ ] Verificar permissões do usuário que executa o cron
- [ ] Testar execução automática após configuração
- [ ] Configurar logs para monitoramento (opcional)

### 📝 Instruções para Implantação em Produção

**No servidor Linux, execute**:

```bash
# 1. Editar crontab
crontab -e

# 2. Adicionar linha (ajustar caminho do projeto)
* * * * * cd /var/www/qfiscal && /usr/bin/php artisan schedule:run >> /var/log/qfiscal-scheduler.log 2>&1

# 3. Verificar se foi adicionado
crontab -l

# 4. Testar execução manual primeiro
cd /var/www/qfiscal
php artisan storage:update-usage

# 5. Verificar logs após algumas execuções
tail -f /var/log/qfiscal-scheduler.log
```

**Nota**: Em hospedagem compartilhada, alguns provedores permitem configurar cron via painel de controle (cPanel, Plesk, etc.).

---

## 🔧 Complementos de Implementação

### 6. Model: StorageAddon

```php
// app/Models/StorageAddon.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorageAddon extends Model
{
    protected $fillable = [
        'tenant_id',
        'type',
        'quantity_mb',
        'price',
        'status',
        'expires_at'
    ];

    protected $casts = [
        'quantity_mb' => 'integer',
        'price' => 'decimal:2',
        'expires_at' => 'date'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
```

### 7. Relacionamento no Tenant Model

```php
// Adicionar em app/Models/Tenant.php

public function storageUsage()
{
    return $this->hasOne(TenantStorageUsage::class);
}

public function storageAddons()
{
    return $this->hasMany(StorageAddon::class)->where('status', 'active');
}
```

---

## 🎯 Integração nos Controllers Existentes

### Exemplo: ClientController@store

```php
// app/Http/Controllers/ClientController.php - Método store

use App\Services\StorageCalculator;

public function store(Request $request)
{
    // ... validações existentes ...
    
    $tenant = auth()->user()->tenant;
    $usage = $tenant->storageUsage;
    
    // Verificar espaço de dados ANTES de criar
    if ($usage) {
        // Estimativa: um cliente médio ocupa ~2-5 KB (aproximação)
        $estimatedSize = 4096; // 4 KB estimado
        if (!$usage->canAddData($estimatedSize)) {
            return back()->withErrors([
                'storage' => 'Limite de armazenamento de dados atingido. <a href="' . route('plans.upgrade') . '">Faça upgrade</a> ou <a href="' . route('storage.index') . '">compre espaço adicional</a>.'
            ])->withInput();
        }
    }
    
    // Criar cliente normalmente
    $client = Client::create($validated);
    
    // Atualizar uso de dados após criar (opcional - pode aguardar job diário)
    // $calculator = new StorageCalculator();
    // $calculator->updateTenantUsage($tenant);
    
    return redirect()->route('clients.index')->with('success', 'Cliente criado com sucesso!');
}
```

### Exemplo: ProductController@store

```php
// app/Http/Controllers/ProductController.php - Método store

public function store(Request $request)
{
    // ... validações existentes ...
    
    $tenant = auth()->user()->tenant;
    $usage = $tenant->storageUsage;
    
    // Verificar espaço de dados
    if ($usage) {
        $estimatedSize = 5120; // 5 KB estimado por produto
        if (!$usage->canAddData($estimatedSize)) {
            return back()->withErrors([
                'storage' => 'Limite de armazenamento de dados atingido. Faça upgrade ou compre espaço adicional.'
            ])->withInput();
        }
    }
    
    // Processar upload de imagem (verificar espaço de arquivos)
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $fileSize = $file->getSize();
        
        if ($usage && !$usage->canAddFiles($fileSize)) {
            return back()->withErrors([
                'image' => 'Limite de armazenamento de arquivos atingido. Faça upgrade ou compre espaço adicional.'
            ])->withInput();
        }
        
        // Upload do arquivo...
        $path = $file->store("tenants/{$tenant->id}/products/images", 'public');
        // ...
    }
    
    Product::create($validated);
    return redirect()->route('products.index')->with('success', 'Produto criado com sucesso!');
}
```

### Exemplo: ServiceOrderController@addAttachment

```php
// app/Http/Controllers/ServiceOrderController.php - Método addAttachment

public function addAttachment(ServiceOrder $serviceOrder, Request $request)
{
    // ... validações existentes ...
    
    $file = $request->file('file');
    $fileSize = $file->getSize();
    $tenant = auth()->user()->tenant;
    $usage = $tenant->storageUsage;
    
    // Verificar espaço de arquivos ANTES de fazer upload
    if ($usage && !$usage->canAddFiles($fileSize)) {
        return back()->withErrors([
            'file' => 'Limite de armazenamento de arquivos atingido. Faça upgrade ou compre espaço adicional.'
        ]);
    }
    
    // Fazer upload normalmente
    $path = $file->store('service_orders/' . $serviceOrder->id, 'public');
    
    // Atualizar uso imediatamente após upload
    if ($usage) {
        $usage->files_size_bytes += $fileSize;
        $usage->save();
    }
    
    ServiceOrderAttachment::create([...]);
    return back()->with('success', 'Anexo adicionado.');
}
```

---

## 🗺️ Rotas Necessárias

```php
// routes/web.php (dentro do grupo autenticado)

// Storage Management
Route::middleware(['auth'])->group(function () {
    Route::get('/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::get('/storage/upgrade', [StorageController::class, 'upgrade'])->name('storage.upgrade');
    Route::post('/storage/purchase-addon', [StorageController::class, 'purchaseAddon'])->name('storage.purchase-addon');
});
```

---

## 💳 Integração com Checkout/Webhook

### Modificar CheckoutController para aceitar StorageAddon

```php
// app/Http/Controllers/CheckoutController.php

public function index(Request $request)
{
    $planId = $request->input('plan_id');
    $addonId = $request->input('addon_id'); // Novo: compra de espaço adicional
    
    if ($addonId) {
        $addon = StorageAddon::findOrFail($addonId);
        $item = [
            'description' => "Espaço adicional: {$addon->quantity_mb} MB de " . ($addon->type === 'data' ? 'dados' : 'arquivos'),
            'price' => $addon->price
        ];
        // Processar pagamento do addon...
    }
    
    // ... resto do código existente ...
}
```

### Processar addon no Webhook do MercadoPago

```php
// app/Http/Controllers/Webhooks/MercadoPagoWebhookController.php

if ($status === 'approved') {
    // ... código existente para invoices ...
    
    // Processar storage addon se houver
    $addonId = $paymentJson['external_reference'] ?? null; // Ajustar conforme estrutura
    if ($addonId && str_starts_with($addonId, 'addon_')) {
        $addon = StorageAddon::find(str_replace('addon_', '', $addonId));
        if ($addon && $addon->status === 'pending') {
            $addon->status = 'active';
            $addon->save();
            
            // Atualizar uso do tenant
            $usage = $addon->tenant->storageUsage;
            if ($usage) {
                if ($addon->type === 'data') {
                    $usage->additional_data_mb += $addon->quantity_mb;
                } else {
                    $usage->additional_files_mb += $addon->quantity_mb;
                }
                $usage->save();
            }
        }
    }
}
```

---

## 📄 Views Completas

### View: storage/index.blade.php

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerenciar Armazenamento
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Uso de Armazenamento</h3>
                        
                        <!-- Dados -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="font-medium">Armazenamento de Dados</span>
                                <span class="text-sm">
                                    {{ number_format($usage->data_usage_mb, 2) }} MB
                                    @if($usage->total_data_limit_mb !== -1)
                                        / {{ $usage->total_data_limit_mb }} MB
                                    @else
                                        / Ilimitado
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                <div class="h-3 rounded-full {{ $usage->data_usage_percent >= 90 ? 'bg-red-500' : ($usage->data_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min(100, $usage->data_usage_percent) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500">Dados estruturados: clientes, produtos, vendas, etc.</p>
                            @if($usage->data_usage_percent >= 75)
                                <p class="text-xs text-orange-600 mt-1">⚠️ Você está usando {{ number_format($usage->data_usage_percent, 1) }}% do seu espaço de dados</p>
                            @endif
                        </div>

                        <!-- Arquivos -->
                        <div class="mb-6">
                            <div class="flex justify-between mb-2">
                                <span class="font-medium">Armazenamento de Arquivos</span>
                                <span class="text-sm">
                                    {{ number_format($usage->files_usage_mb, 2) }} MB
                                    @if($usage->total_files_limit_mb !== -1)
                                        / {{ $usage->total_files_limit_mb }} MB
                                    @else
                                        / Ilimitado
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                <div class="h-3 rounded-full {{ $usage->files_usage_percent >= 90 ? 'bg-red-500' : ($usage->files_usage_percent >= 75 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                     style="width: {{ min(100, $usage->files_usage_percent) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500">Arquivos: XMLs NF-e, imagens, documentos PDF, etc.</p>
                            @if($usage->files_usage_percent >= 75)
                                <p class="text-xs text-orange-600 mt-1">⚠️ Você está usando {{ number_format($usage->files_usage_percent, 1) }}% do seu espaço de arquivos</p>
                            @endif
                        </div>
                    </div>

                    <!-- Espaço Adicional Comprado -->
                    @if($usage->additional_data_mb > 0 || $usage->additional_files_mb > 0)
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="font-semibold mb-2">Espaço Adicional Ativo</h4>
                            @if($usage->additional_data_mb > 0)
                                <p class="text-sm">+{{ $usage->additional_data_mb }} MB de dados</p>
                            @endif
                            @if($usage->additional_files_mb > 0)
                                <p class="text-sm">+{{ $usage->additional_files_mb }} MB de arquivos</p>
                            @endif
                        </div>
                    @endif

                    <!-- Ações -->
                    <div class="flex gap-3">
                        <a href="{{ route('plans.upgrade') }}" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Fazer Upgrade de Plano
                        </a>
                        <a href="{{ route('storage.upgrade') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Comprar Espaço Adicional
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### View: storage/upgrade.blade.php

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Comprar Espaço Adicional
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Escolha o tipo de espaço adicional</h3>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Espaço de Dados -->
                        <div class="border rounded-lg p-6">
                            <h4 class="font-semibold mb-2">Espaço de Dados</h4>
                            <p class="text-sm text-gray-600 mb-4">+50 MB adicionais para dados (clientes, produtos, vendas)</p>
                            <p class="text-2xl font-bold text-green-600 mb-4">
                                R$ {{ number_format($plan->features['additional_data_price'] ?? 9.90, 2, ',', '.') }}/mês
                            </p>
                            <form method="POST" action="{{ route('storage.purchase-addon') }}">
                                @csrf
                                <input type="hidden" name="type" value="data">
                                <input type="hidden" name="quantity_mb" value="50">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                                    Comprar
                                </button>
                            </form>
                        </div>

                        <!-- Espaço de Arquivos -->
                        <div class="border rounded-lg p-6">
                            <h4 class="font-semibold mb-2">Espaço de Arquivos</h4>
                            <p class="text-sm text-gray-600 mb-4">+500 MB adicionais para arquivos (XMLs, imagens, PDFs)</p>
                            <p class="text-2xl font-bold text-green-600 mb-4">
                                R$ {{ number_format($plan->features['additional_files_price'] ?? 9.90, 2, ',', '.') }}/mês
                            </p>
                            <form method="POST" action="{{ route('storage.purchase-addon') }}">
                                @csrf
                                <input type="hidden" name="type" value="files">
                                <input type="hidden" name="quantity_mb" value="500">
                                <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
                                    Comprar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## ⚙️ Detalhes Técnicos Importantes

### 1. Estimativa de Tamanho Antes de Salvar

```php
// Helper para estimar tamanho aproximado de um registro
class StorageEstimateHelper
{
    /**
     * Estima tamanho de um cliente em bytes
     */
    public static function estimateClientSize(): int
    {
        // Campos médios: nome (100 chars), email (50), cpf_cnpj (18), telefone (15), endereço completo
        // Total estimado: ~4 KB por cliente
        return 4096;
    }

    /**
     * Estima tamanho de um produto em bytes
     */
    public static function estimateProductSize(): int
    {
        // Nome (200), descrição (500), SKU (50), EAN (14), preço, impostos, etc.
        // Total estimado: ~5 KB por produto
        return 5120;
    }

    /**
     * Estima tamanho de um pedido com itens
     */
    public static function estimateOrderSize(int $itemsCount = 5): int
    {
        // Pedido base: ~2 KB
        // Cada item: ~1 KB
        return 2048 + ($itemsCount * 1024);
    }
}
```

### 2. Soft Deletes - Dados Deletados Contam?

**Decisão**: Dados deletados (soft deletes) NÃO devem contar no uso, pois estão marcados como deletados.

**Implementação**: Modificar `StorageCalculator::calculateDataSize()` para excluir registros com `deleted_at`:

```php
// Não incluir tabelas com soft deletes nos cálculos OU
// Filtrar por WHERE deleted_at IS NULL nas consultas de tamanho
// Mas: calcular tamanho TOTAL da tabela é mais simples e preciso
// Solução: Aceitar que soft deletes ocupam espaço, mas será limpo quando fizermos purge de dados antigos
```

### 3. Performance - Otimização de Cálculos

**Problema**: Calcular tamanho de tabelas grandes pode ser lento.

**Soluções**:
1. Cachear resultados por 1-2 horas
2. Executar cálculo apenas uma vez por dia (via schedule)
3. Atualizar incrementalmente após cada inserção (mais preciso, mas mais operações)
4. Usar `SHOW TABLE STATUS` ao invés de `information_schema` (mais rápido)

```php
// Versão otimizada do calculateDataSize
public function calculateDataSize(Tenant $tenant): int
{
    // Usar SHOW TABLE STATUS (mais rápido)
    $dbName = DB::connection()->getDatabaseName();
    $tables = ['clients', 'products', 'orders', 'quotes', 'service_orders'];
    
    $totalBytes = 0;
    foreach ($tables as $table) {
        $result = DB::select("SHOW TABLE STATUS LIKE '{$table}'");
        if (!empty($result)) {
            $totalBytes += (int) ($result[0]->Data_length ?? 0) + (int) ($result[0]->Index_length ?? 0);
        }
    }
    
    return $totalBytes;
}
```

### 4. Tratamento de Erros

```php
// Wrapper seguro para verificação de storage
trait StorageLimitCheck
{
    protected function checkStorageLimit(string $type, int $sizeBytes)
    {
        try {
            $tenant = auth()->user()->tenant;
            $usage = $tenant->storageUsage;
            
            if (!$usage) {
                return true; // Sem limite se não houver registro
            }
            
            if ($type === 'data') {
                return $usage->canAddData($sizeBytes);
            } else {
                return $usage->canAddFiles($sizeBytes);
            }
        } catch (\Exception $e) {
            \Log::error('Storage limit check failed', [
                'error' => $e->getMessage(),
                'tenant_id' => auth()->user()->tenant_id ?? null
            ]);
            // Em caso de erro, permitir operação (fail-open para não bloquear usuário)
            return true;
        }
    }
}
```

---

## 🔔 Sistema de Notificações

### Notificar Quando Próximo do Limite

```php
// app/Console/Commands/CheckStorageLimits.php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckStorageLimits extends Command
{
    protected $signature = 'storage:check-limits';
    protected $description = 'Verifica limites de storage e envia notificações';

    public function handle()
    {
        Tenant::where('active', true)->each(function ($tenant) {
            $usage = $tenant->storageUsage;
            if (!$usage) return;

            // Notificar se uso > 75% e ainda não notificado hoje
            if ($usage->data_usage_percent >= 75 || $usage->files_usage_percent >= 75) {
                // Enviar email de alerta (implementar template)
                // Mail::to($tenant->email)->send(new StorageLimitAlert($tenant, $usage));
            }
        });
    }
}
```

---

## 📋 FASES DE IMPLANTAÇÃO

### **FASE 1: Estrutura Base** (Prioridade Alta)
**Estimativa**: 2-3 dias

1. ✅ Criar migrations:
   - `tenant_storage_usage`
   - `storage_addons`

2. ✅ Criar Models:
   - `TenantStorageUsage` (com todos os accessors)
   - `StorageAddon`

3. ✅ Criar Service:
   - `StorageCalculator` (com cálculo de dados e arquivos)

4. ✅ Criar Command:
   - `UpdateStorageUsage` (atualização agendada)

5. ✅ Adicionar relacionamento no Tenant:
   - `storageUsage()`
   - `storageAddons()`

6. ✅ Agendar comando no `Kernel.php`

**Entregável**: Estrutura de banco e modelos funcionais

---

### **FASE 2: Monitoramento e Visualização** (Prioridade Alta)
**Estimativa**: 1-2 dias

7. ✅ Criar Controller:
   - `StorageController@index`
   - `StorageController@upgrade`
   - `StorageController@purchaseAddon`

8. ✅ Criar Views:
   - `storage/index.blade.php` (página completa)
   - `storage/upgrade.blade.php` (comprar espaço)

9. ✅ Criar Widget:
   - `components/storage-widget.blade.php`

10. ✅ Adicionar widget no dashboard

11. ✅ Adicionar rotas

**Entregável**: Usuário pode visualizar uso e comprar espaço adicional

---

### **FASE 3: Integração com Checkout** (Prioridade Alta)
**Estimativa**: 1 dia

12. ✅ Modificar `CheckoutController`:
    - Aceitar parâmetro `addon_id`
    - Processar pagamento de addon

13. ✅ Modificar Webhook MercadoPago:
    - Detectar pagamento de addon
    - Ativar addon após pagamento confirmado
    - Atualizar `additional_data_mb` ou `additional_files_mb`

14. ✅ Testar fluxo completo de compra

**Entregável**: Compra de espaço adicional funcional e integrada

---

### **FASE 4: Bloqueios em Controllers** (Prioridade Média)
**Estimativa**: 2-3 dias

15. ✅ Integrar verificação em `ClientController@store`:
    - Verificar antes de criar
    - Mostrar erro amigável com links para upgrade/comprar espaço

16. ✅ Integrar verificação em `ProductController@store`:
    - Verificar dados antes de criar
    - Verificar arquivos antes de upload de imagem

17. ✅ Integrar verificação em `ServiceOrderController@addAttachment`:
    - Verificar arquivos antes de upload

18. ✅ Integrar verificação em outros controllers:
    - `OrderController@store`
    - `QuoteController@store`
    - Uploads de imagens (products, profiles)
    - Uploads de documentos (invoices, etc)

19. ✅ Criar trait `StorageLimitCheck` para reutilização

**Entregável**: Sistema bloqueia operações quando limite atingido

---

### **FASE 5: Atualização em Tempo Real (Opcional)** (Prioridade Baixa)
**Estimativa**: 1 dia

20. ✅ Adicionar atualização incremental após criar cliente/produto:
    - Atualizar `data_size_bytes` após inserção
    - Usar job em background para não bloquear resposta

21. ✅ Adicionar atualização após upload de arquivo:
    - Atualizar `files_size_bytes` imediatamente após upload

22. ✅ Criar eventos Eloquent:
    - `Client::created` → atualizar uso
    - `Product::created` → atualizar uso
    - Etc.

**Entregável**: Uso atualizado em tempo real (mais preciso)

---

### **FASE 6: Otimizações e Melhorias** (Prioridade Baixa)
**Estimativa**: 1-2 dias

23. ✅ Otimizar cálculo de tamanho de tabelas:
    - Usar `SHOW TABLE STATUS` ao invés de `information_schema`
    - Cachear resultados

24. ✅ Implementar notificações:
    - Email quando > 75% de uso
    - Notificação no sistema quando > 90%

25. ✅ Adicionar histórico de compras:
    - Listar addons comprados em `storage/index`
    - Mostrar data de expiração se houver

26. ✅ Adicionar comando de purge:
    - Limpar registros antigos (soft deletes > 1 ano)
    - Liberar espaço automaticamente

**Entregável**: Sistema otimizado e com notificações

---

### **FASE 7: Testes e Ajustes** (Prioridade Média)
**Estimativa**: 1-2 dias

27. ✅ Testar limites em cada plano:
    - Verificar cálculo correto
    - Verificar bloqueios funcionam
    - Testar compra de espaço adicional

28. ✅ Testar edge cases:
    - Plano ilimitado (Enterprise/Platinum)
    - Tenant sem plano
    - Addon expirado

29. ✅ Ajustar estimativas de tamanho se necessário

30. ✅ Documentar para usuários finais

**Entregável**: Sistema testado e estável

---

## 🔄 Checklist de Implementação por Fase

### Fase 1 ✅
- [ ] Migration `tenant_storage_usage`
- [ ] Migration `storage_addons`
- [ ] Model `TenantStorageUsage`
- [ ] Model `StorageAddon`
- [ ] Service `StorageCalculator`
- [ ] Command `UpdateStorageUsage`
- [ ] Relacionamento `Tenant::storageUsage()`
- [ ] Agendamento no `Kernel.php`

### Fase 2 ✅
- [ ] Controller `StorageController`
- [ ] View `storage/index.blade.php`
- [ ] View `storage/upgrade.blade.php`
- [ ] Widget `components/storage-widget.blade.php`
- [ ] Adicionar widget no `dashboard.blade.php`
- [ ] Rotas de storage

### Fase 3 ✅
- [ ] Modificar `CheckoutController` para addons
- [ ] Modificar webhook para processar addons
- [ ] Testar fluxo de compra

### Fase 4 ✅
- [ ] Integrar em `ClientController`
- [ ] Integrar em `ProductController`
- [ ] Integrar em `ServiceOrderController`
- [ ] Integrar em outros controllers de upload
- [ ] Criar trait `StorageLimitCheck`

### Fase 5 ✅ (Opcional)
- [ ] Jobs para atualização incremental
- [ ] Eventos Eloquent
- [ ] Atualização após uploads

### Fase 6 ✅ (Opcional)
- [ ] Otimizações de performance
- [ ] Sistema de notificações
- [ ] Histórico de compras

### Fase 7 ✅
- [ ] Testes completos
- [ ] Ajustes finais
- [ ] Documentação

---

## 📊 Estimativas de Tamanho por Registro

| Tipo | Tamanho Estimado | Observações |
|------|------------------|-------------|
| Cliente | ~4 KB | Depende dos campos preenchidos |
| Produto | ~5 KB | Inclui descrição, impostos, etc |
| Pedido | ~2 KB + 1 KB/item | Base + itens |
| Orçamento | ~2 KB + 1 KB/item | Similar a pedido |
| OS | ~3 KB + 1 KB/item | Maior por ter mais campos |
| Imagem produto | Tamanho real | Variável (50 KB - 2 MB típico) |
| XML NF-e | 10-50 KB | Tamanho real do XML |
| DANFE PDF | 100-500 KB | Tamanho do PDF gerado |

---

## ⚡ Otimização de Performance - Como Evitar Lentidão

### ⚠️ Problema Potencial
Verificações de storage em **cada** operação (criar cliente, produto, upload) podem causar lentidão se não otimizadas, especialmente:
- Múltiplas queries ao banco
- Cálculos pesados de tamanho
- Sem cache

### ✅ Solução: Verificação Ultra-Rápida (Recomendado)

**Estratégia**: Verificar apenas os **valores já calculados** no banco (não calcular na hora).

```php
// ✅ OTIMIZADO: Verificação instantânea (< 5ms)
trait StorageLimitCheck
{
    protected function checkStorageLimit(string $type, int $sizeBytes)
    {
        // 1. Buscar usage com cache (1 query simples, indexada)
        $usage = Cache::remember("tenant_storage_{$this->tenant_id}", 300, function() {
            return TenantStorageUsage::where('tenant_id', $this->tenant_id)->first();
        });
        
        // Se não tem registro = sem limite
        if (!$usage) return true;
        
        // 2. Verificação matemática simples (sem queries)
        if ($type === 'data') {
            $limitBytes = $usage->total_data_limit_mb === -1 
                ? PHP_INT_MAX 
                : $usage->total_data_limit_mb * 1024 * 1024;
            
            return ($usage->data_size_bytes + $sizeBytes) <= $limitBytes;
        } else {
            $limitBytes = $usage->total_files_limit_mb === -1 
                ? PHP_INT_MAX 
                : $usage->total_files_limit_mb * 1024 * 1024;
            
            return ($usage->files_size_bytes + $sizeBytes) <= $limitBytes;
        }
    }
}
```

**Por que é rápido?**
- ✅ 1 query simples com índice (`tenant_id` é chave única)
- ✅ Cache de 5 minutos (300 segundos)
- ✅ Cálculo matemático simples (adicionar, comparar)
- ✅ Sem consultas pesadas (`SHOW TABLE STATUS`, etc)
- ✅ Total: **< 5ms** por verificação

### 📊 Comparação de Performance

| Método | Tempo | Quando Usar |
|--------|-------|-------------|
| **Verificação com valores cacheados** | < 5ms | ✅ **Operações frequentes** (criar cliente, produto, upload) |
| Calcular tamanho real na hora | 100-500ms | ❌ Muito lento, nunca usar |
| Job assíncrono após criar | 0ms (imediato) | ✅ Operações raras ou batch |

### 🔄 Atualização do Uso de Storage

**Estratégia Híbrida** (melhor balanço velocidade/precisão):

```php
// 1. Verificação ANTES de salvar: usar valores cacheados (rápido)
if (!$this->checkStorageLimit('data', $estimatedSize)) {
    return back()->withErrors(['storage' => 'Limite atingido']);
}

// 2. Salvar o registro
$client = Client::create($validated);

// 3. Atualizar uso INCREMENTAL (rápido, mas opcional)
// Opção A: Job assíncrono (não bloqueia resposta)
dispatch(new UpdateStorageUsageJob($tenantId));

// Opção B: Atualização matemática simples (sem recalcular tudo)
$usage = TenantStorageUsage::where('tenant_id', $tenantId)->first();
if ($usage) {
    $usage->increment('data_size_bytes', $estimatedSize);
}

// 4. Cálculo PRECISO: Apenas uma vez por dia (via schedule)
// Command: storage:update-usage (corrige possíveis discrepâncias)
```

### 🎯 Quando Verificar e Quando NÃO Verificar

#### ✅ SEMPRE Verificar (rápido):
- Upload de arquivos (imagem produto, anexo OS) → Verificar `files`
- Criar registro novo (cliente, produto) → Verificar `data` (estimativa)

#### ⚠️ NÃO Verificar Durante (lento):
- Listagem/Consulta → Não precisa
- Edição (update) → Não precisa (não aumenta uso)
- Delete → Atualizar via job (libera espaço depois)

#### 📝 Verificação Condicional (só se próximo do limite):

```php
// Estratégia: Só verificar se uso > 70% (evita verificações desnecessárias)
protected function shouldCheckStorage(string $type): bool
{
    $usage = Cache::get("tenant_storage_{$this->tenant_id}");
    
    if (!$usage) return false; // Sem limite
    
    // Se está bem abaixo do limite, não verifica
    $percent = $type === 'data' 
        ? $usage->data_usage_percent 
        : $usage->files_usage_percent;
    
    return $percent >= 70; // Só verifica se > 70%
}
```

### 🚀 Otimizações Avançadas

#### 1. Cache Inteligente
```php
// Cache com invalidação automática após uploads
Cache::tags(['storage', "tenant_{$tenantId}"])
    ->remember("tenant_storage_{$tenantId}", 300, function() use ($tenantId) {
        return TenantStorageUsage::where('tenant_id', $tenantId)->first();
    });

// Invalidar após upload
Cache::tags(['storage', "tenant_{$tenantId}"])->flush();
```

#### 2. Bulk Operations (Criar múltiplos de uma vez)
```php
// Em vez de verificar um por um, verificar uma vez para todos
$totalSize = count($clients) * StorageEstimateHelper::estimateClientSize();

if (!$this->checkStorageLimit('data', $totalSize)) {
    return back()->withErrors(['storage' => 'Limite atingido para essa quantidade']);
}

// Criar todos
Client::insert($clientsArray);
```

#### 3. Índices de Banco de Dados
```sql
-- Garantir índice único em tenant_id (já existe no migration)
ALTER TABLE tenant_storage_usage 
ADD UNIQUE INDEX idx_tenant_id (tenant_id);

-- Isso faz a busca ser instantânea (< 1ms)
```

### 📈 Métricas de Performance Esperadas

| Operação | Sem Otimização | Com Otimização | Melhoria |
|----------|----------------|----------------|----------|
| Verificação storage | 100-500ms | < 5ms | **20-100x mais rápido** |
| Criar cliente | 50ms + verificação | 52ms | Negligível |
| Upload arquivo | 200ms + verificação | 205ms | Negligível |

### ✅ Resumo: Boas Práticas

1. ✅ **Sempre use cache** para `TenantStorageUsage` (5-10 minutos)
2. ✅ **Verifique apenas valores calculados**, nunca calcule na hora
3. ✅ **Atualização incremental** após criar (opcional, job assíncrono)
4. ✅ **Cálculo preciso apenas 1x/dia** via schedule
5. ✅ **Skip verificação** se uso < 70% (otimização adicional)
6. ✅ **Índice único** em `tenant_id` na tabela `tenant_storage_usage`

### ❌ O QUE NÃO FAZER (causa lentidão)

```php
// ❌ ERRADO: Calcular tamanho real toda vez (muito lento)
$calculator = new StorageCalculator();
$realSize = $calculator->calculateDataSize($tenant); // 100-500ms!
if ($realSize > $limit) { ... }

// ❌ ERRADO: Query sem cache
$usage = TenantStorageUsage::where('tenant_id', $tenantId)->first(); // Sem cache

// ❌ ERRADO: Verificar mesmo em consultas/listagens
public function index() {
    $this->checkStorageLimit(...); // Desnecessário!
}
```

### 🎯 Implementação Recomendada Final

```php
// app/Http/Controllers/ClientController.php
use Illuminate\Support\Facades\Cache;

public function store(Request $request)
{
    // Validações...
    
    $tenant = auth()->user()->tenant;
    
    // Verificação rápida (com cache)
    $usage = Cache::remember("storage_{$tenant->id}", 300, function() use ($tenant) {
        return $tenant->storageUsage;
    });
    
    if ($usage && $usage->total_data_limit_mb !== -1) {
        $estimatedSize = 4096; // 4 KB
        $newTotal = $usage->data_size_bytes + $estimatedSize;
        $limitBytes = $usage->total_data_limit_mb * 1024 * 1024;
        
        if ($newTotal > $limitBytes) {
            return back()->withErrors(['storage' => 'Limite atingido...']);
        }
    }
    
    // Criar cliente
    $client = Client::create($validated);
    
    // Atualizar incrementalmente (rápido)
    if ($usage) {
        $usage->increment('data_size_bytes', $estimatedSize);
        Cache::forget("storage_{$tenant->id}"); // Invalidar cache
    }
    
    return redirect()->route('clients.index')->with('success', 'Cliente criado!');
}
```

**Resultado**: Sistema rápido, sem lentidão, mesmo com milhares de verificações por dia! ⚡

---

## 🚨 Tratamento de Edge Cases

1. **Tenant sem plano**: Sem limites, permite tudo
2. **Plano ilimitado**: Verificar `-1` em features, sempre permitir
3. **Addon expirado**: Remover do cálculo, notificar tenant
4. **Erro ao calcular**: Permitir operação (fail-open), logar erro
5. **Upload em progresso quando limite atingido**: Rollback do arquivo

---

## 📚 Referências

- [Bling - Planos e Preços](https://www.bling.com.br/planos-e-precos)
- Modelo usado: Separação entre dados (MB) e arquivos (GB)
- Permite compra de espaço adicional em incrementos fixos
- Monitoramento visual com barras de progresso
- Alertas quando próximo do limite (>75% e >90%)

---

## 🎯 Prioridades de Implementação

**Crítico (Fazer Primeiro)**:
1. Fase 1: Estrutura base
2. Fase 2: Monitoramento
3. Fase 3: Integração com checkout
4. Fase 4: Bloqueios básicos (ClientController, ProductController)

**Importante (Depois)**:
5. Fase 4: Bloqueios em outros controllers
6. Fase 7: Testes e ajustes

**Opcional (Melhorias Futuras)**:
7. Fase 5: Atualização em tempo real
8. Fase 6: Otimizações e notificações
