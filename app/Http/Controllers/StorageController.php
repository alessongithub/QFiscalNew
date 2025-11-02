<?php

namespace App\Http\Controllers;

use App\Models\StorageAddon;
use App\Services\StorageCalculator;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $usage = $tenant->storageUsage;
        $plan = $tenant->plan;

        if (!$usage) {
            // Primeira carga: calcular e popular
            app(StorageCalculator::class)->updateTenantUsage($tenant);
            $usage = $tenant->fresh()->storageUsage;
        }

        return view('storage.index', compact('usage', 'plan', 'tenant'));
    }

    public function upgrade()
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);
        
        $plan = $tenant->plan;
        abort_unless($plan, 404, 'Plano não encontrado para este tenant.');
        
        return view('storage.upgrade', compact('plan'));
    }

    public function purchaseAddon(Request $request)
    {
        $request->validate([
            'type' => 'required|in:data,files',
            'quantity_mb' => 'required|integer|min:50',
        ]);

        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);
        
        $plan = $tenant->plan;
        abort_unless($plan, 404, 'Plano não encontrado para este tenant.');
        
        // Garantir que features seja um array
        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
        
        $priceData = (float)($features['additional_data_price'] ?? 9.90);
        $priceFiles = (float)($features['additional_files_price'] ?? 9.90);
        $unitPrice = $request->input('type') === 'data' ? $priceData : $priceFiles;

        // Criar addon e redirecionar para checkout
        $addon = StorageAddon::create([
            'tenant_id' => $tenant->id,
            'type' => $request->input('type'),
            'quantity_mb' => (int)$request->input('quantity_mb'),
            'price' => $unitPrice,
            'status' => 'pending',
        ]);

        // Redirecionar para checkout com addon_id
        return redirect()->route('checkout.index', ['addon_id' => $addon->id]);
    }
}


