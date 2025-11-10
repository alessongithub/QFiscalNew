<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnModel;
use App\Models\ReturnItem;
use App\Models\StockMovement;
use App\Models\Receivable;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('returns.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $returns = ReturnModel::where('tenant_id',$tenantId)->orderByDesc('id')->paginate(12);
        return view('returns.index', compact('returns'));
    }

    public function selectOrder(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('returns.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $orderKey = trim((string)$request->get('order', ''));
        $order = null;
        if ($orderKey !== '') {
            // Tenta localizar pelo número do pedido
            $order = Order::where('tenant_id', $tenantId)->where('number', $orderKey)->first();
            // Se não encontrou e o valor parece ser um ID numérico, tenta pelo ID
            if (!$order && ctype_digit($orderKey)) {
                $id = (int) $orderKey;
                $order = Order::where('tenant_id', $tenantId)->find($id);
            }
            if (!$order) { return back()->with('error','Pedido não encontrado.'); }
            return redirect()->route('returns.create', ['order' => $order->id]);
        }
        
        // Query para pedidos com filtros
        $query = Order::where('tenant_id', $tenantId)->with('client');
        
        // Filtro por cliente (busca por nome)
        if ($request->filled('client_search')) {
            $clientSearch = trim($request->client_search);
            $query->whereHas('client', function($q) use ($clientSearch) {
                $q->where('name', 'like', "%{$clientSearch}%");
            });
        }
        
        // Filtro por data
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Ordenação
        $query->orderByDesc('id');
        
        // Paginação
        $perPage = (int) $request->get('per_page', 20);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 100) { $perPage = 100; }
        
        $orders = $query->paginate($perPage)->appends($request->query());
        
        return view('returns.select_order', compact('orders'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('returns.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $orderId = (int) $request->get('order');
        $order = Order::where('tenant_id',$tenantId)->findOrFail($orderId);
        $items = $order->items()->get();
        // calcular já devolvido por item
        $already = [];
        foreach ($items as $it) {
            $already[$it->id] = (float) ReturnItem::whereIn('return_id', ReturnModel::where('order_id',$order->id)->pluck('id'))
                ->where('order_item_id',$it->id)->sum('quantity');
        }
        
        // Verificar se há NFe transmitida para exibir alerta
        $hasIssuedNfe = $order->has_successful_nfe;
        $nfeNote = null;
        $nfeKey = null;
        if ($hasIssuedNfe) {
            try {
                $nfeNote = $order->latestNfeNoteCompat;
                $nfeKey = $nfeNote->chave_acesso ?? null;
            } catch (\Throwable $e) {}
        }
        
        // Verificar se há recebíveis de cartão/boleto para mostrar opção de cancelamento
        $openReceivables = \App\Models\Receivable::where('tenant_id', $tenantId)
            ->where('order_id', $order->id)
            ->whereIn('status', ['open', 'partial'])
            ->where('amount', '>', 0)
            ->get();
        
        $hasNonModifiableInstallments = $openReceivables->contains(function($r) {
            return in_array($r->payment_method, ['card', 'boleto']);
        });
        
        return view('returns.create', compact('order','items','already','hasIssuedNfe','nfeNote','nfeKey','hasNonModifiableInstallments'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('returns.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'refund_type' => 'nullable|in:refund',
            'refund_method' => 'nullable|in:cash,card,pix',
            'cancel_full_order' => 'nullable|boolean', // Opção para cancelar venda inteira em devolução total
            'cancel_nfe' => 'nullable|boolean', // Opção para cancelar NF-e original na SEFAZ em devolução total
            'nfe_cancel_justification' => 'nullable|string|min:15|max:1000', // Justificativa para cancelamento de NF-e
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
        ]);
        $order = Order::where('tenant_id',$tenantId)->findOrFail($v['order_id']);
        // Bloqueios MVP: não permitir devolução em pedido cancelado ou em aberto/orçado
        $blockedStatuses = ['canceled','open','in_progress'];
        if (in_array($order->status, $blockedStatuses, true)) {
            return back()->with('error', 'Este pedido não permite devolução no status atual.');
        }
        $itemsMap = $order->items()->get()->keyBy('id');

        $ret = ReturnModel::create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'refund_method' => ($v['refund_method'] ?? 'cash'),
            'total_refund' => 0,
            'notes' => $request->input('notes')
        ]);

        $totalRefund = 0.0;
        $assignedQty = [];
        foreach ($v['items'] as $row) {
            $oi = $itemsMap[$row['order_item_id']] ?? null;
            if (!$oi) { continue; }
            $qty = (float) ($row['quantity'] ?? 0);
            if ($qty <= 0) { continue; }
            // validar não exceder vendido - já devolvido
            $returned = (float) ReturnItem::whereIn('return_id', ReturnModel::where('order_id',$order->id)->pluck('id'))
                ->where('order_item_id',$oi->id)->sum('quantity');
            $maxQty = (float) $oi->quantity - $returned;
            if ($qty > $maxQty + 1e-6) { $qty = $maxQty; }
            if ($qty <= 0) { continue; }
            $unit = (float) $oi->unit_price;
            $line = round($qty * $unit, 2);
            ReturnItem::create([
                'return_id' => $ret->id,
                'order_item_id' => $oi->id,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $line,
            ]);
            $totalRefund += $line;
            $assignedQty[$oi->id] = $qty;

            // entrada de estoque
            StockMovement::create([
                'tenant_id' => $tenantId,
                'product_id' => $oi->product_id,
                'movement_type' => 'in',
                'quantity' => $qty,
                'unit_price' => $unit,
                'reason' => 'return',
                'user_id' => auth()->id(),
                'notes' => 'Devolução de venda - Pedido #'.$order->id,
            ]);
        }

        $ret->update(['total_refund' => $totalRefund]);

        // Registrar auditoria de devolução (OrderAudit existente)
        // Montar detalhamento de itens devolvidos (nome, qtd, valor) + texto
        $itemsDetail = [];
        $itemsTextParts = [];
        foreach ($assignedQty as $orderItemId => $qtd) {
            if ($qtd <= 0) continue;
            $oi = $itemsMap[$orderItemId] ?? null;
            if ($oi) {
                $line = round(((float)$oi->unit_price) * (float)$qtd, 2);
                $itemsDetail[] = [
                    'produto' => (string) ($oi->name ?? 'Item'),
                    'quantidade' => (float) $qtd,
                    'valor' => $line,
                ];
                $itemsTextParts[] = (string) ($oi->name ?? 'Item') . ' x ' . number_format((float)$qtd, 3, ',', '.') . ' (R$ ' . number_format($line, 2, ',', '.') . ')';
            }
        }
        \Log::info('returns.items_detail', [
            'order_id' => $order->id,
            'assigned_qty' => $assignedQty,
            'items_detail' => $itemsDetail,
        ]);

        // Removido: evitar duplicidade no /activity (detalhes ficarão no ReturnAudit)

        // Auditoria dedicada de devoluções (ReturnAudit), escopo por tenant
        try {
            \App\Models\ReturnAudit::create([
                'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
                'return_id' => $ret->id,
                'order_id' => $order->id,
                'action' => 'created',
                'notes' => 'Devolução do Pedido #' . $order->number . ' - Valor R$ ' . number_format($totalRefund,2,',','.') ,
                'changes' => [
                'refund_type' => $v['refund_type'],
                    'refund_method' => $v['refund_method'] ?? null,
                'total_refund' => $totalRefund,
                    'itens' => $itemsDetail,
                    'detalhes' => implode('; ', $itemsTextParts),
                ],
            ]);
        } catch (\Throwable $e) { }

        // financeiro: Estorno imediato
        if ($totalRefund > 0 && ($v['refund_type'] ?? null) === 'refund') {
            // Buscar recebíveis do pedido
            $paidReceivables = \App\Models\Receivable::where('tenant_id', $tenantId)
                ->where('order_id', $order->id)
                ->where('status', 'paid')
                ->where('amount', '>', 0)
                ->get();
            
            $openReceivables = \App\Models\Receivable::where('tenant_id', $tenantId)
                ->where('order_id', $order->id)
                ->whereIn('status', ['open', 'partial'])
                ->where('amount', '>', 0)
                ->get();
            
            $totalPaidAmount = $paidReceivables->sum('amount'); // Valor total recebido antecipadamente
            $orderTotal = (float) $order->total_amount; // Valor total do pedido
            
            // Calcular proporção da devolução em relação ao pedido total
            $refundProportion = $orderTotal > 0 ? ($totalRefund / $orderTotal) : 0;
            
            // Métodos não modificáveis (cartão parcelado e boleto)
            $nonModifiableMethods = ['card', 'boleto'];
            
            // Verificar se há parcelas não modificáveis
            $hasNonModifiableInstallments = $openReceivables->contains(function($r) use ($nonModifiableMethods) {
                return in_array($r->payment_method, $nonModifiableMethods);
            });
            
            // DECISÃO: Devolução parcial (< 95%) ou total (>= 95%)?
            $isTotalReturn = $refundProportion >= 0.95;
            $shouldCancelOrder = $isTotalReturn && ($v['cancel_full_order'] ?? false);
            
            if ($shouldCancelOrder) {
                // DEVOLUÇÃO TOTAL: Cancelar venda inteira
                // Estornar entrada do caixa
                if ($totalPaidAmount > 0) {
                    // Identificar método de pagamento da entrada (se houver)
                    $entryReceivable = $paidReceivables->first(function($r) {
                        return stripos($r->description ?? '', 'entrada') !== false;
                    });
                    
                    $refundPaymentMethod = $entryReceivable 
                        ? $entryReceivable->payment_method 
                        : ($v['refund_method'] ?? ($paidReceivables->first()->payment_method ?? 'cash'));
                    
                    Receivable::create([
                        'tenant_id' => $tenantId,
                        'client_id' => $order->client_id,
                        'order_id' => $order->id,
                        'description' => '🔄 Estorno Devolução Total Pedido '.$order->number.' (Cancelamento)',
                        'amount' => -$totalPaidAmount,
                        'due_date' => now()->toDateString(),
                        'status' => 'paid',
                        'received_at' => now(),
                        'payment_method' => $refundPaymentMethod,
                    ]);
                }
                
                // Cancelar parcelas/boletos em aberto (verificar se não foram pagos)
                foreach ($openReceivables as $rec) {
                    // Só cancela se não foi pago (status open/partial)
                    if (in_array($rec->status, ['open', 'partial'])) {
                        $rec->status = 'canceled';
                        $rec->canceled_at = now();
                        $rec->canceled_by = auth()->id();
                        $rec->cancel_reason = 'Cancelamento por devolução total do pedido';
                        $rec->save();
                    }
                }
            } else {
                // DEVOLUÇÃO PARCIAL ou TOTAL sem cancelar: Estornar do caixa
                // Estornar valor real do item devolvido (não proporcional)
                $amountToRefund = $totalRefund;
                
                if ($amountToRefund > 0) {
                    // Priorizar estorno da entrada
                    $entryReceivable = $paidReceivables->first(function($r) {
                        return stripos($r->description ?? '', 'entrada') !== false;
                    });
                    
                    $refundPaymentMethod = $v['refund_method'] ?? 'cash';
                    
                    if ($amountToRefund <= $totalPaidAmount) {
                        // Estornar apenas do que foi recebido (entrada)
                        Receivable::create([
                            'tenant_id' => $tenantId,
                            'client_id' => $order->client_id,
                            'order_id' => $order->id,
                            'description' => '🔄 Estorno Devolução Pedido '.$order->number,
                            'amount' => -$amountToRefund,
                            'due_date' => now()->toDateString(),
                            'status' => 'paid',
                            'received_at' => now(),
                            'payment_method' => $refundPaymentMethod,
                        ]);
                    } else {
                        // Estornar entrada + restante do caixa
                        if ($totalPaidAmount > 0) {
                            Receivable::create([
                                'tenant_id' => $tenantId,
                                'client_id' => $order->client_id,
                                'order_id' => $order->id,
                                'description' => '🔄 Estorno Devolução Pedido '.$order->number.' (Entrada)',
                                'amount' => -$totalPaidAmount,
                                'due_date' => now()->toDateString(),
                                'status' => 'paid',
                                'received_at' => now(),
                                'payment_method' => $refundPaymentMethod,
                            ]);
                        }
                        
                        // Restante do caixa
                        $remainingRefund = $amountToRefund - $totalPaidAmount;
                        if ($remainingRefund > 0) {
                            Receivable::create([
                                'tenant_id' => $tenantId,
                                'client_id' => $order->client_id,
                                'order_id' => $order->id,
                                'description' => '🔄 Estorno Devolução Pedido '.$order->number.' (Complemento)',
                                'amount' => -$remainingRefund,
                                'due_date' => now()->toDateString(),
                                'status' => 'paid',
                                'received_at' => now(),
                                'payment_method' => $refundPaymentMethod,
                            ]);
                        }
                    }
                }
                
                // NUNCA mexer em parcelas de cartão/boleto em devolução parcial
                // Parcelas permanecem intactas
            }
            
            // Atualizar saldo do caixa do dia
            try {
                $dailyCash = \App\Models\DailyCash::where('tenant_id', $tenantId)
                    ->whereDate('date', now()->toDateString())
                    ->first();
                if ($dailyCash) { $dailyCash->updateCurrentBalance(); }
            } catch (\Throwable $e) { /* ignore */ }
        }

        // Atualizar status do pedido conforme devolução (MVP)
        try {
            $order->refresh();
            $order->load('items'); // Carregar items para acessar returned_quantity
            
            // Verificar se TODOS os itens do pedido foram TOTALMENTE devolvidos (somando devoluções anteriores + atuais)
            $allReturned = true; 
            $anyReturned = false;
            $hasItems = false; // Verificar se o pedido tem itens
            
            foreach ($order->items as $it) {
                $hasItems = true;
                $sold = (float) $it->quantity;
                // Usa accessor returned_quantity (calcula devoluções anteriores)
                $returnedBefore = $it->returned_quantity;
                $returnedNow = (float) ($assignedQty[$it->id] ?? 0);
                $returnedTotal = round($returnedBefore + $returnedNow, 3);
                
                // Se está devolvendo algo nesta devolução atual
                if ($returnedNow > 0) { 
                    $anyReturned = true; 
                }
                
                // Só marca como allReturned se este item específico foi TOTALMENTE devolvido
                // E só é "allReturned" se TODOS os itens do pedido foram totalmente devolvidos
                // IMPORTANTE: verificar se returnedTotal é >= sold (com tolerância para arredondamento)
                if ($returnedTotal + 1e-6 < $sold) { 
                    $allReturned = false; // Este item NÃO foi totalmente devolvido (ainda há quantidade restante)
                }
            }

            $oldStatus = $order->status;
            $newStatus = $oldStatus;
            
            // Regras de negócio para atualização de status:
            // 1. Só pode cancelar se TODOS os itens foram TOTALMENTE devolvidos (somando devoluções anteriores + atuais)
            // 2. Só pode cancelar se TODOS os valores foram estornados (sem títulos em aberto)
            // 3. Se há devoluções parciais (algum item ainda tem quantidade restante) → partial_returned
            // 4. Se não há devoluções → mantém status atual
            
            // Se não há itens, não faz sentido cancelar
            if (!$hasItems) {
                // Pedido sem itens - manter status atual
            } elseif ($allReturned) {
                // Todos os itens foram totalmente devolvidos
                // IMPORTANTE: Verificar se todos os valores foram estornados antes de cancelar
                // Não pode cancelar se ainda há títulos em aberto do pedido
                $hasOpenReceivables = \App\Models\Receivable::where('tenant_id', $tenantId)
                    ->where('order_id', $order->id)
                    ->where('status', '!=', 'canceled')
                    ->where('status', '!=', 'paid')
                    ->where('amount', '>', 0)
                    ->exists();
                
                // Só cancela se todos os itens foram devolvidos E não há títulos em aberto
                // Caso contrário, marca como partial_returned (ainda há pendências financeiras)
                if (!$hasOpenReceivables) {
                    // Todos os itens devolvidos E todos os valores estornados → CANCELADO
                    $newStatus = 'canceled';
                } else {
                    // Todos os itens devolvidos mas ainda há títulos em aberto → DEVOLUÇÃO PARCIAL
                    $newStatus = 'partial_returned';
                    $anyReturned = true; // Garante que será tratado como devolução
                }
            } elseif ($anyReturned || $order->getItemsWithPartialReturns()->isNotEmpty()) {
                // Há devoluções parciais (algum item ainda tem quantidade restante)
                // OU já havia devoluções anteriores (verificado pelo getItemsWithPartialReturns)
                // IMPORTANTE: Isso garante que mesmo se você "tirar" um item do formulário,
                // mas ainda há outros itens com devoluções anteriores ou atuais, o status será partial_returned
                $newStatus = 'partial_returned';
            }
            // Se não entrou em nenhuma condição acima, mantém o status atual ($newStatus = $oldStatus)

            if ($newStatus !== $oldStatus) {
                $order->status = $newStatus;
                $order->save();
                // Auditoria de mudança de status
                \App\Models\OrderAudit::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'action' => 'status_changed',
                    'notes' => 'Status alterado por devolução',
                    'changes' => [ 'status' => [ 'old' => $oldStatus, 'new' => $newStatus ] ],
                ]);
            }

            // Sugerir NF-e de devolução quando parcial e houver NF-e de saída emitida
            if ($newStatus === 'partial_returned') {
                // Usa accessor hasSuccessfulNfe do Order
                $hasIssuedNfe = $order->has_successful_nfe;
                $refKey = null;
                $nfeNumber = null;
                if ($hasIssuedNfe) {
                    try {
                        $lastNfe = $order->latestNfeNoteCompat;
                        $refKey = $lastNfe->chave_acesso ?? null;
                        $nfeNumber = $lastNfe->numero_nfe ?? null;
                    } catch (\Throwable $e) {}
                }
                if ($hasIssuedNfe) {
                    $message = 'Devolução parcial registrada. Este pedido possui NF-e transmitida (Nº ' . ($nfeNumber ?? '—') . '). ';
                    $message .= 'Para manter a conformidade fiscal, você deve emitir uma NF-e de devolução (tipo 1 ou 1A) que referencia a NF-e original.';
                    return redirect()->route('orders.edit', $order->id)
                        ->with('warning', $message)
                        ->with('nfe_preset_operation', 'devolucao_venda')
                        ->with('nfe_auto_open', false)
                        ->with('nfe_reference_key', $refKey)
                        ->with('nfe_return_qty', $assignedQty);
                }
            }
        } catch (\Throwable $e) { /* ignore status update issues */ }

        // Calcular se é devolução total para cancelamento de NF-e
        $orderTotal = (float) $order->total_amount;
        $refundProportion = $orderTotal > 0 ? ($totalRefund / $orderTotal) : 0;
        $isTotalReturn = $refundProportion >= 0.95;

        // Cancelar NF-e original na SEFAZ se solicitado em devolução total
        $shouldCancelNfe = $isTotalReturn && ($v['cancel_nfe'] ?? false);
        if ($shouldCancelNfe) {
            try {
                $hasIssuedNfe = $order->has_successful_nfe;
                if ($hasIssuedNfe) {
                    $nfeNote = $order->latestNfeNoteCompat;
                    if ($nfeNote) {
                        // Verificar se pode cancelar (dentro de 24h, sem CC-e, etc.)
                        $statusNorm = strtolower((string) $nfeNote->status);
                        $hasProt = !empty($nfeNote->protocolo_autorizacao ?? $nfeNote->protocolo ?? null);
                        
                        if (in_array($statusNorm, ['emitted','transmitida'], true) && $hasProt) {
                            // Verificar prazo de 24h
                            $emitAt = $nfeNote->emitted_at ?: $nfeNote->data_emissao ?: null;
                            $canCancel = true;
                            $cancelError = null;
                            
                            if ($emitAt && now()->diffInHours($emitAt) > 24) {
                                $canCancel = false;
                                $cancelError = 'Cancelamento não permitido após 24 horas da autorização.';
                            }
                            
                            // Verificar se já tem CC-e
                            $hasCce = false;
                            try { 
                                $hasCce = is_array($nfeNote->response_received ?? null) && !empty(($nfeNote->response_received)['cce_response'] ?? null); 
                            } catch (\Throwable $e) {}
                            
                            if ($hasCce || (string)$nfeNote->status === 'com_cc') {
                                $canCancel = false;
                                $cancelError = 'Esta NF-e possui Carta de Correção registrada. Cancelamento não permitido.';
                            }
                            
                            if ($canCancel) {
                                // Justificativa padrão se não fornecida
                                $justification = $v['nfe_cancel_justification'] ?? 'Cancelamento por devolução total do pedido #' . $order->number;
                                
                                // Verificar permissão
                                if (auth()->user()->hasPermission('nfe.cancel')) {
                                    try {
                                        $svc = app(\App\Services\NFeService::class);
                                        $chave = $nfeNote->chave_acesso ?? null;
                                        
                                        if (!$chave) {
                                            \Log::warning('NF-e sem chave de acesso para cancelamento', [
                                                'nfe_id' => $nfeNote->id,
                                                'order_id' => $order->id
                                            ]);
                                        } else {
                                            // Preparar configurações extras
                                            $extras = [];
                                            try {
                                                $emit = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
                                                if ($emit) {
                                                    $extras['ambiente'] = \App\Models\Setting::get('nfe.environment', \App\Models\Setting::getGlobal('services.delphi.environment', (config('app.env')==='production'?'producao':'homologacao')));
                                                    $extras['configuracoes'] = [
                                                        'path_schemas' => base_path('DelphiEmissor/Win32/Debug/Schemas/'),
                                                        'path_xml' => base_path('DelphiEmissor/Win32/Debug/nfe/'),
                                                    ];
                                                }
                                            } catch (\Throwable $e) {}
                                            
                                            // Buscar XML original se disponível
                                            $xmlPath = null;
                                            try {
                                                if (!empty($nfeNote->xml_path)) {
                                                    $xmlPath = $nfeNote->xml_path;
                                                }
                                            } catch (\Throwable $e) {}
                                            
                                            // Chamar serviço de cancelamento
                                            $res = $svc->cancelarNFe($chave, $justification, $xmlPath, $extras);
                                            
                                            if ($res['success'] ?? false) {
                                                // Processar resposta de sucesso
                                                $payloadResp = $res['data'] ?? [];
                                                $cStat = (string)($payloadResp['cStat'] ?? '');
                                                $xMotivo = (string)($payloadResp['xMotivo'] ?? '');
                                                
                                                // Extrair cStat e xMotivo do XML se não vier no JSON
                                                $xmlRet = (string)($payloadResp['xml_retorno'] ?? '');
                                                if ($cStat === '' && $xmlRet !== '') {
                                                    try {
                                                        $sx = @simplexml_load_string($xmlRet);
                                                        if ($sx !== false) {
                                                            $sx->registerXPathNamespace('nfe', 'http://www.portalfiscal.inf.br/nfe');
                                                            $c = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:cStat');
                                                            if (is_array($c) && isset($c[0])) { $cStat = (string)$c[0]; }
                                                            $m = $sx->xpath('//nfe:retEvento/nfe:infEvento/nfe:xMotivo');
                                                            if (is_array($m) && isset($m[0])) { $xMotivo = (string)$m[0]; }
                                                        }
                                                    } catch (\Throwable $e) {}
                                                }
                                                
                                                // Apenas homologado (135) ou duplicidade (573) pode marcar como cancelada
                                                if (in_array($cStat, ['135','573'], true)) {
                                                    // Salvar XML de cancelamento se presente
                                                    try {
                                                        if ($xmlRet !== '') {
                                                            $digits = preg_replace('/\D+/', '', (string)($chave ?: ($payloadResp['chave'] ?? '')));
                                                            if ($digits !== '') {
                                                                $dir = base_path('DelphiEmissor/Win32/Debug/nfe/');
                                                                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                                                                $seq2 = (int)($payloadResp['sequencia'] ?? 1);
                                                                $file = $dir . $digits . '-procEventoNFe-seq' . $seq2 . '.xml';
                                                                @file_put_contents($file, $xmlRet);
                                                                if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'cancel_xml_path')) { 
                                                                    $nfeNote->cancel_xml_path = $file; 
                                                                }
                                                            }
                                                        }
                                                    } catch (\Throwable $e) {}
                                                    
                                                    // Atualiza status e histórico
                                                    $nfeNote->status = 'cancelled';
                                                    $nfeNote->cancelamento_justificativa = $justification;
                                                    $nfeNote->cancelamento_data = now();
                                                    try {
                                                        $prev = (array)($nfeNote->response_received ?? []);
                                                        $prev['cancel_response'] = $payloadResp;
                                                        $nfeNote->response_received = $prev;
                                                    } catch (\Throwable $e) {}
                                                    $nfeNote->save();
                                                    
                                                    \Log::info('NF-e cancelada com sucesso na devolução total', [
                                                        'nfe_id' => $nfeNote->id,
                                                        'order_id' => $order->id,
                                                        'cStat' => $cStat
                                                    ]);
                                                } else {
                                                    \Log::warning('SEFAZ não confirmou cancelamento de NF-e na devolução', [
                                                        'nfe_id' => $nfeNote->id,
                                                        'order_id' => $order->id,
                                                        'cStat' => $cStat,
                                                        'xMotivo' => $xMotivo
                                                    ]);
                                                }
                                            } else {
                                                \Log::warning('Falha ao cancelar NF-e na devolução total', [
                                                    'nfe_id' => $nfeNote->id,
                                                    'order_id' => $order->id,
                                                    'error' => $res['error'] ?? 'erro desconhecido'
                                                ]);
                                            }
                                        }
                                    } catch (\Throwable $e) {
                                        \Log::warning('Erro ao cancelar NF-e na devolução total', [
                                            'nfe_id' => $nfeNote->id ?? null,
                                            'order_id' => $order->id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            } else {
                                \Log::warning('NF-e não pode ser cancelada na devolução total', [
                                    'nfe_id' => $nfeNote->id,
                                    'order_id' => $order->id,
                                    'error' => $cancelError
                                ]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('Erro ao processar cancelamento de NF-e na devolução', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Se solicitado, redireciona para emissão de NF-e de devolução do próprio pedido (apenas para devoluções parciais)
        if ($request->boolean('emitNfe', false) && !$isTotalReturn) {
            // Localiza chave da NFe emitida anteriormente para referenciar na devolução
            $refKey = optional(\App\Models\NfeNote::where('tenant_id', $tenantId)
                ->where('numero_pedido', $order->number)
                ->where('status', 'emitted')
                ->orderByDesc('id')
                ->first())->chave_acesso;
            // Envia o usuário para a tela de pedido com sinalização para abrir o modal de NFe pré-preenchido como devolução
            return redirect()->route('orders.edit', $order->id)
                ->with('info', 'Devolução registrada. Configure e emita a NF-e de devolução.')
                ->with('nfe_preset_operation', 'devolucao_venda')
                ->with('nfe_auto_open', true)
                ->with('nfe_reference_key', $refKey)
                ->with('nfe_return_qty', $assignedQty);
        }

        $successMessage = 'Devolução registrada.';
        if ($shouldCancelNfe) {
            $successMessage .= ' Cancelamento de NF-e processado.';
        }
        
        return redirect()->route('returns.index')->with('success', $successMessage);
    }

    /**
     * Aplica compensação automática de um crédito/abatimento (valor positivo) aos títulos em aberto do pedido.
     * A compensação reduz o amount dos recebíveis alvo; quando zerados, marca como canceled.
     * Retorna o valor remanescente não compensado.
     */
    private function applyCompensationToOrderReceivables(Order $order, float $amountToApply): float
    {
        $tenantId = auth()->user()->tenant_id;
        $remaining = round($amountToApply, 2);
        if ($remaining <= 0) { return 0.0; }
        // Seleciona títulos do próprio pedido primeiro, abertos ou parciais, com valor positivo
        $targets = \App\Models\Receivable::where('tenant_id', $tenantId)
            ->where('client_id', $order->client_id)
            ->where('order_id', $order->id)
            ->whereIn('status', ['open','partial'])
            ->where('amount', '>', 0)
            ->orderBy('due_date', 'asc')
            ->get();
        foreach ($targets as $r) {
            if ($remaining <= 0) { break; }
            $curr = round((float)$r->amount, 2);
            if ($curr <= 0) { continue; }
            if ($remaining >= $curr - 0.001) {
                // quita por compensação: zera valor e cancela título
                $remaining = round($remaining - $curr, 2);
                $r->amount = 0;
                $r->status = 'canceled';
                $r->save();
            } else {
                // abatimento parcial: reduz valor e marca como partial
                $r->amount = round($curr - $remaining, 2);
                $r->status = $r->amount > 0 ? 'partial' : 'canceled';
                $remaining = 0.0;
                $r->save();
            }
        }
        return max(0.0, $remaining);
    }
}


