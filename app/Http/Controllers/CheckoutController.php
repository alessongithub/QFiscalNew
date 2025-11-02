<?php

namespace App\Http\Controllers;

use App\Models\GatewayConfig;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\StorageAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $planId = $request->integer('plan_id') ?: ($tenant->plan_id ?? null);
        $plan = $planId ? Plan::find($planId) : null;
        $addonId = $request->integer('addon_id');
        $addon = $addonId ? StorageAddon::find($addonId) : null;
        
        // Validar se addon pertence ao tenant
        if ($addon && $addon->tenant_id !== $tenant->id) {
            return redirect()->route('storage.index')->with('error', 'Addon não encontrado.');
        }
        
        $config = GatewayConfig::current();
        return view('checkout.index', compact('tenant', 'plan', 'addon', 'config'));
    }

    public function createPreference(Request $request)
    {
        \Log::info('Checkout createPreference started', [
            'plan_id' => $request->input('plan_id'),
            'addon_id' => $request->input('addon_id'),
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant->id ?? null
        ]);

        $tenant = auth()->user()->tenant;
        $config = GatewayConfig::current();
        
        // Validar se tem plan_id OU addon_id (não ambos)
        $planId = $request->integer('plan_id');
        $addonId = $request->integer('addon_id');
        
        if (!$planId && !$addonId) {
            return back()->withErrors(['gateway' => 'Plano ou addon deve ser informado.'])->withInput();
        }
        
        if ($planId && $addonId) {
            return back()->withErrors(['gateway' => 'Não é possível pagar plano e addon simultaneamente.'])->withInput();
        }

        $accessToken = $config->active_access_token;
        if (empty($accessToken)) {
            return back()->withErrors(['gateway' => 'Access Token do Mercado Pago não configurado. Configure em Admin > Gateway.']);
        }

        $preferencePayload = [];
        
        // Processar compra de plano (fluxo existente)
        if ($planId) {
            $plan = Plan::findOrFail($planId);
            
            \Log::info('Checkout data loaded (plan)', [
                'tenant_id' => $tenant->id,
                'plan_name' => $plan->name,
                'plan_price' => $plan->price,
                'config_mode' => $config->mode ?? 'null',
                'has_access_token' => !empty($config->active_access_token)
            ]);

            // Cria/atualiza invoice aberto
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'partner_id' => $tenant->partner_id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'due_date' => now()->toDateString(),
                'status' => 'pending',
                'description' => 'Assinatura do plano: ' . $plan->name,
            ]);

            $preferencePayload = [
                'external_reference' => (string) $invoice->id,
                'items' => [[
                    'title' => 'Assinatura ' . $plan->name,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $plan->price,
                ]],
                'payer' => [
                    'email' => $tenant->email,
                    'name' => $tenant->name,
                ],
                'payment_methods' => [
                    'excluded_payment_types' => [ ['id' => 'ticket'], ['id' => 'atm'] ],
                    'installments' => 1,
                    'default_payment_method_id' => null,
                ],
                'binary_mode' => true,
                'back_urls' => [
                    'success' => url('/checkout/success'),
                    'pending' => url('/checkout/pending'),
                    'failure' => url('/checkout/failure'),
                ],
                'notification_url' => url('/webhooks/mercadopago'),
            ];
        }
        
        // Processar compra de addon (novo fluxo)
        if ($addonId) {
            $addon = StorageAddon::findOrFail($addonId);
            
            // Validar se addon pertence ao tenant
            if ($addon->tenant_id !== $tenant->id) {
                return back()->withErrors(['gateway' => 'Addon não encontrado.'])->withInput();
            }
            
            // Validar se addon ainda está pendente
            if ($addon->status !== 'pending') {
                return back()->withErrors(['gateway' => 'Este addon já foi processado.'])->withInput();
            }
            
            \Log::info('Checkout data loaded (addon)', [
                'tenant_id' => $tenant->id,
                'addon_id' => $addon->id,
                'addon_type' => $addon->type,
                'addon_quantity_mb' => $addon->quantity_mb,
                'addon_price' => $addon->price,
            ]);

            $typeLabel = $addon->type === 'data' ? 'dados' : 'arquivos';
            $preferencePayload = [
                'external_reference' => 'addon_' . $addon->id,
                'items' => [[
                    'title' => 'Espaço adicional: ' . $addon->quantity_mb . ' MB de ' . $typeLabel,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => (float) $addon->price,
                ]],
                'payer' => [
                    'email' => $tenant->email,
                    'name' => $tenant->name,
                ],
                'payment_methods' => [
                    'excluded_payment_types' => [ ['id' => 'ticket'], ['id' => 'atm'] ],
                    'installments' => 1,
                    'default_payment_method_id' => null,
                ],
                'binary_mode' => true,
                'back_urls' => [
                    'success' => url('/checkout/success'),
                    'pending' => url('/checkout/pending'),
                    'failure' => url('/checkout/failure'),
                ],
                'notification_url' => url('/webhooks/mercadopago'),
            ];
        }

        $apiBase = 'https://api.mercadopago.com';
        
        \Log::info('Mercado Pago API Request', [
            'url' => $apiBase . '/checkout/preferences',
            'payload' => $preferencePayload,
            'access_token' => substr($accessToken, 0, 10) . '...'
        ]);

        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ])->post($apiBase . '/checkout/preferences', $preferencePayload);

        \Log::info('Mercado Pago API Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            $err = $response->json();
            \Log::error('MP preference error', ['status' => $response->status(), 'body' => $err]);
            return back()->withErrors(['gateway' => 'Falha ao criar preferência de pagamento. Verifique as credenciais do gateway.'])->withInput();
        }

        $pref = $response->json();
        
        // Salvar preference_id apenas para invoices (planos)
        if ($planId) {
            $invoice = Invoice::where('tenant_id', $tenant->id)
                ->where('plan_id', $planId)
                ->where('status', 'pending')
                ->latest()
                ->first();
            
            if ($invoice) {
                $invoice->external_preference_id = $pref['id'] ?? null;
                $invoice->save();
            }
        }

        $redirectUrl = $pref['init_point'] ?? ($pref['sandbox_init_point'] ?? null);
        if (!$redirectUrl) {
            return back()->withErrors(['gateway' => 'Preferência criada, mas não foi possível iniciar o checkout.'])->withInput();
        }

        return redirect()->away($redirectUrl);
    }

    public function success()
    {
        return redirect()->route('dashboard')->with('success', 'Pagamento processado. Aguarde a confirmação.');
    }

    public function pending()
    {
        return redirect()->route('dashboard')->with('info', 'Pagamento pendente de confirmação.');
    }

    public function failure()
    {
        return redirect()->route('dashboard')->with('error', 'Pagamento não concluído.');
    }
}


