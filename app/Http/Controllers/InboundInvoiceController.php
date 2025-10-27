<?php

namespace App\Http\Controllers;

use App\Models\InboundInvoice;
use App\Models\InboundInvoiceItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;

class InboundInvoiceController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = InboundInvoice::where('tenant_id', $tenantId);
        if ($s = trim((string) $request->get('search',''))) {
            $q->where(function($qq) use ($s){
                $qq->where('number','like',"%{$s}%")
                   ->orWhere('series','like',"%{$s}%")
                   ->orWhere('access_key','like',"%{$s}%")
                   ->orWhere('total_invoice','like',"%{$s}%");
            });
        }
        if ($supplier = trim((string) $request->get('supplier',''))) {
            $q->whereHas('supplier', function($qq) use ($supplier){
                $qq->where('name','like',"%{$supplier}%")
                   ->orWhere('cpf_cnpj','like',"%{$supplier}%");
            });
        }
        if ($from = $request->get('from')) { $q->whereDate('issue_date','>=',$from); }
        if ($to = $request->get('to')) { $q->whereDate('issue_date','<=',$to); }
        $sort = $request->get('sort','id');
        $dir = $request->get('direction','desc');
        if (!in_array($sort,['id','issue_date','total_invoice'])) { $sort = 'id'; }
        if (!in_array($dir,['asc','desc'])) { $dir = 'desc'; }
        $q->orderBy($sort,$dir);
        $perPage = (int) $request->get('per_page',12);
        if ($perPage < 5) $perPage = 5;
        if ($perPage > 200) $perPage = 200;
        $invoices = $q->paginate($perPage)->appends($request->query());
        return view('inbound.index', compact('invoices'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.create'), 403);
        return view('inbound.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'xml' => 'required|file|mimes:xml',
        ]);

        $xmlContent = file_get_contents($request->file('xml')->getRealPath());
        // Parse simplificado NFe (tag NFe -> infNFe). Em produção, use lib dedicada (e.g. nfephp-org)
        // Parser robusto: tenta SimpleXML; se falhar, tenta limpar namespaces
        $xml = @simplexml_load_string($xmlContent);
        if (!$xml) {
            $xmlContent = preg_replace('/(xmlns(:\w+)?)="[^"]+"/i', '', $xmlContent);
            $xml = @simplexml_load_string($xmlContent);
        }
        if (!$xml) { return back()->with('error','XML inválido.'); }
        $nfe = $xml->NFe ?? $xml; // alguns layouts já partem em NFe
        $inf = $nfe->infNFe ?? $nfe->infNFeSupl ?? null;
        if (isset($nfe->infNFe)) { $inf = $nfe->infNFe; }

        // Extração básica
        $ide = $inf->ide ?? null;
        $emit = $inf->emit ?? null; // fornecedor
        $det = $inf->det ?? [];
        $total = $inf->total->ICMSTot ?? null;
        $cobr = $inf->cobr ?? null; // duplicatas

        $accessKey = (string) ($inf['Id'] ?? '');
        $number = (string) ($ide->nNF ?? '');
        $series = (string) ($ide->serie ?? '');
        $issueDate = (string) ($ide->dhEmi ?? $ide->dEmi ?? '');
        $issueDate = $issueDate ? substr($issueDate,0,10) : null;

        $supplierDoc = (string) ($emit->CNPJ ?? $emit->CPF ?? '');
        $supplierName = (string) ($emit->xNome ?? '');

        // Localizar ou preparar fornecedor
        $supplier = Supplier::where('tenant_id', $tenantId)
            ->where('cpf_cnpj', $supplierDoc)
            ->first();
        if (!$supplier && $supplierDoc) {
            // auto-criação do fornecedor básico
            $supplier = Supplier::create([
                'tenant_id' => $tenantId,
                'name' => $supplierName ?: 'Fornecedor XML',
                'trade_name' => $supplierName ?: null,
                'cpf_cnpj' => $supplierDoc,
                'active' => true,
            ]);
        }

        $invoice = InboundInvoice::create([
            'tenant_id' => $tenantId,
            'supplier_id' => $supplier?->id,
            'access_key' => $accessKey,
            'number' => $number,
            'series' => $series,
            'issue_date' => $issueDate,
            'total_products' => $total ? (float)$total->vProd : null,
            'total_invoice' => $total ? (float)$total->vNF : null,
            'raw_summary' => [ 'supplier_name' => $supplierName, 'supplier_doc' => $supplierDoc ],
        ]);

        foreach ($det as $d) {
            $prod = $d->prod ?? null;
            if (!$prod) { continue; }
            InboundInvoiceItem::create([
                'inbound_invoice_id' => $invoice->id,
                'product_code' => (string)$prod->cProd,
                'product_name' => (string)$prod->xProd,
                'ean' => (string)$prod->cEAN ?? '',
                'ncm' => (string)$prod->NCM ?? '',
                'cfop' => (string)$prod->CFOP ?? '',
                'unit' => (string)$prod->uCom ?? '',
                'quantity' => (float)$prod->qCom,
                'unit_price' => (float)$prod->vUnCom,
                'total_price' => (float)$prod->vProd,
            ]);
        }

        // Guardar duplicatas (na sessão) para futura geração de payables ao concluir conferência
        $dups = [];
        if ($cobr && isset($cobr->dup)) {
            foreach ($cobr->dup as $dup) {
                $dups[] = [
                    'number' => (string)($dup->nDup ?? ''),
                    'due' => (string)($dup->dVenc ?? ''),
                    'value' => (float)($dup->vDup ?? 0),
                ];
            }
        }
        session(['inbound_dups_'.$invoice->id => $dups]);

        return redirect()->route('inbound.edit', $invoice)->with('success','XML importado. Confirme os dados.');
    }

    public function edit(InboundInvoice $inbound)
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.edit'), 403);
        abort_unless($inbound->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $suppliers = Supplier::where('tenant_id',$tenantId)->orderBy('name')->get();
        $items = $inbound->items()->get();
        // mapear possíveis matches de produtos por EAN ou código
        $productCandidates = [];
        foreach ($items as $it) {
            $found = Product::where('tenant_id',$tenantId)
                ->where(function($q) use ($it){
                    $q->where('ean',$it->ean)->orWhere('sku',$it->product_code);
                })->first();
            $productCandidates[$it->id] = $found ? $found->id : null;
        }
        return view('inbound.edit', compact('inbound','items','suppliers','productCandidates'));
    }

    public function update(Request $request, InboundInvoice $inbound)
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.edit'), 403);
        abort_unless($inbound->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;

        $v = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'items' => 'required|array',
            'items.*.action' => 'required|in:link,create,ignore',
            'items.*.product_id' => 'nullable|exists:products,id',
        ]);

        if (!empty($v['supplier_id'])) {
            $sup = Supplier::findOrFail($v['supplier_id']);
            abort_unless($sup->tenant_id === $tenantId, 403);
            $inbound->update(['supplier_id' => $sup->id]);
        }

        // processar itens
        $createdPayable = false;
        foreach ($inbound->items as $it) {
            $act = $v['items'][$it->id]['action'] ?? 'ignore';
            if ($act === 'link') {
                $pid = (int) ($v['items'][$it->id]['product_id'] ?? 0);
                if ($pid) {
                    // Evitar duplicar vínculo: se já estiver vinculado, apenas sumarizar quantidade adicional ao estoque
                    if ($it->linked_product_id && (int)$it->linked_product_id === $pid && $it->linked_movement_id) {
                        // já vinculado antes; somar quantidade extra como novo movimento
                        $mov = \App\Models\StockMovement::create([
                            'tenant_id' => $tenantId,
                            'product_id' => $pid,
                            'type' => 'entry',
                            'quantity' => $it->quantity,
                            'unit_price' => $it->unit_price,
                            'document' => 'NFe '.$inbound->number.'/'.$inbound->series,
                            'note' => 'Entrada adicional via XML (já vinculado)'
                        ]);
                        continue;
                    }
                    // custo médio para produto existente
                    $product = Product::where('tenant_id',$tenantId)->find($pid);
                    if ($product) {
                        $prevQty = (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity')
                                     - (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
                        $prevCost = (float) ($product->avg_cost ?? 0);
                        $newQty = (float) $it->quantity;
                        $newCost = (float) $it->unit_price;
                        $avg = $prevQty > 0 ? (($prevQty*$prevCost)+($newQty*$newCost))/($prevQty+$newQty) : $newCost;
                        $product->update(['avg_cost'=>$avg]);
                    }
                    // entrada de estoque
                $movement = \App\Models\StockMovement::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $pid,
                    'type' => 'entry',
                    'quantity' => $it->quantity,
                    'unit_price' => $it->unit_price,
                    'document' => 'NFe '.$inbound->number.'/'.$inbound->series,
                    'note' => 'Entrada via XML',
                ]);
                // marcar item como vinculado
                $it->update([
                    'linked_product_id' => $pid,
                    'linked_movement_id' => $movement->id,
                    'linked_at' => now(),
                ]);
                
                // Registrar crédito fiscal se houver ICMS
                if ($it->valor_icms > 0) {
                    $taxCreditService = app(\App\Services\TaxCreditService::class);
                    $taxCreditService->registerCreditFromInbound([
                        'tenant_id' => $tenantId,
                        'product_id' => $pid,
                        'document_number' => $inbound->number,
                        'document_series' => $inbound->series,
                        'document_date' => $inbound->date,
                        'supplier_cnpj' => $inbound->supplier?->cpf_cnpj,
                        'supplier_name' => $inbound->supplier?->name,
                        'base_calculo_icms' => $it->base_calculo_icms ?? 0,
                        'valor_icms' => $it->valor_icms,
                        'aliquota_icms' => $it->aliquota_icms ?? 0,
                        'cst_icms' => $it->cst_icms,
                        'cfop' => $it->cfop,
                        'ncm' => $it->ncm,
                        'quantity' => $it->quantity,
                        'unit_price' => $it->unit_price,
                        'total_value' => $it->quantity * $it->unit_price,
                    ]);
                }
                }
            } elseif ($act === 'create') {
                $categoryId = $v['items'][$it->id]['category_id'] ?? null;
                // Confirmar criação sem categoria (front já pergunta); aqui apenas aplica regra e trava o item
                $new = Product::create([
                    'tenant_id' => $tenantId,
                    'category_id' => $categoryId, // pode ser null; usuário escolherá depois
                    'supplier_id' => $inbound->supplier_id,
                    'name' => $it->product_name,
                    'sku' => $it->product_code,
                    'ean' => $it->ean ?: 'SEM GTIN',
                    'unit' => $it->unit ?: 'UN',
                    'ncm' => $it->ncm,
                    'cfop' => $it->cfop,
                    'cest' => '',
                    'origin' => 0,
                    'price' => $it->unit_price,
                    'type' => 'product',
                    'active' => true,
                ]);
                \App\Models\StockMovement::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $new->id,
                    'type' => 'entry',
                    'quantity' => $it->quantity,
                    'unit_price' => $it->unit_price,
                    'document' => 'NFe '.$inbound->number.'/'.$inbound->series,
                    'note' => 'Entrada via XML (novo produto)',
                ]);
                
                // Registrar crédito fiscal se houver ICMS
                if ($it->valor_icms > 0) {
                    $taxCreditService = app(\App\Services\TaxCreditService::class);
                    $taxCreditService->registerCreditFromInbound([
                        'tenant_id' => $tenantId,
                        'product_id' => $new->id,
                        'document_number' => $inbound->number,
                        'document_series' => $inbound->series,
                        'document_date' => $inbound->date,
                        'supplier_cnpj' => $inbound->supplier?->cpf_cnpj,
                        'supplier_name' => $inbound->supplier?->name,
                        'base_calculo_icms' => $it->base_calculo_icms ?? 0,
                        'valor_icms' => $it->valor_icms,
                        'aliquota_icms' => $it->aliquota_icms ?? 0,
                        'cst_icms' => $it->cst_icms,
                        'cfop' => $it->cfop,
                        'ncm' => $it->ncm,
                        'quantity' => $it->quantity,
                        'unit_price' => $it->unit_price,
                        'total_value' => $it->quantity * $it->unit_price,
                    ]);
                }
                // Atualizar custo médio simples: se já havia avg_cost, combinar; se não, usar custo atual
                $prevQty = (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$new->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity')
                             - (float) \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$new->id)->where('type','exit')->sum('quantity');
                $prevCost = (float) ($new->avg_cost ?? 0);
                $newQty = (float) $it->quantity;
                $newCost = (float) $it->unit_price;
                $avg = $prevQty > 0 ? (($prevQty*$prevCost)+($newQty*$newCost))/($prevQty+$newQty) : $newCost;
                $new->update(['avg_cost'=>$avg]);

                // Trava o item para não permitir novo vínculo/criação até intervenção
                $it->update(['link_locked' => true]);

                // Redirecionar diretamente para edição do produto recém-criado
                return redirect()->route('products.edit', $new)->with('success', 'Produto criado a partir da NFe. Complete os dados.');
            }
        }

        // Contas a pagar pelas duplicatas (se houver); se não houver, cria uma parcela única
        if ($inbound->supplier_id) {
            $dups = session('inbound_dups_'.$inbound->id, []);
            if ($dups && is_array($dups) && count($dups)) {
                foreach ($dups as $dup) {
                    \App\Models\Payable::create([
                        'tenant_id' => $tenantId,
                        'supplier_id' => $inbound->supplier_id,
                        'supplier_name' => optional($inbound->supplier)->name,
                        'description' => 'NFe '.$inbound->number.'/'.$inbound->series.' - Parcela '.$dup['number'],
                        'amount' => (float)($dup['value'] ?? 0),
                        'due_date' => $dup['due'] ?: (optional($inbound->issue_date)->copy()->addDays(30) ?? now()->addDays(30)->toDateString()),
                        'status' => 'open',
                        'document_number' => $inbound->access_key,
                    ]);
                }
            } elseif ($inbound->total_invoice) {
                \App\Models\Payable::create([
                    'tenant_id' => $tenantId,
                    'supplier_id' => $inbound->supplier_id,
                    'supplier_name' => optional($inbound->supplier)->name,
                    'description' => 'NFe Entrada '.$inbound->number.'/'.$inbound->series,
                    'amount' => (float)$inbound->total_invoice,
                    'due_date' => optional($inbound->issue_date)->copy()->addDays(30) ?? now()->addDays(30)->toDateString(),
                    'status' => 'open',
                    'document_number' => $inbound->access_key,
                ]);
            }
            session()->forget('inbound_dups_'.$inbound->id);
        }

        return redirect()->route('inbound.edit', $inbound)->with('success','Nota de entrada processada.');
    }

    public function unlinkItem(InboundInvoice $inbound, InboundInvoiceItem $item)
    {
        abort_unless(auth()->user()->hasPermission('inbound_invoices.unlink'), 403);
        abort_unless($inbound->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($item->invoice && $item->invoice->id === $inbound->id, 403);

        // Bloqueia desvincular quando o item foi criado como novo produto (irreversível)
        if ($item->link_locked) {
            return back()->with('error', 'Operação irreversível: item criado como novo produto.');
        }

        $tenantId = auth()->user()->tenant_id;
        if ($item->linked_product_id && $item->linked_movement_id) {
            // estornar estoque
            \App\Models\StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => (int)$item->linked_product_id,
                'type' => 'exit',
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'document' => 'Estorno vinculação NFe '.$inbound->number.'/'.$inbound->series,
                'note' => 'Estorno de entrada via desvinculação',
            ]);
        }
        $item->update([
            'linked_product_id' => null,
            'linked_movement_id' => null,
            'linked_at' => null,
        ]);

        return back()->with('success','Item desvinculado e estoque estornado.');
    }
}


