<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('categories.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $query = Category::where('tenant_id', $tenantId)->with('parent');

        // Filtros
        if ($request->filled('search')) {
            $s = trim((string)$request->get('search'));
            $query->where(function($q) use ($s){
                $q->where('name','like',"%{$s}%")
                  ->orWhereHas('parent', function($qp) use ($s){ $qp->where('name','like',"%{$s}%"); });
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') { $query->where('active', 1); }
            elseif ($request->status === 'inactive') { $query->where('active', 0); }
        }

        // Ordenação
        $sortField = $request->get('sort', 'name'); // name|parent|created_at
        $direction = $request->get('direction', 'asc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'asc'; }
        if ($sortField === 'parent') {
            $query->orderBy('parent_id', $direction)->orderBy('name');
        } elseif (in_array($sortField, ['name','created_at'], true)) {
            $query->orderBy($sortField, $direction);
        } else {
            $query->orderBy('parent_id')->orderBy('name');
        }

        // Paginação
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $categories = $query->paginate($perPage)->appends($request->query());

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('categories.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $parents = Category::where('tenant_id', $tenantId)->whereNull('parent_id')->orderBy('name')->get();
        return view('categories.create', compact('parents'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('categories.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'active' => 'nullable|boolean',
        ]);
        if (!empty($v['parent_id'])) {
            $parent = Category::findOrFail($v['parent_id']);
            abort_unless($parent->tenant_id === $tenantId, 403);
        }
        $v['tenant_id'] = $tenantId;
        $v['active'] = $request->boolean('active', true);
        $cat = Category::create($v);
        \App\Models\CategoryAudit::create([
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'category_id' => $cat->id,
            'action' => 'created',
            'changes' => ['name' => $cat->name, 'active' => $cat->active, 'parent_id' => $cat->parent_id],
        ]);
        return redirect()->route('categories.index')->with('success','Categoria criada.');
    }

    public function edit(Category $category)
    {
        abort_unless(auth()->user()->hasPermission('categories.edit'), 403);
        abort_unless($category->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $parents = Category::where('tenant_id', $tenantId)->whereNull('parent_id')->where('id','<>',$category->id)->orderBy('name')->get();
        return view('categories.edit', compact('category','parents'));
    }

    public function update(Request $request, Category $category)
    {
        abort_unless(auth()->user()->hasPermission('categories.edit'), 403);
        abort_unless($category->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'active' => 'nullable|boolean',
        ]);
        if (!empty($v['parent_id'])) {
            $parent = Category::findOrFail($v['parent_id']);
            abort_unless($parent->tenant_id === $tenantId, 403);
        }
        $v['active'] = $request->boolean('active', true);
        $before = $category->replicate();
        $category->update($v);
        $changes = [];
        foreach (['name','active','parent_id'] as $f) {
            if ($before->$f != $category->$f) {
                $changes[$f] = ['old' => $before->$f, 'new' => $category->$f];
            }
        }
        if (!empty($changes)) {
            \App\Models\CategoryAudit::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'category_id' => $category->id,
                'action' => 'updated',
                'changes' => $changes,
            ]);
        }
        return redirect()->route('categories.index')->with('success','Categoria atualizada.');
    }

    public function destroy(Category $category)
    {
        abort_unless(auth()->user()->hasPermission('categories.delete'), 403);
        abort_unless($category->tenant_id === auth()->user()->tenant_id, 403);
        $snap = ['name' => $category->name, 'active' => $category->active, 'parent_id' => $category->parent_id];
        $category->delete();
        \App\Models\CategoryAudit::create([
            'tenant_id' => auth()->user()->tenant_id,
            'user_id' => auth()->id(),
            'category_id' => null,
            'action' => 'deleted',
            'changes' => $snap,
        ]);
        return redirect()->route('categories.index')->with('success','Categoria excluída.');
    }

    // Retorna um CFOP padrão associado à categoria (placeholder: null por enquanto)
    public function defaultCfop(Category $category)
    {
        abort_unless($category->tenant_id === auth()->user()->tenant_id, 403);
        // No futuro poderemos buscar em tabela de configuração; por agora, devolve null com estrutura estável
        return response()->json(['cfop' => null]);
    }
}


