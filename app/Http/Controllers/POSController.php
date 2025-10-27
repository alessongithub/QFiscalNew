<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Receivable;
use App\Models\Client;
use Illuminate\Http\Request;

class POSController extends Controller
{
    private function generateOrderNumber(int $tenantId): string
    {
        $last = Order::where('tenant_id', $tenantId)
            ->orderByRaw('CAST(number AS UNSIGNED) DESC')
            ->first();
        $n = 0;
        if ($last && is_numeric($last->number)) {
            $n = (int) $last->number;
        }
        return str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);
    }
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        return view('pos.index');
    }

    public function sales(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $orders = Order::where('tenant_id',$tenantId)->where('title','PDV')->orderByDesc('id')->paginate(12);
        return view('pos.sales', compact('orders'));
    }

    public function receipt(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $items = $order->items()->get();
        return view('pos.receipt', compact('order','items'));
    }

    public function printOrder(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $items = $order->items()->get();
        return view('pos.print', compact('order','items'));
    }

    public function printOrder80(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $items = $order->items()->get();
        return view('pos.print-80mm', compact('order','items'));
    }

    public function store(Request $request)
    {
        try {
            abort_unless(auth()->user()->hasPermission('pos.create'), 403);
            $tenantId = auth()->user()->tenant_id;
            
            // Debug básico
            if (!$request->has('items') || empty($request->input('items'))) {
                return response()->json(['ok' => false, 'error' => 'Nenhum item no carrinho'], 422);
            }
            
            // Validação mínima
            $items = $request->input('items', []);
            $payment_method = $request->input('payment_method', 'cash');
            $payment_type = $request->input('payment_type', 'immediate');
            $client_id = $request->input('client_id');
            $installments = (int) $request->input('installments', 3);
            $installment_method = $request->input('installment_method', 'boleto');
            $entry_amount = (float) $request->input('entry_amount', 0);
            
            // Validação básica dos itens
            foreach ($items as $item) {
                if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
                    return response()->json(['ok' => false, 'error' => 'Dados do produto incompletos'], 422);
                }
            }
            // Respeita configuração de cliente obrigatório; se não exigir, usa cliente padrão
            $requireClient = \App\Models\Setting::get('pos.require_client','0')==='1';
            if ($requireClient && empty($client_id)) {
                return response()->json(['ok'=>false,'error'=>'Cliente obrigatório no PDV'], 422);
            }
            if (!$requireClient && empty($client_id)) {
                // Gera CPF numérico único por tenant (ex.: 888 + tenantId zerado até 11 dígitos)
                $cpfCnpj = '888' . str_pad((string) $tenantId, 8, '0', STR_PAD_LEFT);
                $defaultClient = Client::firstOrCreate(
                    ['tenant_id' => $tenantId, 'cpf_cnpj' => $cpfCnpj],
                    ['name' => 'Consumidor Final', 'status' => 'active', 'type' => 'pf', 'consumidor_final' => 'S']
                );
                $client_id = $defaultClient->id;
            }

            // Valida todos os itens antes de criar o pedido (evita pedidos zerados)
            $total = 0.0;
            foreach ($items as $it) {
                $product = Product::where('tenant_id',$tenantId)->find($it['product_id']);
                if (!$product) {
                    return response()->json(['ok' => false, 'error' => 'Produto não encontrado: ' . $it['product_id']], 422);
                }
                // PDV independente: respeita somente a flag específica do PDV
                $posBlock = \App\Models\Setting::get('pos.block_without_stock','1')==='1';
                if ($posBlock && (string)$product->type === 'product') {
                    $entry = (float) StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
                    $exit = (float) StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
                    $balance = $entry - $exit;
                    if ($balance < (float)$it['quantity'] - 1e-6) {
                        return response()->json(['ok'=>false,'error'=>'Estoque insuficiente para '.$product->name], 422);
                    }
                }
                $qty = (float)$it['quantity'];
                $unitPrice = (float)$it['unit_price'];
                $total += round($qty * $unitPrice, 2);
            }

            // Somente agora cria o pedido
            $number = $this->generateOrderNumber($tenantId);
            $order = Order::create([
                'tenant_id' => $tenantId,
                'client_id' => $client_id,
                'number' => $number,
                'title' => 'PDV',
                'status' => 'fulfilled',
                'total_amount' => 0,
                'created_by' => auth()->id(),
            ]);

            // Persistir itens e baixar estoque
            foreach ($items as $it) {
                $product = Product::where('tenant_id',$tenantId)->find($it['product_id']);
            $qty = (float)$it['quantity'];
            $unitPrice = (float)$it['unit_price'];
                $line = round($qty * $unitPrice, 2);
            OrderItem::create([
                'tenant_id' => $tenantId,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'description' => null,
                'quantity' => $qty,
                'unit' => $product->unit,
                'unit_price' => $unitPrice,
                'discount_value' => 0,
                'addition_value' => 0,
                'line_total' => $line,
            ]);
            // baixa estoque apenas para produtos físicos
            if ((string)$product->type === 'product') {
                StockMovement::create([
                    'tenant_id' => $tenantId,
                    'product_id' => $product->id,
                    'type' => 'exit',
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'document' => 'PDV #'.$order->id,
                    'note' => 'Saída PDV',
                ]);
            }
        }

            $order->update(['total_amount' => $total]);

            // Registrar auditoria de criação
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'notes' => 'Venda realizada no PDV',
                'changes' => [
                    'source' => 'pos',
                    'payment_type' => $payment_type,
                    'payment_method' => $payment_method,
                    'total_amount' => $total,
                    'items_count' => count($items),
                    'timestamp' => now()->toISOString()
                ]
            ]);

            // Recebimento conforme pedidos (à vista, parcelado ou misto)
            if ($payment_type === 'immediate') {
                Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $client_id,
                    'order_id' => $order->id,
                    'description' => sprintf('PDV #%d - pagamento à vista', $order->id),
                    'amount' => $total,
                    'due_date' => now()->toDateString(),
                    'status' => 'paid',
                    'received_at' => now(),
                    'payment_method' => $payment_method,
                ]);
            } elseif ($payment_type === 'invoice') {
            $schedule = $request->input('schedule', []);
            if (!empty($schedule)) {
                $valid = []; $sum = 0.0;
                foreach ($schedule as $sc) {
                    $amt = round((float)($sc['amount'] ?? 0), 2);
                    $due = $sc['due_date'] ?? null;
                    if ($amt <= 0 || empty($due)) { continue; }
                    $sum += $amt;
                    $valid[] = [ 'amount' => $amt, 'due_date' => \Carbon\Carbon::parse($due)->toDateString() ];
                }
                if (count($valid) === 0 || abs($sum - $total) > 0.01) {
                    return response()->json(['ok'=>false,'error'=>'Parcelas inválidas/soma diferente do total'], 422);
                }
                $den = count($valid); $idx = 0;
                foreach ($valid as $sc) {
                    $idx++;
                    Receivable::create([
                        'tenant_id' => $tenantId,
                        'client_id' => $client_id,
                        'order_id' => $order->id,
                        'description' => sprintf('PDV #%d - Parcela %d/%d', $order->id, $idx, $den),
                        'amount' => $sc['amount'],
                        'due_date' => $sc['due_date'],
                        'status' => 'open',
                        'payment_method' => $installment_method,
                    ]);
                }
            } else {
                // padrão: 3x iguais, primeiro vencimento +30 dias
                $firstDue = now()->addDays(30)->toDateString();
                $interval = 30; $den=$installments;
                $per = round($total/$installments, 2);
                for($i=1;$i<=$installments;$i++){
                    $due = \Carbon\Carbon::parse($firstDue)->addDays(($i-1)*$interval)->toDateString();
                    Receivable::create([
                        'tenant_id' => $tenantId,
                        'client_id' => $client_id,
                        'order_id' => $order->id,
                        'description' => sprintf('PDV #%d - Parcela %d/%d', $order->id, $i, $den),
                        'amount' => $per,
                        'due_date' => $due,
                        'status' => 'open',
                        'payment_method' => $installment_method,
                    ]);
                }
            }
            } else { // mixed
                $entry = $entry_amount;
            if ($entry > 0) {
                if ($entry > $total) { return response()->json(['ok'=>false,'error'=>'Entrada maior que total'], 422); }
                Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $client_id,
                    'order_id' => $order->id,
                    'description' => sprintf('PDV #%d - entrada', $order->id),
                    'amount' => round($entry,2),
                    'due_date' => now()->toDateString(),
                    'status' => 'paid',
                    'received_at' => now(),
                    'payment_method' => $payment_method,
                ]);
            }
            $remaining = round($total - $entry, 2);
            if ($remaining > 0) {
                // usa o valor já definido no início
                $firstDue = now()->addDays(30)->toDateString();
                $per = round($remaining/$installments, 2);
                for($i=1;$i<=$installments;$i++){
                    $due = \Carbon\Carbon::parse($firstDue)->addDays(($i-1)*30)->toDateString();
                    Receivable::create([
                        'tenant_id' => $tenantId,
                        'client_id' => $client_id,
                        'order_id' => $order->id,
                        'description' => sprintf('PDV #%d - Parcela %d/%d', $order->id, $i, $installments),
                        'amount' => $per,
                        'due_date' => $due,
                        'status' => 'open',
                        'payment_method' => $installment_method,
                    ]);
                }
            }
        }

            return response()->json(['ok' => true, 'order_id' => $order->id, 'total' => $total]);
            
        } catch (\Exception $e) {
            \Log::error('POS Store Error during order processing:', [
                'message' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'tenant_id' => $tenantId ?? 'unknown',
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return response()->json(['ok' => false, 'error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }
}


