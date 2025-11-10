<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\TenantTaxConfig;
use Illuminate\Http\Request;
use App\Traits\StorageLimitCheck;

class ProductController extends Controller
{
    use StorageLimitCheck;
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $query = Product::where('tenant_id', $tenantId);

        // Filtro por tipo (product/service)
        if ($request->filled('type') && in_array($request->type, ['product', 'service'], true)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('ean', 'like', "%{$s}%")
                  ->orWhere('ncm', 'like', "%{$s}%");
            });
        }

        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'asc'; }
        $query->orderBy($sort, $direction);

        $perPage = (int) $request->get('per_page', 10);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }

        $products = $query->paginate($perPage)->appends($request->query());
        // Calcular saldos atuais apenas dos produtos da página
        $balances = [];
        if ($products->count() > 0) {
            $tenantId = auth()->user()->tenant_id;
            $ids = $products->pluck('id')->all();
            foreach ($ids as $pid) {
                $entry = \App\Models\StockMovement::where('tenant_id', $tenantId)
                    ->where('product_id', $pid)
                    ->where(function ($q) {
                        $q->where('movement_type', 'in')
                          ->orWhere(function ($q2) { $q2->whereNull('movement_type')->whereIn('type', ['entry','adjustment']); });
                    })
                    ->sum('quantity');

                $exit = \App\Models\StockMovement::where('tenant_id', $tenantId)
                    ->where('product_id', $pid)
                    ->where(function ($q) {
                        $q->where('movement_type', 'out')
                          ->orWhere(function ($q2) { $q2->whereNull('movement_type')->where('type', 'exit'); });
                    })
                    ->sum('quantity');

                $balances[$pid] = (float) $entry - (float) $exit;
            }
        }
        return view('products.index', compact('products','balances'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $categories = Category::where('tenant_id', $tenantId)->where('active',1)->orderBy('parent_id')->orderBy('name')->get();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('active',1)->orderBy('name')->get();
        $taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();
        return view('products.create', compact('categories','suppliers','taxConfig'));
    }

    public function show(Product $product)
    {
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        return view('products.show', compact('product'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.create'), 403);
        // Verificar limite de produtos do plano
        $tenant = auth()->user()->tenant;
        $plan = $tenant->plan;
        $features = is_array($plan->features) ? $plan->features : (json_decode($plan->features, true) ?? []);
        $maxProducts = $features['max_products'] ?? null; // null = sem limite definido; -1 = ilimitado
        if ($maxProducts !== null && (int)$maxProducts !== -1) {
            $count = Product::where('tenant_id', $tenant->id)->count();
            if ($count >= (int)$maxProducts) {
                return back()->with('error', 'Limite de produtos do seu plano foi atingido. Faça upgrade para cadastrar mais produtos.');
            }
        }

        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'exists' => 'Selecione uma :attribute válida.',
            'numeric' => 'O campo :attribute deve ser numérico.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'regex' => 'O campo :attribute está em formato inválido.',
            'in' => 'O campo :attribute possui um valor inválido.',
            'size' => 'O campo :attribute deve ter exatamente :size caracteres.'
        ];
        $attributes = [
            'category_id' => 'categoria',
            'supplier_id' => 'fornecedor',
            'name' => 'nome',
            'sku' => 'SKU',
            'ean' => 'GTIN',
            'unit' => 'unidade',
            'ncm' => 'NCM',
            'cest' => 'CEST',
            'cfop' => 'CFOP',
            'origin' => 'origem',
            'csosn' => 'CSOSN',
            'cst_icms' => 'CST ICMS',
            'cst_pis' => 'CST PIS',
            'cst_cofins' => 'CST COFINS',
            'aliquota_icms' => 'alíquota ICMS',
            'aliquota_pis' => 'alíquota PIS',
            'aliquota_cofins' => 'alíquota COFINS',
            'price' => 'preço',
            'type' => 'tipo',
            'active' => 'ativo',
        ];
        
        // Processar NCM e CEST removendo pontos e caracteres especiais
        $ncm = $request->input('ncm');
        $cest = $request->input('cest');
        
        if ($ncm) {
            $ncm = preg_replace('/[^0-9]/', '', $ncm); // Remove tudo exceto números
            $request->merge(['ncm' => $ncm]);
        }
        
        if ($cest) {
            $cest = preg_replace('/[^0-9]/', '', $cest); // Remove tudo exceto números
            $request->merge(['cest' => $cest]);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'ean' => ['nullable','string','max:20','regex:/^(sem gtin|\d{8}|\d{12}|\d{13}|\d{14})$/i'],
            'unit' => 'required|string|max:6',
            'ncm' => 'nullable|string|size:8|regex:/^\d{8}$/',
            'cest' => 'nullable|string|size:7|regex:/^\d{7}$/',
            'cfop' => 'nullable|string|max:4',
            'origin' => 'nullable|integer|between:0,8',
            'csosn' => 'nullable|string|max:3',
            'cst_icms' => 'nullable|string|max:3',
            'cst_pis' => 'nullable|string|max:2',
            'cst_cofins' => 'nullable|string|max:2',
            'aliquota_icms' => 'nullable|numeric|min:0',
            'aliquota_pis' => 'nullable|numeric|min:0',
            'aliquota_cofins' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:product,service',
            'active' => 'boolean',
        ], $messages, $attributes);

        // Garantir que campos de texto opcionais não sejam enviados como null ao banco (evita NOT NULL)
        foreach (['sku','ean','ncm','cest','cfop','csosn','cst_icms','cst_pis','cst_cofins'] as $field) {
            if (!array_key_exists($field, $validated) || $validated[$field] === null) {
                $validated[$field] = '';
            }
        }
        // Normalizar origem para inteiro; fallback 0 (Nacional) se vazio para compatibilidade com colunas NOT NULL
        $validated['origin'] = $request->filled('origin') ? (int) $request->input('origin') : 0;
        // Se vazio, padroniza texto "Sem GTIN"
        if (isset($validated['ean']) && trim($validated['ean']) === '') {
            $validated['ean'] = 'Sem GTIN';
        }

        // Garantir que a categoria pertence ao tenant (se informada)
        if (!empty($validated['category_id'])) {
            $cat = Category::findOrFail($validated['category_id']);
            abort_unless($cat->tenant_id === $tenant->id, 403);
        }

        $validated['tenant_id'] = auth()->user()->tenant_id;
        if (!empty($validated['supplier_id'])) {
            $sup = Supplier::findOrFail($validated['supplier_id']);
            abort_unless($sup->tenant_id === $tenant->id, 403);
        }
        $validated['active'] = $request->boolean('active', true);
        
        // Verificar limite de storage de dados antes de criar
        $estimatedSize = 5120; // ~5 KB estimado por produto
        if (!$this->checkStorageLimit('data', $estimatedSize)) {
            return back()->withErrors([
                'storage' => $this->getStorageLimitErrorMessage('data')
            ])->withInput();
        }
        
        // Verificar limite de arquivos se houver upload de imagem
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileSize = $file->getSize();
            
            if (!$this->checkStorageLimit('files', $fileSize)) {
                return back()->withErrors([
                    'image' => $this->getStorageLimitErrorMessage('files')
                ])->withInput();
            }
        }
        
        try {
            $product = Product::create($validated);
            // Auditoria de criação
            \App\Models\ProductAudit::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'action' => 'created',
                'changes' => [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $product->price,
                    'ncm' => $product->ncm,
                    'active' => $product->active,
                ],
                'notes' => 'Produto criado',
            ]);
            
            // Invalidar cache de storage após criar
            $this->invalidateStorageCache();
            
            return redirect()->route('products.index')->with('success', 'Produto criado com sucesso!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Tratar erros específicos do banco de dados
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'aliquota_icms') !== false) {
                return back()->withErrors(['aliquota_icms' => 'Alíquota ICMS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'aliquota_pis') !== false) {
                return back()->withErrors(['aliquota_pis' => 'Alíquota PIS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'aliquota_cofins') !== false) {
                return back()->withErrors(['aliquota_cofins' => 'Alíquota COFINS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'price') !== false) {
                return back()->withErrors(['price' => 'Preço muito alto. Máximo permitido: R$ 99.999.999,99'])->withInput();
            }
            if (strpos($errorMessage, 'ncm') !== false) {
                return back()->withErrors(['ncm' => 'NCM inválido. Deve ter exatamente 8 dígitos'])->withInput();
            }
            if (strpos($errorMessage, 'cest') !== false) {
                return back()->withErrors(['cest' => 'CEST inválido. Deve ter exatamente 7 dígitos'])->withInput();
            }
            
            // Erro genérico
            return back()->withErrors(['general' => 'Erro ao salvar produto. Verifique os dados informados.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Erro inesperado: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Product $product)
    {
        abort_unless(auth()->user()->hasPermission('products.edit'), 403);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        if (!$product->active) {
            return redirect()->route('products.index')->with('error', 'Produto desativado. Reative para editar.');
        }
        $tenantId = auth()->user()->tenant_id;
        $categories = Category::where('tenant_id', $tenantId)->where('active',1)->orderBy('parent_id')->orderBy('name')->get();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('active',1)->orderBy('name')->get();
        $taxConfig = TenantTaxConfig::where('tenant_id', $tenantId)->first();
        return view('products.edit', compact('product','categories','suppliers','taxConfig'));
    }

    public function update(Request $request, Product $product)
    {
        abort_unless(auth()->user()->hasPermission('products.edit'), 403);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        if (!$product->active) {
            return redirect()->route('products.index')->with('error', 'Produto desativado. Reative para editar.');
        }

        $messages = [
            'required' => 'O campo :attribute é obrigatório.',
            'exists' => 'Selecione uma :attribute válida.',
            'numeric' => 'O campo :attribute deve ser numérico.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'regex' => 'O campo :attribute está em formato inválido.',
            'in' => 'O campo :attribute possui um valor inválido.',
            'size' => 'O campo :attribute deve ter exatamente :size caracteres.'
        ];
        $attributes = [
            'category_id' => 'categoria',
            'supplier_id' => 'fornecedor',
            'name' => 'nome',
            'sku' => 'SKU',
            'ean' => 'GTIN',
            'unit' => 'unidade',
            'ncm' => 'NCM',
            'cest' => 'CEST',
            'cfop' => 'CFOP',
            'origin' => 'origem',
            'csosn' => 'CSOSN',
            'cst_icms' => 'CST ICMS',
            'cst_pis' => 'CST PIS',
            'cst_cofins' => 'CST COFINS',
            'aliquota_icms' => 'alíquota ICMS',
            'aliquota_pis' => 'alíquota PIS',
            'aliquota_cofins' => 'alíquota COFINS',
            'price' => 'preço',
            'type' => 'tipo',
            'active' => 'ativo',
        ];
        
        // Processar NCM e CEST removendo pontos e caracteres especiais
        $ncm = $request->input('ncm');
        $cest = $request->input('cest');
        
        if ($ncm) {
            $ncm = preg_replace('/[^0-9]/', '', $ncm); // Remove tudo exceto números
            $request->merge(['ncm' => $ncm]);
        }
        
        if ($cest) {
            $cest = preg_replace('/[^0-9]/', '', $cest); // Remove tudo exceto números
            $request->merge(['cest' => $cest]);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'ean' => ['nullable','string','max:20','regex:/^(sem gtin|\d{8}|\d{12}|\d{13}|\d{14})$/i'],
            'unit' => 'required|string|max:6',
            'ncm' => 'nullable|string|size:8|regex:/^\d{8}$/',
            'cest' => 'nullable|string|size:7|regex:/^\d{7}$/',
            'cfop' => 'nullable|string|max:4',
            'origin' => 'nullable|integer|between:0,8',
            'csosn' => 'nullable|string|max:3',
            'cst_icms' => 'nullable|string|max:3',
            'cst_pis' => 'nullable|string|max:2',
            'cst_cofins' => 'nullable|string|max:2',
            'aliquota_icms' => 'nullable|numeric|min:0',
            'aliquota_pis' => 'nullable|numeric|min:0',
            'aliquota_cofins' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:product,service',
            'active' => 'boolean',
        ], $messages, $attributes);

        // Evitar null nos campos de texto opcionais
        foreach (['sku','ean','ncm','cest','cfop','csosn','cst_icms','cst_pis','cst_cofins'] as $field) {
            if (!array_key_exists($field, $validated) || $validated[$field] === null) {
                $validated[$field] = '';
            }
        }
        // Normalizar origem para inteiro; fallback 0 (Nacional) se vazio para compatibilidade com colunas NOT NULL
        $validated['origin'] = $request->filled('origin') ? (int) $request->input('origin') : 0;
        if (isset($validated['ean']) && trim($validated['ean']) === '') {
            $validated['ean'] = 'Sem GTIN';
        }

        // Garantir que a categoria pertence ao tenant (se informada)
        if (!empty($validated['category_id'])) {
            $cat = Category::findOrFail($validated['category_id']);
            abort_unless($cat->tenant_id === auth()->user()->tenant_id, 403);
        }

        if (!empty($validated['supplier_id'])) {
            $sup = Supplier::findOrFail($validated['supplier_id']);
            abort_unless($sup->tenant_id === auth()->user()->tenant_id, 403);
        }
        $validated['active'] = $request->boolean('active', true);
        
        try {
            $before = $product->replicate();
            $product->update($validated);

            // Auditoria de atualização (diferenças relevantes)
            $fields = ['name','sku','price','ncm','cest','cfop','origin','csosn','cst_icms','cst_pis','cst_cofins','aliquota_icms','aliquota_pis','aliquota_cofins','active'];
            $changes = [];
            foreach ($fields as $f) {
                if ($before->$f != $product->$f) {
                    $changes[$f] = ['old' => $before->$f, 'new' => $product->$f];
                }
            }
            if (!empty($changes)) {
                \App\Models\ProductAudit::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'user_id' => auth()->id(),
                    'product_id' => $product->id,
                    'action' => 'updated',
                    'changes' => $changes,
                    'notes' => 'Produto atualizado',
                ]);
            }

            return redirect()->route('products.index')->with('success', 'Produto atualizado com sucesso!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Tratar erros específicos do banco de dados
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            if (strpos($errorMessage, 'aliquota_icms') !== false) {
                return back()->withErrors(['aliquota_icms' => 'Alíquota ICMS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'aliquota_pis') !== false) {
                return back()->withErrors(['aliquota_pis' => 'Alíquota PIS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'aliquota_cofins') !== false) {
                return back()->withErrors(['aliquota_cofins' => 'Alíquota COFINS muito alta. Máximo permitido: 999.99%'])->withInput();
            }
            if (strpos($errorMessage, 'price') !== false) {
                return back()->withErrors(['price' => 'Preço muito alto. Máximo permitido: R$ 99.999.999,99'])->withInput();
            }
            if (strpos($errorMessage, 'ncm') !== false) {
                return back()->withErrors(['ncm' => 'NCM inválido. Deve ter exatamente 8 dígitos'])->withInput();
            }
            if (strpos($errorMessage, 'cest') !== false) {
                return back()->withErrors(['cest' => 'CEST inválido. Deve ter exatamente 7 dígitos'])->withInput();
            }
            
            // Erro genérico
            return back()->withErrors(['general' => 'Erro ao atualizar produto. Verifique os dados informados.'])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['general' => 'Erro inesperado: ' . $e->getMessage()])->withInput();
        }
    }

    public function destroy(Product $product)
    {
        abort_unless(auth()->user()->hasPermission('products.delete'), 403);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $hasExit = \App\Models\StockMovement::where('tenant_id', $tenantId)
            ->where('product_id', $product->id)
            ->where('type', 'exit')
            ->exists();
        if ($hasExit) {
            return redirect()->route('products.index')->with('error', 'Não é possível excluir: produto possui movimentações de saída. Desative-o.');
        }
        // Sem saídas: remover movimentos (entradas/ajustes) e excluir produto
        \App\Models\StockMovement::where('tenant_id', $tenantId)->where('product_id', $product->id)->delete();
        $snapshot = ['name' => $product->name, 'sku' => $product->sku, 'price' => $product->price, 'active' => $product->active];
        $product->delete();
        \App\Models\ProductAudit::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'product_id' => null,
            'action' => 'deleted',
            'changes' => $snapshot,
            'notes' => 'Produto excluído',
        ]);
        return redirect()->route('products.index')->with('success', 'Produto excluído com sucesso!');
    }

    public function toggleActive(Product $product)
    {
        abort_unless(auth()->user()->hasPermission('products.edit'), 403);
        abort_unless($product->tenant_id === auth()->user()->tenant_id, 403);
        $prev = (bool)$product->active;
        $product->active = !$product->active;
        $product->save();
        \App\Models\ProductAudit::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'action' => $product->active ? 'activated' : 'deactivated',
            'changes' => ['active' => ['old' => $prev, 'new' => (bool)$product->active]],
            'notes' => $product->active ? 'Produto ativado' : 'Produto desativado',
        ]);
        return back()->with('success', $product->active ? 'Produto ativado.' : 'Produto desativado.');
    }

    public function search(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $term = trim((string) $request->get('term', ''));
        $q = \App\Models\Product::where('tenant_id', $tenantId)
            ->where('active', 1);
        
        // Se for para stock/create, mostrar apenas produtos (não serviços)
        if ($request->get('for_stock') === 'true') {
            $q->where('type', 'product');
        } else {
            $q->whereIn('type', ['product','service']);
        }
        if ($term !== '') {
            $q->where(function ($qq) use ($term) {
                $qq->where('name', 'like', "%{$term}%")
                   ->orWhere('sku', 'like', "%{$term}%")
                   ->orWhere('ean', 'like', "%{$term}%")
                   ->orWhere('ncm', 'like', "%{$term}%")
                   ->orWhere('cfop', 'like', "%{$term}%");
            });
        }
        $products = $q->orderBy('name')->limit(10)->get(['id','name','unit','price','type']);
        // anexar saldo atual para produtos (não para serviços)
        $result = [];
        foreach ($products as $p) {
            $balance = null;
            if ($p->type === 'product') {
                $entry = \App\Models\StockMovement::where('tenant_id', $tenantId)
                    ->where('product_id', $p->id)
                    ->where(function ($q) {
                        $q->where('movement_type', 'in')
                          ->orWhere(function ($q2) { $q2->whereNull('movement_type')->whereIn('type', ['entry','adjustment']); });
                    })
                    ->sum('quantity');
                $exit = \App\Models\StockMovement::where('tenant_id', $tenantId)
                    ->where('product_id', $p->id)
                    ->where(function ($q) {
                        $q->where('movement_type', 'out')
                          ->orWhere(function ($q2) { $q2->whereNull('movement_type')->where('type', 'exit'); });
                    })
                    ->sum('quantity');
                $balance = (float) $entry - (float) $exit;
            }
            $result[] = [
                'id' => $p->id,
                'name' => $p->name,
                'unit' => $p->unit,
                'price' => (float) $p->price,
                'type' => $p->type,
                'balance' => $balance,
            ];
        }
        return response()->json($result);
    }
}
