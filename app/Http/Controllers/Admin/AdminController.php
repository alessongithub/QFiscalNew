<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpConfig;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Partner;
use App\Models\Receivable;
use App\Models\Setting;
use App\Models\TenantStorageUsage;
use App\Models\TenantBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use App\Services\StorageCalculator;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('active', true)->count();
        $totalUsers = User::count();
        $smtpConfig = SmtpConfig::where('is_active', true)->first();

        return view('admin.dashboard', compact('totalTenants', 'activeTenants', 'totalUsers', 'smtpConfig'));
    }

    public function emitterHealthcheck()
    {
        $candidates = [
            (string) config('services.delphi.url', ''),
            (string) config('app.delphi_emissor_url', ''),
            'http://127.0.0.1:18080',
            'http://localhost:18080',
        ];

        $results = [];
        foreach ($candidates as $base) {
            $base = trim((string) $base);
            if ($base === '') { continue; }
            try {
                $resp = Http::timeout(3)->get(rtrim($base, '/') . '/api/status');
                $results[] = [
                    'url' => $base,
                    'http' => $resp->status(),
                    'ok' => $resp->successful(),
                    'body' => $resp->json() ?? [ 'raw' => $resp->body() ],
                ];
                if ($resp->successful()) {
                    return back()->with('success', 'Emissor ONLINE em '.$base)
                        ->with('emitter_status', end($results));
                }
            } catch (\Throwable $e) {
                $results[] = [ 'url' => $base, 'error' => $e->getMessage() ];
            }
        }

        return back()->with('error', 'Emissor OFFLINE em todas as URLs testadas.')
            ->with('emitter_status', [ 'tries' => $results ]);
    }

    public function emitterHealthcheckJson()
    {
        $candidates = [
            (string) config('services.delphi.url', ''),
            (string) config('app.delphi_emissor_url', ''),
            'http://127.0.0.1:18080',
            'http://localhost:18080',
        ];

        foreach ($candidates as $base) {
            $base = trim((string) $base);
            if ($base === '') { continue; }
            try {
                $resp = Http::timeout(3)->get(rtrim($base, '/') . '/api/status');
                if ($resp->successful()) {
                    return response()->json([
                        'success' => true,
                        'url' => $base,
                        'http' => $resp->status(),
                        'message' => 'Emissor ONLINE'
                    ]);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Emissor OFFLINE em todas as URLs testadas'
        ]);
    }

    public function smtpSettings()
    {
        $smtpConfig = SmtpConfig::where('is_active', true)->first() ?? new SmtpConfig([
            'host' => 'smtp.hostinger.com',
            'port' => 587,
            'username' => 'suporte@evoqueassessoria.com.br',
            'encryption' => 'tls',
            'from_address' => 'suporte@evoqueassessoria.com.br',
            'from_name' => 'Evoque Assessoria',
        ]);
        return view('admin.smtp-settings', compact('smtpConfig'));
    }

    public function updateSmtpSettings(Request $request)
    {
        $validated = $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
            'encryption' => 'required|in:tls,ssl',
            'from_address' => 'required|email',
            'from_name' => 'required|string',
        ]);

        // Normaliza dados
        $normalized = [
            'host' => trim((string)$validated['host']),
            'port' => (int) $validated['port'],
            'username' => trim((string)$validated['username']),
            'password' => (string) $validated['password'],
            'encryption' => strtolower(trim((string)$validated['encryption'])),
            'from_address' => trim((string)$validated['from_address']),
            'from_name' => trim((string)$validated['from_name']),
        ];

        // Garantir combinação porta/criptografia coerente
        if ($normalized['port'] === 465) {
            $normalized['encryption'] = 'ssl';
        } elseif ($normalized['port'] === 587) {
            $normalized['encryption'] = 'tls';
        }

        // Desativa todas as configurações SMTP existentes e salva uma única ativa
        \DB::transaction(function () use ($normalized) {
            SmtpConfig::query()->update(['is_active' => false]);
            SmtpConfig::create([
                ...$normalized,
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.smtp-settings')->with('success', 'Configurações SMTP atualizadas com sucesso.');
    }

    public function tenants(Request $request)
    {
        $query = Tenant::with(['users', 'partner', 'plan']);
        
        // Filtro por busca (nome, fantasia, email, CNPJ)
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('fantasy_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%" . preg_replace('/[^0-9]/', '', $search) . "%");
            });
        }
        
        // Filtro por parceiro
        if ($partnerId = $request->get('partner_id')) {
            if ($partnerId === 'none') {
                $query->whereNull('partner_id');
            } else {
                $query->where('partner_id', $partnerId);
            }
        }
        
        // Filtro por plano
        if ($planId = $request->get('plan_id')) {
            if ($planId === 'none') {
                $query->whereNull('plan_id');
            } else {
                $query->where('plan_id', $planId);
            }
        }
        
        // Filtro por assinaturas vencidas
        if ($request->boolean('expired')) {
            $query->whereNotNull('plan_expires_at')
                  ->whereDate('plan_expires_at', '<', now()->toDateString());
        }
        
        // Ordenação
        $query->orderBy('name');
        
        // Paginação
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $tenants = $query->paginate($perPage)->withQueryString();
        
        // Dados para os filtros
        $partners = \App\Models\Partner::orderBy('name')->get();
        $plans = \App\Models\Plan::where('active', true)->orderBy('price')->get();
        
        return view('admin.tenants', compact('tenants', 'partners', 'plans'));
    }

    public function editTenant(Tenant $tenant)
    {
        $partners = \App\Models\Partner::where('active', true)->orderBy('name')->get();
        return view('admin.tenants.edit', compact('tenant', 'partners'));
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'partner_id' => ['nullable', 'exists:partners,id'],
        ]);

        $tenant->update($data);
        return redirect()->route('admin.tenants')->with('success', 'Tenant atualizado com sucesso.');
    }

    public function toggleTenantStatus(Tenant $tenant)
    {
        $tenant->update(['active' => !$tenant->active]);
        $status = $tenant->active ? 'ativado' : 'desativado';
        return redirect()->route('admin.tenants')->with('success', "Tenant {$status} com sucesso.");
    }

    public function payments(Request $request)
    {
        $base = Invoice::with(['tenant','payments']);

        // Filtros
        $status = strtolower((string) $request->get('status', ''));
        $tenantId = $request->get('tenant_id');
        $tenantName = trim((string) $request->get('tenant', ''));
        $partnerId = $request->get('partner_id');
        $withoutPartner = (bool) $request->boolean('without_partner');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if (!empty($tenantId)) {
            $base->where('tenant_id', (int) $tenantId);
        }

        if ($tenantName !== '') {
            $base->whereHas('tenant', function ($q) use ($tenantName) {
                $q->where('name', 'like', "%{$tenantName}%")
                  ->orWhere('fantasy_name', 'like', "%{$tenantName}%")
                  ->orWhere('email', 'like', "%{$tenantName}%");
            });
        }

        if (!empty($partnerId)) {
            $pid = (int) $partnerId;
            $base->where(function ($q) use ($pid) {
                $q->where('partner_id', $pid)
                  ->orWhereHas('tenant', function ($t) use ($pid) {
                      $t->where('partner_id', $pid);
                  });
            });
        }

        if ($withoutPartner) {
            // Somente faturas/tenants sem partner vinculado
            $base->whereNull('partner_id')
                ->whereHas('tenant', function ($q) {
                    $q->whereNull('partner_id');
                });
        }

        if (!empty($dateFrom)) {
            $base->whereDate('due_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $base->whereDate('due_date', '<=', $dateTo);
        }

        if ($status === 'paid') {
            $base->where(function ($q) {
                $q->where('status', 1)->orWhere('status', 'paid');
            });
        } elseif ($status === 'pending') {
            $base->where(function ($q) {
                $q->where('status', 0)->orWhere('status', 'pending');
            });
        } elseif ($status === 'overdue') {
            $today = now()->toDateString();
            $base->whereDate('due_date', '<', $today)
                ->where(function ($q) {
                    $q->where('status', 0)->orWhere('status', 'pending');
                });
        } elseif ($status !== '' && $status !== 'all') {
            // Qualquer outro status literal
            $base->where('status', $status);
        }

        // Sumário (antes da paginação)
        $summaryBase = (clone $base);
        $summaryCount = (clone $summaryBase)->count();
        $summaryTotalAmount = (clone $summaryBase)->sum('amount');
        $invoiceIds = (clone $summaryBase)->pluck('id');
        $summaryPaidAmount = Payment::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'approved')
            ->sum('amount');
        $summaryOverdueCount = (clone $summaryBase)
            ->whereDate('due_date', '<', now()->toDateString())
            ->where(function ($q) {
                $q->where('status', 0)->orWhere('status', 'pending');
            })
            ->count();
        $summary = [
            'count' => $summaryCount,
            'total_amount' => $summaryTotalAmount,
            'paid_amount' => $summaryPaidAmount,
            'overdue_count' => $summaryOverdueCount,
        ];

        // Export CSV
        if (strtolower((string) $request->get('export')) === 'csv') {
            $rows = $base->with(['partner'])->orderByDesc('due_date')->get();
            $filename = 'invoices_' . now()->format('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                // Cabeçalho
                fputcsv($out, ['Tenant','Vencimento','Status','Valor','Partner','Pago em','Total Pagamentos']);
                foreach ($rows as $inv) {
                    $tenantName = optional($inv->tenant)->name;
                    $due = optional($inv->due_date)?->format('d/m/Y');
                    $status = $inv->status_name;
                    $amount = number_format((float) $inv->amount, 2, ',', '.');
                    $partnerName = optional($inv->partner)->name;
                    $paidAt = $inv->paid_at ? $inv->paid_at->format('d/m/Y H:i') : '';
                    $paidSum = (float) ($inv->payments?->where('status','approved')->sum('amount') ?? 0);
                    $paidSumFmt = number_format($paidSum, 2, ',', '.');
                    fputcsv($out, [$tenantName, $due, $status, $amount, $partnerName, $paidAt, $paidSumFmt]);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        $invoices = $base->orderByDesc('due_date')->paginate(15)->appends($request->query());
        $partners = Partner::orderBy('name')->get(['id','name']);

        return view('admin.payments', compact('invoices', 'partners', 'summary'));
    }

    public function receivables(Request $request)
    {
        $base = Receivable::with(['tenant','client'])
            ->whereNotNull('boleto_mp_id');

        // Filtros
        $status = strtolower((string) $request->get('status', 'all')); // all|paid|pending|overdue
        $tenantTerm = trim((string) $request->get('tenant', ''));
        $partnerId = $request->get('partner_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        if ($tenantTerm !== '') {
            $base->whereHas('tenant', function ($q) use ($tenantTerm) {
                $q->where('name', 'like', "%{$tenantTerm}%")
                  ->orWhere('fantasy_name', 'like', "%{$tenantTerm}%")
                  ->orWhere('email', 'like', "%{$tenantTerm}%");
            });
        }

        if (!empty($partnerId)) {
            $pid = (int) $partnerId;
            $base->whereHas('tenant', function ($q) use ($pid) {
                $q->where('partner_id', $pid);
            });
        }

        if (!empty($dateFrom)) {
            $base->whereDate('due_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $base->whereDate('due_date', '<=', $dateTo);
        }

        if ($status === 'paid') {
            $base->where('status', 'paid');
        } elseif ($status === 'pending') {
            $base->whereIn('status', ['open','partial'])
                 ->whereDate('due_date', '>=', now()->toDateString());
        } elseif ($status === 'overdue') {
            $base->whereIn('status', ['open','partial'])
                 ->whereDate('due_date', '<', now()->toDateString());
        }

        // Paginação
        $receivables = $base->orderByDesc('due_date')->paginate(20)->appends($request->query());

        $partners = Partner::orderBy('name')->get(['id','name']);

        return view('admin.receivables', compact('receivables','partners'));
    }

    public function balances(Request $request)
    {
        $base = TenantBalance::with(['tenant','receivable'])->orderByDesc('created_at');

        // Filtros
        $status = strtolower((string) $request->get('status', 'requested')); // requested|available|pending|transferred|all
        $tenantTerm = trim((string) $request->get('tenant', ''));

        if ($status !== '' && $status !== 'all') {
            $base->where('status', $status);
        }

        if ($tenantTerm !== '') {
            $term = $tenantTerm;
            $base->whereHas('tenant', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('fantasy_name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $balances = $base->paginate(20)->appends($request->query());

        // Sumário rápido
        $summary = [
            'requested_count' => TenantBalance::where('status','requested')->count(),
            'requested_total' => (float) TenantBalance::where('status','requested')->sum('net_amount'),
        ];

        return view('admin.balances', compact('balances','summary','status','tenantTerm'));
    }

    public function plans()
    {
        $plans = Plan::paginate(10);
        return view('admin.plans', compact('plans'));
    }

    public function createPlan()
    {
        return view('admin.plans.create');
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'active' => 'boolean',
            // Limites de recursos
            'max_clients' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'max_products' => 'nullable|integer',
            // Features de acesso
            'has_api_access' => 'nullable|boolean',
            'has_emissor' => 'nullable|boolean',
            'has_erp' => 'nullable|boolean',
            'allow_issue_nfe' => 'nullable|boolean',
            'allow_pos' => 'nullable|boolean',
            'erp_access_level' => 'nullable|in:free,basic,professional,enterprise',
            'support_type' => 'nullable|in:email,priority,24/7',
            // Limites de armazenamento
            'storage_data_mb' => 'nullable|integer|min:-1',
            'storage_files_mb' => 'nullable|integer|min:-1',
            'additional_data_price' => 'nullable|numeric|min:0',
            'additional_files_price' => 'nullable|numeric|min:0',
            // Display
            'display_features_text' => 'nullable|string',
        ]);

        // Montar objeto de features
        $features = [
            // Limites de recursos
            'max_clients' => $request->input('max_clients', 50),
            'max_users' => $request->input('max_users', 1),
            'max_products' => $request->input('max_products', null),
            // Features de acesso
            'has_api_access' => (bool) $request->input('has_api_access', false),
            'has_emissor' => (bool) $request->input('has_emissor', false),
            'has_erp' => (bool) $request->input('has_erp', true),
            'allow_issue_nfe' => (bool) $request->input('allow_issue_nfe', false),
            'allow_pos' => (bool) $request->input('allow_pos', false),
            'erp_access_level' => $request->input('erp_access_level'),
            'support_type' => $request->input('support_type', 'email'),
            // Limites de armazenamento
            'storage_data_mb' => $request->input('storage_data_mb', 50),
            'storage_files_mb' => $request->input('storage_files_mb', 500),
            'additional_data_price' => $request->input('additional_data_price', 9.90),
            'additional_files_price' => $request->input('additional_files_price', 9.90),
            // Display
            'display_features' => []
        ];

        $displayFeaturesText = $request->input('display_features_text');
        if (!empty($displayFeaturesText)) {
            $lines = preg_split('/\r\n|\r|\n/', $displayFeaturesText);
            $features['display_features'] = array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
        }

        $data = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'features' => $features,
            'active' => (bool) ($validated['active'] ?? true),
        ];

        Plan::create($data);

        return redirect()->route('admin.plans')->with('success', 'Plano criado com sucesso.');
    }

    public function editPlan(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'active' => 'boolean',
            // Limites de recursos
            'max_clients' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'max_products' => 'nullable|integer',
            // Features de acesso
            'has_api_access' => 'nullable|boolean',
            'has_emissor' => 'nullable|boolean',
            'has_erp' => 'nullable|boolean',
            'allow_issue_nfe' => 'nullable|boolean',
            'allow_pos' => 'nullable|boolean',
            'erp_access_level' => 'nullable|in:free,basic,professional,enterprise',
            'support_type' => 'nullable|in:email,priority,24/7',
            // Limites de armazenamento
            'storage_data_mb' => 'nullable|integer|min:-1',
            'storage_files_mb' => 'nullable|integer|min:-1',
            'additional_data_price' => 'nullable|numeric|min:0',
            'additional_files_price' => 'nullable|numeric|min:0',
            // Display
            'display_features_text' => 'nullable|string',
        ]);

        // Features existentes (compatibilidade com formatos antigos)
        $existing = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);

        $features = [
            // Limites de recursos
            'max_clients' => $request->input('max_clients', $existing['max_clients'] ?? 50),
            'max_users' => $request->input('max_users', $existing['max_users'] ?? 1),
            'max_products' => $request->input('max_products', $existing['max_products'] ?? null),
            // Features de acesso
            'has_api_access' => (bool) $request->input('has_api_access', $existing['has_api_access'] ?? false),
            'has_emissor' => (bool) $request->input('has_emissor', $existing['has_emissor'] ?? false),
            'has_erp' => (bool) $request->input('has_erp', $existing['has_erp'] ?? true),
            'allow_issue_nfe' => (bool) $request->input('allow_issue_nfe', $existing['allow_issue_nfe'] ?? false),
            'allow_pos' => (bool) $request->input('allow_pos', $existing['allow_pos'] ?? false),
            'erp_access_level' => $request->input('erp_access_level', $existing['erp_access_level'] ?? null),
            'support_type' => $request->input('support_type', $existing['support_type'] ?? 'email'),
            // Limites de armazenamento
            'storage_data_mb' => $request->input('storage_data_mb', $existing['storage_data_mb'] ?? 50),
            'storage_files_mb' => $request->input('storage_files_mb', $existing['storage_files_mb'] ?? 500),
            'additional_data_price' => $request->input('additional_data_price', $existing['additional_data_price'] ?? 9.90),
            'additional_files_price' => $request->input('additional_files_price', $existing['additional_files_price'] ?? 9.90),
            // Display
            'display_features' => $existing['display_features'] ?? []
        ];

        $displayFeaturesText = $request->input('display_features_text');
        if ($displayFeaturesText !== null) {
            $lines = preg_split('/\r\n|\r|\n/', $displayFeaturesText);
            $features['display_features'] = array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
        }

        $data = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'features' => $features,
            'active' => (bool) ($validated['active'] ?? $plan->active),
        ];

        $plan->update($data);

        return redirect()->route('admin.plans')->with('success', 'Plano atualizado com sucesso.');
    }

    public function togglePlanStatus(Plan $plan)
    {
        $plan->update(['active' => !$plan->active]);
        $status = $plan->active ? 'ativado' : 'desativado';
        return redirect()->route('admin.plans')->with('success', "Plano {$status} com sucesso.");
    }

    public function delphiConfig()
    {
        return view('admin.delphi-config');
    }

    public function updateDelphiConfig(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'timeout' => 'required|integer|min:5|max:300',
            'token' => 'nullable|string|max:255',
        ]);

        Setting::setGlobal('services.delphi.url', $validated['url']);
        Setting::setGlobal('services.delphi.timeout', $validated['timeout']);
        Setting::setGlobal('services.delphi.token', $validated['token'] ?? '');

        return redirect()->route('admin.delphi-config')->with('success', 'Configurações do emissor Delphi atualizadas com sucesso.');
    }

    public function storageUsage(Request $request)
    {
        // Atualizar manualmente se solicitado
        if ($request->has('update') && $request->input('update') === '1') {
            try {
                // Executar em background para não travar a página
                Artisan::call('storage:update-usage');
                return redirect()->route('admin.storage-usage')->with('success', 'Uso de armazenamento atualizado com sucesso!');
            } catch (\Exception $e) {
                return redirect()->route('admin.storage-usage')->with('error', 'Erro ao atualizar: ' . $e->getMessage());
            }
        }
        
        $query = Tenant::with(['plan', 'storageUsage', 'partner'])
            ->where('active', true);
        
        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('fantasy_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('cnpj', 'like', "%".preg_replace('/[^0-9]/','',$search)."%");
            });
        }
        
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }
        
        $tenants = $query->orderBy('name')->paginate(25);
        $partners = Partner::orderBy('name')->get();
        
        // Calcular estatísticas gerais (otimizado - sem recalcular tudo)
        $allUsage = TenantStorageUsage::selectRaw('
            SUM(data_size_bytes) as total_data_bytes,
            SUM(files_size_bytes) as total_files_bytes,
            SUM(additional_data_mb) as total_additional_data_mb,
            SUM(additional_files_mb) as total_additional_files_mb
        ')->first();
        
        $stats = [
            'total_data_gb' => round(($allUsage->total_data_bytes ?? 0) / 1024 / 1024 / 1024, 2),
            'total_files_gb' => round(($allUsage->total_files_bytes ?? 0) / 1024 / 1024 / 1024, 2),
            'total_additional_data_mb' => $allUsage->total_additional_data_mb ?? 0,
            'total_additional_files_mb' => $allUsage->total_additional_files_mb ?? 0,
            'tenants_with_storage' => TenantStorageUsage::count(),
            'tenants_active' => Tenant::where('active', true)->count(),
        ];
        
        return view('admin.storage-usage', compact('tenants', 'partners', 'stats'));
    }

    public function profile()
    {
        $adminEmail = Setting::getGlobal('admin.email', auth()->user()->email);
        // Garantir persistência do e-mail de solicitação na primeira carga
        $existingRequest = Setting::getGlobal('admin.request_email');
        if ($existingRequest === null) {
            Setting::setGlobal('admin.request_email', 'solicitacao@qfiscal.com.br');
            $existingRequest = 'solicitacao@qfiscal.com.br';
        }
        $requestEmail = $existingRequest;
        return view('admin.profile', compact('adminEmail','requestEmail'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'admin_email' => 'required|email',
            'request_email' => 'required|email',
        ]);

        Setting::setGlobal('admin.email', $validated['admin_email']);
        Setting::setGlobal('admin.request_email', $validated['request_email']);

        return redirect()->route('admin.profile')->with('success', 'Perfil do admin atualizado.');
    }
}
