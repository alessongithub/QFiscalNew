<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Models\SmtpConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use App\Http\Controllers\Admin\EmailTestController;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = Quote::where('tenant_id', $tenantId)->with(['client','items']);
        if ($s = $request->get('search')) {
            $q->where(function ($qq) use ($s) {
                $qq->where('number', 'like', "%{$s}%")
                   ->orWhere('title', 'like', "%{$s}%")
                   ->orWhereHas('client', fn($qc) => $qc->where('name','like',"%{$s}%"));
            });
        }
        if ($st = $request->get('status')) { $q->where('status', $st); }
        if ($client = $request->get('client')) {
            $q->whereHas('client', fn($qc) => $qc->where('name','like',"%{$client}%"));
        }
        if ($num = $request->get('number')) { $q->where('number','like',"%{$num}%"); }
        if ($title = $request->get('title')) { $q->where('title','like',"%{$title}%"); }
        if ($from = $request->get('date_from')) { $q->whereDate('created_at','>=',$from); }
        if ($to = $request->get('date_to')) { $q->whereDate('created_at','<=',$to); }
        $numberOrder = $request->get('number_order');
        if (in_array($numberOrder, ['asc','desc'], true)) {
            $q->orderByRaw('CAST(number AS UNSIGNED) ' . strtoupper($numberOrder));
        } else {
            $q->orderByDesc('id');
        }
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $quotes = $q->paginate($perPage)->appends($request->query());
        
        // Verificar e atualizar orçamentos expirados
        foreach ($quotes as $quote) {
            if ($quote->validity_date && $quote->validity_date < now()->toDateString() && $quote->status === 'awaiting') {
                $quote->update(['status' => 'expirado']);
            }
        }
        
        return view('quotes.index', compact('quotes'));
    }

    public function whatsapp(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $quote->loadMissing(['client','items']);
        $client = optional($quote->client);
        $rawPhone = preg_replace('/\D/', '', (string) ($client->phone ?? ''));
        $phone = (substr($rawPhone,0,2) === '55') ? $rawPhone : ('55' . $rawPhone);

        $template = (string) \App\Models\Setting::get('whatsapp.quote_template', 'Olá {cliente}, seu orçamento #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}');
        $statusMap = ['awaiting'=>'Aguardando','approved'=>'Aprovado','not_approved'=>'Rejeitado','canceled'=>'Cancelado'];
        $statusText = $statusMap[$quote->status] ?? $quote->status;
        $itemsLines = $quote->items->map(function($i){
            $qty = number_format((float)$i->quantity, 3, ',', '.');
            $price = number_format((float)$i->unit_price, 2, ',', '.');
            return "- {$i->name} ({$qty} {$i->unit}) x R$ {$price}";
        })->implode("\n");
        $repl = [
            '{cliente}' => (string) ($client->name ?? 'cliente'),
            '{numero}' => (string) $quote->number,
            '{titulo}' => (string) ($quote->title ?? ''),
            '{total}' => number_format((float)$quote->total_amount, 2, ',', '.'),
            '{status}' => (string) $statusText,
            '{itens}' => $itemsLines,
        ];
        $text = strtr($template, $repl);
        $url = 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
        return redirect()->away($url);
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('quotes.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('quotes.create', compact('clients'));
    }

    private function generateNumber(int $tenantId): string
    {
        $last = Quote::where('tenant_id',$tenantId)->orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
        $n = 0; if ($last && is_numeric($last->number)) { $n = (int)$last->number; }
        return str_pad((string)($n+1), 6, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('quotes.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'number' => 'nullable|string|max:30',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'validity_date' => 'nullable|date|after_or_equal:today',
            'discount_total' => 'nullable|numeric|min:0',
            'payment_methods' => 'nullable|array',
            'card_installments' => 'nullable|integer|min:1|max:24',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.delivery_date' => 'nullable|date|after_or_equal:today',
        ]);

        $itemsIn = $request->input('items', []);
        $items = []; $total=0; $disc=0; $add=0;
        foreach ($itemsIn as $it) {
            $productId = (int)($it['product_id'] ?? 0);
            $qty = (float)($it['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) { continue; }
            $product = Product::where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->where('active', 1)
                ->whereIn('type', ['product','service'])
                ->first();
            if (!$product) { continue; }
            $allowNegative = \App\Models\Setting::get('stock.allow_negative','0')==='1';
            if (!$allowNegative && $product->type === 'product') {
                $entry = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
                $exit = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
                $balance = (float)$entry - (float)$exit;
                if ($balance + 1e-6 < $qty) { continue; }
            }
            $price = (float)($product->price ?? 0);
            $discountValue = (float)($it['discount_value'] ?? 0);
            $line = round(($qty * $price) - $discountValue, 2);
            $items[] = [
                'product_id'=>$product->id,
                'name'=>$product->name,
                'description'=>$it['description'] ?? null,
                'delivery_date'=>!empty($it['delivery_date']) ? $it['delivery_date'] : null,
                'quantity'=>$qty,
                'unit'=>$product->unit ?? null,
                'unit_price'=>$price,
                'discount_value'=>$discountValue,
                'addition_value'=>0,
                'line_total'=>$line,
            ];
            $total += $line;
        }

        $number = $v['number'] ?? $this->generateNumber($tenantId);
        $discountTotal = (float)($v['discount_total'] ?? 0);
        $finalTotal = $total - $discountTotal;
        
        $quote = Quote::create([
            'tenant_id'=>$tenantId,
            'client_id'=>$v['client_id'],
            'number'=>$number,
            'title'=>$v['title'],
            'status'=>'awaiting',
            'total_amount'=>$finalTotal,
            'discount_total'=>$discountTotal,
            'addition_total'=>$add,
            'notes'=>$v['notes'] ?? null,
            'validity_date'=>$v['validity_date'] ?? null,
            'payment_methods'=>$v['payment_methods'] ?? null,
            'card_installments'=>$v['card_installments'] ?? null,
            'volume_qtd' => $request->input('volume_qtd'),
            'volume_especie' => $request->input('volume_especie'),
            'peso_bruto' => $request->input('peso_bruto'),
            'peso_liquido' => $request->input('peso_liquido'),
            'valor_seguro' => $request->input('valor_seguro'),
            'outras_despesas' => $request->input('outras_despesas'),
            'created_by' => auth()->id(),
        ]);

        foreach ($items as $it) {
            QuoteItem::create([...$it, 'tenant_id'=>$tenantId, 'quote_id'=>$quote->id]);
        }

        // Registrar auditoria de criação
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'notes' => 'Orçamento criado',
        ]);

        return redirect()->route('quotes.index')->with('success','Orçamento criado.');
    }

    public function edit(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.edit'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get();
        $items = $quote->items()->orderBy('id')->get();
        return view('quotes.edit', compact('quote','clients','items'));
    }

    public function update(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.edit'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $v = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:255',
            'validity_date' => 'nullable|date|after_or_equal:today',
            'discount_total' => 'nullable|numeric|min:0',
            'payment_methods' => 'nullable|array',
            'card_installments' => 'nullable|integer|min:1|max:24',
            'status' => 'required|in:awaiting,approved,not_approved,canceled,expirado',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.delivery_date' => 'nullable|date|after_or_equal:today',
        ]);
        $statusNorm = strtolower(trim((string) $quote->status));
        if (in_array($statusNorm, ['approved','customer_notified','canceled'], true)) {
            return back()->with('error','Este orçamento não pode ser alterado no status atual.');
        }
        $originalStatus = $quote->status;
        $tenantId = auth()->user()->tenant_id;
        $itemsIn = $request->input('items', []);
        $items = []; $total=0; $disc=0; $add=0;
        foreach ($itemsIn as $it) {
            $productId = (int)($it['product_id'] ?? 0);
            $qty = (float)($it['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) { continue; }
            $product = Product::where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->where('active', 1)
                ->whereIn('type', ['product','service'])
                ->first();
            if (!$product) { continue; }
            $allowNegative = \App\Models\Setting::get('stock.allow_negative','0')==='1';
            if (!$allowNegative && $product->type === 'product') {
                $entry = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
                $exit = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
                $balance = (float)$entry - (float)$exit;
                if ($balance + 1e-6 < $qty) { continue; }
            }
            $price = (float)($product->price ?? 0);
            $discountValue = (float)($it['discount_value'] ?? 0);
            $line = round(($qty * $price) - $discountValue, 2);
            $items[] = [
                'product_id'=>$product->id,
                'name'=>$product->name,
                'description'=>$it['description'] ?? null,
                'delivery_date'=>!empty($it['delivery_date']) ? $it['delivery_date'] : null,
                'quantity'=>$qty,
                'unit'=>$product->unit ?? null,
                'unit_price'=>$price,
                'discount_value'=>$discountValue,
                'addition_value'=>0,
                'line_total'=>$line,
            ];
            $total += $line;
        }

        $discountTotal = (float)($v['discount_total'] ?? 0);
        $finalTotal = $total - $discountTotal;

        // Preparar dados de atualização
        $updateData = [
            'client_id' => $v['client_id'],
            'title' => $v['title'],
            'notes' => $v['notes'] ?? null,
            'validity_date' => $v['validity_date'] ?? null,
            'payment_methods' => $v['payment_methods'] ?? null,
            'card_installments' => $v['card_installments'] ?? null,
            'status' => $v['status'],
            'total_amount' => $finalTotal,
            'discount_total' => $discountTotal,
            'addition_total' => $add,
            'volume_qtd' => $request->input('volume_qtd'),
            'volume_especie' => $request->input('volume_especie'),
            'peso_bruto' => $request->input('peso_bruto'),
            'peso_liquido' => $request->input('peso_liquido'),
            'valor_seguro' => $request->input('valor_seguro'),
            'outras_despesas' => $request->input('outras_despesas'),
            'updated_by' => auth()->id(),
            'last_edited_at' => now(),
        ];

        // Se está sendo aprovado, registrar log de aprovação
        if ($originalStatus !== 'approved' && $v['status'] === 'approved') {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = auth()->user()->name;
            $updateData['approval_reason'] = 'Aprovado via edição do orçamento';
        }

        $quote->update($updateData);

        if (!empty($items)) {
            QuoteItem::where('quote_id', $quote->id)->delete();
            foreach ($items as $it) {
                QuoteItem::create([...$it, 'tenant_id'=>$tenantId, 'quote_id'=>$quote->id]);
            }
        }
        if ($originalStatus !== 'approved' && $quote->status === 'approved') {
            $tenantId = auth()->user()->tenant_id;
            $last = \App\Models\Order::where('tenant_id',$tenantId)->orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
            $n=0; if ($last && is_numeric($last->number)) { $n=(int)$last->number; }
            $number = str_pad((string)($n+1), 6, '0', STR_PAD_LEFT);
            
            // Calcular valor bruto real (soma dos itens sem desconto algum)
            $grossTotal = 0;
            foreach ($quote->items as $item) {
                $grossTotal += (float)$item->quantity * (float)$item->unit_price;
            }
            
            $order = \App\Models\Order::create([
                'tenant_id'=>$tenantId,
                'client_id'=>$quote->client_id,
                'number'=>$number,
                'title'=>$quote->title,
                'status'=>'open',
                'total_amount'=>$grossTotal,
                'discount_total'=>$quote->discount_total,
                'addition_total'=>$quote->addition_total,
                'additional_info'=>$quote->additional_info,
                'fiscal_info'=>$quote->fiscal_info,
                'volume_qtd'=>$quote->volume_qtd,
                'volume_especie'=>$quote->volume_especie,
                'peso_bruto'=>$quote->peso_bruto,
                'peso_liquido'=>$quote->peso_liquido,
                'valor_seguro'=>$quote->valor_seguro,
                'outras_despesas'=>$quote->outras_despesas,
            ]);
            foreach ($quote->items as $it) {
                \App\Models\OrderItem::create([
                    'tenant_id'=>$tenantId,
                    'order_id'=>$order->id,
                    'product_id'=>$it->product_id,
                    'name'=>$it->name,
                    'description'=>$it->description,
                    'quantity'=>$it->quantity,
                    'unit'=>$it->unit,
                    'unit_price'=>$it->unit_price,
                    'discount_value'=>$it->discount_value,
                    'addition_value'=>$it->addition_value,
                    'line_total'=>$it->line_total,
                ]);
            }
        }

        // Registrar auditoria de atualização
        $action = 'updated';
        $notes = 'Orçamento atualizado';
        
        if ($originalStatus !== $v['status']) {
            if ($v['status'] === 'approved') {
                $action = 'approved';
                $notes = 'Orçamento aprovado';
            } elseif ($v['status'] === 'canceled') {
                $action = 'canceled';
                $notes = 'Orçamento cancelado';
            } elseif ($v['status'] === 'not_approved') {
                $action = 'rejected';
                $notes = 'Orçamento reprovado';
            }
        }

        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'notes' => $notes,
        ]);

        return redirect()->route('quotes.index')->with('success','Orçamento aprovado e convertido em pedido.');
    }

    public function audit(Quote $quote)
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->hasPermission('quotes.audit'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        
        $audits = \App\Models\QuoteAudit::where('quote_id', $quote->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('quotes.audit', compact('quote', 'audits'));
    }

    public function print(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        
        $quote->load(['client', 'items', 'tenant']);
        return view('quotes.print', compact('quote'));
    }

    public function show(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        
        $quote->load(['client', 'items', 'tenant']);
        return view('quotes.show', compact('quote'));
    }

    public function destroy(Request $request, Quote $quote)
    {
        \Log::info('QuoteController::destroy iniciado', [
            'quote_id' => $quote->id,
            'quote_number' => $quote->number,
            'user_id' => auth()->user()->id,
            'user_name' => auth()->user()->name,
            'request_data' => $request->all()
        ]);

        abort_unless(auth()->user()->hasPermission('quotes.delete'), 403);
        \Log::info('Permissão quotes.delete verificada com sucesso');
        
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        \Log::info('Tenant_id verificado com sucesso', [
            'quote_tenant_id' => $quote->tenant_id,
            'user_tenant_id' => auth()->user()->tenant_id
        ]);
        
        // Verificar se já está cancelado
        if ($quote->status === 'canceled') {
            \Log::warning('Tentativa de cancelar orçamento já cancelado', [
                'quote_id' => $quote->id,
                'current_status' => $quote->status
            ]);
            return back()->with('error', 'Este orçamento já foi cancelado.');
        }
        
        \Log::info('Iniciando validação do request');
        $v = $request->validate([
            'cancel_reason' => 'required|string|min:10|max:500',
        ]);
        \Log::info('Validação do request concluída', ['validated_data' => $v]);
        
        \Log::info('Iniciando atualização do orçamento');
        // Cancelar o orçamento ao invés de deletar
        $quote->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'canceled_by' => auth()->user()->name,
            'cancel_reason' => $v['cancel_reason'],
        ]);
        \Log::info('Orçamento atualizado com sucesso', [
            'quote_id' => $quote->id,
            'new_status' => 'canceled',
            'cancel_reason' => $v['cancel_reason']
        ]);
        
        // Registrar auditoria de cancelamento
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'canceled',
            'notes' => 'Orçamento cancelado: ' . $v['cancel_reason'],
        ]);

        \Log::info('QuoteController::destroy concluído com sucesso');
        return redirect()->route('quotes.index')->with('success', 'Orçamento cancelado com sucesso. Esta ação não pode ser desfeita.');
    }

    public function approve(Quote $quote, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('quotes.approve'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array($quote->status, ['approved','canceled'], true)) {
            return back()->with('error','Ação não permitida para este status.');
        }
        $quote->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->user()->name,
            'approval_reason' => 'Aprovado via botão de aprovação'
        ]);

        // Registrar auditoria de aprovação
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'approved',
            'notes' => 'Orçamento aprovado via botão de aprovação',
        ]);

        return back()->with('success','Orçamento aprovado.');
    }

    public function notify(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.notify'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array($quote->status, ['canceled'], true)) {
            return back()->with('error','Ação não permitida para este status.');
        }
        $quote->status='customer_notified'; $quote->notified_at = now(); $quote->save();

        // Registrar auditoria de notificação
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'notified',
            'notes' => 'Cliente notificado sobre o orçamento',
        ]);

        return back()->with('success','Cliente notificado.');
    }

    public function reject(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.reject'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array($quote->status, ['approved','canceled'], true)) {
            return back()->with('error','Ação não permitida para este status.');
        }
        $quote->status='not_approved'; $quote->not_approved_at = now(); $quote->save();

        // Registrar auditoria de rejeição
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'notes' => 'Orçamento reprovado',
        ]);

        return back()->with('success','Orçamento reprovado.');
    }

    public function convert(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.convert'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array($quote->status, ['canceled'], true)) {
            return back()->with('error','Ação não permitida para este status.');
        }
        $tenantId = auth()->user()->tenant_id;
        if ($quote->status !== 'approved') {
            $quote->status = 'approved';
            $quote->approved_at = now();
            $quote->approved_by = auth()->user()->name;
            $quote->approval_reason = 'Aprovado via conversão em pedido';
            $quote->save();
        }
        $last = Order::where('tenant_id',$tenantId)->orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
        $n=0; if ($last && is_numeric($last->number)) { $n=(int)$last->number; }
        $number = str_pad((string)($n+1), 6, '0', STR_PAD_LEFT);

        // Calcular valor bruto real (soma dos itens sem desconto algum)
        $grossTotal = 0;
        foreach ($quote->items as $item) {
            $grossTotal += (float)$item->quantity * (float)$item->unit_price;
        }

        $order = Order::create([
            'tenant_id'=>$tenantId,
            'client_id'=>$quote->client_id,
            'number'=>$number,
            'title'=>$quote->title,
            'status'=>'open',
            'total_amount'=>$grossTotal,
            'discount_total'=>$quote->discount_total,
            'addition_total'=>$quote->addition_total,
            'additional_info'=>$quote->additional_info,
            'fiscal_info'=>$quote->fiscal_info,
            'created_by' => auth()->id(),
        ]);
        foreach ($quote->items as $it) {
            OrderItem::create([
                'tenant_id'=>$tenantId,
                'order_id'=>$order->id,
                'product_id'=>$it->product_id,
                'name'=>$it->name,
                'description'=>$it->description,
                'quantity'=>$it->quantity,
                'unit'=>$it->unit,
                'unit_price'=>$it->unit_price,
                'discount_value'=>$it->discount_value,
                'addition_value'=>$it->addition_value,
                'line_total'=>$it->line_total,
            ]);
        }

        // Registrar auditoria de conversão no orçamento
        \App\Models\QuoteAudit::create([
            'quote_id' => $quote->id,
            'user_id' => auth()->id(),
            'action' => 'converted',
            'notes' => 'Orçamento convertido em pedido #' . $order->number,
        ]);

        // Registrar auditoria de criação no pedido
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'notes' => 'Pedido criado a partir do orçamento #' . $quote->number,
            'changes' => [
                'source' => 'quote_conversion',
                'quote_id' => $quote->id,
                'quote_number' => $quote->number,
                'timestamp' => now()->toISOString()
            ]
        ]);

        return redirect()->route('orders.edit', $order)->with('success', 'Orçamento convertido em pedido.');
    }

    public function generatePdf(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $quote->load(['client','items','tenant']);

        // Rateio por item (vDesc, vFrete, vSeg, vOutro) para impressão do orçamento
        $rateioItems = [];
        if ($quote->items && $quote->items->count() > 0) {
            $items = $quote->items;
            $weights = [];
            $grosses = [];
            $itemDescValues = [];
            $itemAddValues = [];
            foreach ($items as $it) {
                $gross = round(((float)$it->quantity) * ((float)$it->unit_price), 2);
                $itemDisc = (float)($it->discount_value ?? 0.0);
                $itemAdd = (float)($it->addition_value ?? 0.0);
                $net = max($gross - $itemDisc + $itemAdd, 0.0);
                $weights[] = $net;
                $grosses[] = $gross;
                $itemDescValues[] = $itemDisc;
                $itemAddValues[] = $itemAdd;
            }

            $allocate = function (float $total, array $weights, int $scale = 2): array {
                $count = count($weights);
                if ($count === 0 || abs($total) < 1e-9) { return array_fill(0, $count, 0.0); }
                $sumWeights = array_sum($weights);
                if ($sumWeights <= 0) {
                    $base = round($total / $count, $scale);
                    $vals = array_fill(0, $count, $base);
                    $diff = round($total - array_sum($vals), $scale);
                    for ($i = 0; abs($diff) >= pow(10, -$scale) && $i < $count; $i++) {
                        $vals[$i] = round($vals[$i] + ($diff > 0 ? pow(10, -$scale) : -pow(10, -$scale)), $scale);
                        $diff = round($total - array_sum($vals), $scale);
                    }
                    return $vals;
                }
                $alloc = [];
                $fractions = [];
                $factor = pow(10, $scale);
                $sumFloor = 0;
                for ($i = 0; $i < $count; $i++) {
                    $raw = ($weights[$i] / $sumWeights) * $total;
                    $floored = floor($raw * $factor) / $factor;
                    $alloc[$i] = $floored;
                    $fractions[$i] = $raw - $floored;
                    $sumFloor += $floored;
                }
                $remainder = round($total - $sumFloor, $scale);
                if (abs($remainder) >= pow(10, -$scale)) {
                    $indices = array_keys($fractions);
                    usort($indices, function ($a, $b) use ($fractions, $remainder) {
                        if ($remainder >= 0) { return $fractions[$b] <=> $fractions[$a]; }
                        return $fractions[$a] <=> $fractions[$b];
                    });
                    $step = ($remainder >= 0) ? (1 / $factor) : (-1 / $factor);
                    $units = (int) round(abs($remainder) * $factor);
                    for ($k = 0; $k < $units && $k < count($indices); $k++) {
                        $idx = $indices[$k];
                        $alloc[$idx] = round($alloc[$idx] + $step, $scale);
                    }
                }
                return $alloc;
            };

            $freteTotal = (float)($quote->freight_cost ?? 0.0);
            $segTotal = (float)($quote->valor_seguro ?? 0.0);
            $outrosTotal = (float)($quote->outras_despesas ?? 0.0);
            $descontoHeader = (float)($quote->discount_total ?? 0.0);
            $acrescimoHeader = (float)($quote->addition_total ?? 0.0);

            $alocFrete = $allocate($freteTotal, $weights, 2);
            $alocSeg = $allocate($segTotal, $weights, 2);
            $alocOutros = $allocate($outrosTotal, $weights, 2);
            $alocDescHeader = $allocate($descontoHeader, $weights, 2);
            $alocOutroHeader = $allocate($acrescimoHeader, $weights, 2);

            foreach ($items as $index => $it) {
                $vDesc = round($itemDescValues[$index] + ($alocDescHeader[$index] ?? 0.0), 2);
                $vOutro = round($itemAddValues[$index] + ($alocOutros[$index] ?? 0.0) + ($alocOutroHeader[$index] ?? 0.0), 2);
                $vFrete = round($alocFrete[$index] ?? 0.0, 2);
                $vSeg = round($alocSeg[$index] ?? 0.0, 2);
                $rateioItems[] = [
                    'name' => $it->name,
                    'vDesc' => $vDesc,
                    'vFrete' => $vFrete,
                    'vSeg' => $vSeg,
                    'vOutro' => $vOutro,
                ];
            }
        }

        try {
            $html = view('quotes.print', compact('quote','rateioItems'))->render();
            
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4');
                return $pdf->download('Orcamento_' . $quote->number . '.pdf');
            }
            if (class_exists(\Barryvdh\DomPDF\Facades\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facades\Pdf::loadHTML($html)->setPaper('a4');
                return $pdf->download('Orcamento_' . $quote->number . '.pdf');
            }
            return back()->with('error','Biblioteca PDF não instalada. Rode: composer require barryvdh/laravel-dompdf');
        } catch (\Throwable $e) {
            \Log::error('Falha ao gerar PDF do orçamento', ['id'=>$quote->id, 'error'=>$e->getMessage()]);
            return back()->with('error','Falha ao gerar PDF: '.$e->getMessage());
        }
    }

    public function emailForm(Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $quote->load(['client','items','tenant']);
        $to = optional($quote->client)->email;
        $subject = 'Orçamento #' . $quote->number . ' - ' . ($quote->title ?: 'Detalhes do Orçamento');
        return view('quotes.email', compact('quote','to','subject'));
    }

    public function sendEmail(Request $request, Quote $quote)
    {
        abort_unless(auth()->user()->hasPermission('quotes.view'), 403);
        abort_unless($quote->tenant_id === auth()->user()->tenant_id, 403);
        $v = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'template' => 'nullable|in:quote_default',
        ]);

        $quote->load(['client','items','tenant']);

        $html = trim((string)($v['message'] ?? ''));
        if (($v['template'] ?? '') === 'quote_default' || $html === '') {
            $html = view('quotes.emails._quote_default', [
                'quote' => $quote,
            ])->render();
        }

        $active = SmtpConfig::where('is_active', true)->first();
        if (!$active) {
            $active = new SmtpConfig([
                'host' => env('MAIL_HOST', '127.0.0.1'),
                'port' => (int) env('MAIL_PORT', 2525),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'from_address' => env('MAIL_FROM_ADDRESS'),
                'from_name' => env('MAIL_FROM_NAME', config('app.name')),
                'is_active' => false,
            ]);
        }

        $host = (string)($active->host ?? '');
        $port = (int)($active->port ?? 0);
        $username = (string)($active->username ?? '');
        $password = (string)($active->password ?? '');
        $encryption = strtolower((string)($active->encryption ?? 'tls'));
        $fromAddress = (string)($active->from_address ?? $username);
        $fromName = (string)($quote->tenant->fantasy_name ?? $quote->tenant->name ?? $active->from_name ?? config('app.name'));

        // Gerar PDF do orçamento para anexar
        $pdfContent = null;
        try {
            $rateioItems = [];
            if ($quote->items && $quote->items->count() > 0) {
                $items = $quote->items;
                $weights = [];
                $itemDescValues = [];
                $itemAddValues = [];
                foreach ($items as $it) {
                    $gross = round(((float)$it->quantity) * ((float)$it->unit_price), 2);
                    $itemDisc = (float)($it->discount_value ?? 0.0);
                    $itemAdd = (float)($it->addition_value ?? 0.0);
                    $net = max($gross - $itemDisc + $itemAdd, 0.0);
                    $weights[] = $net;
                    $itemDescValues[] = $itemDisc;
                    $itemAddValues[] = $itemAdd;
                }

                $allocate = function (float $total, array $weights, int $scale = 2): array {
                    $count = count($weights);
                    if ($count === 0 || abs($total) < 1e-9) { return array_fill(0, $count, 0.0); }
                    $sumWeights = array_sum($weights);
                    if ($sumWeights <= 0) {
                        $base = round($total / $count, $scale);
                        $vals = array_fill(0, $count, $base);
                        $diff = round($total - array_sum($vals), $scale);
                        for ($i = 0; abs($diff) >= pow(10, -$scale) && $i < $count; $i++) {
                            $vals[$i] = round($vals[$i] + ($diff > 0 ? pow(10, -$scale) : -pow(10, -$scale)), $scale);
                            $diff = round($total - array_sum($vals), $scale);
                        }
                        return $vals;
                    }
                    $alloc = [];
                    $fractions = [];
                    $factor = pow(10, $scale);
                    $sumFloor = 0;
                    for ($i = 0; $i < $count; $i++) {
                        $raw = ($weights[$i] / $sumWeights) * $total;
                        $floored = floor($raw * $factor) / $factor;
                        $alloc[$i] = $floored;
                        $fractions[$i] = $raw - $floored;
                        $sumFloor += $floored;
                    }
                    $remainder = round($total - $sumFloor, $scale);
                    if (abs($remainder) >= pow(10, -$scale)) {
                        $indices = array_keys($fractions);
                        usort($indices, function ($a, $b) use ($fractions, $remainder) {
                            if ($remainder >= 0) { return $fractions[$b] <=> $fractions[$a]; }
                            return $fractions[$a] <=> $fractions[$b];
                        });
                        $step = ($remainder >= 0) ? (1 / $factor) : (-1 / $factor);
                        $units = (int) round(abs($remainder) * $factor);
                        for ($k = 0; $k < $units && $k < count($indices); $k++) {
                            $idx = $indices[$k];
                            $alloc[$idx] = round($alloc[$idx] + $step, $scale);
                        }
                    }
                    return $alloc;
                };

                $freteTotal = (float)($quote->freight_cost ?? 0.0);
                $segTotal = (float)($quote->valor_seguro ?? 0.0);
                $outrosTotal = (float)($quote->outras_despesas ?? 0.0);
                $descontoHeader = (float)($quote->discount_total ?? 0.0);
                $acrescimoHeader = (float)($quote->addition_total ?? 0.0);

                $alocFrete = $allocate($freteTotal, $weights, 2);
                $alocSeg = $allocate($segTotal, $weights, 2);
                $alocOutros = $allocate($outrosTotal, $weights, 2);
                $alocDescHeader = $allocate($descontoHeader, $weights, 2);
                $alocOutroHeader = $allocate($acrescimoHeader, $weights, 2);

                foreach ($items as $index => $it) {
                    $vDesc = round($itemDescValues[$index] + ($alocDescHeader[$index] ?? 0.0), 2);
                    $vOutro = round($itemAddValues[$index] + ($alocOutros[$index] ?? 0.0) + ($alocOutroHeader[$index] ?? 0.0), 2);
                    $vFrete = round($alocFrete[$index] ?? 0.0, 2);
                    $vSeg = round($alocSeg[$index] ?? 0.0, 2);
                    $rateioItems[] = [
                        'name' => $it->name,
                        'vDesc' => $vDesc,
                        'vFrete' => $vFrete,
                        'vSeg' => $vSeg,
                        'vOutro' => $vOutro,
                    ];
                }
            }

            $pdfHtml = view('quotes.print', compact('quote','rateioItems'))->render();
            
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                $pdfContent = $pdf->output();
            } elseif (class_exists(\Barryvdh\DomPDF\Facades\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facades\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                $pdfContent = $pdf->output();
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao gerar PDF para anexo', ['quote_id'=>$quote->id, 'error'=>$e->getMessage()]);
        }

        $mailer = new PHPMailer(true);
        try {
            EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($v['to']);
            $mailer->isHTML(true);
            $mailer->Subject = $v['subject'];
            $mailer->Body = $html;
            $mailer->AltBody = strip_tags($mailer->Body);
            
            // Anexar PDF se foi gerado com sucesso
            if ($pdfContent) {
                $mailer->addStringAttachment($pdfContent, 'Orcamento_' . $quote->number . '.pdf', 'base64', 'application/pdf');
            }
            
            $mailer->send();
            return back()->with('success','E-mail enviado com sucesso.');
        } catch (PHPMailerException $e) {
            try {
                $altEnc = ($encryption === 'ssl') ? 'tls' : 'ssl';
                $altPort = ($altEnc === 'ssl') ? 465 : 587;
                $mailer = new PHPMailer(true);
                EmailTestController::configureMailer($mailer, $host, $altPort, $username, $password, $altEnc, $fromAddress, $fromName);
                $mailer->addAddress($v['to']);
                $mailer->isHTML(true);
                $mailer->Subject = $v['subject'];
                $mailer->Body = $html;
                $mailer->AltBody = strip_tags($mailer->Body);
                
                // Anexar PDF se foi gerado com sucesso
                if ($pdfContent) {
                    $mailer->addStringAttachment($pdfContent, 'Orcamento_' . $quote->number . '.pdf', 'base64', 'application/pdf');
                }
                
                $mailer->send();
                return back()->with('success','E-mail enviado com sucesso (fallback).');
            } catch (PHPMailerException $e2) {
                return back()->withErrors(['email'=>'Falha ao enviar: '.$e->getMessage().' | Tentativa alternativa: '.$e2->getMessage()])->withInput();
            }
        }
    }
}


