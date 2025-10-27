<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('carriers.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = Carrier::where('tenant_id', $tenantId);
        if ($s = trim($request->get('search', ''))) {
            $q->where(function($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                   ->orWhere('trade_name', 'like', "%{$s}%")
                   ->orWhere('cnpj', 'like', "%{$s}%")
                   ->orWhere('vehicle_plate', 'like', "%{$s}%");
            });
        }
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $sortField = in_array($request->get('sort_field'), ['name','trade_name','cnpj','created_at']) ? $request->get('sort_field') : 'name';
        $sortDir = $request->get('sort_direction') === 'desc' ? 'desc' : 'asc';
        $carriers = $q->orderBy($sortField, $sortDir)->paginate($perPage)->appends($request->query());
        return view('carriers.index', compact('carriers'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('carriers.create'), 403);
        return view('carriers.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('carriers.create'), 403);
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'ie' => 'nullable|string|max:30',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'vehicle_plate' => 'nullable|string|max:10',
            'vehicle_state' => 'nullable|string|max:2',
            'rntc' => 'nullable|string|max:20',
            'active' => 'nullable|boolean',
        ]);
        $v['tenant_id'] = auth()->user()->tenant_id;
        $v['active'] = (bool)($request->boolean('active'));
        Carrier::create($v);
        return redirect()->route('carriers.index')->with('success', 'Transportadora criada.');
    }

    public function edit(Carrier $carrier)
    {
        abort_unless(auth()->user()->hasPermission('carriers.edit'), 403);
        abort_unless($carrier->tenant_id === auth()->user()->tenant_id, 403);
        return view('carriers.edit', compact('carrier'));
    }

    public function update(Request $request, Carrier $carrier)
    {
        abort_unless(auth()->user()->hasPermission('carriers.edit'), 403);
        abort_unless($carrier->tenant_id === auth()->user()->tenant_id, 403);
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'ie' => 'nullable|string|max:30',
            'street' => 'nullable|string|max:255',
            'number' => 'nullable|string|max:30',
            'complement' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'vehicle_plate' => 'nullable|string|max:10',
            'vehicle_state' => 'nullable|string|max:2',
            'rntc' => 'nullable|string|max:20',
            'active' => 'nullable|boolean',
        ]);
        $v['active'] = (bool)($request->boolean('active'));
        $carrier->update($v);
        return redirect()->route('carriers.index')->with('success', 'Transportadora atualizada.');
    }

    public function destroy(Carrier $carrier)
    {
        abort_unless(auth()->user()->hasPermission('carriers.delete'), 403);
        abort_unless($carrier->tenant_id === auth()->user()->tenant_id, 403);
        $carrier->delete();
        return redirect()->route('carriers.index')->with('success', 'Transportadora excluída.');
    }
}
