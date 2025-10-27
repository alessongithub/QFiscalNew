<?php

namespace App\Http\Controllers;

use App\Models\GatewayConfig;
use App\Models\Invoice;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $tenant = auth()->user()->tenant;
        $planId = $request->integer('plan_id') ?: ($tenant->plan_id ?? null);
        $plan = $planId ? Plan::find($planId) : null;
        $config = GatewayConfig::current();
        return view('checkout.index', compact('tenant', 'plan', 'config'));
    }

    public function createPreference(Request $request)
    {
        \Log::info('Checkout createPreference started', [
            'plan_id' => $request->input('plan_id'),
            'user_id' => auth()->id(),
            'tenant_id' => auth()->user()->tenant->id ?? null
        ]);

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = auth()->user()->tenant;
        $plan = Plan::findOrFail($request->input('plan_id'));
        $config = GatewayConfig::current();

        \Log::info('Checkout data loaded', [
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

        $accessToken = $config->active_access_token;
        if (empty($accessToken)) {
            return back()->withErrors(['gateway' => 'Access Token do Mercado Pago não configurado. Configure em Admin > Gateway.']);
        }
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
                // habilita cartão crédito e pix
                'excluded_payment_types' => [ ['id' => 'ticket'], ['id' => 'atm'] ],
                'installments' => 1,
                'default_payment_method_id' => null,
            ],
            'binary_mode' => true,
            'back_urls' => [
                'success' => 'http://localhost:8000/checkout/success',
                'pending' => 'http://localhost:8000/checkout/pending',
                'failure' => 'http://localhost:8000/checkout/failure',
            ],
            'notification_url' => 'http://localhost:8000/webhooks/mercadopago',
        ];

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
        $invoice->external_preference_id = $pref['id'] ?? null;
        $invoice->save();

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


