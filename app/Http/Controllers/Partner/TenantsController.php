<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Http\Request;

class TenantsController extends Controller
{
    public function index(Request $request)
    {
        // Verificar se é admin geral autenticado
        $isAdmin = auth()->check() && auth()->user()->is_admin;
        
        // Se for admin, mostrar todos os tenants; senão, mostrar apenas do parceiro
        $partnerId = null;
        if (!$isAdmin) {
            $partnerId = auth('partner')->id() ? auth('partner')->user()->partner_id : null;
        }
        
        $search = trim((string) $request->get('search', ''));
        $planId = $request->get('plan_id');
        $expired = $request->has('expired') && $request->get('expired') == '1';
        $perPage = (int) $request->get('per_page', 15);

        $query = Tenant::with(['plan', 'partner'])
            ->when($partnerId !== null, function($q) use ($partnerId) {
                $q->where('partner_id', $partnerId);
            })
            ->when($search !== '', function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('fantasy_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('cnpj', 'like', "%".preg_replace('/[^0-9]/','',$search)."%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%");
                });
            })
            ->when($planId !== null && $planId !== '', function($q) use ($planId) {
                if ($planId === 'none') {
                    $q->whereNull('plan_id');
                } else {
                    $q->where('plan_id', $planId);
                }
            })
            ->when($expired, function($q) {
                $q->whereNotNull('plan_expires_at')
                  ->whereDate('plan_expires_at', '<', now()->toDateString());
            })
            ->orderBy('name');

        $tenants = $query->paginate($perPage)->withQueryString();
        $plans = Plan::where('active', true)->orderBy('name')->get();

        return view('partner.tenants.index', compact('tenants', 'search', 'planId', 'expired', 'perPage', 'isAdmin', 'plans'));
    }
}



