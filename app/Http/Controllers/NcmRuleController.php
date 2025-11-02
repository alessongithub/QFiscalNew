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
        abort_unless(auth()->user()->is_admin, 403);
        return view('ncm_rules.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->is_admin, 403);
        $data = $request->validate([
            'ncm' => ['required','string','max:20','regex:/^[\d\.]{4,20}$/'],
            'requires_gtin' => ['nullable','in:0,1'],
            'note' => ['nullable','string','max:255'],
        ]);
        // Normalizar NCM (permitir com pontos na entrada, salvar apenas dígitos)
        $data['ncm'] = preg_replace('/\D+/', '', $data['ncm'] ?? '');
        $data['requires_gtin'] = (bool)($data['requires_gtin'] ?? false);
        // Evitar duplicatas do mesmo NCM
        $existing = NcmRule::where('ncm', $data['ncm'])->first();
        if ($existing) {
            $before = $existing->getOriginal();
            $existing->update(['requires_gtin' => $data['requires_gtin'], 'note' => $data['note'] ?? null]);
            $after = $existing->fresh();
            $changes = [];
            foreach (['requires_gtin','note'] as $f) {
                if (($before[$f] ?? null) != ($after->$f ?? null)) {
                    $changes[$f] = ['old' => $before[$f] ?? null, 'new' => $after->$f ?? null];
                }
            }
            if (!empty($changes)) {
                \App\Models\NcmRuleAudit::create([
                    'ncm_rule_id' => $existing->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'notes' => 'Regra NCM atualizada (merge por NCM)',
                    'changes' => $changes,
                ]);
            }
        } else {
            $rule = NcmRule::create($data);
            \App\Models\NcmRuleAudit::create([
                'ncm_rule_id' => $rule->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'notes' => 'Regra NCM criada',
                'changes' => [ 'ncm' => $rule->ncm, 'requires_gtin' => $rule->requires_gtin, 'note' => $rule->note ],
            ]);
        }
        return redirect()->route('ncm_rules.index')->with('success','Regra NCM salva.');
    }

    public function edit(NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->is_admin, 403);
        return view('ncm_rules.edit', ['rule' => $ncm_rule]);
    }

    public function update(Request $request, NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->is_admin, 403);
        $data = $request->validate([
            'ncm' => ['required','string','max:20','regex:/^[\d\.]{4,20}$/'],
            'requires_gtin' => ['nullable','in:0,1'],
            'note' => ['nullable','string','max:255'],
        ]);
        // Normalizar NCM (permitir com pontos na entrada, salvar apenas dígitos)
        $data['ncm'] = preg_replace('/\D+/', '', $data['ncm'] ?? '');
        $data['requires_gtin'] = (bool)($data['requires_gtin'] ?? false);
        // Se NCM foi alterado para outro já existente, mesclar/atualizar aquele e remover este
        $other = NcmRule::where('ncm', $data['ncm'])->where('id','<>',$ncm_rule->id)->first();
        if ($other) {
            $beforeOther = $other->getOriginal();
            $other->update(['requires_gtin' => $data['requires_gtin'], 'note' => $data['note'] ?? null]);
            $afterOther = $other->fresh();
            $changes = [];
            foreach (['requires_gtin','note'] as $f) {
                if (($beforeOther[$f] ?? null) != ($afterOther->$f ?? null)) {
                    $changes[$f] = ['old' => $beforeOther[$f] ?? null, 'new' => $afterOther->$f ?? null];
                }
            }
            if (!empty($changes)) {
                \App\Models\NcmRuleAudit::create([
                    'ncm_rule_id' => $other->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'notes' => 'Regra NCM mesclada por NCM; registro anterior removido',
                    'changes' => $changes,
                ]);
            }
            $deletedId = $ncm_rule->id;
            $ncm_rule->delete();
            \App\Models\NcmRuleAudit::create([
                'ncm_rule_id' => $other->id,
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'notes' => 'Registro NCM antigo removido (ID '.$deletedId.') após mescla',
            ]);
        } else {
            $before = $ncm_rule->getOriginal();
            $ncm_rule->update($data);
            $after = $ncm_rule->fresh();
            $changes = [];
            foreach (['ncm','requires_gtin','note'] as $f) {
                if (($before[$f] ?? null) != ($after->$f ?? null)) {
                    $changes[$f] = ['old' => $before[$f] ?? null, 'new' => $after->$f ?? null];
                }
            }
            if (!empty($changes)) {
                \App\Models\NcmRuleAudit::create([
                    'ncm_rule_id' => $ncm_rule->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'notes' => 'Regra NCM atualizada',
                    'changes' => $changes,
                ]);
            }
        }
        return redirect()->route('ncm_rules.index')->with('success','Regra NCM atualizada.');
    }

    public function destroy(NcmRule $ncm_rule)
    {
        abort_unless(auth()->user()->is_admin, 403);
        $id = $ncm_rule->id;
        $snapshot = ['ncm' => $ncm_rule->ncm, 'requires_gtin' => $ncm_rule->requires_gtin, 'note' => $ncm_rule->note];
        $ncm_rule->delete();
        \App\Models\NcmRuleAudit::create([
            'ncm_rule_id' => $id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'notes' => 'Regra NCM excluída',
            'changes' => $snapshot,
        ]);
        return redirect()->route('ncm_rules.index')->with('success','Regra excluída.');
    }
}


