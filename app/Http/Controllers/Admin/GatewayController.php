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
        return view('admin.gateway', compact('config'));
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
        ]);

        $config = GatewayConfig::query()->first();
        if (!$config) {
            $config = new GatewayConfig();
        }
        $config->fill(array_merge($validated, ['provider' => 'mercadopago']));
        $config->save();

        return redirect()->route('admin.gateway.edit')->with('success', 'Configurações do gateway atualizadas.');
    }
}


