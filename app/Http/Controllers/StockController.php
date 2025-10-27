<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('stock.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        // Lista de produtos com filtros/sort/paginação
        $productsQuery = Product::where('tenant_id', $tenantId);
        $sort = $request->get('sort', 'name'); // name|sku
        $direction = $request->get('direction', 'asc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'asc'; }
        if (!in_array($sort, ['name','sku'], true)) { $sort = 'name'; }
        $productsQuery->orderBy($sort, $direction);
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $products = $productsQuery->paginate($perPage)->appends($request->query());

        // Mapear saldos atuais (compatível com schemas antigos e novos)
        $balances = [];
        foreach ($products as $p) {
            $entry = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $p->id)
                ->where(function ($q) {
                    $q->where('movement_type', 'in')
                      ->orWhere(function ($q2) { $q2->whereNull('movement_type')->whereIn('type', ['entry', 'adjustment']); });
                })
                ->sum('quantity');

            $exit = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $p->id)
                ->where(function ($q) {
                    $q->where('movement_type', 'out')
                      ->orWhere(function ($q2) { $q2->whereNull('movement_type')->where('type', 'exit'); });
                })
                ->sum('quantity');

            $balances[$p->id] = (float) $entry - (float) $exit;
        }

        $movements = StockMovement::where('tenant_id', $tenantId)
            ->with('product')
            ->orderByDesc('id')
            ->paginate(10);
        return view('stock.index', compact('products', 'balances', 'movements'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('stock.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $products = Product::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('stock.create', compact('products'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('stock.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entry,exit,adjustment',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'nullable|numeric|min:0',
            'document' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);

        // Garantir escopo do tenant
        $product = Product::findOrFail($validated['product_id']);
        abort_unless($product->tenant_id === $tenantId, 403);

        StockMovement::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('stock.index')->with('success', 'Movimento registrado.');
    }

    public function edit(\App\Models\StockMovement $movement)
    {
        abort_unless(auth()->user()->hasPermission('stock.edit'), 403);
        abort_unless($movement->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $products = Product::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('stock.edit', compact('movement','products'));
    }

    public function update(Request $request, \App\Models\StockMovement $movement)
    {
        abort_unless(auth()->user()->hasPermission('stock.edit'), 403);
        abort_unless($movement->tenant_id === auth()->user()->tenant_id, 403);
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:entry,exit,adjustment',
            'quantity' => 'required|numeric|min:0.001',
            'unit_price' => 'nullable|numeric|min:0',
            'document' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:500',
        ]);
        $product = Product::findOrFail($validated['product_id']);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        $movement->update($validated);
        return redirect()->route('stock.index')->with('success', 'Movimento atualizado.');
    }

    public function kardex(Product $product, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('stock.view'), 403);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $type = $request->get('type'); // entry|exit|adjustment|all

        $q = StockMovement::where('tenant_id', $tenantId)->where('product_id', $product->id);
        if ($dateFrom) { $q->whereDate('created_at', '>=', $dateFrom); }
        if ($dateTo) { $q->whereDate('created_at', '<=', $dateTo); }
        if (in_array($type, ['entry','exit','adjustment'], true)) { $q->where('type', $type); }
        $q->orderBy('created_at');
        $movements = $q->paginate(50)->appends($request->query());

        // Saldo acumulado por movimento (Kardex)
        $entryTotal = StockMovement::where('tenant_id', $tenantId)->where('product_id', $product->id)->whereIn('type', ['entry','adjustment'])->sum('quantity');
        $exitTotal = StockMovement::where('tenant_id', $tenantId)->where('product_id', $product->id)->where('type', 'exit')->sum('quantity');
        $currentBalance = (float)$entryTotal - (float)$exitTotal;

        return view('stock.kardex', compact('product','movements','currentBalance','dateFrom','dateTo','type'));
    }

    public function movements(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('stock.view'), 403);
        $tenantId = auth()->user()->tenant_id;

        // Query base para movimentos
        $query = StockMovement::where('tenant_id', $tenantId)->with(['product', 'user']);

        // Filtros
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('product')) {
            $productSearch = trim($request->product);
            $query->whereHas('product', function($q) use ($productSearch) {
                $q->where('name', 'like', "%{$productSearch}%")
                  ->orWhere('sku', 'like', "%{$productSearch}%");
            });
        }
        if ($request->filled('type') && in_array($request->type, ['entry', 'exit', 'adjustment'])) {
            $query->where('type', $request->type);
        }
        if ($request->filled('user_id')) {
            $query->where('created_by', $request->user_id);
        }

        // Ordenação
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'desc'; }
        if (!in_array($sort, ['created_at','product_id','type','quantity','unit_price'], true)) { $sort = 'created_at'; }
        
        $query->orderBy($sort, $direction);

        // Paginação
        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 10) { $perPage = 10; }
        if ($perPage > 100) { $perPage = 100; }

        $movements = $query->paginate($perPage)->appends($request->query());

        // Estatísticas
        $stats = [
            'total_entries' => StockMovement::where('tenant_id', $tenantId)->where('type', 'entry')->sum('quantity'),
            'total_exits' => StockMovement::where('tenant_id', $tenantId)->where('type', 'exit')->sum('quantity'),
            'total_adjustments' => StockMovement::where('tenant_id', $tenantId)->where('type', 'adjustment')->sum('quantity'),
            'total_movements' => StockMovement::where('tenant_id', $tenantId)->count(),
        ];

        // Usuários para filtro
        $users = \App\Models\User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);

        return view('stock.movements', compact('movements', 'stats', 'users'));
    }

    public function reverse(\App\Models\StockMovement $movement)
    {
        abort_unless(auth()->user()->hasPermission('stock.edit'), 403);
        abort_unless($movement->tenant_id === auth()->user()->tenant_id, 403);

        $reverseType = $movement->type === 'entry' ? 'exit' : ($movement->type === 'exit' ? 'entry' : 'adjustment');
        if ($reverseType === 'adjustment') {
            return back()->with('error', 'Estorno automático não disponível para ajustes.');
        }

        \App\Models\StockMovement::create([
            'tenant_id' => $movement->tenant_id,
            'product_id' => $movement->product_id,
            'type' => $reverseType,
            'quantity' => $movement->quantity,
            'unit_price' => $movement->unit_price,
            'document' => $movement->document,
            'note' => 'Estorno automático do movimento #'.$movement->id,
        ]);

        return back()->with('success', 'Estorno registrado no Kardex.');
    }
}


