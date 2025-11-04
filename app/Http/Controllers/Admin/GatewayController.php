<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewayConfig;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function edit()
    {
        $config = GatewayConfig::current();
        $globalFine = \App\Models\Setting::getGlobal('boleto.fine_percent', '0');
        $globalInterest = \App\Models\Setting::getGlobal('boleto.interest_month_percent', '0');
        $globalBoletoMpFeeFixed = \App\Models\Setting::getGlobal('boleto.mp_fee_fixed', '1.99');
        return view('admin.gateway', compact('config','globalFine','globalInterest','globalBoletoMpFeeFixed'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:sandbox,production',
            'public_key_sandbox' => 'nullable|string',
            'access_token_sandbox' => 'nullable|string',
            'public_key_production' => 'nullable|string',
            'access_token_production' => 'nullable|string',
            'client_id_production' => 'nullable|string',
            'client_secret_production' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'block_login_after_days' => 'required|integer|min:0|max:30',
            // Boleto defaults (globais)
            'global_boleto_fine_percent' => 'nullable|numeric|min:0|max:2',
            'global_boleto_interest_month_percent' => 'nullable|numeric|min:0|max:1',
            'global_boleto_mp_fee_fixed' => 'nullable|numeric|min:0|max:50',
        ]);

        $config = GatewayConfig::query()->first();
        if (!$config) {
            $config = new GatewayConfig();
        }
        $config->fill(array_merge($validated, ['provider' => 'mercadopago']));
        $config->save();

        // Persistir padrões globais de boleto
        if ($request->has('global_boleto_fine_percent')) {
            \App\Models\Setting::setGlobal('boleto.fine_percent', (string) $request->input('global_boleto_fine_percent', '0'));
        }
        if ($request->has('global_boleto_interest_month_percent')) {
            \App\Models\Setting::setGlobal('boleto.interest_month_percent', (string) $request->input('global_boleto_interest_month_percent', '0'));
        }
        if ($request->has('global_boleto_mp_fee_fixed')) {
            \App\Models\Setting::setGlobal('boleto.mp_fee_fixed', (string) $request->input('global_boleto_mp_fee_fixed', '1.99'));
        }

        return redirect()->route('admin.gateway.edit')->with('success', 'Configurações do gateway atualizadas.');
    }
}


