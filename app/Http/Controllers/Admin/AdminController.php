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
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

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

    public function tenants()
    {
        $tenants = Tenant::with('users')->paginate(10);
        return view('admin.tenants', compact('tenants'));
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
            // Campos dinâmicos de features
            'max_clients' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'has_api_access' => 'nullable|boolean',
            'has_emissor' => 'nullable|boolean',
            'has_erp' => 'nullable|boolean',
            'support_type' => 'nullable|in:email,priority,24/7',
            'display_features_text' => 'nullable|string',
            'max_products' => 'nullable|integer',
        ]);

        // Montar objeto de features
        $features = [
            'max_clients' => $request->input('max_clients', 50),
            'max_users' => $request->input('max_users', 1),
            'max_products' => $request->input('max_products', null),
            'has_api_access' => (bool) $request->input('has_api_access', false),
            'has_emissor' => (bool) $request->input('has_emissor', false),
            'has_erp' => (bool) $request->input('has_erp', true),
            'support_type' => $request->input('support_type', 'email'),
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
            // Campos dinâmicos de features
            'max_clients' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'has_api_access' => 'nullable|boolean',
            'has_emissor' => 'nullable|boolean',
            'has_erp' => 'nullable|boolean',
            'support_type' => 'nullable|in:email,priority,24/7',
            'display_features_text' => 'nullable|string',
            'max_products' => 'nullable|integer',
        ]);

        // Features existentes (compatibilidade com formatos antigos)
        $existing = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);

        $features = [
            'max_clients' => $request->input('max_clients', $existing['max_clients'] ?? 50),
            'max_users' => $request->input('max_users', $existing['max_users'] ?? 1),
            'max_products' => $request->input('max_products', $existing['max_products'] ?? null),
            'has_api_access' => (bool) $request->input('has_api_access', $existing['has_api_access'] ?? false),
            'has_emissor' => (bool) $request->input('has_emissor', $existing['has_emissor'] ?? false),
            'has_erp' => (bool) $request->input('has_erp', $existing['has_erp'] ?? true),
            'support_type' => $request->input('support_type', $existing['support_type'] ?? 'email'),
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
}
