<?php

namespace App\Http\Controllers;

use App\Models\NcmRule;
use Illuminate\Http\Request;

class NcmRuleController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        
        $query = NcmRule::query();
        
        // Filtro por código NCM
        if ($request->filled('ncm')) {
            $query->where('ncm', 'like', '%' . $request->ncm . '%');
        }
        
        // Filtro por observação/descrição
        if ($request->filled('note')) {
            $query->where('note', 'like', '%' . $request->note . '%');
        }
        
        // Filtro por GTIN obrigatório
        if ($request->has('requires_gtin') && $request->requires_gtin !== '') {
            $query->where('requires_gtin', $request->requires_gtin);
        }
        
        // Ordenação
        $sortBy = $request->get('sort_by', 'ncm');
        $sortDirection = $request->get('sort_direction', 'asc');
        
        if (in_array($sortBy, ['ncm', 'note', 'requires_gtin', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('ncm', 'asc');
        }
        
        // Registros por página
        $perPage = $request->get('per_page', 20);
        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 20;
        }
        
        $rules = $query->paginate($perPage)->withQueryString();
        
        return view('ncm_rules.index', compact('rules'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        return view('ncm_rules.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        $data = $request->validate([
            'ncm' => ['required','string','max:20','regex:/^\d{4,20}$/'],
            'requires_gtin' => ['nullable','in:0,1'],
            'note' => ['nullable','string','max:255'],
        ]);
        $data['requires_gtin'] = (bool)($data['requires_gtin'] ?? false);
        // Evitar duplicatas do mesmo NCM
        $existing = NcmRule::where('ncm', $data['ncm'])->first();
        if ($existing) {
            $existing->update(['requires_gtin' => $data['requires_gtin'], 'note' => $data['note'] ?? null]);
        } else {
            NcmRule::create($data);
        }
        return redirect()->route('ncm_rules.index')->with('success','Regra NCM salva.');
    }

    public function edit(NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        return view('ncm_rules.edit', ['rule' => $ncm_rule]);
    }

    public function update(Request $request, NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        $data = $request->validate([
            'ncm' => ['required','string','max:20','regex:/^\d{4,20}$/'],
            'requires_gtin' => ['nullable','in:0,1'],
            'note' => ['nullable','string','max:255'],
        ]);
        $data['requires_gtin'] = (bool)($data['requires_gtin'] ?? false);
        // Se NCM foi alterado para outro já existente, mesclar/atualizar aquele e remover este
        $other = NcmRule::where('ncm', $data['ncm'])->where('id','<>',$ncm_rule->id)->first();
        if ($other) {
            $other->update(['requires_gtin' => $data['requires_gtin'], 'note' => $data['note'] ?? null]);
            $ncm_rule->delete();
        } else {
            $ncm_rule->update($data);
        }
        return redirect()->route('ncm_rules.index')->with('success','Regra NCM atualizada.');
    }

    public function destroy(NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->hasPermission('tax_config.edit'), 403);
        $ncm_rule->delete();
        return redirect()->route('ncm_rules.index')->with('success','Regra excluída.');
    }
}


