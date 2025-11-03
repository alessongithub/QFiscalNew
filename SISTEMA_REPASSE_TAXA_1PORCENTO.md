# Sistema de Repasse com Taxa de 1%

## 📋 Resumo Executivo

Sistema para gerenciar repasse de saldo dos boletos pagos pelos tenants, descontando automaticamente a taxa do Mercado Pago e uma taxa de 1% da plataforma antes de disponibilizar o valor para transferência.

---

## 🎯 Fluxo Completo

```
1. Tenant emite boleto
   ↓
2. Cliente paga boleto
   ↓
3. Webhook MP notifica pagamento (status: approved)
   ↓
4. Sistema marca Receivable como 'paid'
   ↓
5. Sistema aguarda liquidação do boleto (1-3 dias úteis)
   ↓
6. Sistema verifica se saldo está disponível no MP
   ↓
7. Sistema calcula saldo líquido:
   - Valor do boleto: R$ 100,00
   - Taxa MP (~3,99%): R$ 3,99
   - Taxa plataforma (1%): R$ 1,00
   - Saldo disponível: R$ 95,01
   ↓
8. Saldo aparece na dashboard do tenant como "disponível para transferência"
   ↓
9. Tenant solicita transferência
   ↓
10. Sistema transfere saldo para conta do tenant (PIX/TED)
   ↓
11. Sistema marca transferência como concluída
```

---

## 📊 Estrutura de Banco de Dados

### Migration: `tenant_balances`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('receivable_id')->nullable()->constrained()->onDelete('set null');
            
            // Valores
            $table->decimal('gross_amount', 10, 2); // Valor bruto do boleto
            $table->decimal('mp_fee_amount', 10, 2)->default(0); // Taxa do Mercado Pago
            $table->decimal('platform_fee_amount', 10, 2)->default(0); // Taxa de 1% da plataforma
            $table->decimal('net_amount', 10, 2); // Valor líquido (gross - mp_fee - platform_fee)
            
            // Status
            $table->enum('status', [
                'pending',      // Aguardando liquidação
                'available',    // Disponível para transferência
                'requested',    // Tenant solicitou transferência
                'transferring', // Transferindo
                'transferred',  // Transferido com sucesso
                'failed'        // Falha na transferência
            ])->default('pending');
            
            // Datas
            $table->timestamp('payment_received_at')->nullable(); // Quando o boleto foi pago
            $table->timestamp('available_at')->nullable(); // Quando ficou disponível
            $table->timestamp('requested_at')->nullable(); // Quando tenant solicitou
            $table->timestamp('transferred_at')->nullable(); // Quando foi transferido
            
            // Informações de transferência
            $table->string('transfer_method')->nullable(); // pix, ted, etc
            $table->string('transfer_account')->nullable(); // Conta bancária do tenant
            $table->string('transfer_reference')->nullable(); // Referência da transferência
            $table->text('transfer_notes')->nullable();
            
            // IDs do Mercado Pago
            $table->string('mp_payment_id')->nullable(); // ID do pagamento no MP
            $table->string('mp_transfer_id')->nullable(); // ID da transferência no MP (se usar API)
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_balances');
    }
};
```

### Migration: `tenant_transfer_settings`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_transfer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->unique();
            
            // Conta bancária para receber transferências
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('agency')->nullable();
            $table->string('account')->nullable();
            $table->string('account_type')->nullable(); // checking, savings
            $table->string('account_holder_name')->nullable();
            $table->string('account_holder_document')->nullable(); // CPF/CNPJ
            
            // Chave PIX
            $table->string('pix_key')->nullable();
            $table->enum('pix_key_type', ['cpf', 'cnpj', 'email', 'phone', 'random'])->nullable();
            
            // Preferência
            $table->enum('preferred_method', ['pix', 'ted'])->default('pix');
            $table->boolean('auto_transfer_enabled')->default(false); // Transferência automática
            $table->decimal('auto_transfer_min_amount', 10, 2)->nullable(); // Valor mínimo para auto-transfer
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_transfer_settings');
    }
};
```

---

## 🏗️ Models

### Model: `TenantBalance`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantBalance extends Model
{
    protected $fillable = [
        'tenant_id',
        'receivable_id',
        'gross_amount',
        'mp_fee_amount',
        'platform_fee_amount',
        'net_amount',
        'status',
        'payment_received_at',
        'available_at',
        'requested_at',
        'transferred_at',
        'transfer_method',
        'transfer_account',
        'transfer_reference',
        'transfer_notes',
        'mp_payment_id',
        'mp_transfer_id',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'mp_fee_amount' => 'decimal:2',
        'platform_fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_received_at' => 'datetime',
        'available_at' => 'datetime',
        'requested_at' => 'datetime',
        'transferred_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Métodos
    public function markAsAvailable()
    {
        $this->update([
            'status' => 'available',
            'available_at' => now(),
        ]);
    }

    public function requestTransfer()
    {
        $this->update([
            'status' => 'requested',
            'requested_at' => now(),
        ]);
    }

    public function markAsTransferred($transferReference, $transferMethod = 'pix')
    {
        $this->update([
            'status' => 'transferred',
            'transferred_at' => now(),
            'transfer_method' => $transferMethod,
            'transfer_reference' => $transferReference,
        ]);
    }
}
```

### Model: `TenantTransferSetting`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantTransferSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'bank_name',
        'bank_code',
        'agency',
        'account',
        'account_type',
        'account_holder_name',
        'account_holder_document',
        'pix_key',
        'pix_key_type',
        'preferred_method',
        'auto_transfer_enabled',
        'auto_transfer_min_amount',
    ];

    protected $casts = [
        'auto_transfer_enabled' => 'boolean',
        'auto_transfer_min_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

---

## 🔧 Implementação

### 1. Atualizar Webhook para Criar TenantBalance

```php
// app/Http/Controllers/Webhooks/MercadoPagoWebhookController.php

use App\Models\TenantBalance;
use App\Models\GatewayConfig;

public function handle(Request $request)
{
    // ... código existente ...
    
    if ($status === 'approved') {
        if ($receivable) {
            $receivable->status = 'paid';
            $receivable->payment_method = 'boleto';
            $receivable->received_at = now();
            $receivable->save();
            
            // NOVO: Criar registro de saldo para repasse
            $this->createTenantBalance($receivable, $paymentJson);
        }
        // ... resto do código
    }
}

private function createTenantBalance(Receivable $receivable, array $paymentData)
{
    $grossAmount = (float) ($paymentData['transaction_amount'] ?? 0);
    
    // Calcular taxa do Mercado Pago
    // Nota: MP retorna fees no payment, mas pode variar
    $mpFeePercent = $this->calculateMpFee($paymentData);
    $mpFeeAmount = $grossAmount * ($mpFeePercent / 100);
    
    // Taxa da plataforma: 1%
    $platformFeePercent = 1.00;
    $platformFeeAmount = $grossAmount * ($platformFeePercent / 100);
    
    // Valor líquido
    $netAmount = $grossAmount - $mpFeeAmount - $platformFeeAmount;
    
    // Criar registro (status: pending - aguardando liquidação)
    TenantBalance::create([
        'tenant_id' => $receivable->tenant_id,
        'receivable_id' => $receivable->id,
        'gross_amount' => $grossAmount,
        'mp_fee_amount' => $mpFeeAmount,
        'platform_fee_amount' => $platformFeeAmount,
        'net_amount' => $netAmount,
        'status' => 'pending',
        'payment_received_at' => now(),
        'mp_payment_id' => (string) ($paymentData['id'] ?? null),
    ]);
    
    // Agendar verificação de liquidação (1-3 dias úteis)
    // Usar Job ou Scheduler
}

private function calculateMpFee(array $paymentData): float
{
    // Taxa padrão do MP para boleto: ~3,99%
    // Pode buscar do payment data se disponível
    $fees = $paymentData['fee_details'] ?? [];
    $totalFee = 0;
    
    foreach ($fees as $fee) {
        $totalFee += (float) ($fee['amount'] ?? 0);
    }
    
    $transactionAmount = (float) ($paymentData['transaction_amount'] ?? 0);
    
    if ($transactionAmount > 0) {
        return ($totalFee / $transactionAmount) * 100;
    }
    
    // Fallback: taxa padrão
    return 3.99;
}
```

### 2. Job para Verificar Liquidação

```php
// app/Jobs/CheckBalanceAvailability.php

<?php

namespace App\Jobs;

use App\Models\TenantBalance;
use App\Models\GatewayConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckBalanceAvailability implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Buscar saldos pendentes com mais de 1 dia desde o pagamento
        $pendingBalances = TenantBalance::where('status', 'pending')
            ->where('payment_received_at', '<=', now()->subDay())
            ->get();

        $config = GatewayConfig::current();
        $accessToken = $config->active_access_token;

        foreach ($pendingBalances as $balance) {
            // Verificar no MP se o pagamento está disponível
            $payment = Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$balance->mp_payment_id}")
                ->json();

            // Verificar status de liquidação
            // MP: status_detail pode ser 'accredited' quando está disponível
            $statusDetail = $payment['status_detail'] ?? null;
            
            if ($statusDetail === 'accredited' || $this->isAvailable($payment)) {
                $balance->markAsAvailable();
                
                // Notificar tenant (opcional)
                // Notification::send(...);
                
                // Se auto-transfer estiver habilitado, processar
                $this->processAutoTransfer($balance);
            }
        }
    }

    private function isAvailable(array $payment): bool
    {
        // Lógica para determinar se está disponível
        // Boletos geralmente levam 1-3 dias úteis
        $dateAvailable = $payment['date_available'] ?? null;
        if ($dateAvailable) {
            return now()->isAfter($dateAvailable);
        }
        
        return false;
    }

    private function processAutoTransfer(TenantBalance $balance)
    {
        $tenant = $balance->tenant;
        $settings = TenantTransferSetting::where('tenant_id', $tenant->id)->first();
        
        if ($settings && $settings->auto_transfer_enabled) {
            $minAmount = $settings->auto_transfer_min_amount ?? 0;
            
            if ($balance->net_amount >= $minAmount) {
                // Processar transferência automática
                dispatch(new ProcessTransfer($balance->id));
            }
        }
    }
}
```

### 3. Job para Processar Transferência

```php
// app/Jobs/ProcessTransfer.php

<?php

namespace App\Jobs;

use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $balanceId
    ) {}

    public function handle()
    {
        $balance = TenantBalance::findOrFail($this->balanceId);
        
        if ($balance->status !== 'requested' && $balance->status !== 'available') {
            return;
        }

        $tenant = $balance->tenant;
        $settings = TenantTransferSetting::where('tenant_id', $tenant->id)->first();
        
        if (!$settings || !$settings->pix_key) {
            // Notificar tenant que precisa configurar conta
            return;
        }

        $balance->update(['status' => 'transferring']);

        // Aqui você faria a transferência real
        // Pode ser via API do MP, PIX direto, ou manual
        
        // Exemplo: Transferência manual (você faz manualmente)
        // Você pode gerar um QR Code PIX ou fazer TED
        
        // Após transferir, marcar como transferido
        // $balance->markAsTransferred($reference, 'pix');
    }
}
```

### 4. Controller para Dashboard do Tenant

```php
// app/Http/Controllers/Tenant/BalanceController.php

<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;
use Illuminate\Http\Request;
use App\Jobs\ProcessTransfer;

class BalanceController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        
        $balances = TenantBalance::forTenant($tenant->id)
            ->with('receivable')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Totais
        $totalAvailable = TenantBalance::forTenant($tenant->id)
            ->available()
            ->sum('net_amount');
        
        $totalPending = TenantBalance::forTenant($tenant->id)
            ->pending()
            ->sum('net_amount');
        
        $totalTransferred = TenantBalance::forTenant($tenant->id)
            ->where('status', 'transferred')
            ->sum('net_amount');
        
        return view('tenant.balance.index', compact(
            'balances',
            'totalAvailable',
            'totalPending',
            'totalTransferred'
        ));
    }

    public function requestTransfer(Request $request)
    {
        $request->validate([
            'balance_id' => 'required|exists:tenant_balances,id',
        ]);
        
        $tenant = auth()->user()->tenant;
        $balance = TenantBalance::findOrFail($request->balance_id);
        
        // Verificar se é do tenant
        if ($balance->tenant_id !== $tenant->id) {
            abort(403);
        }
        
        // Verificar se está disponível
        if ($balance->status !== 'available') {
            return back()->withErrors(['transfer' => 'Este saldo não está disponível para transferência.']);
        }
        
        // Verificar se tem conta configurada
        $settings = TenantTransferSetting::where('tenant_id', $tenant->id)->first();
        if (!$settings || (!$settings->pix_key && !$settings->account)) {
            return back()->withErrors(['transfer' => 'Configure uma conta bancária ou chave PIX antes de solicitar transferência.']);
        }
        
        // Marcar como solicitado
        $balance->requestTransfer();
        
        // Processar transferência
        dispatch(new ProcessTransfer($balance->id));
        
        return back()->with('success', 'Transferência solicitada. Processando...');
    }
}
```

### 5. Scheduler para Verificar Liquidação

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Verificar liquidação de saldos a cada 6 horas
    $schedule->job(new \App\Jobs\CheckBalanceAvailability)
        ->everySixHours();
}
```

---

## 📱 Interface do Tenant

### View: `resources/views/tenant/balance/index.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Saldo Disponível</h1>
    
    <!-- Cards de Resumo -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Disponível para Transferência</div>
            <div class="text-2xl font-bold text-green-600">R$ {{ number_format($totalAvailable, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Aguardando Liquidação</div>
            <div class="text-2xl font-bold text-yellow-600">R$ {{ number_format($totalPending, 2, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-600">Total Transferido</div>
            <div class="text-2xl font-bold text-blue-600">R$ {{ number_format($totalTransferred, 2, ',', '.') }}</div>
        </div>
    </div>
    
    <!-- Tabela de Saldos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Valor Bruto</th>
                    <th>Taxa MP</th>
                    <th>Taxa Plataforma</th>
                    <th>Valor Líquido</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balances as $balance)
                <tr>
                    <td>{{ $balance->payment_received_at->format('d/m/Y') }}</td>
                    <td>{{ $balance->receivable->description ?? 'N/A' }}</td>
                    <td>R$ {{ number_format($balance->gross_amount, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($balance->mp_fee_amount, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($balance->platform_fee_amount, 2, ',', '.') }}</td>
                    <td class="font-bold">R$ {{ number_format($balance->net_amount, 2, ',', '.') }}</td>
                    <td>
                        @if($balance->status === 'available')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded">Disponível</span>
                        @elseif($balance->status === 'pending')
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Aguardando</span>
                        @elseif($balance->status === 'requested')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">Solicitado</span>
                        @elseif($balance->status === 'transferred')
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded">Transferido</span>
                        @endif
                    </td>
                    <td>
                        @if($balance->status === 'available')
                            <form action="{{ route('tenant.balance.request-transfer') }}" method="POST">
                                @csrf
                                <input type="hidden" name="balance_id" value="{{ $balance->id }}">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Solicitar Transferência
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
```

---

## 📋 Checklist de Implementação

- [ ] Criar migration `tenant_balances`
- [ ] Criar migration `tenant_transfer_settings`
- [ ] Criar Model `TenantBalance`
- [ ] Criar Model `TenantTransferSetting`
- [ ] Atualizar `MercadoPagoWebhookController` para criar `TenantBalance`
- [ ] Criar Job `CheckBalanceAvailability`
- [ ] Criar Job `ProcessTransfer`
- [ ] Criar Controller `Tenant\BalanceController`
- [ ] Criar view `tenant/balance/index.blade.php`
- [ ] Adicionar rota para dashboard de saldo
- [ ] Adicionar rota para solicitar transferência
- [ ] Configurar scheduler para verificar liquidação
- [ ] Adicionar link na dashboard do tenant
- [ ] Testar fluxo completo

---

## 💡 Observações Importantes

1. **Taxa do Mercado Pago**: Pode variar. Verificar na resposta do pagamento ou usar taxa padrão
2. **Liquidação**: Boletos levam 1-3 dias úteis para liquidar no MP
3. **Transferência**: Pode ser manual (você faz) ou via API do MP (se tiver conta de marketplace)
4. **Valor mínimo**: Pode configurar valor mínimo para transferência
5. **Notificações**: Considerar enviar email quando saldo ficar disponível

