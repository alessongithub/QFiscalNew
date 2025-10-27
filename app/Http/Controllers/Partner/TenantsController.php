<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantsController extends Controller
{
    public function index(Request $request)
    {
        $partnerId = auth('partner')->id() ? auth('partner')->user()->partner_id : null;
        $q = trim((string) $request->get('q', ''));

        $tenants = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->when($q !== '', function($query) use ($q) {
                $query->where(function($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('fantasy_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('cnpj', 'like', "%".preg_replace('/[^0-9]/','',$q)."%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('partner.tenants.index', compact('tenants','q'));
    }
}



