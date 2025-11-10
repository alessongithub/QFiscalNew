<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderAudit;
use App\Models\QuoteAudit;
use App\Models\TaxRateAudit;
use App\Models\SettingsAudit;
use App\Models\NcmRuleAudit;
use App\Models\UserAudit;
use App\Models\StockAudit;
use App\Models\ProductAudit;
use App\Models\CategoryAudit;
use App\Models\ClientAudit;
use App\Models\SupplierAudit;
use App\Models\ProfileAudit;
use App\Models\CarrierAudit;
use App\Models\CashWithdrawalAudit;
use App\Models\ReceiptAudit;
use App\Models\ReturnAudit;
use App\Models\FinanceAudit;
use App\Models\ServiceOrderAudit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->hasRoleSlug('admin'), 403);

        $tenantId = auth()->user()->tenant_id;
        
        // Filtros
        // Intervalo de datas (padrão: atividades do DIA atual)
        $dateFromInput = $request->input('date_from');
        $dateToInput = $request->input('date_to');
        $dateFrom = $dateFromInput ? Carbon::parse($dateFromInput)->startOfDay() : now()->startOfDay();
        $dateTo = $dateToInput ? Carbon::parse($dateToInput)->endOfDay() : now()->endOfDay();
        $type = $request->input('type', 'all'); // all, orders, quotes, tax_rates, settings
        $userId = $request->input('user_id');

        // Agregar atividades de todas as tabelas
        $activities = collect();

        // 1. Order Audits
        if ($type === 'all' || $type === 'orders') {
            $orderAudits = OrderAudit::with(['user', 'order'])
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->whereHas('order', function($sub) use ($tenantId) {
                        $sub->where('tenant_id', $tenantId);
                    });
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->get()
                ->map(function($audit) {
                    // Traduzir mudanças de status para PT-BR, quando existirem
                    $translatedChanges = $audit->changes;
                    if (is_array($translatedChanges) && isset($translatedChanges['status'])) {
                        $statusMap = [
                            'open' => 'Em análise',
                            'in_progress' => 'Orçada',
                            'in_service' => 'Em andamento',
                            'service_finished' => 'Serviço finalizado',
                            'warranty' => 'Garantia',
                            'no_repair' => 'Sem reparo',
                            'finished' => 'Finalizada',
                            'canceled' => 'Cancelada',
                            'partial_returned' => 'Devolução parcial',
                            'fulfilled' => 'Finalizada',
                        ];
                        $old = $translatedChanges['status']['old'] ?? null;
                        $new = $translatedChanges['status']['new'] ?? null;
                        if ($old !== null) { $translatedChanges['status']['old'] = $statusMap[$old] ?? $old; }
                        if ($new !== null) { $translatedChanges['status']['new'] = $statusMap[$new] ?? $new; }
                    }
                    return [
                        'id' => 'order_' . $audit->id,
                        'type' => 'order',
                        'type_label' => 'Pedido',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $this->getActionLabel($audit->action, 'order'),
                        'entity' => $audit->order,
                        'entity_label' => $audit->order ? 'Pedido #' . $audit->order->number : 'Pedido',
                        'notes' => $audit->notes,
                        'changes' => $translatedChanges,
                        'created_at' => $audit->created_at,
                        'url' => $audit->order ? route('orders.edit', $audit->order) : null,
                    ];
                });
            $activities = $activities->merge($orderAudits);
        }

        // 2. Quote Audits
        if ($type === 'all' || $type === 'quotes') {
            $quoteAudits = QuoteAudit::with(['user', 'quote'])
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->whereHas('quote', function($sub) use ($tenantId) {
                        $sub->where('tenant_id', $tenantId);
                    });
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->get()
                ->map(function($audit) {
                    return [
                        'id' => 'quote_' . $audit->id,
                        'type' => 'quote',
                        'type_label' => 'Orçamento',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $this->getActionLabel($audit->action, 'quote'),
                        'entity' => $audit->quote,
                        'entity_label' => $audit->quote ? 'Orçamento #' . $audit->quote->number : 'Orçamento',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => $audit->quote ? route('quotes.edit', $audit->quote) : null,
                    ];
                });
            $activities = $activities->merge($quoteAudits);
        }

        // 3. Tax Rate Audits
        if ($type === 'all' || $type === 'tax_rates') {
            $taxRateAudits = TaxRateAudit::with(['user', 'taxRate'])
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->whereHas('taxRate', function($sub) use ($tenantId) {
                        $sub->where('tenant_id', $tenantId);
                    });
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->get()
                ->map(function($audit) {
                    return [
                        'id' => 'tax_' . $audit->id,
                        'type' => 'tax_rate',
                        'type_label' => 'Tributação',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $this->getActionLabel($audit->action, 'tax_rate'),
                        'entity' => $audit->taxRate,
                        'entity_label' => $audit->taxRate ? ($audit->taxRate->name ?: 'Alíquota #' . $audit->taxRate->id) : 'Configuração Tributária',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => $audit->taxRate ? route('tax_rates.show', $audit->taxRate) : null,
                    ];
                });
            $activities = $activities->merge($taxRateAudits);
        }

        // 4. NCM Rule Audits
        if ($type === 'all' || $type === 'ncm_rules') {
            $ncmAudits = NcmRuleAudit::with(['user','rule'])
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->whereIn('user_id', \App\Models\User::where('tenant_id', $tenantId)->pluck('id'));
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    return [
                        'id' => 'ncm_' . $audit->id,
                        'type' => 'ncm_rule',
                        'type_label' => 'Regra NCM→GTIN',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => match($audit->action){
                            'created' => 'Criou regra NCM',
                            'updated' => 'Editou regra NCM',
                            'deleted' => 'Excluiu regra NCM',
                            default => ucfirst($audit->action)
                        },
                        'entity' => $audit->rule,
                        'entity_label' => $audit->rule ? ('NCM ' . $audit->rule->ncm) : 'Regra NCM',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('ncm_rules.index'),
                    ];
                });
            $activities = $activities->merge($ncmAudits);
        }

        // 5. Settings Audits
        if ($type === 'all' || $type === 'settings') {
            $settingsAudits = SettingsAudit::with('user')
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->get()
                ->map(function($audit) {
                    return [
                        'id' => 'setting_' . $audit->id,
                        'type' => 'settings',
                        'type_label' => 'Configurações',
                        'user' => $audit->user,
                        'action' => 'updated',
                        'action_label' => 'Configuração alterada',
                        'entity' => null,
                        'entity_label' => $this->getSettingLabel($audit->setting_key),
                        'notes' => $audit->notes,
                        'changes' => ['old' => $audit->old_value, 'new' => $audit->new_value],
                        'created_at' => $audit->created_at,
                        'url' => route('settings.fiscal.edit'),
                    ];
                });
            $activities = $activities->merge($settingsAudits);
        }

        // 6. User Audits (CRUD e roles/perms)
        if ($type === 'all' || $type === 'users') {
            $userAudits = UserAudit::with(['actor','target'])
                ->when($tenantId, function($q) use ($tenantId) {
                    $actorIds = \App\Models\User::where('tenant_id', $tenantId)->pluck('id');
                    $q->whereIn('actor_user_id', $actorIds);
                })
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('actor_user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [
                        'created_user' => 'Criou usuário',
                        'updated_user' => 'Editou usuário',
                        'deleted_user' => 'Excluiu usuário',
                        'role_assigned' => 'Atribuiu papel',
                        'role_revoked' => 'Removeu papel',
                        'perm_granted' => 'Concedeu permissão',
                        'perm_revoked' => 'Revogou permissão',
                    ];
                    return [
                        'id' => 'usr_' . $audit->id,
                        'type' => 'user',
                        'type_label' => 'Usuários',
                        'user' => $audit->actor,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->target,
                        'entity_label' => $audit->target ? $audit->target->name : 'Usuário',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('users.index'),
                    ];
                });
            $activities = $activities->merge($userAudits);
        }

        // 7. Stock Audits (movimentações manuais)
        if ($type === 'all' || $type === 'stock') {
            $stockAudits = StockAudit::with(['user','product'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [
                        'entry' => 'Entrada de estoque',
                        'exit' => 'Saída de estoque',
                        'adjustment' => 'Ajuste de estoque',
                        'reverse' => 'Estorno de estoque',
                    ];
                    return [
                        'id' => 'stk_' . $audit->id,
                        'type' => 'stock',
                        'type_label' => 'Estoque',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->product,
                        'entity_label' => $audit->product ? ($audit->product->name . ' (Código: ' . ($audit->product->sku ?? '-') . ')') : 'Produto',
                        'notes' => $audit->details['note'] ?? null,
                        'changes' => [
                            'qty' => $audit->details['quantity'] ?? null,
                            'prev' => $audit->details['prev_balance'] ?? null,
                            'new' => $audit->details['new_balance'] ?? null,
                        ],
                        'created_at' => $audit->created_at,
                        'url' => route('stock.movements'),
                    ];
                });
            $activities = $activities->merge($stockAudits);
        }

        // 8. Finance Audits (Receivables/Payables)
        if ($type === 'all' || $type === 'finance') {
            $finAudits = FinanceAudit::with(['user'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $typeLabel = $audit->entity_type === 'receivable' ? 'Recebível' : 'Pagar';
                    $actionLabels = [
                        'created' => 'Lançou ' . strtolower($typeLabel),
                        'updated' => 'Editou ' . strtolower($typeLabel),
                        'paid' => 'Baixou ' . strtolower($typeLabel),
                        'canceled' => 'Cancelou ' . strtolower($typeLabel),
                        'reversed' => 'Estornou ' . strtolower($typeLabel),
                        'bulk_paid' => 'Baixa em lote',
                    ];
                    $url = null;
                    if ($audit->entity_type === 'receivable') {
                        try { $url = route('receivables.index'); } catch (\Throwable $e) { $url = null; }
                    } else {
                        try { $url = route('payables.index'); } catch (\Throwable $e) { $url = null; }
                    }
                    return [
                        'id' => 'fin_' . $audit->id,
                        'type' => 'finance',
                        'type_label' => 'Financeiro',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $actionLabels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => null,
                        'entity_label' => $typeLabel . ' #' . ($audit->entity_id ?: ''),
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => $url,
                    ];
                });
            $activities = $activities->merge($finAudits);
        }

        // 9. Carrier Audits
        if ($type === 'all' || $type === 'carriers') {
            $carAudits = CarrierAudit::with(['user','carrier'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'created' => 'Criou transportadora', 'updated' => 'Editou transportadora', 'deleted' => 'Excluiu transportadora' ];
                    return [
                        'id' => 'car_' . $audit->id,
                        'type' => 'carrier',
                        'type_label' => 'Transportadora',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->carrier,
                        'entity_label' => $audit->carrier?->name ?? 'Transportadora',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('carriers.index'),
                    ];
                });
            $activities = $activities->merge($carAudits);
        }

        // 10. Cash Withdrawals (Sangrias)
        if ($type === 'all' || $type === 'cash_withdrawals') {
            $cwAudits = CashWithdrawalAudit::with(['user','withdrawal'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'created' => 'Registrou sangria', 'updated' => 'Editou sangria', 'deleted' => 'Excluiu sangria', 'reversed' => 'Estornou sangria' ];
                    $amountInfo = null;
                    if ($audit->withdrawal) {
                        $amountInfo = 'R$ ' . number_format((float)$audit->withdrawal->amount, 2, ',', '.');
                    }
                    return [
                        'id' => 'cw_' . $audit->id,
                        'type' => 'cash_withdrawal',
                        'type_label' => 'Sangria',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->withdrawal,
                        'entity_label' => $amountInfo ? ('Sangria de ' . $amountInfo) : 'Sangria',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('cash_withdrawals.index'),
                    ];
                });
            $activities = $activities->merge($cwAudits);
        }

        // 11. Receipts (Recibos)
        if ($type === 'all' || $type === 'receipts') {
            $recAudits = ReceiptAudit::with(['user','receipt'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'created' => 'Emitiu recibo', 'updated' => 'Editou recibo', 'canceled' => 'Cancelou recibo', 'email_sent' => 'Enviou email' ];
                    $num = $audit->receipt?->number ? (' #' . $audit->receipt->number) : '';
                    return [
                        'id' => 'rcp_' . $audit->id,
                        'type' => 'receipt',
                        'type_label' => 'Recibo',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->receipt,
                        'entity_label' => 'Recibo' . $num,
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('receipts.index'),
                    ];
                });
            $activities = $activities->merge($recAudits);
        }

        // 12. Product Audits
        if ($type === 'all' || $type === 'products') {
            $prodAudits = ProductAudit::with(['user','product'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [
                        'created' => 'Criou produto',
                        'updated' => 'Editou produto',
                        'deleted' => 'Excluiu produto',
                        'activated' => 'Ativou produto',
                        'deactivated' => 'Desativou produto',
                    ];
                    return [
                        'id' => 'prd_' . $audit->id,
                        'type' => 'product',
                        'type_label' => 'Produtos',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => $audit->product,
                        'entity_label' => $audit->product ? ($audit->product->name . ' (Código: ' . ($audit->product->sku ?? '-') . ')') : 'Produto',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('products.index'),
                    ];
                });
            $activities = $activities->merge($prodAudits);
        }

        // 9. Category Audits
        if ($type === 'all' || $type === 'categories') {
            $catAudits = CategoryAudit::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [
                        'created' => 'Criou categoria',
                        'updated' => 'Editou categoria',
                        'deleted' => 'Excluiu categoria',
                    ];
                    return [
                        'id' => 'cat_' . $audit->id,
                        'type' => 'category',
                        'type_label' => 'Categorias',
                        'user' => \App\Models\User::find($audit->user_id),
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => null,
                        'entity_label' => $audit->changes['name'] ?? 'Categoria',
                        'notes' => $audit->notes,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('categories.index'),
                    ];
                });
            $activities = $activities->merge($catAudits);
        }

        // 13. Client Audits
        if ($type === 'all' || $type === 'clients') {
            $cliAudits = ClientAudit::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'created' => 'Criou cliente', 'updated' => 'Editou cliente', 'deleted' => 'Excluiu cliente' ];
                    return [
                        'id' => 'cli_' . $audit->id,
                        'type' => 'client',
                        'type_label' => 'Clientes',
                        'user' => \App\Models\User::find($audit->user_id),
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => null,
                        'entity_label' => $audit->changes['name'] ?? 'Cliente',
                        'notes' => null,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('clients.index'),
                    ];
                });
            $activities = $activities->merge($cliAudits);
        }

        // 14. Supplier Audits
        if ($type === 'all' || $type === 'suppliers') {
            $supAudits = SupplierAudit::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'created' => 'Criou fornecedor', 'updated' => 'Editou fornecedor', 'deleted' => 'Excluiu fornecedor', 'deactivated' => 'Desativou fornecedor' ];
                    return [
                        'id' => 'sup_' . $audit->id,
                        'type' => 'supplier',
                        'type_label' => 'Fornecedores',
                        'user' => \App\Models\User::find($audit->user_id),
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => null,
                        'entity_label' => $audit->changes['name'] ?? 'Fornecedor',
                        'notes' => null,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('suppliers.index'),
                    ];
                });
            $activities = $activities->merge($supAudits);
        }

        // 15. Profile Audits
        if ($type === 'all' || $type === 'profile') {
            $profAudits = ProfileAudit::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $labels = [ 'updated_profile' => 'Atualizou perfil', 'updated_logo' => 'Atualizou logo' ];
                    return [
                        'id' => 'prf_' . $audit->id,
                        'type' => 'profile',
                        'type_label' => 'Perfil',
                        'user' => \App\Models\User::find($audit->user_id),
                        'action' => $audit->action,
                        'action_label' => $labels[$audit->action] ?? ucfirst($audit->action),
                        'entity' => null,
                        'entity_label' => 'Perfil do Tenant',
                        'notes' => null,
                        'changes' => $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('profile.edit'),
                    ];
                });
            $activities = $activities->merge($profAudits);
        }

        // 16. Service Orders (status/mudanças) — se o modelo existir
        if ($type === 'all' || $type === 'service_orders') {
            try {
                if (class_exists(\App\Models\ServiceOrderStatusLog::class)) {
                    $soLogs = \App\Models\ServiceOrderStatusLog::with(['changedBy', 'serviceOrder'])
                        ->when($tenantId, function($q) use ($tenantId) {
                            $q->whereHas('serviceOrder', function($sub) use ($tenantId) {
                                $sub->where('tenant_id', $tenantId);
                            });
                        })
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->when($userId, function($q) use ($userId) { 
                            $q->where('changed_by', $userId); 
                        })
                        ->get()
                        ->map(function($log) {
                            return [
                                'id' => 'so_' . $log->id,
                                'type' => 'service_order',
                                'type_label' => 'Ordem de Serviço',
                                'user' => $log->changedBy,
                                'action' => 'updated',
                                'action_label' => 'Atualizou ordem de serviço',
                                'entity' => $log->serviceOrder,
                                'entity_label' => $log->serviceOrder ? ('OS #' . ($log->serviceOrder->number ?? $log->serviceOrder->id)) : 'Ordem de Serviço',
                                'notes' => $log->reason ?? null,
                                'changes' => [ 'status' => [ 'old' => $log->old_status ?? null, 'new' => $log->new_status ?? null ] ],
                                'created_at' => $log->created_at,
                                'url' => $log->serviceOrder ? route('service_orders.edit', $log->serviceOrder) : null,
                            ];
                        });
                    $activities = $activities->merge($soLogs);
                }
                
                // Service Order Audits (emails, etc)
                if (class_exists(\App\Models\ServiceOrderAudit::class)) {
                    $soAudits = ServiceOrderAudit::with(['user', 'serviceOrder'])
                        ->when($tenantId, function($q) use ($tenantId) {
                            $q->whereHas('serviceOrder', function($sub) use ($tenantId) {
                                $sub->where('tenant_id', $tenantId);
                            });
                        })
                        ->whereBetween('created_at', [$dateFrom, $dateTo])
                        ->when($userId, function($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                        ->get()
                        ->map(function($audit) {
                            // Traduzir mudanças de status para PT-BR, quando existirem
                            $translatedChanges = $audit->changes ?? [];
                            if (is_array($translatedChanges) && isset($translatedChanges['status'])) {
                                $statusMap = [
                                    'open' => 'Em análise',
                                    'in_progress' => 'Orçada',
                                    'in_service' => 'Em andamento',
                                    'service_finished' => 'Serviço finalizado',
                                    'warranty' => 'Garantia',
                                    'no_repair' => 'Sem reparo',
                                    'finished' => 'Finalizada',
                                    'canceled' => 'Cancelada',
                                ];
                                $old = $translatedChanges['status']['old'] ?? null;
                                $new = $translatedChanges['status']['new'] ?? null;
                                if ($old !== null) { $translatedChanges['status']['old'] = $statusMap[$old] ?? $old; }
                                if ($new !== null) { $translatedChanges['status']['new'] = $statusMap[$new] ?? $new; }
                            }
                            return [
                                'id' => 'soa_' . $audit->id,
                                'type' => 'service_order',
                                'type_label' => 'Ordem de Serviço',
                                'user' => $audit->user,
                                'action' => $audit->action,
                                'action_label' => $this->getActionLabel($audit->action, 'service_order'),
                                'entity' => $audit->serviceOrder,
                                'entity_label' => $audit->serviceOrder ? ('OS #' . ($audit->serviceOrder->number ?? $audit->serviceOrder->id)) : 'Ordem de Serviço',
                                'notes' => $audit->notes,
                                'changes' => $translatedChanges,
                                'created_at' => $audit->created_at,
                                'url' => $audit->serviceOrder ? route('service_orders.edit', $audit->serviceOrder) : null,
                            ];
                        });
                    $activities = $activities->merge($soAudits);
                }
            } catch (\Throwable $e) {
                \Log::warning('Erro ao buscar ServiceOrder logs/audits', ['error' => $e->getMessage()]);
            }
        }

        // 17. Returns (Devoluções)
        if ($type === 'all' || $type === 'returns') {
            $retAudits = ReturnAudit::with(['user','order'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->when($tenantId, function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->when($userId, function($q) use ($userId) { $q->where('user_id', $userId); })
                ->get()
                ->map(function($audit) {
                    $changes = $audit->changes;
                    // Simplificar visualização: mostrar linha resumida com itens devolvidos
                    $summary = '';
                    if (is_array($changes)) {
                        $summary = $changes['detalhes'] ?? ($changes['itens_text'] ?? '');
                    }
                    return [
                        'id' => 'ret_' . $audit->id,
                        'type' => 'return',
                        'type_label' => 'Devolução',
                        'user' => $audit->user,
                        'action' => $audit->action,
                        'action_label' => 'Registrou devolução',
                        'entity' => $audit->order,
                        'entity_label' => $audit->order ? ('Pedido #' . ($audit->order->number ?? $audit->order->id)) : 'Devolução',
                        'notes' => $audit->notes,
                        'changes' => $summary ? ['Itens devolvidos' => $summary] : $audit->changes,
                        'created_at' => $audit->created_at,
                        'url' => route('returns.index'),
                    ];
                });
            $activities = $activities->merge($retAudits);
        }

        // Ordenar por data (mais recente primeiro)
        $activities = $activities->sortByDesc('created_at')->values();

        // Paginação manual da collection resultante
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) { $perPage = 25; }
        $currentPage = (int) max(1, (int) $request->input('page', 1));
        $total = $activities->count();
        $items = $activities->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($items, $total, $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // Debug: registrar estatísticas para investigação
        try {
            // Diagnóstico adicional (auditorias sem filtros)
            $diag = [
                'all_tax_rate_audits' => null,
                'tax_rate_audits_by_user' => null,
                'tax_rate_audits_by_tenant' => null,
            ];
            try {
                $diag['all_tax_rate_audits'] = TaxRateAudit::count();
                $diag['tax_rate_audits_by_user'] = TaxRateAudit::where('user_id', auth()->id())->count();
                if ($tenantId) {
                    $diag['tax_rate_audits_by_tenant'] = TaxRateAudit::whereHas('taxRate', function($q) use ($tenantId){ $q->where('tenant_id', $tenantId); })->count();
                }
            } catch (\Throwable $e) {}
            \Log::info('activity.index.debug', [
                'auth_user_id' => auth()->id(),
                'auth_user_name' => auth()->user()->name ?? null,
                'auth_tenant_id' => $tenantId,
                'filters' => [
                    'date_from' => $dateFrom?->toDateTimeString(),
                    'date_to' => $dateTo?->toDateTimeString(),
                    'type' => $type,
                    'user_id' => $userId,
                ],
                'counts' => [
                    'total_activities' => $activities->count(),
                    'orders' => isset($orderAudits) ? $orderAudits->count() : null,
                    'quotes' => isset($quoteAudits) ? $quoteAudits->count() : null,
                    'tax_rates' => isset($taxRateAudits) ? $taxRateAudits->count() : null,
                    'settings' => isset($settingsAudits) ? $settingsAudits->count() : null,
                    'service_orders' => isset($soLogs) ? $soLogs->count() : null,
                ],
                'diagnostics' => $diag,
                'sample' => $activities->take(3)->map(function($a){
                    return [
                        'id' => $a['id'] ?? null,
                        'type' => $a['type'] ?? null,
                        'action' => $a['action'] ?? null,
                        'user' => $a['user']->name ?? null,
                        'created_at' => method_exists($a['created_at'] ?? null, 'toDateTimeString') ? $a['created_at']->toDateTimeString() : (string)($a['created_at'] ?? ''),
                        'entity_label' => $a['entity_label'] ?? null,
                    ];
                })->all(),
            ]);
        } catch (\Throwable $e) { /* ignore logging issues */ }

        // Usuários para filtro
        $users = \App\Models\User::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('activity.index', compact('activities', 'paginator', 'dateFrom', 'dateTo', 'type', 'userId', 'users', 'perPage'));
    }

    private function getActionLabel(string $action, string $context): string
    {
        $labels = [
            'order' => [
                'created' => 'Criou pedido',
                'updated' => 'Editou pedido',
                'canceled' => 'Cancelou pedido',
                'approved' => 'Aprovou pedido',
                'returned' => 'Registrou devolução',
                'status_changed' => 'Alterou status do pedido',
                'finalized' => 'Finalizou pedido',
                'email_sent' => 'Enviou email',
                'fulfilled' => 'Finalizou pedido',
                'reopened' => 'Reabriu pedido',
                'auto_adjusted_with_returns' => 'Ajustou automaticamente',
            ],
            'quote' => [
                'created' => 'Criou orçamento',
                'updated' => 'Editou orçamento',
                'approved' => 'Aprovou orçamento',
                'canceled' => 'Cancelou orçamento',
                'rejected' => 'Reprovou orçamento',
                'converted' => 'Convertiu orçamento em pedido',
                'email_sent' => 'Enviou email',
            ],
            'service_order' => [
                'created' => 'Criou ordem de serviço',
                'updated' => 'Editou ordem de serviço',
                'canceled' => 'Cancelou ordem de serviço',
                'finalized' => 'Finalizou ordem de serviço',
                'email_sent' => 'Enviou email',
            ],
            'tax_rate' => [
                'created' => 'Criou configuração tributária',
                'updated' => 'Editou configuração tributária',
                'deleted' => 'Excluiu configuração tributária',
            ],
        ];

        return $labels[$context][$action] ?? ucfirst($action);
    }

    private function getSettingLabel(string $key): string
    {
        $labels = [
            'tax.regime_tributario' => 'Regime Tributário',
            'tax.cnae_principal' => 'CNAE Principal',
            'tax.anexo_simples' => 'Anexo Simples Nacional',
            'tax.aliquota_simples_nacional' => 'Alíquota Simples Nacional',
            'tax.habilitar_ibpt' => 'Habilitar IBPT',
            'tax.codigo_ibpt_padrao' => 'Código IBPT Padrão',
        ];

        return $labels[$key] ?? str_replace('tax.', '', $key);
    }
}

