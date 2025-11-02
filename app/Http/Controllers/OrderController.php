<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\TaxRate;
use App\Models\Carrier;
use Illuminate\Http\Request;
use App\Models\SmtpConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use App\Http\Controllers\Admin\EmailTestController;
use App\Traits\StorageLimitCheck;

class OrderController extends Controller
{
    use StorageLimitCheck;
    /**
     * Valida se o pedido possui dados mínimos para emissão de NF-e.
     * Retorna array de mensagens de erro (vazio quando sem erros).
     */
    private function validateOrderForNfe(Order $order): array
    {
        $errors = [];

        // Itens
        $items = $order->items()->with('product')->get();
        if ($items->count() === 0) {
            $errors[] = 'Pedido sem itens. Adicione produtos antes de emitir a nota.';
            return $errors;
        }
        foreach ($items as $idx => $it) {
            $n = $idx + 1;
            $p = $it->product;
            if (!$p) { $errors[] = "Item #{$n} ('{$it->name}') sem produto vinculado."; continue; }
            $ncm = preg_replace('/\D/', '', (string)($p->ncm ?? ''));
            if (strlen($ncm) !== 8) { $errors[] = "Produto '{$p->name}' (Item #{$n}): NCM inválido ou faltando (8 dígitos)."; }
            $cst = (string) ($p->cst ?? $p->cst_icms ?? '');
            if ($cst === '') { $errors[] = "Produto '{$p->name}' (Item #{$n}): CST/CSOSN não informado."; }
            $cfop = (string) ($p->cfop ?? '');
            if ($cfop === '' || strlen($cfop) < 4) { $errors[] = "Produto '{$p->name}' (Item #{$n}): CFOP inválido ou não informado."; }
            $origem = (int) ($p->origin ?? -1);
            if ($origem < 0 || $origem > 8) { $errors[] = "Produto '{$p->name}' (Item #{$n}): Origem não informada (0 a 8)."; }
            $unit = (string) ($p->unit ?? $it->unit ?? '');
            if ($unit === '') { $errors[] = "Produto '{$p->name}' (Item #{$n}): Unidade (UN/KG/...) não informada."; }
        }

        // Cliente
        $c = $order->client;
        if (!$c) { $errors[] = 'Pedido sem cliente. Selecione um cliente.'; return $errors; }
        $isCF = (string)($c->name ?? '') === 'Consumidor Final' || (string)($c->consumidor_final ?? '') === 'S';
        if (!$isCF) {
            $doc = preg_replace('/\D/', '', (string)($c->cpf_cnpj ?? ''));
            if ($doc === '' || (strlen($doc) !== 11 && strlen($doc) !== 14)) {
                $errors[] = "Cliente '{$c->name}': CPF/CNPJ inválido ou não informado.";
            }
        }
        if (empty($c->address) || empty($c->number)) { $errors[] = "Cliente '{$c->name}': Endereço incompleto."; }
        if (empty($c->neighborhood)) { $errors[] = "Cliente '{$c->name}': Bairro não informado."; }
        if (empty($c->city) || empty($c->state)) { $errors[] = "Cliente '{$c->name}': Cidade/UF não informados."; }
        $cep = preg_replace('/\D/', '', (string)($c->zip_code ?? ''));
        if (strlen($cep) !== 8) { $errors[] = "Cliente '{$c->name}': CEP inválido (8 dígitos)."; }
        $ibge = (int) ($c->codigo_ibge ?? $c->codigo_municipio ?? 0);
        if ($ibge === 0) { $errors[] = "Cliente '{$c->name}': Código IBGE do município não informado."; }

        // Totais coerentes
        $sumLines = (float) $order->items()->sum('line_total');
        $sumItemDisc = (float) $order->items()->sum('discount_value');
        $headerDisc = (float) ($order->discount_total ?? 0);
        $calculatedNet = max(0.0, $sumLines - $sumItemDisc - $headerDisc);
        $savedNet = (float) ($order->total_amount ?? 0);
        if (abs($calculatedNet - $savedNet) > 0.02) {
            $errors[] = 'Total do pedido inconsistente. Reabra e salve o pedido novamente.';
        }

        // Pagamentos existentes
        $hasReceivables = Receivable::where('tenant_id', $order->tenant_id)->where('order_id', $order->id)->exists();
        if (!$hasReceivables) { $errors[] = 'Nenhuma forma de pagamento definida. Finalize o pedido antes da emissão.'; }

        return $errors;
    }
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = Order::where('tenant_id', $tenantId)
            ->with(['client','items']);
        if ($s = $request->get('search')) {
            $q->where(function ($qq) use ($s) {
                $qq->where('number', 'like', "%{$s}%")
                   ->orWhere('title', 'like', "%{$s}%")
                   ->orWhereHas('client', fn($qc) => $qc->where('name','like',"%{$s}%"));
            });
        }
        if ($st = $request->get('status')) { $q->where('status', $st); }
        $numberOrder = $request->get('number_order');
        if (in_array($numberOrder, ['asc','desc'], true)) {
            $q->orderByRaw('CAST(number AS UNSIGNED) ' . strtoupper($numberOrder));
        } else {
            $q->orderByDesc('id');
        }
        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }
        $orders = $q->paginate($perPage)->appends($request->query());
        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        
        $order->load(['client', 'items.product', 'carrier', 'receivables']);
        
        return view('orders.show', compact('order'));
    }

    public function whatsapp(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $order->loadMissing(['client','items']);
        $client = optional($order->client);
        $rawPhone = preg_replace('/\D/', '', (string) ($client->phone ?? ''));
        $phone = (substr($rawPhone,0,2) === '55') ? $rawPhone : ('55' . $rawPhone);

        $template = (string) \App\Models\Setting::get('whatsapp.order_template', 'Olá {cliente}, seu pedido #{numero} - {titulo} no valor de R$ {total} está {status}. Itens:\n{itens}');
        $statusMap = ['open'=>'Aberto','fulfilled'=>'Finalizado','canceled'=>'Cancelado'];
        $statusText = $statusMap[$order->status] ?? $order->status;
        $itemsLines = $order->items->map(function($i){
            $qty = number_format((float)$i->quantity, 3, ',', '.');
            $price = number_format((float)$i->unit_price, 2, ',', '.');
            return "- {$i->name} ({$qty} {$i->unit}) x R$ {$price}";
        })->implode("\n");
        $repl = [
            '{cliente}' => (string) ($client->name ?? 'cliente'),
            '{numero}' => (string) $order->number,
            '{titulo}' => (string) ($order->title ?? ''),
            '{total}' => number_format((float)$order->total_amount, 2, ',', '.'),
            '{status}' => (string) $statusText,
            '{itens}' => $itemsLines,
        ];
        $text = strtr($template, $repl);
        $url = 'https://wa.me/' . $phone . '?text=' . rawurlencode($text);
        return redirect()->away($url);
    }

    public function emailForm(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $order->load(['client','items','tenant','carrier']);
        $to = optional($order->client)->email;
        $subject = 'Pedido #' . $order->number . ' - ' . ($order->title ?: 'Detalhes do Pedido');
        // Recomendar template com base no status
        $defaultTemplate = $order->status === 'open' ? 'order_confirmation' : ($order->status === 'fulfilled' ? 'order_fulfilled' : 'order_shipped');
        return view('orders.email', compact('order','to','subject','defaultTemplate'));
    }

    public function sendEmail(Request $request, Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $v = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'template' => 'nullable|in:order_confirmation,order_fulfilled,order_shipped',
        ]);

        $order->load(['client','items','tenant','carrier']);

        // Renderizar template
        $html = trim((string)($v['message'] ?? ''));
        if (($v['template'] ?? '') !== '' || $html === '') {
            $viewMap = [
                'order_confirmation' => 'orders.emails._order_confirmation',
                'order_fulfilled'    => 'orders.emails._order_fulfilled',
                'order_shipped'      => 'orders.emails._order_shipped',
            ];
            $tpl = $viewMap[$v['template'] ?? 'order_confirmation'] ?? $viewMap['order_confirmation'];
            $html = view($tpl, [ 'order' => $order ])->render();
        }

        // SMTP ativo
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
        $tenant = $order->tenant;
        $fromName = (string)($tenant->fantasy_name ?? $tenant->name ?? $active->from_name ?? config('app.name'));

        // Gerar PDF do pedido para anexar
        $pdfContent = null;
        try {
            $order->loadMissing(['client','items','tenant']);
            
            // Rateio por item (vDesc, vFrete, vSeg, vOutro) para impressão
            $rateioItems = [];
            if ($order->items && $order->items->count() > 0) {
                $items = $order->items;
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

                $freteTotal = (float)($order->freight_cost ?? 0.0);
                $segTotal = (float)($order->valor_seguro ?? 0.0);
                $outrosTotal = (float)($order->outras_despesas ?? 0.0);
                $descontoHeader = (float)($order->discount_total ?? 0.0);
                $acrescimoHeader = (float)($order->addition_total ?? 0.0);

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

            // Dados para o template de impressão
            $receivables = $order->receivables ?? collect();
            $taxEstimate = ['icms' => 0, 'pis' => 0, 'cofins' => 0];
            $icmsSuggestions = [];

            $pdfHtml = view('orders.print', compact('order','receivables','taxEstimate','rateioItems','icmsSuggestions'))->render();
            
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                $pdfContent = $pdf->output();
            } elseif (class_exists(\Barryvdh\DomPDF\Facades\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facades\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                $pdfContent = $pdf->output();
            }
        } catch (\Throwable $e) {
            \Log::warning('Falha ao gerar PDF para anexo', ['order_id'=>$order->id, 'error'=>$e->getMessage()]);
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
                $mailer->addStringAttachment($pdfContent, 'Pedido_' . $order->number . '.pdf', 'base64', 'application/pdf');
            }
            
            $mailer->send();
            
            // Registrar auditoria de envio de email
            try {
                \App\Models\OrderAudit::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'action' => 'email_sent',
                    'notes' => 'Email enviado para ' . $v['to'],
                    'changes' => [
                        'to' => $v['to'],
                        'subject' => $v['subject'],
                        'template' => $v['template'] ?? 'custom',
                        'has_pdf' => !empty($pdfContent),
                    ],
                ]);
            } catch (\Throwable $auditError) {
                \Log::warning('Erro ao registrar auditoria de email', [
                    'order_id' => $order->id,
                    'error' => $auditError->getMessage()
                ]);
            }
            
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
                    $mailer->addStringAttachment($pdfContent, 'Pedido_' . $order->number . '.pdf', 'base64', 'application/pdf');
                }
                
                $mailer->send();
                
                // Registrar auditoria de envio de email (fallback)
                try {
                    \App\Models\OrderAudit::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id(),
                        'action' => 'email_sent',
                        'notes' => 'Email enviado para ' . $v['to'] . ' (fallback)',
                        'changes' => [
                            'to' => $v['to'],
                            'subject' => $v['subject'],
                            'template' => $v['template'] ?? 'custom',
                            'has_pdf' => !empty($pdfContent),
                        ],
                    ]);
                } catch (\Throwable $auditError) {
                    \Log::warning('Erro ao registrar auditoria de email', [
                        'order_id' => $order->id,
                        'error' => $auditError->getMessage()
                    ]);
                }
                
                return back()->with('success','E-mail enviado com sucesso (fallback).');
            } catch (PHPMailerException $e2) {
                return back()->withErrors(['email'=>'Falha ao enviar: '.$e->getMessage().' | Tentativa alternativa: '.$e2->getMessage()])->withInput();
            }
        }
    }

    private function generateNumber(int $tenantId): string
    {
        $last = Order::where('tenant_id',$tenantId)->orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
        $n=0; if ($last && is_numeric($last->number)) { $n=(int)$last->number; }
        return str_pad((string)($n+1), 6, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('orders.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get();
        $products = Product::where('tenant_id', $tenantId)->where('active',1)->orderBy('name')->get(['id','name','unit','price']);
        $productsMap = $products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'unit'=>$p->unit,'price'=>(float)$p->price]);
        return view('orders.create', compact('clients','productsMap'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'number' => 'nullable|string|max:30',
            'title' => 'required|string|max:255',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'discount_total' => 'nullable|numeric|min:0',
        ]);

        $itemsIn = $request->input('items', []);
        $items=[]; $subtotal=0.0; $itemsDiscountSum=0.0; $headerDiscount=0.0; $add=0.0;
        $groupedItems = []; // Para agrupar produtos duplicados
        
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
            
            // Agrupar por produto_id
            if (!isset($groupedItems[$productId])) {
                $groupedItems[$productId] = [
                    'product' => $product,
                    'total_quantity' => 0,
                    'total_discount' => 0,
                ];
            }
            $groupedItems[$productId]['total_quantity'] += $qty;
            $groupedItems[$productId]['total_discount'] += max(0.0, (float)($it['discount_value'] ?? 0));
        }
        
        // Processar itens agrupados
        foreach ($groupedItems as $productId => $grouped) {
            $product = $grouped['product'];
            $qty = $grouped['total_quantity'];
            
            // Verificação de estoque na criação (respeita configuração de permitir negativo)
            $allowNegative = \App\Models\Setting::get('stock.allow_negative','0')==='1';
            if (!$allowNegative && $product->type === 'product') {
                $entry = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
                $exit = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
                $balance = (float)$entry - (float)$exit;
                if ($balance + 1e-6 < $qty) {
                    return back()->withInput()->with('error', 'Estoque insuficiente para '.$product->name.'. Saldo: '.number_format($balance,3,',','.'));
                }
            }
            $price = (float)($product->price ?? 0);
            $line = round($qty * $price, 2);
            // Desconto por item (apenas se tiver permissão)
            $itemDisc = 0.0;
            if (auth()->user()->hasPermission('orders.discount')) {
                $itemDisc = $grouped['total_discount'];
                if ($itemDisc > $line) { $itemDisc = $line; }
            }
            $items[] = [
                'product_id'=>$product->id,
                'name'=>$product->name,
                'description'=>null,
                'quantity'=>$qty,
                'unit'=>$product->unit ?? null,
                'unit_price'=>$price,
                'discount_value'=>$itemDisc,
                'addition_value'=>0,
                'line_total'=>$line,
            ];
            $subtotal += $line;
            $itemsDiscountSum += $itemDisc;
        }

        $number = $v['number'] ?? $this->generateNumber($tenantId);
        if (auth()->user()->hasPermission('orders.discount')) {
            $headerDiscount = max(0.0, (float)($request->input('discount_total', 0)));
        }
        $netTotal = max(0.0, $subtotal - $itemsDiscountSum - $headerDiscount);
        
        // Verificar limite de storage de dados antes de criar
        // Estimativa: pedido base (~2 KB) + cada item (~1 KB)
        $itemsCount = count($items);
        $estimatedSize = 2048 + ($itemsCount * 1024);
        if (!$this->checkStorageLimit('data', $estimatedSize)) {
            return back()->withErrors([
                'storage' => $this->getStorageLimitErrorMessage('data')
            ])->withInput();
        }
        
        $order = Order::create([
            'tenant_id'=>$tenantId,
            'client_id'=>$v['client_id'],
            'number'=>$number,
            'title'=>$v['title'],
            'status'=>'open',
            'total_amount'=>$netTotal,
            'discount_total'=>$headerDiscount,
            'addition_total'=>$add,
            'created_by' => auth()->id(),
        ]);

        foreach ($items as $it) { OrderItem::create([...$it,'tenant_id'=>$tenantId,'order_id'=>$order->id]); }

        // Invalidar cache de storage após criar
        $this->invalidateStorageCache();

        // Registrar auditoria de criação
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'notes' => 'Pedido criado manualmente',
            'changes' => [
                'source' => 'manual_creation',
                'client_id' => $order->client_id,
                'total_amount' => $order->total_amount,
                'items_count' => count($items),
                'timestamp' => now()->toISOString()
            ]
        ]);

        return redirect()->route('orders.index')->with('success','✅ Pedido criado com sucesso!');
    }

    public function edit(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id',$tenantId)->orderBy('name')->get();
        $items = $order->items()->orderBy('id')->get();
        // Detecta se há itens físicos (produtos) para exigir frete
        $productIds = $items->pluck('product_id')->filter()->values();
        $hasPhysicalProducts = false;
        if ($productIds->count() > 0) {
            $hasPhysicalProducts = Product::where('tenant_id', $tenantId)
                ->whereIn('id', $productIds)
                ->where('type', 'product')
                ->exists();
        }
        $canIssueNfe = ($order->status === 'fulfilled') && $this->hasPaymentDefinition($order);
        // Estados de NF-e para controle de UI (via accessors do Model)
        $hadCancelledNfe = (bool) $order->has_cancelled_nfe;
        $hasSuccessfulNfe = (bool) $order->has_successful_nfe;
        $canShowReopen = ($order->status === 'fulfilled') && !$hasSuccessfulNfe;
        // Prefill de pagamento a partir de títulos existentes
        $paymentPreset = [
            'type' => 'immediate',
            'entry' => null,
            'installments' => 1,
            'firstDue' => now()->toDateString(),
            'interval' => 30,
            'immediate_method' => 'cash',
            'entry_method' => 'cash',
            'installment_method' => 'boleto',
        ];
        $recs = \App\Models\Receivable::where('tenant_id', $tenantId)
            ->where('client_id', $order->client_id)
            ->where('description', 'like', 'Pedido '.$order->number.'%')
            ->get();
        $entryRec = $recs->first(fn($r) => str_contains(strtolower($r->description), 'entrada') && $r->status === 'paid');
        $immediateRec = $recs->first(fn($r) => str_contains(strtolower($r->description), 'pagamento à vista') && $r->status === 'paid');
        $installmentRecs = $recs->filter(fn($r) => str_contains(strtolower($r->description), 'parcela') && $r->status !== 'paid');
        if ($immediateRec) {
            $paymentPreset['type'] = 'immediate';
            $paymentPreset['immediate_method'] = $immediateRec->payment_method ?: 'cash';
        } elseif ($entryRec || $installmentRecs->count() > 0) {
            if ($entryRec) {
                $paymentPreset['type'] = 'mixed';
                $paymentPreset['entry'] = (float) $entryRec->amount;
                $paymentPreset['entry_method'] = $entryRec->payment_method ?: 'cash';
            } else {
                $paymentPreset['type'] = 'invoice';
            }
            if ($installmentRecs->count() > 0) {
                $paymentPreset['installments'] = $installmentRecs->count();
                $paymentPreset['firstDue'] = $installmentRecs->min('due_date')?->toDateString() ?? now()->toDateString();
                $sorted = $installmentRecs->sortBy('due_date')->values();
                if ($sorted->count() >= 2) {
                    $paymentPreset['interval'] = \Carbon\Carbon::parse($sorted[0]->due_date)->diffInDays(\Carbon\Carbon::parse($sorted[1]->due_date));
                }
                // método mais frequente
                $method = $installmentRecs->groupBy('payment_method')->sortByDesc(fn($g)=>$g->count())->keys()->first();
                $paymentPreset['installment_method'] = $method ?: 'boleto';
            }
        }
        return view('orders.edit', compact('order','clients','items','hasPhysicalProducts','canIssueNfe','paymentPreset','hadCancelledNfe','hasSuccessfulNfe','canShowReopen'));
    }

    public function paymentForm(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless(auth()->user()->hasPermission('orders.freight.assign'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array(strtolower((string)$order->status), ['fulfilled','canceled','partial_returned'], true)) {
            if (strtolower((string)$order->status) === 'partial_returned') {
                return redirect()->route('orders.edit', $order)->with('error', 'Pedido com devolução parcial não permite definir pagamento. Reabra o pedido para permitir edições.');
            }
            return redirect()->route('orders.edit', $order)->with('error', 'Pedido neste status não permite definir pagamento.');
        }
        $tenantId = auth()->user()->tenant_id;
        $items = $order->items()->orderBy('id')->get(['product_id']);
        $hasPhysicalProducts = false;
        if ($items->count() > 0) {
            $productIds = $items->pluck('product_id')->filter()->values();
            if ($productIds->count() > 0) {
                $hasPhysicalProducts = Product::where('tenant_id', $tenantId)
                    ->whereIn('id', $productIds)
                    ->where('type', 'product')
                    ->exists();
            }
        }
        return view('orders.payment', compact('order', 'hasPhysicalProducts'));
    }

    public function update(Request $request, Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        // Bloquear alterações quando o pedido já está finalizado, cancelado ou com devolução parcial
        $statusNorm = strtolower(trim((string) $order->status));
        if (in_array($statusNorm, ['fulfilled','canceled','partial_returned'], true)) {
            if ($statusNorm === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não pode ser alterado. Reabra o pedido para permitir edições.');
            }
            return back()->with('error', 'Pedido finalizado/cancelado não pode ser alterado. Reabra o pedido para editar.');
        }
        $clientOnly = $request->boolean('client_only');

        if ($clientOnly) {
            // Bloquear alteração mesmo via client_only se estiver com devolução parcial
            $statusNormCheck = strtolower(trim((string) $order->status));
            if ($statusNormCheck === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não pode ser alterado. Reabra o pedido para permitir edições.');
            }
            
            $vv = $request->validate(['client_id' => 'required|exists:clients,id']);
            // Permitir alterar cliente mesmo com pedido finalizado, sem refazer baixa de estoque ou financeiro
            // (mas NÃO se estiver com partial_returned - já bloqueado acima)
            $order->client_id = (int) $vv['client_id'];
            
            // Também processar desconto se enviado
            if ($request->filled('discount_total_override')) {
                $newDiscount = max(0.0, (float) $request->input('discount_total_override'));
                if ($newDiscount != $order->discount_total) {
                    $order->discount_total = $newDiscount;
                    
                    // Recalcular total
                    $items = $order->items()->get(['quantity','unit_price','discount_value']);
                    $netItems = 0.0;
                    foreach ($items as $it) {
                        $gross = (float) $it->quantity * (float) $it->unit_price;
                        $netItems += max(0.0, $gross - (float) ($it->discount_value ?? 0));
                    }
                    $order->total_amount = max(0.0, $netItems - $newDiscount + (float) ($order->addition_total ?? 0));
                    
                    // Registrar auditoria
                    \App\Models\OrderAudit::create([
                        'order_id' => $order->id,
                        'user_id' => auth()->id(),
                        'action' => 'updated',
                        'notes' => 'Cliente e desconto atualizados',
                        'changes' => [
                            'client_id' => ['old' => $order->getOriginal('client_id'), 'new' => $order->client_id],
                            'discount_total' => ['old' => $order->getOriginal('discount_total'), 'new' => $newDiscount],
                            'total_amount' => ['old' => $order->getOriginal('total_amount'), 'new' => $order->total_amount]
                        ]
                    ]);
                }
            }
            
            $order->save();
            return back()->with('success','Cliente e desconto do pedido atualizados.');
        }

        // Bloquear alterações plenas em pedidos finalizados, cancelados ou com devolução parcial
        $statusNorm = strtolower(trim((string) $order->status));
        if (in_array($statusNorm, ['fulfilled','canceled','partial_returned'], true)) {
            if ($statusNorm === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não pode ser alterado. Reabra o pedido para permitir edições.');
            }
            return back()->with('error', 'Pedido neste status não pode ser alterado.');
        }
        if (!empty($order->nfe_issued_at)) {
            return back()->with('error', 'Pedido com NF-e emitida não pode ser alterado.');
        }

        // Regras: quando reaberto (status 'open'), permitir salvar sem forçar alteração de status
        $rules = [
            'client_id' => 'nullable|exists:clients,id',
            'title' => 'nullable|string|max:255',
            // ✅ permitir salvar desconto geral pelo formulário principal
            'discount_total_override' => 'nullable|numeric|min:0',
            // ✅ permitir salvar descontos por item na tela de edição
            'item_discounts' => 'nullable|array',
            'item_discounts.*' => 'nullable|numeric|min:0',
        ];
        // Só exigir 'status' quando houver tentativa de mudança explícita
        if ($request->has('status')) {
            $rules['status'] = 'in:open,canceled';
        }
        $messages = [
            'client_id.exists' => 'Cliente inválido.',
            'title.string' => 'O título deve ser um texto válido.',
            'title.max' => 'O título deve ter no máximo 255 caracteres.',
            'status.in' => 'Status inválido. Use Aberto ou Cancelado.',
            'discount_total_override.numeric' => 'Desconto deve ser um número válido.',
            'discount_total_override.min' => 'Desconto não pode ser negativo.',
        ];
        $attributes = [
            'client_id' => 'cliente',
            'title' => 'título',
            'status' => 'status',
            'discount_total_override' => 'desconto total',
        ];
        $v = $request->validate($rules, $messages, $attributes);

        // Evitar finalizar por alteração direta de status
        if (($v['status'] ?? '') === 'fulfilled') {
            return back()->with('error', 'Para finalizar o pedido use o botão "Finalizar pedido" na seção de frete.');
        }

        $payload = [];
        $changes = [];
        
        // Verificar mudanças no título
        if (array_key_exists('title', $v) && $v['title'] !== $order->title) {
            $payload['title'] = $v['title'];
            $changes['title'] = ['old' => $order->title, 'new' => $v['title']];
        }
        
        // Verificar mudanças no cliente
        if (array_key_exists('client_id', $v) && !empty($v['client_id']) && $v['client_id'] != $order->client_id) {
            $payload['client_id'] = $v['client_id'];
            $changes['client_id'] = ['old' => $order->client_id, 'new' => $v['client_id']];
        }
        
        // Verificar mudanças no status
        if (array_key_exists('status', $v) && $v['status'] !== $order->status) {
            $payload['status'] = $v['status'];
            $changes['status'] = ['old' => $order->status, 'new' => $v['status']];
        }
        
        // Sempre atualizar campos de auditoria se houver mudanças
        if (!empty($payload)) {
            $payload['updated_by'] = auth()->id();
            $payload['last_edited_at'] = now();
        }
        // ✅ processar e persistir desconto geral quando enviado
        if (array_key_exists('discount_total_override', $v)) {
            $newDiscount = max(0.0, (float) ($v['discount_total_override'] ?? 0));
            if ($newDiscount != $order->discount_total) {
                $payload['discount_total'] = $newDiscount;
                $changes['discount_total'] = ['old' => $order->discount_total, 'new' => $newDiscount];

                // ✅ Recalcular total considerando itens (qtd * preço - desc_item), desconto geral e acréscimos
                $items = $order->items()->get(['quantity','unit_price','discount_value']);
                $netItems = 0.0;
                foreach ($items as $it) {
                    $gross = (float) $it->quantity * (float) $it->unit_price;
                    $netItems += max(0.0, $gross - (float) ($it->discount_value ?? 0));
                }
                $newTotal = max(0.0, $netItems - $newDiscount + (float) ($order->addition_total ?? 0));
                if ($newTotal != $order->total_amount) {
                    $payload['total_amount'] = $newTotal;
                    $changes['total_amount'] = ['old' => $order->total_amount, 'new' => $newTotal];
                }
            }
        }

        // ✅ persistir descontos por item quando enviados
        if (isset($v['item_discounts']) && is_array($v['item_discounts'])) {
            // Helper local para parsear números no formato pt-BR
            $toFloat = static function($val): float {
                if ($val === null) return 0.0;
                $s = is_string($val) ? $val : (string)$val;
                $s = preg_replace('/\s+/', '', $s);
                $s = preg_replace('/[^0-9,\.\-]/', '', $s);
                $hasComma = strpos($s, ',') !== false;
                $hasDot = strpos($s, '.') !== false;
                if ($hasComma && $hasDot) { $s = str_replace('.', '', $s); $s = str_replace(',', '.', $s); }
                elseif ($hasComma) { $s = str_replace(',', '.', $s); }
                return (float) $s;
            };

            $itemIds = array_keys($v['item_discounts']);
            if (!empty($itemIds)) {
                $itemsMap = \App\Models\OrderItem::where('order_id', $order->id)
                    ->whereIn('id', array_map('intval', $itemIds))
                    ->get()
                    ->keyBy('id');
                
                $itemDiscountsChanged = false;
                foreach ($v['item_discounts'] as $itemId => $discVal) {
                    $it = $itemsMap->get((int)$itemId);
                    if (!$it) { continue; }
                    $gross = round(((float)$it->quantity) * ((float)$it->unit_price), 2);
                    $disc = $toFloat($discVal);
                    if ($disc < 0) { $disc = 0; }
                    if ($disc > $gross) { $disc = $gross; }
                    
                    if ($disc != $it->discount_value) {
                        $itemDiscountsChanged = true;
                        $changes['item_discounts'][$itemId] = ['old' => $it->discount_value, 'new' => $disc];
                    }
                    
                    $it->discount_value = round($disc, 2);
                    $it->line_total = round(max(0.0, $gross - $it->discount_value), 2);
                    $it->save();
                }
                
                if ($itemDiscountsChanged) {
                    // Recalcula totais após atualizar os itens
                    $this->recalculateTotals($order->fresh());
                    // Atualizar campos de auditoria se não foram atualizados ainda
                    if (!isset($payload['updated_by'])) {
                        $payload['updated_by'] = auth()->id();
                        $payload['last_edited_at'] = now();
                    }
                }
            }
        }

        $order->update($payload);

        // Registrar auditoria de atualização apenas se houver mudanças
        if (!empty($payload) || !empty($changes)) {
            try {
                \App\Models\OrderAudit::create([
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'action' => 'updated',
                    'notes' => 'Pedido atualizado',
                    'changes' => [
                        'updated_fields' => array_keys($payload),
                        'field_changes' => $changes,
                        'timestamp' => now()->toISOString()
                    ]
                ]);
            } catch (\Exception $e) {
                \Log::error('Erro ao criar auditoria de pedido: ' . $e->getMessage());
            }
        }

        return back()->with('success','Pedido atualizado com sucesso.');
    }

    public function destroy(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.delete'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);

        // Debug: verificar dados recebidos
        \Log::info('Cancelamento de pedido', [
            'order_id' => $order->id,
            'cancel_reason' => $request->input('cancel_reason'),
            'all_data' => $request->all()
        ]);

        // Validar justificativa obrigatória
        $v = $request->validate([
            'cancel_reason' => 'required|string|min:15|max:500',
        ], [
            'cancel_reason.required' => 'A justificativa do cancelamento é obrigatória.',
            'cancel_reason.min' => 'A justificativa deve ter pelo menos 15 caracteres.',
            'cancel_reason.max' => 'A justificativa não pode ultrapassar 500 caracteres.',
        ]);

        // Validação: Bloquear se NF-e foi transmitida
        $latestNfe = $order->latestNfeNoteCompat;
        $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
        $hasSuccessfulNfe = in_array($nfeStatus, ['emitted','transmitida']);
        
        if ($hasSuccessfulNfe || !empty($order->nfe_issued_at)) {
            return back()->with('error', 'Pedido com NF-e transmitida não pode ser cancelado. Cancele a NF-e primeiro na SEFAZ.');
        }

        // Validação: Prazo máximo configurável por tenant
        $maxDays = (int) \App\Models\Setting::get('orders.cancel.max_days', 90);
        if ($maxDays > 0 && $order->created_at) {
            $daysSinceCreation = now()->diffInDays($order->created_at);
            if ($daysSinceCreation > $maxDays) {
                return back()->with('error', "Não é permitido cancelar pedidos após {$maxDays} dias da criação.");
            }
        }

        // 1) Devolver estoque dos itens físicos
        foreach ($order->items as $item) {
            if ($item->product && $item->product->type === 'product') {
                \App\Models\StockMovement::create([
                    'tenant_id' => $order->tenant_id,
                    'product_id' => $item->product_id,
                    'type' => 'entry',
                    'quantity' => (float)$item->quantity,
                    'unit_price' => (float)$item->unit_price,
                    'document' => 'Cancelamento Pedido #'.$order->number,
                    'note' => 'Devolução de estoque por cancelamento de pedido',
                ]);
            }
        }

        // 2) Reversão financeira: estornar títulos pagos + cancelar títulos em aberto
        $receivables = \App\Models\Receivable::where('order_id', $order->id)->get();
        $totalEstornado = 0;
        $totalCancelado = 0;
        $taxaAntecipacao = 0;

        foreach ($receivables as $rec) {
            if ($rec->status === 'paid') {
                // Verificar se já foi estornado anteriormente
                $alreadyRefunded = \App\Models\Payable::where('tenant_id', $order->tenant_id)
                    ->where('description', 'like', '%Estorno de recebimento%')
                    ->where('description', 'like', '%pedido #'.$order->number.'%')
                    ->where('amount', (float)$rec->amount)
                    ->exists();
                
                if (!$alreadyRefunded) {
                    // Estornar valor total pago
                    \App\Models\Payable::create([
                        'tenant_id' => $order->tenant_id,
                        'supplier_name' => 'Estorno Financeiro',
                        'description' => '⚡ Estorno Automático - Cancelamento Pedido #'.$order->number,
                        'amount' => -(float)$rec->amount,
                        'due_date' => now()->toDateString(),
                        'status' => 'paid',
                        'paid_at' => now(),
                        'payment_method' => $rec->payment_method,
                    ]);
                    $totalEstornado += (float)$rec->amount;

                    // Taxa de antecipação para cartão
                    $percent = (float) \App\Models\Setting::get('orders.cancel.card_anticipation_fee_percent', 0);
                    if (($rec->payment_method === 'card') && $percent > 0) {
                        $fee = round(((float)$rec->amount) * ($percent/100), 2);
                        if ($fee > 0) {
                            \App\Models\Payable::create([
                                'tenant_id' => $order->tenant_id,
                                'supplier_name' => 'Taxa de Antecipação (Estorno Cartão)',
                                'description' => '⚡ Taxa Antecipação - Cancelamento Pedido #'.$order->number,
                                'amount' => $fee,
                                'due_date' => now()->toDateString(),
                                'status' => 'paid',
                                'paid_at' => now(),
                                'payment_method' => 'card',
                            ]);
                            $taxaAntecipacao += $fee;
                        }
                    }
                } else {
                    // Já foi estornado anteriormente, apenas registrar no log
                    \Log::info('Receivable já estornado anteriormente', [
                        'receivable_id' => $rec->id,
                        'order_id' => $order->id,
                        'amount' => $rec->amount
                    ]);
                }
            } else {
                // Cancelar parcelas em aberto
                $totalCancelado += (float)$rec->amount;
            }
            
            $rec->status = 'canceled';
            $rec->save();
        }

        // 3) Marcar pedido como cancelado
        $order->status = 'canceled';
        $order->canceled_at = now();
        $order->canceled_by = auth()->user()->name;
        $order->cancel_reason = $v['cancel_reason'];
        $order->save();

        // 4) Registrar auditoria completa
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'canceled',
            'notes' => 'Pedido cancelado: ' . $v['cancel_reason'],
            'changes' => [
                'cancel_reason' => $v['cancel_reason'],
                'canceled_by' => auth()->user()->name,
                'stock_returned' => true,
                'financial_reversal' => true,
                'total_estornado' => $totalEstornado,
                'total_cancelado' => $totalCancelado,
                'taxa_antecipacao' => $taxaAntecipacao,
                'receivables_count' => $receivables->count(),
                'timestamp' => now()->toISOString()
            ]
        ]);

        $message = "Pedido cancelado com sucesso. Estoque devolvido.";
        if ($totalEstornado > 0) {
            $message .= " Valor estornado: R$ " . number_format($totalEstornado, 2, ',', '.');
        }
        if ($taxaAntecipacao > 0) {
            $message .= " Taxa de antecipação: R$ " . number_format($taxaAntecipacao, 2, ',', '.');
        }

        return redirect()->route('orders.index')->with('success', $message);
    }

    public function addItem(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        // Bloquear inclusão em finalizado/cancelado ou com devolução parcial
        if (in_array(strtolower((string)$order->status), ['fulfilled','canceled','partial_returned'], true)) {
            if (strtolower((string)$order->status) === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não permite adicionar itens. Reabra o pedido para permitir edições.');
            }
            return back()->with('error','Pedido neste status não permite adicionar itens.');
        }

        // Se já houve NFe emitida e cancelada, bloquear alteração de itens
        try {
            $latestNfe = $order->latestNfeNoteCompat;
            $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
            $hadCancelledNfe = in_array($nfeStatus, ['cancelada','cancelled'], true);
            if ($hadCancelledNfe) {
                return back()->with('error', 'Pedido reaberto com NFe cancelada: não é permitido alterar itens.');
            }
        } catch (\Throwable $e) {}

        $rules = [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.001',
            'discount_value' => 'nullable', // parse manual para aceitar vírgula
            '_new_price_hidden' => 'nullable', // parse manual para aceitar vírgula
        ];
        $messages = [
            'product_id.required' => 'Selecione um produto.',
            'product_id.exists' => 'Produto inválido.',
            'quantity.required' => 'Informe a quantidade.',
            'quantity.numeric' => 'A quantidade deve ser um número válido.',
            'quantity.min' => 'A quantidade deve ser maior que zero.',
        ];
        $attributes = [
            'product_id' => 'produto',
            'quantity' => 'quantidade',
        ];
        $data = $request->validate($rules, $messages, $attributes);

        $product = Product::where('tenant_id', auth()->user()->tenant_id)
            ->where('active', 1)
            ->find($data['product_id']);
        if (!$product) {
            return back()->with('error', 'Produto inválido.');
        }

        $qty = (float) $data['quantity'];
        // Verificação de estoque (respeita configuração de permitir negativo)
        $allowNegative = \App\Models\Setting::get('stock.allow_negative','0')==='1';
        if (!$allowNegative && $product->type === 'product') {
            $tenantId = auth()->user()->tenant_id;
            $entry = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->whereIn('type',["entry","adjustment"]) ->sum('quantity');
            $exit = \App\Models\StockMovement::where('tenant_id',$tenantId)->where('product_id',$product->id)->where('type','exit')->sum('quantity');
            $balance = (float)$entry - (float)$exit;
            if ($balance + 1e-6 < $qty) {
                return back()->with('error', 'Estoque insuficiente para '.$product->name.'. Saldo: '.number_format($balance,3,',','.'));
            }
        }
        // Helpers de parse para aceitar vírgula como separador decimal
        $toFloat = static function($val): float {
            if ($val === null) return 0.0;
            $s = is_string($val) ? $val : (string)$val;
            $s = preg_replace('/\s+/', '', $s);
            $s = preg_replace('/[^0-9,\.\-]/', '', $s);
            $hasComma = strpos($s, ',') !== false;
            $hasDot = strpos($s, '.') !== false;
            if ($hasComma && $hasDot) {
                // Formato pt-BR: "." milhar e "," decimal => remove pontos e troca vírgula por ponto
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } elseif ($hasComma) {
                // Apenas vírgula => decimal
                $s = str_replace(',', '.', $s);
            } else {
                // Apenas ponto ou inteiro => mantém
            }
            return (float) $s;
        };

        // Preço unitário pode ser sobrescrito pelo formulário (quando disponível)
        $priceForm = $toFloat($request->input('_new_price_hidden'));
        $price = $priceForm > 0 ? $priceForm : (float) ($product->price ?? 0);

        // Verificar se o produto já existe no pedido
        $existingItem = OrderItem::where('tenant_id', auth()->user()->tenant_id)
            ->where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            // Produto já existe - somar quantidades e descontos
            $newQuantity = $existingItem->quantity + $qty;
            $newDiscountValue = $toFloat($request->input('discount_value'));
            $newDiscountTotal = $existingItem->discount_value + $newDiscountValue;
            
            $gross = round($newQuantity * $price, 2);
            if ($newDiscountTotal > $gross) { $newDiscountTotal = $gross; }
            $line = round(max(0, $gross - $newDiscountTotal), 2);

            $existingItem->update([
                'quantity' => $newQuantity,
                'discount_value' => $newDiscountTotal,
                'line_total' => $line,
            ]);
        } else {
            // Produto não existe - criar novo item
            $gross = round($qty * $price, 2);
            // Desconto informado no formulário é valor da linha (não por unidade)
            $discountLine = $toFloat($request->input('discount_value'));
            if ($discountLine < 0) { $discountLine = 0; }
            if ($discountLine > $gross) { $discountLine = $gross; }
            $discountTotal = round($discountLine, 2);
            $line = round(max(0, $gross - $discountTotal), 2);

            OrderItem::create([
                'tenant_id' => auth()->user()->tenant_id,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'description' => null,
                'quantity' => $qty,
                'unit' => $product->unit,
                'unit_price' => $price,
                'discount_value' => $discountTotal,
                'addition_value' => 0,
                'line_total' => $line,
            ]);
        }

        $this->recalculateTotals($order);

        // Registrar auditoria de adição de item
        try {
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => 'Item adicionado ao pedido',
                'changes' => [
                    'action_type' => 'add_item',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'was_existing' => $existingItem ? true : false,
                    'new_total_amount' => $order->fresh()->total_amount,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar auditoria de adição de item: ' . $e->getMessage());
        }

        return redirect()->route('orders.edit', $order)->with('success', 'Item adicionado.');
    }

    public function removeItem(Order $order, OrderItem $item)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id && $item->order_id === $order->id, 403);
        if (in_array(strtolower((string)$order->status), ['fulfilled','canceled','partial_returned'], true)) {
            if (strtolower((string)$order->status) === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não permite remover itens. Reabra o pedido para permitir edições.');
            }
            return back()->with('error','Pedido neste status não permite remover itens.');
        }

        // Se já houve NFe emitida e cancelada, bloquear alteração de itens
        try {
            $latestNfe = $order->latestNfeNoteCompat;
            $nfeStatus = strtolower((string) ($latestNfe->status ?? ''));
            $hadCancelledNfe = in_array($nfeStatus, ['cancelada','cancelled'], true);
            if ($hadCancelledNfe) {
                return back()->with('error', 'Pedido reaberto com NFe cancelada: não é permitido alterar itens.');
            }
        } catch (\Throwable $e) {}

        // Devolução de estoque se remover item em pedido reaberto sem NFe emitida anteriormente
        $product = $item->product;
        if ($product && $product->type === 'product') {
            \App\Models\StockMovement::create([
                'tenant_id' => $order->tenant_id,
                'product_id' => $product->id,
                'type' => 'entry',
                'quantity' => (float)$item->quantity,
                'unit_price' => (float)$item->unit_price,
                'document' => 'Reabertura Pedido #'.$order->number,
                'note' => 'Retorno de estoque por remoção de item em pedido reaberto',
            ]);
        }
        $item->delete();
        $this->recalculateTotals($order);
        
        // Registrar auditoria de remoção de item
        try {
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => 'Item removido do pedido',
                'changes' => [
                    'action_type' => 'remove_item',
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'stock_returned' => $product && $product->type === 'product',
                    'new_total_amount' => $order->fresh()->total_amount,
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar auditoria de remoção de item: ' . $e->getMessage());
        }
        
        return redirect()->route('orders.edit', $order)->with('success','Item removido.');
    }

    public function print(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        $order->loadMissing(['client','items','tenant']);
        
        // Usa accessor returned_quantity do OrderItem para calcular quantidades devolvidas
        // Criar coleção de itens ajustados (com quantidades após devolução)
        $adjustedItems = collect();
        foreach ($order->items as $item) {
            // Usa accessor returned_quantity do OrderItem
            $returnedQty = $item->returned_quantity;
            $originalQty = (float) $item->quantity;
            $remainingQty = max(0, round($originalQty - $returnedQty, 3));
            
            // Se item foi totalmente devolvido, não incluir no print
            if ($remainingQty <= 0.001) {
                continue;
            }
            
            // Calcular desconto ajustado (proporcional ou zerado - decidir política)
            $originalDiscount = (float) ($item->discount_value ?? 0);
            $adjustedDiscount = 0.0; // Por enquanto, zeramos desconto após devolução (pode mudar)
            
            // Se quisermos proporcional: $adjustedDiscount = $originalQty > 0 ? ($remainingQty / $originalQty) * $originalDiscount : 0;
            
            // Calcular total ajustado
            $gross = $remainingQty * (float)$item->unit_price;
            $adjustedLineTotal = max(0, round($gross - $adjustedDiscount, 2));
            
            // Criar item ajustado (clonar mas com valores ajustados)
            $adjustedItem = (object) [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $remainingQty,
                'original_quantity' => $originalQty, // Para referência se necessário
                'returned_quantity' => $returnedQty,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'discount_value' => $adjustedDiscount,
                'addition_value' => $item->addition_value ?? 0,
                'line_total' => $adjustedLineTotal,
                'product_id' => $item->product_id,
            ];
            
            $adjustedItems->push($adjustedItem);
        }
        
        // Rateio por item (vDesc, vFrete, vSeg, vOutro) para impressão - usar itens ajustados
        $rateioItems = [];
        if ($adjustedItems->count() > 0) {
            $items = $adjustedItems;
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

            $freteTotal = (float)($order->freight_cost ?? 0.0);
            $segTotal = (float)($order->valor_seguro ?? 0.0);
            $outrosTotal = (float)($order->outras_despesas ?? 0.0);
            $descontoHeader = (float)($order->discount_total ?? 0.0);
            $acrescimoHeader = (float)($order->addition_total ?? 0.0);

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
        // Calcular totais ajustados (após devoluções)
        $adjustedTotal = $adjustedItems->sum('line_total');
        $adjustedDiscountTotal = $adjustedItems->sum('discount_value');
        $adjustedAdditionTotal = (float)($order->addition_total ?? 0); // Manter acréscimos gerais
        $adjustedFinalTotal = max(0, $adjustedTotal - ((float)($order->discount_total ?? 0)) + $adjustedAdditionTotal);
        
        // Filtrar recebíveis: nunca mostrar estornos (valores negativos) misturados com formas de pagamento
        $receivables = Receivable::where('tenant_id', auth()->user()->tenant_id)
            ->where('order_id', $order->id)
            ->where('amount', '>', 0) // Excluir estornos (valores negativos)
            ->orderBy('due_date')
            ->get();
        
        // Opções de impressão (pode vir via query string ou ter padrões)
        $printOptions = [
            'show_payment' => (bool) ($request->input('show_payment', true)),
            'show_fiscal_info' => (bool) ($request->input('show_fiscal_info', true)),
            'show_transport' => (bool) ($request->input('show_transport', true)),
            'show_rateio' => (bool) ($request->input('show_rateio', false)),
            'show_tax_estimate' => (bool) ($request->input('show_tax_estimate', false)),
        ];
        // Estimativa de tributos considerando créditos fiscais - usar itens ajustados
        $icms = 0.0; $pis = 0.0; $cofins = 0.0;
        $taxCreditService = app(\App\Services\TaxCreditService::class);
        $icmsSuggestions = [];
        
        foreach ($adjustedItems as $index => $it) {
            $line = (float) ($it->line_total ?? 0);
            if ($line <= 0) { continue; }
            $prod = $it->product_id ? Product::find($it->product_id) : null;
            if (!$prod) { continue; }
            
            // Busca regra tributária
            $rate = TaxRate::where('tenant_id', $order->tenant_id)
                ->where('tipo_nota', 'produto')
                ->where(function($q) use ($prod) {
                    $q->where('ncm', $prod->ncm)->orWhere('cfop', $prod->cfop);
                })
                ->where('ativo', 1)
                ->orderByRaw("CASE WHEN ncm = ? AND cfop = ? THEN 0 WHEN ncm = ? THEN 1 WHEN cfop = ? THEN 2 ELSE 3 END", [$prod->ncm, $prod->cfop, $prod->ncm, $prod->cfop])
                ->first();
                
            if ($rate) {
                // Base de cálculo do ICMS inclui frete/seguro/outras despesas rateados e desconta descontos
                // Usar valores do item ajustado
                $itemDisc = (float)($it->discount_value ?? 0.0);
                $itemAdd = (float)($it->addition_value ?? 0.0);
                $vDesc = round($itemDisc + ($alocDescHeader[$index] ?? 0.0), 2);
                $vOutro = round($itemAdd + ($alocOutros[$index] ?? 0.0) + ($alocOutroHeader[$index] ?? 0.0), 2);
                $vFrete = round($alocFrete[$index] ?? 0.0, 2);
                $vSeg = round($alocSeg[$index] ?? 0.0, 2);
                $gross = (float)$it->quantity * (float)$it->unit_price; // valor bruto da linha ajustada
                $baseIcms = max($gross - $vDesc, 0.0) + $vFrete + $vSeg + $vOutro;
                
                // Alíquota do produto ou da regra tributária
                $aliquota = (float)($prod->aliquota_icms ?? $rate->icms_aliquota ?? 0);
                
                // Calcula ICMS considerando créditos fiscais - usar quantidade ajustada
                $icmsCalculation = $taxCreditService->calculateIcmsWithCredits(
                    $prod,
                    $baseIcms,
                    $aliquota,
                    (float)$it->quantity, // Quantidade já ajustada (após devolução)
                    $order->tenant_id
                );
                
                $icms += $icmsCalculation['icms_due'];
                
                // Armazena sugestões para exibição
                if ($icmsCalculation['suggestion']) {
                    $icmsSuggestions[] = [
                        'product_name' => $prod->name,
                        'suggestion' => $icmsCalculation['suggestion']
                    ];
                }

                // Mantém PIS/COFINS como estimativa simples sobre o total da linha
                $pis += $line * (float)($rate->pis_aliquota ?? 0);
                $cofins += $line * (float)($rate->cofins_aliquota ?? 0);
            }
        }
        $taxEstimate = [ 'icms' => $icms, 'pis' => $pis, 'cofins' => $cofins ];
        
        // Passar itens ajustados para a view
        $printData = [
            'order' => $order,
            'items' => $adjustedItems, // Itens ajustados (após devoluções)
            'receivables' => $receivables,
            'taxEstimate' => $taxEstimate,
            'rateioItems' => $rateioItems,
            'icmsSuggestions' => $icmsSuggestions,
            'adjustedTotals' => [
                'subtotal' => $adjustedTotal,
                'discount' => $adjustedDiscountTotal,
                'addition' => $adjustedAdditionTotal,
                'final' => $adjustedFinalTotal,
            ],
            'options' => $printOptions,
        ];
        
        return view('orders.print', $printData);
    }

    private function recalculateTotals(Order $order): void
    {
        // ✅ Recalcula somando (qtd * preço - desconto do item) para cada item, garantindo consistência
        $items = OrderItem::where('order_id', $order->id)->get(['quantity','unit_price','discount_value']);
        $netItems = 0.0;
        foreach ($items as $it) {
            $gross = (float) $it->quantity * (float) $it->unit_price;
            $netItems += max(0.0, $gross - (float) ($it->discount_value ?? 0));
        }
        $headerDiscount = (float) ($order->discount_total ?? 0);
        $headerAddition = (float) ($order->addition_total ?? 0);
        $order->total_amount = max(0.0, $netItems - $headerDiscount + $headerAddition);
        $order->save();
    }

    public function fulfill(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless(auth()->user()->hasPermission('orders.freight.assign'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);

        // Bloquear re-finalização: se já estiver finalizado ou com devolução parcial, exigir reabertura antes de qualquer nova finalização
        $statusNorm = strtolower((string) $order->status);
        if ($statusNorm === 'fulfilled') {
            return back()->with('error', 'Este pedido já está finalizado. Para alterar ou finalizar novamente, reabra o pedido primeiro.');
        }
        
        // Bloquear finalização quando o pedido está com devolução parcial
        if ($statusNorm === 'partial_returned') {
            return back()->with('error', 'Pedido com devolução parcial não pode ser finalizado. Reabra o pedido primeiro para permitir edições e finalização.');
        }

        // Impedir duplicidade
        if (!empty($order->nfe_issued_at)) {
            return back()->with('error', 'Pedido com NF-e emitida não pode ser alterado.');
        }

        // Detectar se devemos preservar o financeiro (há títulos não cancelados vinculados a este pedido)
        $preserveFinancial = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)
            ->where('order_id', $order->id)
            ->where('status', '!=', 'canceled')
            ->exists();

        // Validar e salvar frete
        $freightRules = [
            'freight_mode' => 'required|in:0,1,2,9',
            'freight_payer' => 'required|in:company,buyer',
            'carrier_id' => 'nullable|exists:carriers,id',
            'freight_cost' => 'nullable|numeric|min:0',
            'freight_obs' => 'nullable|string|max:255',
            // Volumes/Peso/Despesas
            'volume_qtd' => 'nullable|integer|min:1',
            'volume_especie' => 'nullable|string|max:50',
            'peso_bruto' => 'nullable|numeric|min:0',
            'peso_liquido' => 'nullable|numeric|min:0',
            'valor_seguro' => 'nullable|numeric|min:0',
            'outras_despesas' => 'nullable|numeric|min:0',
        ];
        $paymentRules = [
            // Pagamento
            'payment_type' => 'required|in:immediate,invoice,mixed',
            'entry_amount' => 'nullable|numeric|min:0',
            'installments' => 'nullable|integer|min:1|max:36',
        ];
        $data = $request->validate($preserveFinancial ? $freightRules : ($freightRules + $paymentRules));

        // Verificar necessidade de frete para pedidos com produtos físicos
        $hasPhysicalProducts = OrderItem::where('order_id', $order->id)
            ->whereNotNull('product_id')
            ->whereIn('product_id', function($q){
                $q->select('id')->from('products')->where('type','product');
            })
            ->exists();
        // Permitir finalizar sem frete definido; exigiremos no momento da emissão da NF-e

        // Regras por modalidade
        // Não obrigar transportadora/valor no fechamento do pedido; será exigido na emissão quando aplicável
        if ((int)$data['freight_mode'] === 9) {
            // Sem frete - zera campos
            $data['carrier_id'] = null;
            $data['freight_cost'] = null;
            $data['freight_obs'] = null;
            $data['volume_qtd'] = null;
            $data['volume_especie'] = null;
            $data['peso_bruto'] = null;
            $data['peso_liquido'] = null;
            $data['valor_seguro'] = null;
            $data['outras_despesas'] = null;
        }

        // carrier deve pertencer ao tenant, se informado
        if (!empty($data['carrier_id'])) {
            $carrierOk = Carrier::where('id', $data['carrier_id'])
                ->where('tenant_id', auth()->user()->tenant_id)
                ->exists();
            abort_unless($carrierOk, 403);
        }

        $order->freight_mode = (int)$data['freight_mode'];
        $order->freight_payer = $data['freight_payer'];
        $order->carrier_id = $data['carrier_id'] ?? null;
        $order->freight_cost = $data['freight_cost'] ?? null;
        $order->freight_obs = $data['freight_obs'] ?? null;
        // Observações não são mais salvas aqui; serão definidas no modal de emissão
        $order->volume_qtd = $data['volume_qtd'] ?? null;
        $order->volume_especie = $data['volume_especie'] ?? null;
        $order->peso_bruto = $data['peso_bruto'] ?? null;
        $order->peso_liquido = $data['peso_liquido'] ?? null;
        $order->valor_seguro = $data['valor_seguro'] ?? null;
        $order->outras_despesas = $data['outras_despesas'] ?? null;

        // Se configuração não permite estoque negativo, validar saldo antes de gerar financeiro e baixar estoque
        $allowNegative = \App\Models\Setting::get('stock.allow_negative','0')==='1';
        if (!$allowNegative) {
            $items = \App\Models\OrderItem::where('order_id', $order->id)
                ->whereNotNull('product_id')
                ->get(['product_id','quantity']);
            if ($items->count() > 0) {
                $productIds = $items->pluck('product_id')->filter()->values()->all();
                if (!empty($productIds)) {
                    $products = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)
                        ->whereIn('id', $productIds)
                        ->get(['id','name','type'])
                        ->keyBy('id');
                    // Somar necessidade por produto
                    $needByProduct = [];
                    foreach ($items as $it) {
                        $pid = (int) $it->product_id;
                        if (!$pid) { continue; }
                        $prod = $products->get($pid);
                        if (!$prod || (string)$prod->type !== 'product') { continue; }
                        $needByProduct[$pid] = ($needByProduct[$pid] ?? 0) + (float) $it->quantity;
                    }
                    foreach ($needByProduct as $pid => $needQty) {
                        $entry = (float) \App\Models\StockMovement::where('tenant_id', auth()->user()->tenant_id)
                            ->where('product_id', $pid)
                            ->whereIn('type',["entry","adjustment"]) ->sum('quantity');
                        $exit = (float) \App\Models\StockMovement::where('tenant_id', auth()->user()->tenant_id)
                            ->where('product_id', $pid)
                            ->where('type','exit')->sum('quantity');
                        $balance = $entry - $exit;
                        if ($balance + 1e-6 < $needQty) {
                            $p = $products->get($pid);
                            return back()->withErrors(['stock' => 'Estoque insuficiente para ' . (($p->name ?? 'produto').' (ID '.$pid.')') . '. Saldo: ' . number_format($balance,3,',','.') . ' | Necessário: ' . number_format($needQty,3,',','.')]);
                        }
                    }
                }
            }
        }

        // Se já estava finalizado e ainda sem NF-e, limpamos recebíveis anteriores deste pedido e regravamos
        if (strtolower((string)$order->status) === 'fulfilled') {
            // Limpa apenas títulos vinculados a este pedido (quando existentes)
            if (\Illuminate\Support\Facades\Schema::hasColumn('receivables','order_id')) {
                Receivable::where('tenant_id', auth()->user()->tenant_id)
                    ->where('order_id', $order->id)
                    ->delete();
            } else {
                Receivable::where('tenant_id', auth()->user()->tenant_id)
                    ->where('client_id', $order->client_id)
                    ->where('description', 'like', 'Pedido '.$order->number.'%')
                    ->delete();
            }
        }

        // Criar recebíveis conforme opção de pagamento (exceto quando preservando financeiro)
        // Valor líquido: itens (line_total - descontos itens) - desconto total + frete + seguro + outras despesas
        $sumLines = (float) $order->items()->sum('line_total');
        $sumItemDisc = (float) $order->items()->sum('discount_value');
        $netItems = max(0.0, $sumLines - $sumItemDisc);
        $total = max(0.0,
            $netItems
            - (float)($order->discount_total ?? 0)
            + (float)($order->addition_total ?? 0)
            + (float)($order->freight_cost ?? 0)
            + (float)($order->valor_seguro ?? 0)
            + (float)($order->outras_despesas ?? 0)
        );
        $total = round($total, 2);
        $today = now()->toDateString();
        $paymentType = $data['payment_type'] ?? null;
        $createdAny = false;

        // Sempre substituir quaisquer recebíveis anteriores (preservando ou não)
        Receivable::where('tenant_id', auth()->user()->tenant_id)
            ->where('order_id', $order->id)
            ->delete();


        // Regras de limites de parcelas
        $maxInstallments = 12;
        if ($total >= 100) { $maxInstallments = 24; }

        if ($paymentType === 'immediate') {
        Receivable::create([
            'tenant_id'=>auth()->user()->tenant_id,
            'client_id'=>$order->client_id,
            'order_id'=>$order->id,
            'description'=>sprintf('Pedido %s - pagamento à vista', $order->number),
                'amount'=>$total,
                'due_date'=>$today,
                'status'=>'paid',
                'received_at'=>now(),
                'payment_method'=> $data['immediate_method'] ?? 'cash'
            ]);
            $createdAny = true;
        } elseif ($paymentType === 'invoice') {
            // Use schedule se enviado; senão gera automaticamente
            $schedule = $request->input('schedule', []);
            if (!empty($schedule)) {
                // Normaliza e valida soma e quantidade
                $valid = [];
                $sum = 0.0;
                foreach ($schedule as $sc) {
                    $amt = round((float)($sc['amount'] ?? 0), 2);
                    $due = $sc['due_date'] ?? null;
                    if ($amt <= 0 || empty($due)) { continue; }
                    $sum += $amt;
                    $valid[] = [
                        'amount' => $amt,
                        'due_date' => \Carbon\Carbon::parse($due)->toDateString(),
                    ];
                }
                $count = count($valid);
                if ($count === 0) {
                    return back()->withErrors(['schedule' => 'Informe ao menos uma parcela válida.']);
                }
                if ($count > $maxInstallments) {
                    return back()->withErrors(['schedule' => 'Quantidade de parcelas superior ao permitido.']);
                }
                if (abs($sum - $total) > 0.01) {
                    return back()->withErrors(['schedule' => 'A soma das parcelas (' . number_format($sum,2,',','.') . ') deve ser igual ao total (' . number_format($total,2,',','.') . ').']);
                }
                $den = $count; $idx = 0;
                foreach ($valid as $sc) {
                    $idx++;
                    Receivable::create([
                        'tenant_id'=>auth()->user()->tenant_id,
                        'client_id'=>$order->client_id,
                        'order_id'=>$order->id,
                        'description'=>sprintf('Pedido %s - Parcela %d/%d', $order->number, $idx, $den),
                        'amount'=>$sc['amount'],
                        'due_date'=>$sc['due_date'],
                        'status'=>'open',
                        'payment_method'=> $data['installment_method'] ?? 'boleto',
                    ]);
                }
            } else {
                $installments = min($maxInstallments, max(1, (int)($data['installments'] ?? 1)));
                $firstDue = now()->addMonth()->toDateString();
                $interval = 30;
                $this->createInstallments($order, $total, $installments, $firstDue, $interval, $data['installment_method'] ?? 'boleto');
            }
            $createdAny = true;
        } else { // mixed
            $entry = (float)($data['entry_amount'] ?? 0);
            if ($entry > 0) {
                if ($entry > $total) { return back()->withErrors(['entry_amount' => 'Entrada não pode ser maior que o total.']); }
                Receivable::create([
                    'tenant_id'=>auth()->user()->tenant_id,
                    'client_id'=>$order->client_id,
                    'order_id'=>$order->id,
                    'description'=>sprintf('Pedido %s - entrada', $order->number),
                    'amount'=>round($entry,2),
                    'due_date'=>$today,
            'status'=>'paid',
            'received_at'=>now(),
                    'payment_method'=> $data['entry_method'] ?? 'cash'
        ]);
                $createdAny = true;
            }
            $remaining = round($total - $entry, 2);
            if ($remaining > 0) {
                $schedule = $request->input('schedule', []);
                if (!empty($schedule)) {
                    $valid = [];
                    $sum = 0.0;
                    foreach ($schedule as $sc) {
                        $amt = round((float)($sc['amount'] ?? 0), 2);
                        $due = $sc['due_date'] ?? null;
                        if ($amt <= 0 || empty($due)) { continue; }
                        $sum += $amt;
                        $valid[] = [
                            'amount' => $amt,
                            'due_date' => \Carbon\Carbon::parse($due)->toDateString(),
                        ];
                    }
                    $count = count($valid);
                    if ($count === 0) {
                        return back()->withErrors(['schedule' => 'Informe ao menos uma parcela válida.']);
                    }
                    if ($count > $maxInstallments) {
                        return back()->withErrors(['schedule' => 'Quantidade de parcelas superior ao permitido.']);
                    }
                    if (abs($sum - $remaining) > 0.01) {
                        return back()->withErrors(['schedule' => 'A soma das parcelas (' . number_format($sum,2,',','.') . ') deve ser igual ao restante (' . number_format($remaining,2,',','.') . ').']);
                    }
                    $den = $count; $idx = 0;
                    foreach ($valid as $sc) {
                        $idx++;
                        Receivable::create([
                            'tenant_id'=>auth()->user()->tenant_id,
                            'client_id'=>$order->client_id,
                            'order_id'=>$order->id,
                            'description'=>sprintf('Pedido %s - Parcela %d/%d', $order->number, $idx, $den),
                            'amount'=>$sc['amount'],
                            'due_date'=>$sc['due_date'],
                            'status'=>'open',
                            'payment_method'=> $data['installment_method'] ?? 'boleto',
                        ]);
                    }
                } else {
                    $installments = min($maxInstallments, max(1, (int)($data['installments'] ?? 1)));
                    $firstDue = now()->addMonth()->toDateString();
                    $interval = 30;
                    $this->createInstallments($order, $remaining, $installments, $firstDue, $interval, $data['installment_method'] ?? 'boleto');
                }
                $createdAny = true;
            }
        }

        if (!$createdAny) {
            return back()->with('error', 'Defina a forma de pagamento.');
        }

        // Baixa automática de estoque ao finalizar pedido (somente itens de produto)
        try {
            $items = \App\Models\OrderItem::where('order_id', $order->id)
                ->whereNotNull('product_id')
                ->get(['id','product_id','quantity','unit_price']);
            if ($items->count() > 0) {
                $productIds = $items->pluck('product_id')->filter()->values()->all();
                if (!empty($productIds)) {
                    $products = \App\Models\Product::where('tenant_id', auth()->user()->tenant_id)
                        ->whereIn('id', $productIds)
                        ->get(['id','type'])
                        ->keyBy('id');
                    foreach ($items as $it) {
                        $prod = $products->get($it->product_id);
                        if ($prod && (string)$prod->type === 'product') {
                            \App\Models\StockMovement::create([
                                'tenant_id' => auth()->user()->tenant_id,
                                'product_id' => $it->product_id,
                                'type' => 'exit',
                                'quantity' => (float) $it->quantity,
                                'unit_price' => (float) $it->unit_price,
                                'document' => 'Pedido #'.$order->number,
                                'note' => 'Baixa por finalização de pedido',
                            ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) { \Log::warning('Falha na baixa automática de estoque ao finalizar pedido', ['order_id'=>$order->id, 'error'=>$e->getMessage()]); }

        $order->status = 'fulfilled';
        $order->save();
        try {
            if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                activity()->performedOn($order)->causedBy(auth()->user())->log('Pedido finalizado');
            }
        } catch (\Throwable $e) {}

        // Registrar auditoria de finalização
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'finalized',
            'notes' => 'Pedido finalizado - estoque baixado e pagamentos registrados',
            'changes' => [
                'freight_mode' => $order->freight_mode,
                'freight_value' => $order->freight_cost,
                'stock_reduced' => true,
                'payments_registered' => true,
                'timestamp' => now()->toISOString()
            ]
        ]);

        return redirect()->route('orders.index')->with('success','Pedido finalizado. Pagamentos registrados.');
    }

    public function reopen(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        // Permissão específica para reabrir
        abort_unless(auth()->user()->hasPermission('orders.reopen') || auth()->user()->hasPermission('admin'), 403);

        // Usa método helper canBeReopened()
        if (!$order->canBeReopened()) {
            return back()->with('error', 'Pedido com NF-e transmitida não pode ser reaberto.');
        }

        // Reabrir apenas se estiver finalizado e sem NFe transmitida
        if ($order->status === 'fulfilled') {
            $v = $request->validate([
                'justification' => 'required|string|min:10|max:500',
            ]);

            // Verificar se há recebíveis (mas não estornar - financeiro já foi tratado na devolução se houver)
            $hasReceivables = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)
                ->where('order_id', $order->id)
                ->where('status','!=','canceled')
                ->exists();
            
            // Preservar financeiro existente
            $order->reopen_preserve_financial = $hasReceivables;
            // Usa método helper getItemsWithPartialReturns()
            $itemsWithReturns = $order->getItemsWithPartialReturns();
            $hasPartialReturns = $itemsWithReturns->isNotEmpty();

            $order->status = 'open';
            $order->save();
            
            // Log de atividade (se disponível)
            try {
                if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                    activity()->performedOn($order)->causedBy(auth()->user())->withProperties([
                        'justification' => $v['justification'] ?? null,
                        'financial_reversed' => (bool)$estornar,
                        'preserve_financial' => (bool)$order->reopen_preserve_financial,
                    ])->log('Pedido reaberto manualmente');
                }
            } catch (\Throwable $e) {}

            // Registrar auditoria de reabertura
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'reopened',
                'notes' => 'Pedido reaberto: ' . ($v['justification'] ?? 'Sem justificativa'),
                'changes' => [
                    'justification' => $v['justification'] ?? 'Sem justificativa',
                    'financial_reversal' => $estornar,
                    'stock_reversal' => true,
                    'timestamp' => now()->toISOString()
                ]
            ]);

            // Preparar mensagem de sucesso com aviso sobre devoluções, se houver
            $successMsg = 'Pedido reaberto para edição.';
            if ($hasPartialReturns && count($itemsWithReturns) > 0) {
                $itemsList = collect($itemsWithReturns)->map(function($item) {
                    return "{$item['name']} ({$item['returned']} devolvido(s) de {$item['sold']})";
                })->implode('; ');
                $successMsg .= ' Atenção: Este pedido possui itens com devoluções parciais. Verifique os descontos e ajuste os itens conforme necessário: ' . $itemsList;
            }

            return back()->with('success', $successMsg)->with('items_with_returns', $itemsWithReturns);
        }

        return back()->with('error', 'Este pedido não está finalizado.');
    }

    /**
     * Prepara dados de ajuste para exibição no modal de reabertura
     * Retorna JSON com preview das mudanças que serão aplicadas
     */
    public function prepareReopenAdjustment(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);

        // Carregar items do pedido antes de calcular devoluções
        $order->load('items');
        
        // Usa método helper getItemsWithPartialReturns()
        $itemsWithReturns = $order->getItemsWithPartialReturns();
        
        return response()->json([
            'has_adjustments' => $itemsWithReturns->isNotEmpty(),
            'items' => $itemsWithReturns->map(function($item) {
                return [
                    'item_id' => $item['item_id'],
                    'name' => $item['name'],
                    'sold' => $item['sold'],
                    'returned' => $item['returned'],
                    'remaining' => $item['remaining'],
                    'has_discount' => $item['has_discount'],
                    'discount_value' => $item['discount_value'],
                    'unit_price' => $item['unit_price'],
                    // Valores após ajuste
                    'new_discount' => 0.0, // Por enquanto zeramos desconto (pode mudar)
                    'new_line_total' => round($item['remaining'] * $item['unit_price'], 2),
                ];
            })->values(), // Usar values() para garantir array JSON válido
        ]);
    }

    /**
     * Reabre pedido aplicando ajustes automáticos nas quantidades e descontos
     */
    public function reopenWithAdjustment(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless(auth()->user()->hasPermission('orders.reopen') || auth()->user()->hasPermission('admin'), 403);

        // Usa método helper canBeReopened()
        if (!$order->canBeReopened()) {
            return back()->with('error', 'Pedido com NF-e transmitida não pode ser reaberto.');
        }

        // Reabrir apenas se estiver finalizado ou com devolução parcial
        if (!in_array($order->status, ['fulfilled', 'partial_returned'], true)) {
            return back()->with('error', 'Este pedido não pode ser reaberto no status atual.');
        }

        $v = $request->validate([
            'justification' => 'required|string|min:10|max:500',
            'apply_adjustments' => 'nullable|boolean',
        ]);

        $applyAdjustments = isset($v['apply_adjustments']) ? (bool) $v['apply_adjustments'] : true;

        // Financeiro já foi estornado na devolução, então não precisa estornar novamente na reabertura
        // Apenas preservar o financeiro existente
        $hasReceivables = \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)
            ->where('order_id', $order->id)
            ->where('status','!=','canceled')
            ->exists();
        
        $order->reopen_preserve_financial = $hasReceivables;

        // Usa método helper getItemsWithPartialReturns()
        $itemsWithReturns = $order->getItemsWithPartialReturns();
        $adjustmentsApplied = [];

        if ($applyAdjustments && $itemsWithReturns->isNotEmpty()) {
            // Aplicar ajustes automáticos
            foreach ($itemsWithReturns as $itemData) {
                $item = \App\Models\OrderItem::find($itemData['item_id']);
                if (!$item || $item->order_id !== $order->id) continue;

                $originalQty = (float) $item->quantity;
                $remainingQty = $itemData['remaining'];
                
                // Se item foi totalmente devolvido, remover completamente
                if ($remainingQty <= 0.001) {
                    $adjustmentsApplied[] = [
                        'action' => 'removed',
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'original_qty' => $originalQty,
                    ];
                    $item->delete();
                    continue;
                }

                // Se item foi parcialmente devolvido, ajustar quantidade e desconto
                $originalDiscount = (float) ($item->discount_value ?? 0);
                
                // Atualizar quantidade
                $item->quantity = $remainingQty;
                
                // Zerar desconto após devolução parcial (política atual)
                $item->discount_value = 0.0;
                
                // Recalcular line_total
                $gross = $remainingQty * (float)$item->unit_price;
                $item->line_total = round($gross - $item->discount_value, 2);
                $item->save();

                $adjustmentsApplied[] = [
                    'action' => 'adjusted',
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'original_qty' => $originalQty,
                    'new_qty' => $remainingQty,
                    'original_discount' => $originalDiscount,
                    'new_discount' => 0.0,
                ];
            }

            // Recalcular totais do pedido
            $this->recalculateTotals($order);
        }

        // Reabrir pedido
        $order->status = 'open';
        $order->save();

        // Registrar auditoria detalhada
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => $applyAdjustments ? 'reopened_with_auto_adjustment' : 'reopened',
            'notes' => 'Pedido reaberto' . ($applyAdjustments ? ' com ajuste automático' : '') . ': ' . ($v['justification'] ?? 'Sem justificativa'),
            'changes' => [
                'justification' => $v['justification'] ?? 'Sem justificativa',
                'auto_adjustments_applied' => $applyAdjustments,
                'adjustments' => $adjustmentsApplied,
                'total_adjustments' => count($adjustmentsApplied),
                'timestamp' => now()->toISOString()
            ]
        ]);

        $successMsg = 'Pedido reaberto' . ($applyAdjustments && !empty($adjustmentsApplied) ? ' com ajuste automático aplicado' : '') . '.';
        if (!empty($adjustmentsApplied)) {
            $itemsList = collect($adjustmentsApplied)->map(function($adj) {
                if ($adj['action'] === 'removed') {
                    return "{$adj['name']} (removido - {$adj['original_qty']} devolvido(s))";
                } else {
                    return "{$adj['name']} ({$adj['original_qty']} → {$adj['new_qty']})";
                }
            })->implode('; ');
            $successMsg .= ' Ajustes: ' . $itemsList;
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Ajusta quantidades e descontos automaticamente para pedidos já abertos com devolução parcial
     * Não reabre o pedido (já está aberto), apenas ajusta os itens
     */
    public function adjustWithReturns(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        
        // Só pode ajustar se o pedido estiver aberto e com devolução parcial
        if ($order->status !== 'open' && $order->status !== 'partial_returned') {
            return back()->with('error', 'Este pedido não está aberto para ajuste automático.');
        }

        // Verificar se tem NFe transmitida
        if ($order->has_successful_nfe || !empty($order->nfe_issued_at)) {
            return back()->with('error', 'Pedido com NF-e transmitida não pode ser ajustado automaticamente.');
        }

        $v = $request->validate([
            'apply_adjustments' => 'nullable|boolean',
        ]);

        $applyAdjustments = isset($v['apply_adjustments']) ? (bool) $v['apply_adjustments'] : true;

        // Usa método helper getItemsWithPartialReturns()
        $itemsWithReturns = $order->getItemsWithPartialReturns();
        $adjustmentsApplied = [];

        if ($applyAdjustments && $itemsWithReturns->isNotEmpty()) {
            // Aplicar ajustes automáticos
            foreach ($itemsWithReturns as $itemData) {
                $item = \App\Models\OrderItem::find($itemData['item_id']);
                if (!$item || $item->order_id !== $order->id) continue;

                $originalQty = (float) $item->quantity;
                $remainingQty = $itemData['remaining'];
                
                // Se item foi totalmente devolvido, remover completamente
                if ($remainingQty <= 0.001) {
                    $adjustmentsApplied[] = [
                        'action' => 'removed',
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'original_qty' => $originalQty,
                    ];
                    $item->delete();
                    continue;
                }

                // Se item foi parcialmente devolvido, ajustar quantidade e desconto
                $originalDiscount = (float) ($item->discount_value ?? 0);
                
                // Atualizar quantidade
                $item->quantity = $remainingQty;
                
                // Zerar desconto após devolução parcial (política atual)
                $item->discount_value = 0.0;
                
                // Recalcular line_total
                $gross = $remainingQty * (float)$item->unit_price;
                $item->line_total = round($gross - $item->discount_value, 2);
                $item->save();

                $adjustmentsApplied[] = [
                    'action' => 'adjusted',
                    'item_id' => $item->id,
                    'name' => $item->name,
                    'original_qty' => $originalQty,
                    'new_qty' => $remainingQty,
                    'original_discount' => $originalDiscount,
                    'new_discount' => 0.0,
                ];
            }

            // Recalcular totais do pedido
            $this->recalculateTotals($order);
            
            // Atualizar status para 'open' se estava como 'partial_returned'
            if ($order->status === 'partial_returned') {
                $order->status = 'open';
                $order->save();
            }
        }

        // Registrar auditoria detalhada
        \App\Models\OrderAudit::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => 'auto_adjusted_with_returns',
            'notes' => 'Ajuste automático de quantidades e descontos devido a devoluções parciais',
            'changes' => [
                'auto_adjustments_applied' => $applyAdjustments,
                'adjustments' => $adjustmentsApplied,
                'total_adjustments' => count($adjustmentsApplied),
                'timestamp' => now()->toISOString()
            ]
        ]);

        $successMsg = 'Quantidades e descontos ajustados automaticamente.';
        if (!empty($adjustmentsApplied)) {
            $itemsList = collect($adjustmentsApplied)->map(function($adj) {
                if ($adj['action'] === 'removed') {
                    return "{$adj['name']} (removido - {$adj['original_qty']} devolvido(s))";
                } else {
                    return "{$adj['name']} ({$adj['original_qty']} → {$adj['new_qty']})";
                }
            })->implode('; ');
            $successMsg .= ' Ajustes: ' . $itemsList;
        }

        return back()->with('success', $successMsg);
    }

    public function updateDiscounts(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        if (in_array(strtolower((string)$order->status), ['fulfilled','canceled','partial_returned'], true)) {
            if (strtolower((string)$order->status) === 'partial_returned') {
                return back()->with('error', 'Pedido com devolução parcial não permite alterar descontos. Reabra o pedido para permitir edições.');
            }
            return back()->with('error','Pedido neste status não permite alterar descontos.');
        }
        $data = $request->validate([
            'discount_total' => 'nullable|numeric|min:0',
            'item_discounts' => 'nullable|array',
            'item_discounts.*' => 'nullable|numeric|min:0',
        ]);

        // Atualiza desconto total do pedido
        $order->discount_total = (float)($data['discount_total'] ?? 0);
        $order->save();

        // Atualiza descontos por item
        $map = (array)($data['item_discounts'] ?? []);
        if (!empty($map)) {
            $items = $order->items()->get(['id','line_total']);
            $byId = $items->keyBy('id');
            foreach ($map as $itemId => $val) {
                $it = $byId->get((int)$itemId);
                if (!$it) { continue; }
                $disc = max(0.0, (float)$val);
                if ($disc > (float)$it->line_total) { $disc = (float)$it->line_total; }
                \App\Models\OrderItem::where('id', (int)$itemId)->update(['discount_value' => $disc]);
            }
        }

        // Recalcular total do pedido (itens - desc itens) e aplicar desconto total no total_amount
        $sumLines = (float) $order->items()->sum('line_total');
        $sumItemDisc = (float) $order->items()->sum('discount_value');
        $netItems = max(0.0, $sumLines - $sumItemDisc);
        $order->total_amount = max(0.0, $netItems - (float)($order->discount_total ?? 0));
        $order->save();

        // Registrar auditoria de atualização de descontos
        try {
            \App\Models\OrderAudit::create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'action' => 'updated',
                'notes' => 'Descontos atualizados no pedido',
                'changes' => [
                    'discount_total' => $order->discount_total,
                    'total_amount' => $order->total_amount,
                    'items_updated' => count($map),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Erro ao criar auditoria de descontos: ' . $e->getMessage());
        }
    }

    private function createInstallments(Order $order, float $amount, int $installments, string $firstDueDate, int $intervalDays, string $paymentMethod = 'boleto'): void
    {
        $tenantId = auth()->user()->tenant_id;
        $base = floor(($amount / $installments) * 100) / 100; // truncate to cents
        $remainder = round($amount - ($base * $installments), 2);
        $due = \Carbon\Carbon::parse($firstDueDate);
        for ($i = 1; $i <= $installments; $i++) {
            $value = $base + ($i === $installments ? $remainder : 0);
            Receivable::create([
                'tenant_id' => $tenantId,
                'client_id' => $order->client_id,
                'description' => sprintf('Pedido %s - Parcela %d/%d', $order->number, $i, $installments),
                'amount' => round($value, 2),
                'due_date' => $due->toDateString(),
                'status' => 'open',
                'payment_method' => $paymentMethod,
            ]);
            $due = $due->copy()->addDays($intervalDays);
        }
    }

    public function issueNfe(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless(auth()->user()->hasPermission('nfe.emit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        \Log::info('Orders.issueNfe called', ['order_id' => $order->id, 'status' => $order->status]);
        
        if ($order->status !== 'fulfilled') {
            return back()->with('error', 'Para emitir NF-e, o pedido precisa estar Finalizado.');
        }
        
        if (!$this->hasPaymentDefinition($order)) {
            return back()->with('error', 'Defina a forma/condição de pagamento antes de emitir a NF-e.')->with('action', route('orders.payment', $order));
        }

        // Bloqueio: se já houver NFe vinculada a este pedido (compatível com esquemas diferentes)
        $latestNfe = null;
        try {
            $hasOrderId = \Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'order_id');
            $hasNumeroPedido = \Illuminate\Support\Facades\Schema::hasColumn('nfe_notes', 'numero_pedido');
            $qNfe = \App\Models\NfeNote::where('tenant_id', auth()->user()->tenant_id);
            if ($hasOrderId || $hasNumeroPedido) {
                $qNfe->where(function($qq) use ($order, $hasOrderId, $hasNumeroPedido){
                    if ($hasOrderId) { $qq->where('order_id', $order->id); }
                    if ($hasNumeroPedido) { $qq->orWhere('numero_pedido', (string) $order->number); }
                });
                $latestNfe = $qNfe->orderByDesc('id')->first();
            }
        } catch (\Throwable $e) {
            // Se o schema não estiver acessível, prosseguir sem bloqueio adicional
        }

        if ($latestNfe) {
            $status = strtolower(trim((string) $latestNfe->status));
            $isRejection = in_array($status, ['error','rejeitada','rejected'], true);
            if (!$isRejection) {
                // Redireciona para o gerenciador/detalhe da NFe
                return redirect()->route('nfe.show', $latestNfe)
                    ->with('error', 'Este pedido já possui uma NF-e registrada. Gerencie a nota na tela de Notas Fiscais.');
            }
        }

        // ✅ Validação pré-emissão (dados fiscais, cliente, totais, pagamentos)
        if (method_exists($this, 'validateOrderForNfe')) {
            try {
                $validationErrors = $this->validateOrderForNfe($order);
                if (!empty($validationErrors)) {
                    $errorList = '<ul class="list-disc pl-5">';
                    foreach ($validationErrors as $err) { $errorList .= '<li>' . e($err) . '</li>'; }
                    $errorList .= '</ul>';
                    return back()->with('error', 'Corrija os seguintes problemas antes de emitir a NF-e:')
                        ->with('validation_errors', $errorList);
                }
            } catch (\Throwable $e) {
                \Log::warning('Falha na validação pré-NFe', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // Verificar se o emissor Delphi está disponível
        $nfeService = app(\App\Services\NFeService::class);
        if (!$nfeService->verificarDisponibilidade()) {
            return back()->with('error', 'Emissor de notas fiscais não está disponível. Verifique se o aplicativo está rodando.');
        }

        try {
            $type = $request->input('type', 'products'); // products | services | mixed
            $operation = $request->input('operation_type', 'venda');
            $tpNF = (int) $request->input('tpNF', 1);
            $finNFe = (int) $request->input('finNFe', 1);
            $idDest = (int) $request->input('idDest', 1);
            $cfop = (string) $request->input('cfop', '5102');
            $natOp = (string) $request->input('natOp', 'Venda de mercadoria');
            $referenceKey = trim((string) $request->input('reference_key', ''));
            
            // Montar payload completo conforme documento INSTRUCOES_PAYLOAD_NFE_EMISSOR.md
            $tenantId = auth()->user()->tenant_id;
            $payload = $nfeService->buildOrderPayload($order, $tenantId, [
                'tipo_operacao' => $operation,
            ]);

            // ✅ Se o usuário informou discount_total_override no modal, sincroniza com o pedido e payload
            try {
                if ($request->filled('discount_total_override')) {
                    $override = max(0.0, (float) $request->input('discount_total_override'));
                    if (abs((float)($order->discount_total ?? 0) - $override) > 0.009) {
                        $order->discount_total = $override;
                        // Recalcular total_amount do pedido para manter consistência
                        $sumLines = (float) $order->items()->sum('line_total');
                        $sumItemDisc = (float) $order->items()->sum('discount_value');
                        $netItems = max(0.0, $sumLines - $sumItemDisc);
                        $order->total_amount = max(0.0,
                            $netItems - $override + (float)($order->addition_total ?? 0)
                        );
                        $order->save();
                    }
                    // Atualiza vDesc e vNF no payload após override
                    $vProd = (float) ($payload['totais']['vProd'] ?? 0);
                    $descItens = (float) $order->items()->sum('discount_value');
                    $vDesc = $descItens + $override;
                    $vFrete = (float) ($payload['totais']['vFrete'] ?? 0);
                    $vSeg = (float) ($payload['totais']['vSeg'] ?? 0);
                    $vOutro = (float) ($payload['totais']['vOutro'] ?? 0);
                    $vNF = max(0.0, ($vProd - $vDesc) + $vFrete + $vSeg + $vOutro);
                    $payload['totais']['vDesc'] = number_format($vDesc, 2, '.', '');
                    $payload['totais']['vNF'] = number_format($vNF, 2, '.', '');
                }
            } catch (\Throwable $e) { \Log::warning('Sync discount_total_override falhou', ['order_id'=>$order->id,'error'=>$e->getMessage()]); }
            // Overrides de CFOP/natureza/idDest quando informados
            $payload['configuracoes'] = [
                'cfop' => $cfop,
                'natOp' => $natOp,
                'tpNF' => $tpNF,
                'finNFe' => $finNFe,
                'idDest' => $idDest,
                'tipo_nota' => $type,
                'operation_type' => $operation,
                'reference_key' => $referenceKey !== '' ? $referenceKey : null,
            ];

            // ✅ Reconcilia pagamentos com vNF (evita erro: soma dos pagamentos difere do total)
            try {
                $vNF = (float) ($payload['totais']['vNF'] ?? 0);
                $sumPag = 0.0;
                if (!empty($payload['pagamentos']) && is_array($payload['pagamentos'])) {
                    foreach ($payload['pagamentos'] as $pg) { $sumPag += (float) ($pg['valor'] ?? 0); }
                }
                if ($vNF > 0 && abs($sumPag - $vNF) > 0.01) {
                    // Determina tPag a partir do método escolhido no formulário (fallback: dinheiro)
                    $method = strtoupper((string) $request->input('payment_method', 'DINHEIRO'));
                    $map = [
                        'DINHEIRO' => '01', 'CASH' => '01',
                        'CHEQUE' => '02',
                        'CARTAO' => '03', 'CARTAO_CREDITO' => '03', 'CREDIT' => '03',
                        'CARTAO_DEBITO' => '04', 'DEBIT' => '04',
                        'CREDITO_LOJA' => '05',
                        'DEPOSITO' => '16',
                        'BOLETO' => '15',
                        'PIX' => '17',
                        'OUTROS' => '99',
                    ];
                    $tpag = $map[$method] ?? '01';
                    $payload['pagamentos'] = [[ 'tPag' => $tpag, 'valor' => round($vNF, 2) ]];
                }
            } catch (\Throwable $e) { \Log::warning('Reconciliação de pagamentos falhou', ['order_id'=>$order->id,'error'=>$e->getMessage()]); }
            // Definir série e candidato de próximo número sem commitar; commitar somente após sucesso
            try {
                $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
                $serieConfigured = (string) ($emitter->serie_nfe ?? '1');
                // Calcula candidato sem atualizar ponteiros globais ainda
                $keyNext = 'nfe.next_number.series.' . $serieConfigured;
                $configuredNext = (int) ((string) \App\Models\Setting::get($keyNext, '0'));
                $emitterCurrent = (int) ($emitter?->numero_atual_nfe ?: 0);
                $maxNumero = (int) (\App\Models\NfeNote::where('tenant_id', $tenantId)
                    ->when(\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','serie_nfe'), function($q) use ($serieConfigured) {
                        $q->where(function($qq) use ($serieConfigured){ $qq->where('serie_nfe',$serieConfigured)->orWhereNull('serie_nfe'); });
                    })
                    ->whereNotNull('numero_nfe')->where('numero_nfe','!=','')
                    ->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
                    ->value(\Illuminate\Support\Facades\DB::raw('CAST(numero_nfe AS UNSIGNED)')) ?? 0);
                $calcNext = max(1, $maxNumero + 1);
                $nextNumber = max($calcNext, $configuredNext, ($emitterCurrent > 0 ? $emitterCurrent + 1 : 0));
                $payload['serie'] = (int) $serieConfigured;
                $payload['numero'] = (int) $nextNumber;
                $payload['numero_nfe'] = (int) $nextNumber;
                // Também sinaliza nas configurações caso o emissor aceite
                $payload['configuracoes']['force_new_number'] = true;
                $payload['configuracoes']['serie'] = (int) $serieConfigured;
                $payload['configuracoes']['numero'] = (int) $nextNumber;

                // Não comitar ponteiros antes da autorização
            } catch (\Throwable $e) {
                \Log::warning('Falha ao calcular próximo número da NFe para OrderController@issueNfe', ['error'=>$e->getMessage()]);
            }
                // Garantir registro de NFe para este pedido: reutiliza pending/error, cria novo se todos anteriores foram emitidos/cancelados
                try {
                    $note = \App\Models\NfeNote::where('tenant_id', $tenantId)
                        ->where('numero_pedido', $order->number)
                        ->whereIn('status', ['pending','error'])
                        ->orderByDesc('id')
                        ->first();
                    if (!$note) {
                        $note = \App\Models\NfeNote::create([
                            'tenant_id' => $tenantId,
                            'client_id' => $order->client_id,
                            'numero_pedido' => $order->number,
                            'status' => 'pending',
                        ]);
                    }
                    \Illuminate\Support\Facades\DB::table('nfe_notes')->where('id', $note->id)->update([
                        'numero_nfe' => (string) $nextNumber,
                        'serie_nfe' => (string) $serieConfigured,
                    ]);
                    $note->numero_nfe = (string) $nextNumber;
                    $note->serie_nfe = (string) $serieConfigured;
                } catch (\Throwable $e) { \Log::warning('Falha ao preparar/atualizar nfe_notes para o pedido', ['error'=>$e->getMessage()]); }

                // Ajuda o serviço a resolver contexto em tentativas de retry
            $payload['tenant_id'] = $tenantId;
            \Log::info('Orders.issueNfe payload', ['order_id' => $order->id, 'payload' => $payload]);

            // Emitir NFe
            $resultado = $nfeService->emitirNFe($payload);
            \Log::info('Orders.issueNfe result', ['order_id' => $order->id, 'result' => $resultado]);

            if ($resultado['success']) {
                // Marcar como emitida
                $order->nfe_issued_at = now();
                $order->nfe_number = $resultado['data']['numero'] ?? null;
                $order->save();

                // Persistir nota emitida em nfe_notes
                try {
                    $tenantId = auth()->user()->tenant_id;
                    $data = $resultado['data'] ?? [];
                    // numero/chave/protocolo podem vir com nomes diferentes
                    $numeroNfe = $data['numero'] ?? ($data['nNF'] ?? null);
                    $chaveAcesso = $data['chave_acesso'] ?? ($data['chNFe'] ?? ($data['chave'] ?? null));
                    $protocolo = $data['protocolo'] ?? ($data['nProt'] ?? null);
                    $create = [
                        'tenant_id' => $tenantId,
                        'order_id' => $order->id,
                        'numero_nfe' => $numeroNfe,
                        'chave_acesso' => $chaveAcesso,
                        'protocolo' => $protocolo,
                        'status' => 'transmitida',
                        'xml_path' => (string) ($data['xml_path'] ?? ''),
                        'pdf_path' => (string) ($data['pdf_path'] ?? ''),
                        'data_emissao' => now(),
                        'data_transmissao' => now(),
                    ];
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','serie_nfe')) {
                        $create['serie_nfe'] = (string) ($payload['serie'] ?? '1');
                    }
                    $note = \App\Models\NfeNote::create($create);
                    try {
                        if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                            activity()->performedOn($order)->causedBy(auth()->user())->withProperties([
                                'nfe_id' => $note->id,
                                'numero' => $numeroNfe,
                            ])->log('NF-e emitida para o pedido');
                        }
                    } catch (\Throwable $e) {}
                } catch (\Throwable $e) {
                    \Log::warning('Falha ao registrar NFe emitida', ['error' => $e->getMessage()]);
                }

                // Se operação for devolução de venda (entrada), registrar entrada de estoque conforme return_qty,
                // exceto quando a emissão veio diretamente de um registro de devolução (flag skip_stock_entry)
                $skipStock = $request->boolean('skip_stock_entry', false);
                if ($operation === 'devolucao_venda' && !$skipStock) {
                    $returnQty = (array) $request->input('return_qty', []);
                    if (!empty($returnQty)) {
                        $items = $order->items()->get()->keyBy('id');
                        foreach ($returnQty as $orderItemId => $qty) {
                            $qtyNum = (float) $qty;
                            if ($qtyNum <= 0) { continue; }
                            $oi = $items[$orderItemId] ?? null;
                            if (!$oi || empty($oi->product_id)) { continue; }
                            // limita à quantidade vendida
                            $max = (float) $oi->quantity;
                            if ($qtyNum > $max) { $qtyNum = $max; }
                            \App\Models\StockMovement::create([
                                'tenant_id' => auth()->user()->tenant_id,
                                'product_id' => $oi->product_id,
                                'type' => 'entry',
                                'quantity' => $qtyNum,
                                'unit_price' => (float) $oi->unit_price,
                                'document' => 'Devolução NF-e Pedido #'.$order->number,
                                'note' => 'Entrada por devolução de venda',
                            ]);
                        }
                    }
                }

                // Commit dos ponteiros após sucesso
                try {
                    $numOk = (int) ($resultado['data']['numero'] ?? $nextNumber);
                    if ($numOk) {
                        $keyNext = 'nfe.next_number.series.' . (string) ($payload['serie'] ?? '1');
                        $ptr = (int) ((string) \App\Models\Setting::get($keyNext, '0'));
                        $target = $numOk + 1;
                        if ($target > $ptr) { \App\Models\Setting::set($keyNext, (string) $target); }
                        $emitter = \App\Models\TenantEmitter::where('tenant_id', $tenantId)->first();
                        if ($emitter) { $emitter->numero_atual_nfe = $numOk; $emitter->save(); }
                    }
                } catch (\Throwable $e) { \Log::warning('Falha ao commitar numeração pós-sucesso', ['error'=>$e->getMessage()]); }

                return back()->with('success', 'NF-e emitida com sucesso! Número: ' . ($resultado['data']['numero'] ?? 'N/A'));
            } else {
                return back()->with('error', 'Não foi possível emitir a nota. Revise os dados e tente novamente.');
            }

        } catch (\Exception $e) {
            \Log::error('Erro ao emitir NFe do pedido', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Ocorreu um erro ao emitir a nota. Tente novamente.');
        }
    }

    public function issueNfce(Order $order, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('orders.edit'), 403);
        abort_unless(auth()->user()->hasPermission('nfe.emit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);

        if ($order->status !== 'fulfilled') {
            return back()->with('error', 'Para emitir NFC-e, o pedido precisa estar Finalizado.');
        }

        // Verificar emissor
        $nfeService = app(\App\Services\NFeService::class);
        if (!$nfeService->verificarDisponibilidade()) {
            return back()->with('error', 'Emissor de notas fiscais não está disponível. Verifique se o aplicativo está rodando.');
        }

        try {
            $tenantId = auth()->user()->tenant_id;
            // Reaproveita o builder completo de pedido e ajusta para NFC-e
            if (!method_exists($nfeService, 'buildOrderPayload')) {
                return back()->with('error', 'Função de montagem de payload indisponível.');
            }
            $payload = $nfeService->buildOrderPayload($order, $tenantId, [
                'tipo_operacao' => 'venda',
            ]);
            // Ajustes NFC-e: modelo 65, presença, sem frete/volumes por padrão
            $payload['tipo'] = 'nfce';
            if (!isset($payload['configuracoes']) || !is_array($payload['configuracoes'])) { $payload['configuracoes'] = []; }
            $payload['configuracoes']['modelo'] = 65;
            $payload['configuracoes']['tipo_nota'] = '65';
            $payload['configuracoes']['indPres'] = (int) ($payload['configuracoes']['indPres'] ?? 1);
            $payload['configuracoes']['idDest'] = (int) ($payload['configuracoes']['idDest'] ?? 1);
            // Série específica da NFC-e, se configurada
            try {
                $serieNfce = (string) (\App\Models\Setting::get('nfce.series', '1'));
                $payload['configuracoes']['serie'] = (int) $serieNfce;
            } catch (\Throwable $e) {}

            // Definir candidato de numeração para NFC-e sem commitar; commitar somente após sucesso
            $nextNumber = null; $keyNext = null;
            try {
                $serie = (int) ($payload['configuracoes']['serie'] ?? 1);
                $keyNext = 'nfce.next_number.series.' . (string) $serie;
                $configuredNext = (int) ((string) \App\Models\Setting::get($keyNext, '0'));
                $maxNumero = (int) (\App\Models\NfeNote::where('tenant_id', $tenantId)
                    ->where(function($q){ $q->where('modelo', 65)->orWhereNull('modelo'); })
                    ->whereNotNull('numero_nfe')->where('numero_nfe','!=','')
                    ->orderByRaw('CAST(numero_nfe AS UNSIGNED) DESC')
                    ->value(\Illuminate\Support\Facades\DB::raw('CAST(numero_nfe AS UNSIGNED)')) ?? 0);
                $calcNext = max(1, $maxNumero + 1);
                $nextNumber = max($calcNext, $configuredNext);
                $payload['numero'] = (int) $nextNumber;
                $payload['numero_nfe'] = (int) $nextNumber;
                $payload['configuracoes']['numero'] = (int) $nextNumber;
            } catch (\Throwable $e) { \Log::warning('NFCE next number candidate compute failed', ['error'=>$e->getMessage()]); }

            // NFC-e: permitir emissor de teste quando habilitado em settings
            try {
                $amb = strtolower((string)($payload['configuracoes']['ambiente'] ?? ''));
                $hasCertPath = (string)($payload['cert']['path'] ?? '') !== '';
                $hasCertSerial = (string)($payload['cert']['serial'] ?? '') !== '';
                $useTest = ($amb === 'homologacao') && !$hasCertPath && !$hasCertSerial;
                if ($useTest) {
                    // Usa CNPJ de teste com dígitos verificadores válidos
                    $testCnpj = preg_replace('/\D+/','', (string) (\App\Models\Setting::get('nfce.test_emitter.cnpj','99999999000191')));
                    $testIe   = (string) (\App\Models\Setting::get('nfce.test_emitter.ie','ISENTO'));
                    $testRS   = (string) (\App\Models\Setting::get('nfce.test_emitter.razao','Emitente Teste'));
                    $testNF   = (string) (\App\Models\Setting::get('nfce.test_emitter.fantasia','Teste NFCE'));
                    $testEnd  = (string) (\App\Models\Setting::get('nfce.test_emitter.endereco','Rua Teste'));
                    $testNum  = (string) (\App\Models\Setting::get('nfce.test_emitter.numero','100'));
                    $testBai  = (string) (\App\Models\Setting::get('nfce.test_emitter.bairro','Centro'));
                    $testCid  = (string) (\App\Models\Setting::get('nfce.test_emitter.cidade','São Paulo'));
                    $testUF   = (string) (\App\Models\Setting::get('nfce.test_emitter.uf','SP'));
                    $testIBGE = (int) (\App\Models\Setting::get('nfce.test_emitter.ibge','3550308'));
                    $testCEP  = (string) (\App\Models\Setting::get('nfce.test_emitter.cep','01001000'));

                    $payload['emitente'] = [
                        'cnpj' => $testCnpj,
                        'ie' => $testIe,
                        'razao_social' => $testRS,
                        'nome_fantasia' => $testNF,
                        'endereco' => $testEnd,
                        'numero' => $testNum,
                        'complemento' => '',
                        'bairro' => $testBai,
                        'codigo_municipio' => $testIBGE,
                        'cidade' => $testCid,
                        'uf' => $testUF,
                        'cep' => $testCEP,
                    ];
                    // Força UF/cMun na config para a SEFAZ correta
                    $payload['configuracoes']['uf'] = $testUF;
                    $payload['configuracoes']['cMunFG'] = $testIBGE;
                }
            } catch (\Throwable $e) {}

        // Totais e pagamentos: garantir pelo menos 1 pagamento coerente com vNF
        if (empty($payload['pagamentos'])) {
            $vNF = (float) ($payload['totais']['vNF'] ?? 0);
            if ($vNF <= 0) {
                // fallback seguro ao total calculado do pedido
                $sumLines = (float) $order->items()->sum('line_total');
                $sumItemDisc = (float) $order->items()->sum('discount_value');
                $netItems = max(0.0, $sumLines - $sumItemDisc);
                $vNF = max(0.0,
                    $netItems
                    - (float)($order->discount_total ?? 0)
                    + (float)($order->addition_total ?? 0)
                    + (float)($order->freight_cost ?? 0)
                    + (float)($order->valor_seguro ?? 0)
                    + (float)($order->outras_despesas ?? 0)
                );
            }
            if ($vNF > 0) {
                $payload['pagamentos'] = [[ 'tPag' => '01', 'valor' => round($vNF, 2) ]];
            }
        }

            // Registrar rascunho/entrada em nfe_notes com modelo 65
            try {
                $note = \App\Models\NfeNote::where('tenant_id', $tenantId)
                    ->where('numero_pedido', (string) $order->number)
                    ->whereIn('status', ['pending','error'])
                    ->orderByDesc('id')
                    ->first();
                if (!$note) {
                    $create = [
                        'tenant_id' => $tenantId,
                        'client_id' => $order->client_id,
                        'numero_pedido' => $order->number,
                        'status' => 'pending',
                        'modelo' => 65,
                    ];
                    if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','order_id')) {
                        $create['order_id'] = $order->id;
                    }
                    $note = \App\Models\NfeNote::create($create);
                }
                $note->payload_sent = $payload;
                $note->save();
            } catch (\Throwable $e) { \Log::warning('NFCE note draft persist failed', ['error'=>$e->getMessage()]); }

            // Emitir NFC-e
            $resultado = $nfeService->emitirNFCE($payload);
            if ($resultado['success']) {
                // Atualiza pedido e grava nota
                $order->nfe_issued_at = now();
                $order->save();

                try {
                    $data = $resultado['data'] ?? [];
                    $numero = $data['numero'] ?? ($data['nNF'] ?? null);
                    $chave = $data['chave_acesso'] ?? ($data['chNFe'] ?? ($data['chave'] ?? null));
                    $prot = $data['protocolo'] ?? ($data['nProt'] ?? null);
                    if (isset($note) && $note) {
                        $note->numero_nfe = $numero;
                        $note->chave_acesso = $chave;
                        $note->protocolo = $prot;
                        $note->status = 'transmitida';
                        $note->modelo = 65;
                        $note->xml_path = (string) ($data['xml_path'] ?? '');
                        $note->pdf_path = (string) ($data['pdf_path'] ?? '');
                        $note->data_emissao = now();
                        $note->data_transmissao = now();
                        $note->response_received = $data;
                        $note->save();
                    } else {
                        $create = [
                            'tenant_id' => $tenantId,
                            'client_id' => $order->client_id,
                            'numero_pedido' => $order->number,
                            'numero_nfe' => $numero,
                            'chave_acesso' => $chave,
                            'protocolo' => $prot,
                            'status' => 'transmitida',
                            'modelo' => 65,
                            'xml_path' => (string) ($data['xml_path'] ?? ''),
                            'pdf_path' => (string) ($data['pdf_path'] ?? ''),
                            'data_emissao' => now(),
                            'data_transmissao' => now(),
                            'response_received' => $data,
                        ];
                        if (\Illuminate\Support\Facades\Schema::hasColumn('nfe_notes','order_id')) {
                            $create['order_id'] = $order->id;
                        }
                        \App\Models\NfeNote::create($create);
                    }
                } catch (\Throwable $e) {}

                // Commit dos ponteiros de numeração da NFC-e somente após sucesso
                try {
                    $serie = (int) ($payload['configuracoes']['serie'] ?? 1);
                    $usedNum = (int) ($data['numero'] ?? $nextNumber);
                    if ($usedNum && $serie) {
                        $key = 'nfce.next_number.series.' . (string) $serie;
                        $ptr = (int) ((string) \App\Models\Setting::get($key, '0'));
                        $target = $usedNum + 1;
                        if ($target > $ptr) { \App\Models\Setting::set($key, (string) $target); }
                    }
                } catch (\Throwable $e) { \Log::warning('Falha ao commitar numeração NFC-e', ['error'=>$e->getMessage()]); }

                return back()->with('success', 'NFC-e emitida com sucesso!');
            }

            return back()->with('error', 'Não foi possível emitir a nota. Revise os dados e tente novamente.');

        } catch (\Throwable $e) {
            \Log::error('Erro ao emitir NFC-e do pedido', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Ocorreu um erro ao emitir a nota. Tente novamente.');
        }
    }

    private function hasPaymentDefinition(Order $order): bool
    {
        // Consideramos "definido" quando existem títulos vinculados ao pedido
        // (por padrão, descrição começa com "Pedido {number}")
        return Receivable::where('tenant_id', auth()->user()->tenant_id)
            ->where('client_id', $order->client_id)
            ->where('description', 'like', 'Pedido '.$order->number.'%')
            ->exists();
    }

    /**
     * Exibe a auditoria completa do pedido
     */
    public function audit(Order $order)
    {
        abort_unless(auth()->user()->is_admin || auth()->user()->hasPermission('orders.audit'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);
        
        $audits = \App\Models\OrderAudit::where('order_id', $order->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('orders.audit', compact('order', 'audits'));
    }

    public function pdf(Order $order)
    {
        abort_unless(auth()->user()->hasPermission('orders.view'), 403);
        abort_unless($order->tenant_id === auth()->user()->tenant_id, 403);

        // Carregar relacionamentos necessários
        $order->load(['client', 'items.product', 'carrier', 'receivables', 'tenant']);

        // Criar HTML para PDF
        $html = view('orders.pdf', compact('order'))->render();

        // Configurar PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retornar PDF como download
        return $dompdf->stream("pedido_{$order->number}.pdf");
    }
}


