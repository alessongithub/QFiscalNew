<?php

namespace App\Http\Controllers;

use App\Models\TaxCredit;
use App\Models\Product;
use App\Services\TaxCreditService;
use Illuminate\Http\Request;

class TaxCreditController extends Controller
{
    protected $taxCreditService;

    public function __construct(TaxCreditService $taxCreditService)
    {
        $this->taxCreditService = $taxCreditService;
    }

    public function index()
    {
        abort_unless(auth()->user()->hasPermission('tax_credits.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        
        $credits = TaxCredit::where('tenant_id', $tenantId)
            ->with('product')
            ->orderByDesc('created_at')
            ->paginate(20);
            
        return view('tax_credits.index', compact('credits'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('tax_credits.create'), 403);
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        return view('tax_credits.create', compact('products'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('tax_credits.create'), 403);
        
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'document_number' => 'required|string|max:20',
            'document_series' => 'nullable|string|max:10',
            'document_date' => 'required|date',
            'supplier_cnpj' => 'nullable|string|max:20',
            'supplier_name' => 'nullable|string|max:255',
            'base_calculo_icms' => 'required|numeric|min:0',
            'valor_icms' => 'required|numeric|min:0',
            'aliquota_icms' => 'required|numeric|min:0|max:1',
            'cst_icms' => 'nullable|string|max:3',
            'cfop' => 'nullable|string|max:4',
            'ncm' => 'nullable|string|max:10',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_value' => 'required|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['document_type'] = 'nfe';
        
        $credit = $this->taxCreditService->registerCreditFromInbound($data);
        
        return redirect()->route('tax_credits.index')
            ->with('success', 'Crédito fiscal registrado com sucesso.');
    }

    public function show(TaxCredit $tax_credit)
    {
        abort_unless(
            auth()->user()->hasPermission('tax_credits.view') && 
            $tax_credit->tenant_id === auth()->user()->tenant_id, 
            403
        );
        
        $tax_credit->load('product');
        return view('tax_credits.show', compact('tax_credit'));
    }

    public function edit(TaxCredit $tax_credit)
    {
        abort_unless(
            auth()->user()->hasPermission('tax_credits.edit') && 
            $tax_credit->tenant_id === auth()->user()->tenant_id, 
            403
        );
        
        $products = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        return view('tax_credits.edit', compact('tax_credit', 'products'));
    }

    public function update(Request $request, TaxCredit $tax_credit)
    {
        abort_unless(
            auth()->user()->hasPermission('tax_credits.edit') && 
            $tax_credit->tenant_id === auth()->user()->tenant_id, 
            403
        );
        
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'document_number' => 'required|string|max:20',
            'document_series' => 'nullable|string|max:10',
            'document_date' => 'required|date',
            'supplier_cnpj' => 'nullable|string|max:20',
            'supplier_name' => 'nullable|string|max:255',
            'base_calculo_icms' => 'required|numeric|min:0',
            'valor_icms' => 'required|numeric|min:0',
            'aliquota_icms' => 'required|numeric|min:0|max:1',
            'cst_icms' => 'nullable|string|max:3',
            'cfop' => 'nullable|string|max:4',
            'ncm' => 'nullable|string|max:10',
            'quantity' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
            'total_value' => 'required|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        $tax_credit->update($data);
        
        return redirect()->route('tax_credits.index')
            ->with('success', 'Crédito fiscal atualizado com sucesso.');
    }

    public function destroy(TaxCredit $tax_credit)
    {
        abort_unless(
            auth()->user()->hasPermission('tax_credits.delete') && 
            $tax_credit->tenant_id === auth()->user()->tenant_id, 
            403
        );
        
        $tax_credit->delete();
        
        return redirect()->route('tax_credits.index')
            ->with('success', 'Crédito fiscal excluído com sucesso.');
    }

    /**
     * API: Busca sugestão de ICMS para um produto
     */
    public function getIcmsSuggestion(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'base_calculo' => 'required|numeric|min:0',
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Verifica se o produto pertence ao tenant
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Produto não encontrado'], 404);
        }

        $suggestion = $this->taxCreditService->getIcmsSuggestion(
            $product,
            $request->base_calculo,
            auth()->user()->tenant_id
        );

        return response()->json($suggestion);
    }

    /**
     * API: Lista créditos disponíveis para um produto
     */
    public function getAvailableCredits(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($request->product_id);
        
        if ($product->tenant_id !== auth()->user()->tenant_id) {
            return response()->json(['error' => 'Produto não encontrado'], 404);
        }

        $credits = $this->taxCreditService->getAvailableCredits(
            $request->product_id,
            auth()->user()->tenant_id
        );

        return response()->json([
            'credits' => $credits->map(function($credit) {
                return [
                    'id' => $credit->id,
                    'document' => $credit->document_number,
                    'date' => $credit->document_date->format('d/m/Y'),
                    'supplier' => $credit->supplier_name,
                    'quantity_available' => $credit->quantity - $credit->quantity_used,
                    'valor_icms_available' => $credit->valor_icms - $credit->valor_icms_used,
                    'aliquota' => $credit->aliquota_icms,
                ];
            })
        ]);
    }
}
