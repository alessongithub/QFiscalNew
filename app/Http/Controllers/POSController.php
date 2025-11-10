<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Receivable;
use App\Models\Client;
use App\Models\GatewayConfig;
use Illuminate\Support\Facades\Http;
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

    private function buildPixHeaders(string $accessToken, int $receivableId): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => 'pix_' . $receivableId . '_' . uniqid('', true),
        ];
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
        
        $query = Order::where('tenant_id', $tenantId)
            ->where('title', 'PDV')
            ->with(['client']);
        
        // Filtro de busca
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%")
                                  ->orWhere('cpf_cnpj', 'like', "%{$search}%");
                  });
            });
        }
        
        // Filtro de status
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'paid') {
                $query->whereHas('receivables', function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)->where('status', 'paid');
                });
            } elseif ($status === 'pending') {
                $query->whereDoesntHave('receivables', function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId)->where('status', 'paid');
                })->where('status', '!=', 'canceled');
            } elseif ($status === 'canceled') {
                $query->where(function($q) {
                    $q->where('status', 'canceled')->orWhereNotNull('canceled_at');
                });
            }
        }
        
        // Filtro de cliente
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        
        // Filtro de data
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Filtro de valor mínimo
        if ($request->filled('min_amount')) {
            $query->where('total_amount', '>=', $request->min_amount);
        }
        
        // Filtro de valor máximo
        if ($request->filled('max_amount')) {
            $query->where('total_amount', '<=', $request->max_amount);
        }
        
        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $orders = $query->paginate(12)->withQueryString();
        
        // Lista de clientes para o filtro
        $clients = Client::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return view('pos.sales', compact('orders', 'clients'));
    }

    public function reopen(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        // Bloquear reabertura se já houver pagamento ou devoluções
        $hasPaid = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $order->id)->where('status','paid')->exists();
        $hasReturns = \App\Models\ReturnModel::where('tenant_id', auth()->user()->tenant_id)->where('order_id', $order->id)->exists();
        if ($hasPaid || $hasReturns) { abort(403, 'Pedido não pode ser reaberto no PDV.'); }
        $items = $order->items()->get(['product_id','name','unit','quantity','unit_price']);
        $client = $order->client ? [
            'id' => $order->client->id,
            'name' => $order->client->name,
            'cpf_cnpj' => $order->client->cpf_cnpj,
        ] : null;
        return view('pos.index', [
            'reopenOrder' => $order,
            'reopenItems' => $items,
            'reopenClient' => $client,
        ]);
    }

    public function payPix(Order $order)
    {
        try {
            abort_unless(auth()->user()->hasPermission('pos.create'), 403);
            abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
            $tenantId = auth()->user()->tenant_id;
            $client = $order->client;
            $total = (float) $order->total_amount;

            // Encontrar ou criar recebível aberto PIX
            $receivable = Receivable::where('tenant_id',$tenantId)
                ->where('order_id',$order->id)
                ->where('payment_method','pix')
                ->whereIn('status',[ 'open','partial' ])
                ->latest('id')->first();
            if (!$receivable) {
                $receivable = Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $client?->id,
                    'order_id' => $order->id,
                    'description' => sprintf('PDV #%d - pagamento à vista (PIX)', $order->id),
                    'amount' => $total,
                    'due_date' => now()->toDateString(),
                    'status' => 'open',
                    'payment_method' => 'pix',
                ]);
            }

            // Reutiliza lógica de criação de PIX
            $config = GatewayConfig::current();
            $accessToken = $config?->active_access_token;
            if (!$accessToken) {
                return response()->json(['ok' => false, 'error' => 'Gateway de pagamento não configurado (Mercado Pago).'], 422);
            }
            if ($total < 0.5) {
                return response()->json(['ok' => false, 'error' => 'Valor mínimo para PIX é R$ 0,50'], 422);
            }

            $clientModel = $client;
            $clientName = $clientModel?->name ?: 'Cliente';
            $parts = explode(' ', trim($clientName), 2);
            $firstName = $parts[0] ?? 'Cliente';
            $lastName = $parts[1] ?? 'PDV';
            $idDigits = preg_replace('/\D/', '', (string)($clientModel?->cpf_cnpj ?? ''));
            $payload = [
                'transaction_amount' => (float) $total,
                'description' => (string) ('PDV #' . $order->id),
                'payment_method_id' => 'pix',
                'external_reference' => 'rec_' . $receivable->id,
            ];
            $isSandbox = (string) ($config->mode ?? 'sandbox') === 'sandbox';
            $sandboxEmail = (string) (\App\Models\Setting::getGlobal('pos.pix_sandbox_email',''));
            if ($isSandbox) {
                if ($sandboxEmail === '' || !filter_var($sandboxEmail, FILTER_VALIDATE_EMAIL)) {
                    return response()->json(['ok'=>false,'error'=>'Configure um e-mail de teste PIX em /admin (Gateway) para gerar cobranças no sandbox.'], 422);
                }
                $payload['payer'] = ['email' => $sandboxEmail];
            } else {
                $email = (string) ($clientModel?->email ?? (auth()->user()->tenant->email ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $payload['payer'] = ['email' => $email];
                }
            }

            \Log::info('POS PIX reissue: payload', ['payload' => $payload, 'order_id' => $order->id, 'receivable_id' => $receivable->id]);
            $resp = \Illuminate\Support\Facades\Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))
                ->post('https://api.mercadopago.com/v1/payments', $payload);

            if (!$resp->successful()) {
                $body = $resp->json();
                $isInvalidEmail = false; $isParamsError = false;
                if (is_array($body) && ($body['error'] ?? '') === 'bad_request' && !empty($body['cause'])) {
                    foreach ($body['cause'] as $c) {
                        $code = (int)($c['code'] ?? 0);
                        if ($code === 4050) { $isInvalidEmail = true; }
                        if ($code === 1) { $isParamsError = true; }
                    }
                }
                if ($isInvalidEmail && isset($payload['payer']['email'])) {
                    unset($payload['payer']);
                    $resp = \Illuminate\Support\Facades\Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))
                        ->post('https://api.mercadopago.com/v1/payments', $payload);
                }
                if (!$resp->successful() && $isParamsError) {
                    $payloadMin = [
                        'transaction_amount' => (float) $total,
                        'description' => 'PDV',
                        'payment_method_id' => 'pix',
                        'external_reference' => 'rec_' . $receivable->id,
                    ];
                    $resp = \Illuminate\Support\Facades\Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))
                        ->post('https://api.mercadopago.com/v1/payments', $payloadMin);
                }
                if (!$resp->successful()) {
                    return response()->json(['ok'=>false,'error'=>'Falha ao criar cobrança PIX: '.$resp->body()], 422);
                }
            }

            $json = $resp->json();
            $mpId = (string) ($json['id'] ?? '');
            $qrCode = (string) ($json['point_of_interaction']['transaction_data']['qr_code'] ?? '');
            $qrCodeBase64 = (string) ($json['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '');
            $expiresAt = (string) ($json['date_of_expiration'] ?? '');

            // Salvar payment_id no receivable para facilitar busca posterior
            if (!empty($mpId)) {
                $receivable->update([
                    'metadata' => json_encode(['mp_payment_id' => $mpId]),
                ]);
            }

            $statusUrl = route('pos.pix.status', ['payment' => $mpId]);
            $receiptUrl = route('pos.receipt', ['order' => $order->id]);
            $printerType = \App\Models\Setting::get('print.printer_type', 'thermal_80');
            $printUrl = ($printerType === 'thermal_58' || $printerType === 'thermal_80') 
                ? route('pos.print80', ['order' => $order->id]) 
                : route('pos.print', ['order' => $order->id]);
            $autoPrint = (\App\Models\Setting::get('pos.auto_print_on_payment','0') === '1');

            return response()->json([
                'ok' => true,
                'order_id' => $order->id,
                'total' => $total,
                'is_pix' => true,
                'mp_payment_id' => $mpId,
                'qr_code' => $qrCode,
                'qr_code_base64' => $qrCodeBase64,
                'expires_at' => $expiresAt,
                'status_url' => $statusUrl,
                'receipt_url' => $receiptUrl,
                'print_url' => $printUrl,
                'auto_print' => $autoPrint,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok'=>false,'error'=>'Erro interno: '.$e->getMessage()], 500);
        }
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
                    // Novo schema: movement_type in|out
                    $entry = (float) StockMovement::where('tenant_id',$tenantId)
                        ->where('product_id',$product->id)
                        ->where('movement_type','in')
                        ->sum('quantity');
                    $exit = (float) StockMovement::where('tenant_id',$tenantId)
                        ->where('product_id',$product->id)
                        ->where('movement_type','out')
                        ->sum('quantity');
                    $balance = $entry - $exit;
                    if ($balance < (float)$it['quantity'] - 1e-6) {
                        return response()->json(['ok'=>false,'error'=>'Estoque insuficiente para '.$product->name], 422);
                    }
                }
                $qty = (float)$it['quantity'];
                $unitPrice = (float)$it['unit_price'];
                $total += round($qty * $unitPrice, 2);
            }

            $orderId = (int) ($request->input('order_id') ?? 0);
            if ($orderId > 0) {
                $order = Order::where('tenant_id', $tenantId)->findOrFail($orderId);
                // Elegibilidade: sem pagos e sem devoluções
                $paidExists = \App\Models\Receivable::where('tenant_id',$tenantId)->where('order_id',$order->id)->where('status','paid')->exists();
                $returnsExists = \App\Models\ReturnModel::where('tenant_id',$tenantId)->where('order_id',$order->id)->exists();
                if ($paidExists || $returnsExists) {
                    return response()->json(['ok'=>false,'error'=>'Pedido não pode ser reaberto no PDV.'], 422);
                }
                // Apagar itens e movimentos antigos do PDV
                \App\Models\OrderItem::where('tenant_id',$tenantId)->where('order_id',$order->id)->delete();
                \App\Models\StockMovement::where('tenant_id',$tenantId)
                    ->where('movement_type','out')
                    ->where('reason','pos_sale')
                    ->where('notes','like','%PDV #'.$order->id.'%')
                    ->delete();
            } else {
                // Criar novo pedido
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
            }

            // Persistir itens e (condicionalmente) baixar estoque
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
            // baixa estoque apenas para produtos físicos, exceto quando pagamento imediato for PIX (adiar até aprovação)
            if ((string)$product->type === 'product') {
                if (!($payment_type === 'immediate' && $payment_method === 'pix')) {
                    StockMovement::create([
                        'tenant_id' => $tenantId,
                        'product_id' => $product->id,
                        'movement_type' => 'out',
                        'quantity' => $qty,
                        'reason' => 'pos_sale',
                        'user_id' => auth()->id(),
                        'notes' => 'Saída PDV #'.$order->id,
                    ]);
                }
            }
        }

            $order->update(['total_amount' => $total]);

            // Registrar auditoria
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => ($orderId>0?'updated':'created'),
                'notes' => ($orderId>0?'Venda atualizada no PDV':'Venda realizada no PDV'),
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
            if ($payment_type === 'immediate' && $payment_method !== 'pix') {
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
            } elseif ($payment_type === 'immediate' && $payment_method === 'pix') {
                // Cria recebível em aberto para PIX
                $receivable = Receivable::create([
                    'tenant_id' => $tenantId,
                    'client_id' => $client_id,
                    'order_id' => $order->id,
                    'description' => sprintf('PDV #%d - pagamento à vista (PIX)', $order->id),
                    'amount' => $total,
                    'due_date' => now()->toDateString(),
                    'status' => 'open',
                    'payment_method' => 'pix',
                ]);

                // Cria cobrança PIX no Mercado Pago
                $config = GatewayConfig::current();
                $accessToken = $config?->active_access_token;
                if (!$accessToken) {
                    return response()->json(['ok' => false, 'error' => 'Gateway de pagamento não configurado (Mercado Pago).'], 422);
                }

                // Valor mínimo de PIX (evita erros de parâmetro no gateway)
                if ($total < 0.5) {
                    return response()->json(['ok' => false, 'error' => 'Valor mínimo para PIX é R$ 0,50'], 422);
                }

                $clientModel = Client::find($client_id);
                $clientName = $clientModel?->name ?: 'Cliente';
                $parts = explode(' ', trim($clientName), 2);
                $firstName = $parts[0] ?? 'Cliente';
                $lastName = $parts[1] ?? 'PDV';

                $idDigits = preg_replace('/\D/', '', (string)($clientModel?->cpf_cnpj ?? ''));
                $payload = [
                    'transaction_amount' => (float) $total,
                    'description' => (string) ('PDV #' . $order->id),
                    'payment_method_id' => 'pix',
                    'external_reference' => 'rec_' . $receivable->id,
                ];

                // Sandbox exige e-mail de test user do MP
                $isSandbox = (string) ($config->mode ?? 'sandbox') === 'sandbox';
                $sandboxEmail = (string) (\App\Models\Setting::getGlobal('pos.pix_sandbox_email',''));
                if ($isSandbox) {
                    if ($sandboxEmail === '' || !filter_var($sandboxEmail, FILTER_VALIDATE_EMAIL)) {
                        return response()->json(['ok'=>false,'error'=>'Configure um e-mail de teste PIX em /settings (PIX Sandbox) para gerar cobranças no sandbox.'], 422);
                    }
                    $payload['payer'] = ['email' => $sandboxEmail];
                } else {
                    // Produção: usar e-mail do cliente ou do tenant, se válido
                    $email = (string) ($clientModel?->email ?? (auth()->user()->tenant->email ?? ''));
                    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $payload['payer'] = ['email' => $email];
                    }
                }

                \Log::info('POS PIX create: payload', ['payload' => $payload, 'order_id' => $order->id, 'receivable_id' => $receivable->id]);
                $resp = Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))->post('https://api.mercadopago.com/v1/payments', $payload);

                if (!$resp->successful()) {
                    $body = $resp->json();
                    $isInvalidId = false;
                    $isInvalidEmail = false;
                    $isParamsError = false;
                    if (is_array($body)) {
                        if (($body['error'] ?? '') === 'bad_request' && !empty($body['cause']) && is_array($body['cause'])) {
                            foreach ($body['cause'] as $c) {
                                $code = (int)($c['code'] ?? 0);
                                if ($code === 2067) { $isInvalidId = true; }
                                if ($code === 4050) { $isInvalidEmail = true; }
                                if ($code === 1) { $isParamsError = true; }
                            }
                        }
                    }
                    if (($isInvalidEmail || $isInvalidId) && isset($payload['payer'])) {
                        // Primeiro, se for e-mail inválido, remova apenas o e-mail
                        if ($isInvalidEmail && isset($payload['payer']['email'])) {
                            unset($payload['payer']['email']);
                            $resp = Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))->post('https://api.mercadopago.com/v1/payments', $payload);
                        }
                        // Se ainda falhar ou se for identificação inválida, remova o payer por completo
                        if (!$resp->successful()) {
                            unset($payload['payer']);
                            $resp = Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))->post('https://api.mercadopago.com/v1/payments', $payload);
                        }
                    }
                    // Se ainda não deu certo e for erro de parâmetros, tente variações: acrescentar payer mínimo só com nome
                    if (!$resp->successful() && $isParamsError) {
                        if (!isset($payload['payer'])) {
                            $payload['payer'] = [ 'first_name' => $firstName, 'last_name' => $lastName ];
                            $resp = Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))->post('https://api.mercadopago.com/v1/payments', $payload);
                        }
                    }
                    // Fallback final: payload mínimo
                    if (!$resp->successful()) {
                        $payloadMin = [
                            'transaction_amount' => (float) $total,
                            'description' => 'PDV',
                            'payment_method_id' => 'pix',
                            'external_reference' => 'rec_' . $receivable->id,
                        ];
                        \Log::warning('POS PIX create: retry with minimal payload');
                        $resp = Http::withHeaders($this->buildPixHeaders($accessToken, $receivable->id))->post('https://api.mercadopago.com/v1/payments', $payloadMin);
                    }
                    if (!$resp->successful()) {
                        \Log::error('PIX create failed', ['status' => $resp->status(), 'body' => $resp->body(), 'order_id' => $order->id, 'receivable_id' => $receivable->id]);
                        return response()->json(['ok' => false, 'error' => 'Falha ao criar cobrança PIX: ' . $resp->body()], 422);
                    }
                }
                $json = $resp->json();
                $mpId = (string) ($json['id'] ?? '');
                $qrCode = (string) ($json['point_of_interaction']['transaction_data']['qr_code'] ?? '');
                $qrCodeBase64 = (string) ($json['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '');
                $expiresAt = (string) ($json['date_of_expiration'] ?? '');

                // Salvar payment_id no receivable para facilitar busca posterior
                if (!empty($mpId)) {
                    $receivable->update([
                        'boleto_mp_id' => $mpId, // Reutilizando campo para armazenar payment_id do PIX
                    ]);
                }

                $statusUrl = route('pos.pix.status', ['payment' => $mpId]);
                $receiptUrl = route('pos.receipt', ['order' => $order->id]);
                $printerType = \App\Models\Setting::get('print.printer_type', 'thermal_80');
                $printUrl = ($printerType === 'thermal_58' || $printerType === 'thermal_80') 
                    ? route('pos.print80', ['order' => $order->id]) 
                    : route('pos.print', ['order' => $order->id]);
                $autoPrint = (\App\Models\Setting::get('pos.auto_print_on_payment','0') === '1');

                return response()->json([
                    'ok' => true,
                    'order_id' => $order->id,
                    'total' => $total,
                    'is_pix' => true,
                    'mp_payment_id' => $mpId,
                    'qr_code' => $qrCode,
                    'qr_code_base64' => $qrCodeBase64,
                    'expires_at' => $expiresAt,
                    'status_url' => $statusUrl,
                    'receipt_url' => $receiptUrl,
                    'print_url' => $printUrl,
                    'auto_print' => $autoPrint,
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
                $entry_method = $request->input('entry_method', 'cash');
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
                    'payment_method' => $entry_method,
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

            // Preparar URLs de impressão e recibo para pagamentos não-PIX
            $printerType = \App\Models\Setting::get('print.printer_type', 'thermal_80');
            $printUrl = ($printerType === 'thermal_58' || $printerType === 'thermal_80') 
                ? route('pos.print80', ['order' => $order->id]) 
                : route('pos.print', ['order' => $order->id]);
            $receiptUrl = route('pos.receipt', ['order' => $order->id]);
            $autoPrint = (\App\Models\Setting::get('pos.auto_print_on_payment','0') === '1');

            return response()->json([
                'ok' => true, 
                'order_id' => $order->id, 
                'total' => $total,
                'print_url' => $printUrl,
                'receipt_url' => $receiptUrl,
                'auto_print' => $autoPrint
            ]);
            
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

    public function pixStatus(string $payment)
    {
        abort_unless(auth()->user()->hasPermission('pos.view'), 403);
        $config = GatewayConfig::current();
        $accessToken = $config?->active_access_token;
        if (!$accessToken) {
            return response()->json(['ok' => false, 'error' => 'Gateway não configurado'], 422);
        }
        try {
            $resp = Http::withToken($accessToken)->get('https://api.mercadopago.com/v1/payments/' . $payment);
            if (!$resp->successful()) {
                return response()->json(['ok' => false, 'error' => 'Falha ao consultar pagamento', 'status' => $resp->status()], 400);
            }
            $json = $resp->json();
            $status = (string) ($json['status'] ?? 'pending');
            $approved = $status === 'approved';
            
            // Se pagamento foi aprovado, criar TenantBalance se ainda não existir
            if ($approved) {
                $this->createTenantBalanceForPix($json);
            }
            
            return response()->json(['ok' => true, 'status' => $status, 'approved' => $approved]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    private function createTenantBalanceForPix(array $paymentData): void
    {
        try {
            $paymentId = (string) ($paymentData['id'] ?? '');
            if (empty($paymentId)) {
                return;
            }
            
            // Verificar se já existe TenantBalance para este pagamento
            $existing = \App\Models\TenantBalance::where('mp_payment_id', $paymentId)->first();
            if ($existing) {
                return; // Já foi criado
            }
            
            // Buscar recebível pelo external_reference ou payment_id
            $externalRef = $paymentData['external_reference'] ?? '';
            $receivable = null;
            
            if (!empty($externalRef) && str_starts_with($externalRef, 'rec_')) {
                $receivableId = (int) str_replace('rec_', '', $externalRef);
                $receivable = Receivable::where('tenant_id', auth()->user()->tenant_id)
                    ->where('id', $receivableId)
                    ->where('payment_method', 'pix')
                    ->first();
            }
            
            if (!$receivable) {
                // Tentar buscar pelo payment_id armazenado no boleto_mp_id do receivable
                $receivable = Receivable::where('tenant_id', auth()->user()->tenant_id)
                    ->where('payment_method', 'pix')
                    ->where('status', 'open')
                    ->where('boleto_mp_id', $paymentId)
                    ->first();
            }
            
            if (!$receivable) {
                \Log::warning('Receivable não encontrado para criar TenantBalance PIX', ['payment_id' => $paymentId, 'external_ref' => $externalRef]);
                return;
            }
            
            // Atualizar status do receivable para 'paid'
            $receivable->status = 'paid';
            $receivable->received_at = now();
            $receivable->save();
            
            $grossAmount = (float) ($paymentData['transaction_amount'] ?? $receivable->amount);
            
            // Taxa do Mercado Pago (PIX) - usar taxa configurada
            $pixFeePercent = (float) \App\Models\Setting::getGlobal('pix.mp_fee_percent', '0.99');
            $mpFeeAmount = round($grossAmount * ($pixFeePercent / 100), 2);
            
            // Taxa da plataforma de 1%
            $platformFeeAmount = round($grossAmount * 0.01, 2);
            
            // Valor líquido
            $netAmount = max(0, $grossAmount - $mpFeeAmount - $platformFeeAmount);
            
            \App\Models\TenantBalance::create([
                'tenant_id' => $receivable->tenant_id,
                'receivable_id' => $receivable->id,
                'gross_amount' => $grossAmount,
                'mp_fee_amount' => $mpFeeAmount,
                'platform_fee_amount' => $platformFeeAmount,
                'net_amount' => $netAmount,
                'status' => 'pending',
                'payment_received_at' => now(),
                'mp_payment_id' => $paymentId,
            ]);
            
            \Log::info('TenantBalance criado para PIX', [
                'receivable_id' => $receivable->id,
                'payment_id' => $paymentId,
                'gross_amount' => $grossAmount,
                'mp_fee_amount' => $mpFeeAmount,
                'platform_fee_amount' => $platformFeeAmount,
                'net_amount' => $netAmount,
            ]);

            // Garantir baixa de estoque somente após aprovação do PIX (se ainda não foi baixado)
            try {
                $orderId = (int) $receivable->order_id;
                if ($orderId > 0) {
                    $tenantId = (int) $receivable->tenant_id;
                    $alreadyOut = StockMovement::where('tenant_id', $tenantId)
                        ->where('movement_type', 'out')
                        ->where('reason', 'pos_sale')
                        ->where('notes', 'like', '%PDV #'.$orderId.'%')
                        ->exists();
                    if (!$alreadyOut) {
                        $order = Order::where('tenant_id', $tenantId)->find($orderId);
                        if ($order) {
                            $items = $order->items()->get();
                            foreach ($items as $it) {
                                $product = Product::where('tenant_id', $tenantId)->find($it->product_id);
                                if ($product && (string)$product->type === 'product') {
                                    StockMovement::create([
                                        'tenant_id' => $tenantId,
                                        'product_id' => $product->id,
                                        'movement_type' => 'out',
                                        'quantity' => (float) $it->quantity,
                                        'reason' => 'pos_sale',
                                        'user_id' => auth()->id(),
                                        'notes' => 'Saída PDV #'.$orderId,
                                    ]);
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $sx) {
                \Log::error('Falha ao baixar estoque pós-aprovação PIX', [
                    'order_id' => $receivable->order_id,
                    'error' => $sx->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Falha ao criar TenantBalance para PIX', [
                'payment_data' => $paymentData,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}


