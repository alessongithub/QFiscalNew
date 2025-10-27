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
        return view('returns.create', compact('order','items','already'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('returns.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'refund_type' => 'required|in:abatement,refund,credit',
            'refund_method' => 'nullable|in:cash,card,pix',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'nullable|numeric|min:0',
        ]);
        $order = Order::where('tenant_id',$tenantId)->findOrFail($v['order_id']);
        $itemsMap = $order->items()->get()->keyBy('id');

        $ret = ReturnModel::create([
            'tenant_id' => $tenantId,
            'order_id' => $order->id,
            'refund_method' => $v['refund_type'] === 'refund' ? ($v['refund_method'] ?? 'cash') : ($v['refund_type'] === 'credit' ? 'credit' : 'abatement'),
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
                'type' => 'entry',
                'quantity' => $qty,
                'unit_price' => $unit,
                'document' => 'Devolução Pedido #'.$order->id,
                'note' => 'Devolução',
            ]);
        }

        $ret->update(['total_refund' => $totalRefund]);

        // Registrar auditoria de devolução
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'returned',
            'notes' => 'Devolução registrada - Tipo: ' . ($v['refund_type'] === 'credit' ? 'Crédito' : ($v['refund_type'] === 'abatement' ? 'Abatimento' : 'Estorno')) . ' - Valor: R$ ' . number_format($totalRefund, 2, ',', '.'),
            'changes' => [
                'return_id' => $ret->id,
                'refund_type' => $v['refund_type'],
                'total_refund' => $totalRefund,
                'items_returned' => $assignedQty,
                'refund_method' => $v['refund_method'] ?? null
            ]
        ]);

        // financeiro: abater, estornar, ou crédito
        if ($totalRefund > 0) {
            if ($v['refund_type'] === 'credit') {
                // Crédito ao cliente: título negativo em aberto, com compensação automática nos títulos do pedido
                $credit = Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $order->client_id,
                    'order_id' => $order->id,
                    'description' => 'Crédito por Devolução Pedido '.$order->number,
                    'amount' => -$totalRefund,
                    'due_date' => now()->toDateString(),
                    'status' => 'open',
                    'payment_method' => 'credit',
                ]);
                $left = $this->applyCompensationToOrderReceivables($order, abs((float)$credit->amount));
                $credit->amount = -round($left, 2);
                if ($left <= 0.001) { $credit->status = 'canceled'; }
                $credit->save();
            } elseif ($v['refund_type'] === 'abatement') {
                // Abatimento: título negativo em aberto, com compensação automática nos títulos do pedido
                $abat = Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $order->client_id,
                    'order_id' => $order->id,
                    'description' => 'Abatimento Devolução Pedido '.$order->number,
                    'amount' => -$totalRefund,
                    'due_date' => now()->toDateString(),
                    'status' => 'open',
                    'payment_method' => 'abatement',
                ]);
                $left = $this->applyCompensationToOrderReceivables($order, abs((float)$abat->amount));
                $abat->amount = -round($left, 2);
                if ($left <= 0.001) { $abat->status = 'canceled'; }
                $abat->save();
            } else { // refund immediate
                // Estorno imediato: título negativo pago (impacta caixa)
                Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $order->client_id,
                    'order_id' => $order->id,
                    'description' => 'Estorno Devolução Pedido '.$order->number,
                    'amount' => -$totalRefund,
                    'due_date' => now()->toDateString(),
                    'status' => 'paid',
                    'received_at' => now(),
                    'payment_method' => $v['refund_method'] ?? 'cash',
                ]);
            }
        }

        // Se solicitado, redireciona para emissão de NF-e de devolução do próprio pedido
        if ($request->boolean('emitNfe', false)) {
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

        return redirect()->route('returns.index')->with('success','Devolução registrado.');
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


