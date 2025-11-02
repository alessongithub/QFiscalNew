<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        
        $query = TaxRate::where('tenant_id', $tenantId)->with(['createdBy', 'updatedBy']);
        
        // Filtro por nome
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Filtro por tipo de nota
        if ($request->filled('tipo_nota')) {
            $query->where('tipo_nota', $request->tipo_nota);
        }
        
        // Filtro por NCM
        if ($request->filled('ncm')) {
            $query->where('ncm', 'like', '%' . $request->ncm . '%');
        }
        
        // Filtro por CFOP
        if ($request->filled('cfop')) {
            $query->where('cfop', 'like', '%' . $request->cfop . '%');
        }
        
        // Filtro por código de serviço
        if ($request->filled('codigo_servico')) {
            $query->where('codigo_servico', 'like', '%' . $request->codigo_servico . '%');
        }
        
        // Filtro por status ativo
        if ($request->has('ativo') && $request->ativo !== '') {
            $query->where('ativo', $request->ativo);
        }
        
        // Ordenação
        $sortBy = $request->get('sort_by', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');
        
        if (in_array($sortBy, ['id', 'name', 'tipo_nota', 'ncm', 'cfop', 'codigo_servico', 'ativo', 'created_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('id', 'desc');
        }
        
        // Registros por página
        $perPage = $request->get('per_page', 20);
        if (!in_array($perPage, [10, 25, 50, 100, 200])) {
            $perPage = 20;
        }
        
        $rates = $query->paginate($perPage)->withQueryString();
        
        return view('tax_rates.index', compact('rates'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.create'), 403);
        return view('tax_rates.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'tipo_nota' => 'required|in:produto,servico',
            'name' => 'nullable|string|max:100',
            'ncm' => 'nullable|string|max:10',
            'cfop' => 'nullable|string|max:10',
            'codigo_servico' => 'nullable|string|max:30',
            'icms_aliquota' => 'nullable|numeric|min:0|max:1',
            'icms_reducao_bc' => 'nullable|numeric|min:0|max:1',
            'pis_aliquota' => 'nullable|numeric|min:0|max:1',
            'cofins_aliquota' => 'nullable|numeric|min:0|max:1',
            'iss_aliquota' => 'nullable|numeric|min:0|max:1',
            'csll_aliquota' => 'nullable|numeric|min:0|max:1',
            'inss_aliquota' => 'nullable|numeric|min:0|max:1',
            'irrf_aliquota' => 'nullable|numeric|min:0|max:1',
            'icmsst_modalidade' => 'nullable|integer|min:0|max:9',
            'icmsst_mva' => 'nullable|numeric|min:0|max:1',
            'icmsst_aliquota' => 'nullable|numeric|min:0|max:1',
            'icmsst_reducao_bc' => 'nullable|numeric|min:0|max:1',
            'ativo' => 'nullable|boolean',
            // Aceita também os campos em percentual; converteremos abaixo
            'pis_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'cofins_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icms_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icms_reducao_bc_percent' => 'nullable|numeric|min:0|max:100',
            'iss_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'csll_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'inss_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'irrf_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_mva_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_reducao_bc_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        // Conversão server-side de percentuais para decimais (fallback caso JS não rode)
        $percentToDecimal = [
            'pis_aliquota_percent' => 'pis_aliquota',
            'cofins_aliquota_percent' => 'cofins_aliquota',
            'icms_aliquota_percent' => 'icms_aliquota',
            'icms_reducao_bc_percent' => 'icms_reducao_bc',
            'iss_aliquota_percent' => 'iss_aliquota',
            'csll_aliquota_percent' => 'csll_aliquota',
            'inss_aliquota_percent' => 'inss_aliquota',
            'irrf_aliquota_percent' => 'irrf_aliquota',
            'icmsst_mva_percent' => 'icmsst_mva',
            'icmsst_aliquota_percent' => 'icmsst_aliquota',
            'icmsst_reducao_bc_percent' => 'icmsst_reducao_bc',
        ];
        foreach ($percentToDecimal as $percentKey => $decimalKey) {
            if (!isset($data[$decimalKey]) && isset($data[$percentKey]) && $data[$percentKey] !== null && $data[$percentKey] !== '') {
                $data[$decimalKey] = ((float) $data[$percentKey]) / 100.0;
            }
            unset($data[$percentKey]);
        }
        $data['tenant_id'] = $tenantId;
        $data['ativo'] = (bool)($data['ativo'] ?? true);
        $data['created_by'] = auth()->id();
        $taxRate = TaxRate::create($data);
        
        // Registrar auditoria de criação
        \App\Models\TaxRateAudit::create([
            'tax_rate_id' => $taxRate->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'notes' => 'Configuração tributária criada',
            'changes' => [
                'name' => $taxRate->name,
                'tipo_nota' => $taxRate->tipo_nota,
                'ncm' => $taxRate->ncm,
                'cfop' => $taxRate->cfop,
            ],
        ]);
        
        return redirect()->route('tax_rates.index')->with('success', 'Alíquota criada.');
    }

    public function edit(TaxRate $tax_rate)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.edit') && $tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        return view('tax_rates.edit', ['rate' => $tax_rate]);
    }

    public function update(Request $request, TaxRate $tax_rate)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.edit') && $tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        $data = $request->validate([
            'tipo_nota' => 'required|in:produto,servico',
            'name' => 'nullable|string|max:100',
            'ncm' => 'nullable|string|max:10',
            'cfop' => 'nullable|string|max:10',
            'codigo_servico' => 'nullable|string|max:30',
            'icms_aliquota' => 'nullable|numeric|min:0|max:1',
            'icms_reducao_bc' => 'nullable|numeric|min:0|max:1',
            'pis_aliquota' => 'nullable|numeric|min:0|max:1',
            'cofins_aliquota' => 'nullable|numeric|min:0|max:1',
            'iss_aliquota' => 'nullable|numeric|min:0|max:1',
            'csll_aliquota' => 'nullable|numeric|min:0|max:1',
            'inss_aliquota' => 'nullable|numeric|min:0|max:1',
            'irrf_aliquota' => 'nullable|numeric|min:0|max:1',
            'icmsst_modalidade' => 'nullable|integer|min:0|max:9',
            'icmsst_mva' => 'nullable|numeric|min:0|max:1',
            'icmsst_aliquota' => 'nullable|numeric|min:0|max:1',
            'icmsst_reducao_bc' => 'nullable|numeric|min:0|max:1',
            'ativo' => 'nullable|boolean',
            // Campos em percentual (fallback sem JS)
            'pis_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'cofins_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icms_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icms_reducao_bc_percent' => 'nullable|numeric|min:0|max:100',
            'iss_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'csll_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'inss_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'irrf_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_mva_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_aliquota_percent' => 'nullable|numeric|min:0|max:100',
            'icmsst_reducao_bc_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        $percentToDecimal = [
            'pis_aliquota_percent' => 'pis_aliquota',
            'cofins_aliquota_percent' => 'cofins_aliquota',
            'icms_aliquota_percent' => 'icms_aliquota',
            'icms_reducao_bc_percent' => 'icms_reducao_bc',
            'iss_aliquota_percent' => 'iss_aliquota',
            'csll_aliquota_percent' => 'csll_aliquota',
            'inss_aliquota_percent' => 'inss_aliquota',
            'irrf_aliquota_percent' => 'irrf_aliquota',
            'icmsst_mva_percent' => 'icmsst_mva',
            'icmsst_aliquota_percent' => 'icmsst_aliquota',
            'icmsst_reducao_bc_percent' => 'icmsst_reducao_bc',
        ];
        foreach ($percentToDecimal as $percentKey => $decimalKey) {
            if (!isset($data[$decimalKey]) && isset($data[$percentKey]) && $data[$percentKey] !== null && $data[$percentKey] !== '') {
                $data[$decimalKey] = ((float) $data[$percentKey]) / 100.0;
            }
            unset($data[$percentKey]);
        }
        
        // Capturar valores originais ANTES de qualquer modificação
        $tax_rate->refresh(); // Garantir que temos os valores atuais do banco
        $originalValues = $tax_rate->getAttributes();
        
        // Tratar campo 'ativo' separadamente
        $data['ativo'] = (bool)($data['ativo'] ?? $tax_rate->ativo);
        
        // Comparar valores antes e depois
        $changedFields = [];
        foreach ($data as $key => $newValue) {
            $oldValue = $originalValues[$key] ?? null;
            
            // Comparação mais robusta
            if ($oldValue != $newValue) {
                $changedFields[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }
        
        // Atualizar o modelo
        $tax_rate->fill($data);
        $tax_rate->updated_by = auth()->id();
        $tax_rate->save();
        
        // Registrar auditoria de atualização se houver mudanças (desconsiderar campos internos)
        unset($changedFields['updated_by'], $changedFields['updated_at']);
        if (!empty($changedFields)) {
            \App\Models\TaxRateAudit::create([
                'tax_rate_id' => $tax_rate->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => 'Configuração tributária atualizada',
                'changes' => $changedFields,
            ]);
        }
        
        return redirect()->route('tax_rates.index')->with('success', 'Alíquota atualizada.');
    }

    public function destroy(TaxRate $tax_rate)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.delete') && $tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        
        // Registrar auditoria de exclusão antes de deletar
        \App\Models\TaxRateAudit::create([
            'tax_rate_id' => $tax_rate->id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'notes' => 'Configuração tributária excluída',
            'changes' => [
                'name' => $tax_rate->name,
                'tipo_nota' => $tax_rate->tipo_nota,
            ],
        ]);
        
        $tax_rate->delete();
        return redirect()->route('tax_rates.index')->with('success', 'Alíquota excluída.');
    }

    // Retorna uma configuração em JSON (para aplicar no cadastro de produtos)
    public function showJson(TaxRate $tax_rate)
    {
        abort_unless($tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        return response()->json([
            'id' => $tax_rate->id,
            'name' => $tax_rate->name,
            'tipo_nota' => $tax_rate->tipo_nota,
            'ncm' => $tax_rate->ncm,
            'cfop' => $tax_rate->cfop,
            'codigo_servico' => $tax_rate->codigo_servico,
            'icms_aliquota' => $tax_rate->icms_aliquota, // 0..1
            'pis_aliquota' => $tax_rate->pis_aliquota,
            'cofins_aliquota' => $tax_rate->cofins_aliquota,
            'iss_aliquota' => $tax_rate->iss_aliquota,
        ]);
    }

    public function show(TaxRate $tax_rate)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.view') && $tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        $tax_rate->load(['createdBy', 'updatedBy']);
        return view('tax_rates.show', ['rate' => $tax_rate]);
    }

    public function history(TaxRate $tax_rate)
    {
        abort_unless(auth()->user()->hasPermission('tax_rates.view') && $tax_rate->tenant_id === auth()->user()->tenant_id, 403);
        
        $audits = \App\Models\TaxRateAudit::where('tax_rate_id', $tax_rate->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('tax_rates.history', compact('tax_rate', 'audits'));
    }
}


