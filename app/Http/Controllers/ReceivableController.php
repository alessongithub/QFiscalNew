<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\Client;
use App\Models\GatewayConfig;
use App\Models\SmtpConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Carbon\Carbon;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $query = Receivable::where('tenant_id', $tenantId);

        // Filtros
        $status = $request->input('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $overdue = $request->boolean('overdue');
        if ($status) {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }
        if ($dateFrom) {
            $query->whereDate('due_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('due_date', '<=', $dateTo);
        }
        if ($overdue) {
            $query->whereIn('status', ['open','partial'])
                  ->whereDate('due_date', '<', now()->toDateString());
        }
        if ($request->boolean('has_boleto')) {
            $query->whereNotNull('boleto_mp_id');
        }

        // Somatórios (com os mesmos filtros aplicados)
        $base = Receivable::where('tenant_id', $tenantId);
        if ($status) {
            if (is_array($status)) {
                $base->whereIn('status', $status);
            } else {
                $base->where('status', $status);
            }
        }
        if ($dateFrom) { $base->whereDate('due_date', '>=', $dateFrom); }
        if ($dateTo) { $base->whereDate('due_date', '<=', $dateTo); }
        if ($overdue) {
            $base->whereIn('status', ['open','partial'])
                 ->whereDate('due_date', '<', now()->toDateString());
        }
        if ($request->boolean('has_boleto')) {
            $base->whereNotNull('boleto_mp_id');
        }

        $totalOpen = (clone $base)->whereIn('status', ['open','partial'])->sum('amount');
        $totalPaid = (clone $base)->where('status', 'paid')->sum('amount');
        $totalOverdue = (clone $base)->whereIn('status', ['open','partial'])->whereDate('due_date', '<', now()->toDateString())->sum('amount');

        $sort = $request->get('sort', 'due_date');
        $direction = $request->get('direction', 'desc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'desc'; }
        $query->orderBy($sort, $direction);

        $perPage = (int) $request->get('per_page', 12);
        if ($perPage < 5) { $perPage = 5; }
        if ($perPage > 200) { $perPage = 200; }

        $receivables = $query->paginate($perPage)->appends($request->query());
        return view('receivables.index', compact('receivables', 'totalOpen', 'totalPaid', 'totalOverdue', 'status', 'dateFrom', 'dateTo', 'sort', 'direction', 'overdue'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('receivables.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('receivables.create', compact('clients'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.create'), 403);
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'tpag_override' => 'nullable|string|max:4',
            'tpag_hint' => 'nullable|string|max:30',
        ]);

        // Garantir escopo do tenant em client_id
        if (!empty($validated['client_id'])) {
            $client = Client::findOrFail($validated['client_id']);
            abort_unless($client->tenant_id === $tenantId, 403);
        }

        Receivable::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        // Audit: created
        try {
            $created = Receivable::where('tenant_id', $tenantId)->latest('id')->first();
            if ($created) {
                \App\Models\FinanceAudit::create([
                    'tenant_id' => $tenantId,
                    'user_id' => auth()->id(),
                    'entity_type' => 'receivable',
                    'entity_id' => $created->id,
                    'action' => 'created',
                    'notes' => 'Recebível lançado: ' . ($created->description ?? ''),
                    'changes' => $created->toArray(),
                ]);
            }
        } catch (\Throwable $e) { /* ignore audit errors */ }

        return redirect()->route('receivables.index')->with('success', 'Recebível lançado com sucesso.');
    }

    public function edit(Receivable $receivable)
    {
        abort_unless(auth()->user()->hasPermission('receivables.edit'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear edição de recebimentos de pedidos
        $isFromOrder = !empty($receivable->order_id) || 
                       str_contains($receivable->description, 'Pedido') || 
                       str_contains($receivable->description, 'PDV') ||
                       str_contains($receivable->description, 'pagamento à vista');
        
        if ($isFromOrder) {
            return back()->with('error', 'Recebimentos de pedidos devem ser editados através do pedido correspondente.');
        }
        
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();
        return view('receivables.edit', compact('receivable', 'clients'));
    }

    public function show(Receivable $receivable)
    {
        abort_unless(auth()->user()->hasPermission('receivables.view'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Carregar relacionamentos para auditoria
        $receivable->load(['client', 'order', 'createdBy', 'updatedBy', 'receivedBy', 'reversedBy', 'canceledBy']);
        
        return view('receivables.show', compact('receivable'));
    }

    public function update(Request $request, Receivable $receivable)
    {
        abort_unless(auth()->user()->hasPermission('receivables.edit'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear edição de recebimentos de pedidos
        $isFromOrder = !empty($receivable->order_id) || 
                       str_contains($receivable->description, 'Pedido') || 
                       str_contains($receivable->description, 'PDV') ||
                       str_contains($receivable->description, 'pagamento à vista');
        
        if ($isFromOrder) {
            return back()->with('error', 'Recebimentos de pedidos devem ser editados através do pedido correspondente.');
        }

        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'status' => 'required|in:open,partial,paid,canceled',
            'payment_method' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'tpag_override' => 'nullable|string|max:4',
            'tpag_hint' => 'nullable|string|max:30',
        ]);

        if (!empty($validated['client_id'])) {
            $client = Client::findOrFail($validated['client_id']);
            abort_unless($client->tenant_id === auth()->user()->tenant_id, 403);
        }

        // Se status = paid e não há received_at, define agora
        if ($validated['status'] === 'paid' && !$receivable->received_at) {
            $receivable->received_at = now();
        }

        $original = $receivable->getOriginal();
        $receivable->update(array_merge($validated, ['updated_by' => auth()->id()]));

        // Audit: updated
        try {
            $fresh = $receivable->fresh();
            $newValues = $fresh->toArray();
            $diff = [];
            $label = function(string $key, $val) use ($fresh) {
                if ($val === null || $val === '') return '';
                switch ($key) {
                    case 'due_date':
                    case 'received_at':
                        try { return \Carbon\Carbon::parse($val)->format('d/m/Y'); } catch (\Throwable $e) { return (string)$val; }
                    case 'amount':
                        return 'R$ ' . number_format((float)$val, 2, ',', '.');
                    case 'status':
                        $map = ['open' => 'Em aberto', 'partial' => 'Parcial', 'paid' => 'Pago', 'canceled' => 'Cancelado', 'reversed' => 'Estornado'];
                        return $map[$val] ?? (string)$val;
                    case 'payment_method':
                        $map = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'pix' => 'Pix'];
                        return $map[$val] ?? (string)$val;
                    case 'client_id':
                        $client = $fresh->client ?: (\App\Models\Client::find($val));
                        return $client?->name ?? ('Cliente #' . $val);
                    default:
                        return (string)$val;
                }
            };
            foreach (array_keys($validated) as $k) {
                $oldRaw = $original[$k] ?? null;
                $newRaw = $newValues[$k] ?? null;
                $oldNorm = $label($k, $oldRaw);
                $newNorm = $label($k, $newRaw);
                if ($oldNorm !== $newNorm) {
                    $diff[$this->humanField($k)] = ['old' => $oldNorm, 'new' => $newNorm];
                }
            }
            if (!empty($diff)) {
                \App\Models\FinanceAudit::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'user_id' => auth()->id(),
                    'entity_type' => 'receivable',
                    'entity_id' => $receivable->id,
                    'action' => 'updated',
                    'notes' => 'Recebível atualizado',
                    'changes' => $diff,
                ]);
            }
        } catch (\Throwable $e) { /* ignore */ }

        return redirect()->route('receivables.index')->with('success', 'Recebível atualizado.');
    }

    public function cancel(Receivable $receivable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.delete'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear cancelamento de recebimentos pagos
        if ($receivable->status === 'paid') {
            return back()->with('error', 'Recebimentos já pagos não podem ser cancelados. Use a função de estorno se necessário.');
        }
        
        // Bloquear cancelamento de recebimentos já cancelados
        if ($receivable->status === 'canceled') {
            return back()->with('error', 'Este recebimento já está cancelado.');
        }
        
        // Bloquear cancelamento de recebimentos de pedidos
        $isFromOrder = !empty($receivable->order_id) || 
                       str_contains($receivable->description, 'Pedido') || 
                       str_contains($receivable->description, 'PDV') ||
                       str_contains($receivable->description, 'pagamento à vista');
        
        if ($isFromOrder) {
            return back()->with('error', 'Recebimentos de pedidos devem ser cancelados através do pedido correspondente.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|min:10|max:500',
        ]);
        
        $prevStatus = $receivable->status;
        $receivable->update([
            'status' => 'canceled',
            'received_at' => null,
            'updated_by' => auth()->id(),
            'cancel_reason' => $validated['cancel_reason'],
            'canceled_at' => now(),
            'canceled_by' => auth()->id(),
        ]);
        
        // Audit: canceled
        try {
            \App\Models\FinanceAudit::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'entity_type' => 'receivable',
                'entity_id' => $receivable->id,
                'action' => 'canceled',
                'notes' => 'Motivo: ' . ($validated['cancel_reason'] ?? ''),
                'changes' => ['status' => ['old' => ($prevStatus === 'open' ? 'Em aberto' : $prevStatus), 'new' => 'Cancelado']],
            ]);
        } catch (\Throwable $e) { }

        return redirect()->route('receivables.index')->with('success', 'Recebimento cancelado com sucesso.');
    }

    public function destroy(Receivable $receivable)
    {
        // Método removido - usar cancel() em vez de destroy()
        return redirect()->route('receivables.index')->with('error', 'Use a função de cancelamento em vez de exclusão.');
    }

    public function receive(Receivable $receivable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.receive'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);

        $data = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'received_at' => 'nullable|date',
        ]);

        $oldStatus = $receivable->status;
        $receivable->status = 'paid';
        $receivable->payment_method = $data['payment_method'] ?? $receivable->payment_method;
        $receivable->received_at = isset($data['received_at']) ? $data['received_at'] : now();
        $receivable->received_by = auth()->id();
        $receivable->updated_by = auth()->id();
        $receivable->save();

        // Audit: paid
        try {
            \App\Models\FinanceAudit::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'entity_type' => 'receivable',
                'entity_id' => $receivable->id,
                'action' => 'paid',
                'notes' => 'Baixa manual',
                'changes' => [ 'status' => ['old' => $oldStatus, 'new' => 'Pago'], 'payment_method' => ($receivable->payment_method ? (['cash'=>'Dinheiro','card'=>'Cartão','pix'=>'Pix'][$receivable->payment_method] ?? $receivable->payment_method) : null) ],
            ]);
        } catch (\Throwable $e) { }

        return back()->with('success', 'Recebível baixado como pago.');
    }

    public function reverse(Receivable $receivable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.create'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear estorno de recebimentos não pagos
        if ($receivable->status !== 'paid') {
            return back()->with('error', 'Apenas recebimentos já realizados podem ser estornados.');
        }
        
        // Bloquear estorno de recebimentos de pedidos (devem ser estornados no pedido)
        $isFromOrder = !empty($receivable->order_id) || 
                       str_contains($receivable->description, 'Pedido') || 
                       str_contains($receivable->description, 'PDV') ||
                       str_contains($receivable->description, 'pagamento à vista');
        
        if ($isFromOrder) {
            return back()->with('error', 'Recebimentos de pedidos devem ser estornados através do pedido correspondente.');
        }

        $validated = $request->validate([
            'reverse_reason' => 'required|string|min:10|max:500',
        ]);

        // Registrar estorno no receivable original
        $receivable->status = 'reversed';
        $receivable->reversed_by = auth()->id();
        $receivable->reversed_at = now();
        $receivable->reverse_reason = $validated['reverse_reason'];
        $receivable->updated_by = auth()->id();
        $receivable->save();

        // Criar estorno como novo payable (entrada negativa no caixa)
        \App\Models\Payable::create([
            'tenant_id' => $receivable->tenant_id,
            'supplier_id' => null,
            'supplier_name' => 'Estorno Financeiro',
            'description' => '🔄 Estorno Manual - ' . ($receivable->client->name ?? 'Cliente') . ' (ID: ' . $receivable->id . '): ' . $validated['reverse_reason'],
            'amount' => -(float)$receivable->amount,
            'due_date' => now()->toDateString(),
            'payment_method' => $receivable->payment_method,
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => auth()->id(),
        ]);
        // Audit: reversed
        try {
            \App\Models\FinanceAudit::create([
                'tenant_id' => auth()->user()->tenant_id,
                'user_id' => auth()->id(),
                'entity_type' => 'receivable',
                'entity_id' => $receivable->id,
                'action' => 'reversed',
                'notes' => 'Motivo: ' . ($validated['reverse_reason'] ?? ''),
                'changes' => ['status' => ['old' => 'paid', 'new' => 'reversed']],
            ]);
        } catch (\Throwable $e) { }

        return redirect()->route('receivables.index')->with('success', 'Estorno criado com sucesso. O recebimento original foi preservado para auditoria.');
    }

    public function emitBoleto(Receivable $receivable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.receive'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);

        $data = $request->validate([
            'due_date' => 'required|date',
            'fine_percent' => 'nullable|numeric|min:0|max:2',
            'interest_month_percent' => 'nullable|numeric|min:0|max:1',
            'send_email' => 'nullable|boolean',
        ]);

        \Log::info('Emitindo boleto', [
            'receivable_id' => $receivable->id,
            'client_id' => $receivable->client_id,
            'client_email' => $receivable->client->email ?? 'N/A',
            'amount' => $receivable->amount,
            'due_date' => $data['due_date']
        ]);

        $config = GatewayConfig::current();
        $accessToken = $config->active_access_token;
        if (empty($accessToken)) {
            \Log::error('Access Token do Mercado Pago não configurado');
            return back()->withErrors(['boleto' => 'Access Token do Mercado Pago não configurado.']);
        }

        if (!$receivable->client || !$receivable->client->email) {
            \Log::error('Cliente sem e-mail', ['receivable_id' => $receivable->id, 'client_id' => $receivable->client_id]);
            return back()->withErrors(['boleto' => 'Cliente sem e-mail. Cadastre o e-mail do cliente para enviar o boleto.']);
        }

        // Validar dados mínimos do cliente para boleto
        $client = $receivable->client;
        $missingFields = [];
        if (empty($client->name)) $missingFields[] = 'Nome';
        if (empty($client->cpf_cnpj)) $missingFields[] = 'CPF/CNPJ';
        if (empty($client->address)) $missingFields[] = 'Endereço';
        if (empty($client->city)) $missingFields[] = 'Cidade';
        if (empty($client->state)) $missingFields[] = 'Estado';
        
        if (!empty($missingFields)) {
            \Log::error('Cliente com dados incompletos para boleto', [
                'receivable_id' => $receivable->id,
                'missing_fields' => $missingFields
            ]);
            return back()->withErrors(['boleto' => 'Cliente precisa ter dados completos para emitir boleto. Campos faltando: ' . implode(', ', $missingFields)]);
        }

        // Para boleto, precisa de campos adicionais obrigatórios
        $clientName = $receivable->client->name ?: 'Cliente';
        $nameParts = explode(' ', trim($clientName), 2);
        $firstName = $nameParts[0] ?? 'Cliente';
        $lastName = $nameParts[1] ?? 'Silva';
        
        $payload = [
            'transaction_amount' => (float) $receivable->amount,
            'description' => (string) ($receivable->description ?: 'Cobrança'),
            'payment_method_id' => 'bolbradesco',
            'payer' => [
                'email' => (string) $receivable->client->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'identification' => [
                    'type' => 'CPF',
                    'number' => preg_replace('/\D/', '', $receivable->client->cpf_cnpj ?? '00000000000')
                ],
                'address' => [
                    'zip_code' => preg_replace('/\D/', '', $receivable->client->zip_code ?? '01310100'),
                    'street_name' => (string) ($receivable->client->address ?: 'Rua Teste'),
                    'street_number' => (string) ($receivable->client->number ?: '123'),
                    'neighborhood' => (string) ($receivable->client->neighborhood ?: 'Centro'),
                    'city' => (string) ($receivable->client->city ?: 'São Paulo'),
                    'federal_unit' => (string) ($receivable->client->state ?: 'SP')
                ]
            ],
            'external_reference' => 'rec_' . $receivable->id,
            // Data/hora ISO8601. Mercado Pago aceita até 30 dias
            'date_of_expiration' => Carbon::parse($data['due_date'])->endOfDay()->format('Y-m-d\TH:i:s.000-03:00'),
        ];

        // Multa/Juros: Mercado Pago (boleto) não aceita configurar na criação — ignorar no payload

        \Log::info('Mercado Pago Request Payload', [
            'url' => 'https://api.mercadopago.com/v1/payments',
            'payload' => $payload,
            'access_token' => substr($accessToken, 0, 10) . '...'
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => 'rec_' . $receivable->id . '_' . time(),
        ])->post('https://api.mercadopago.com/v1/payments', $payload);

        \Log::info('Mercado Pago Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
            'headers' => $response->headers()
        ]);

        if (!$response->successful()) {
            \Log::error('Falha na resposta do Mercado Pago', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return back()->withErrors(['boleto' => 'Falha ao emitir boleto: ' . $response->body()])->withInput();
        }

        $json = $response->json();
        
        \Log::info('Processando resposta do MP', [
            'json_keys' => array_keys($json),
            'id' => $json['id'] ?? 'N/A',
            'status' => $json['status'] ?? 'N/A',
            'transaction_details' => $json['transaction_details'] ?? 'N/A'
        ]);

        $receivable->boleto_mp_id = (string) ($json['id'] ?? null);
        $receivable->boleto_url = (string) ($json['transaction_details']['external_resource_url'] ?? null);
        $receivable->boleto_pdf_url = (string) ($json['transaction_details']['external_resource_url'] ?? null);
        $receivable->boleto_barcode = (string) ($json['barcode']['content'] ?? ($json['transaction_details']['barcode']['content'] ?? ''));

        // Polling leve: se o link ainda não estiver disponível, tentar consultar o pagamento algumas vezes
        if (empty($receivable->boleto_url) && !empty($receivable->boleto_mp_id)) {
            try {
                $maxTries = 4; // ~3s total
                for ($i = 0; $i < $maxTries; $i++) {
                    $poll = \Illuminate\Support\Facades\Http::withToken($accessToken)
                        ->get('https://api.mercadopago.com/v1/payments/' . $receivable->boleto_mp_id);
                    if ($poll->successful()) {
                        $pj = $poll->json();
                        $link = (string) ($pj['transaction_details']['external_resource_url'] ?? '');
                        $barcode = (string) ($pj['barcode']['content'] ?? ($pj['transaction_details']['barcode']['content'] ?? ''));
                        if (!empty($link)) {
                            $receivable->boleto_url = $link;
                            $receivable->boleto_pdf_url = $link;
                            if (!empty($barcode)) { $receivable->boleto_barcode = $barcode; }
                            break;
                        }
                    }
                    usleep(750000); // 0,75s
                }
            } catch (\Throwable $e) {
                \Log::warning('Polling boleto link failed', ['error' => $e->getMessage()]);
            }
        }
        $receivable->boleto_emitted_at = now();
        // Ajusta vencimento se alterado no modal
        $receivable->due_date = $data['due_date'];
        
        \Log::info('Salvando dados do boleto', [
            'receivable_id' => $receivable->id,
            'boleto_mp_id' => $receivable->boleto_mp_id,
            'boleto_url' => $receivable->boleto_url,
            'boleto_barcode' => $receivable->boleto_barcode
        ]);
        
        $receivable->save();

        // E-mail com link do boleto
        $shouldSend = (bool) ($data['send_email'] ?? true);
        if ($shouldSend && $receivable->client->email) {
            $link = $receivable->boleto_url ?: '';
            $subject = 'Seu boleto - ' . (string) ($receivable->description ?: 'Cobrança');
            $body = view('receivables.emails._boleto', [ 'receivable' => $receivable, 'link' => $link ])->render();

            $active = SmtpConfig::where('is_active', true)->first();
            $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
            $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
            $username = (string) ($active->username ?? env('MAIL_USERNAME'));
            $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
            $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
            $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
            $tenantCtx = $receivable->tenant ?? (auth()->user()->tenant ?? null);
            $fromName = (string) (($tenantCtx?->fantasy_name) ?: ($tenantCtx?->name) ?: ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name'))));

            try {
                $mailer = new PHPMailer(true);
                \App\Http\Controllers\Admin\EmailTestController::configureMailer(
                    $mailer,
                    $host,
                    $port,
                    $username,
                    $password,
                    $encryption,
                    $fromAddress,
                    $fromName
                );
                // Forçar nome do remetente e Reply-To do tenant
                $tenantName = (string) (($tenantCtx?->fantasy_name) ?: ($tenantCtx?->name) ?: ($fromName));
                if (!empty($fromAddress)) {
                    $mailer->setFrom($fromAddress, $tenantName);
                } elseif (!empty($username)) {
                    $mailer->setFrom($username, $tenantName);
                }
                if (!empty($tenantCtx?->email)) {
                    $mailer->addReplyTo($tenantCtx->email, $tenantName);
                }
                $mailer->addAddress($receivable->client->email, $receivable->client->name ?: 'Cliente');
                $mailer->isHTML(true);
                $mailer->Subject = $subject;
                $mailer->Body = $body;
                $mailer->AltBody = strip_tags($body . "\n" . $link);
                $mailer->send();
                
                \Log::info('E-mail enviado com sucesso', ['to' => $receivable->client->email]);
            } catch (PHPMailerException $e) {
                \Log::warning('Falha ao enviar e-mail', ['error' => $e->getMessage()]);
                // não bloqueia fluxo
            }
        }

        return back()->with('success', 'Boleto emitido com sucesso.');
    }

    public function receiveBulk(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receivables.receive'), 403);
        $tenantId = auth()->user()->tenant_id;
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'received_at' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'fee_amount' => 'nullable|numeric|min:0',
            'fee_description' => 'nullable|string|max:255',
        ]);

        $when = $data['received_at'] ?? now();
        $method = $data['payment_method'] ?? null;
        $ids = $data['ids'];

        $count = 0; $sum = 0.0;
        foreach ($ids as $id) {
            $r = Receivable::where('tenant_id', $tenantId)->whereIn('status',[ 'open','partial' ])->find($id);
            if (!$r) { continue; }
            $r->status = 'paid';
            if ($method) { $r->payment_method = $method; }
            $r->received_at = $when;
            $r->received_by = auth()->id();
            $r->updated_by = auth()->id();
            $r->save();
            $count++;
            $sum += (float)$r->amount;
        }

        // Se informado, lança a taxa como despesa (Payable pago) para impactar o caixa do dia
        if (($data['fee_amount'] ?? 0) > 0) {
            \App\Models\Payable::create([
                'tenant_id' => $tenantId,
                'supplier_name' => $data['fee_description'] ?: 'Taxa de antecipação',
                'description' => $data['fee_description'] ?: 'Taxa de antecipação de recebíveis',
                'amount' => (float)$data['fee_amount'],
                'due_date' => \Carbon\Carbon::parse($when)->toDateString(),
                'status' => 'paid',
                'paid_at' => $when,
                'payment_method' => $method ?? 'card',
            ]);
        }

        // Audit: bulk_paid
        try {
            \App\Models\FinanceAudit::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'entity_type' => 'receivable',
                'entity_id' => 0,
                'action' => 'bulk_paid',
                'notes' => "Baixa em lote de {$count} títulos. Total R$ " . number_format($sum,2,',','.'),
                'changes' => ['ids' => $ids],
            ]);
        } catch (\Throwable $e) { }

        return back()->with('success', "Baixa em lote concluída: {$count} títulos, total R$ " . number_format($sum,2,',','.'));
    }

    public function sendBoletoEmail(Receivable $receivable)
    {
        abort_unless(auth()->user()->hasPermission('receivables.receive'), 403);
        abort_unless($receivable->tenant_id === auth()->user()->tenant_id, 403);

        if (!$receivable->client || empty($receivable->client->email)) {
            return back()->withErrors(['email' => 'Cliente sem e-mail cadastrado.']);
        }

        $link = $receivable->boleto_pdf_url ?: $receivable->boleto_url;
        if (empty($link)) {
            return back()->withErrors(['email' => 'Não há boleto emitido para este recebível.']);
        }

        $subject = 'Seu boleto - ' . (string) ($receivable->description ?: 'Cobrança');
        $body = view('receivables.emails._boleto', [ 'receivable' => $receivable, 'link' => $link ])->render();

        $active = SmtpConfig::where('is_active', true)->first();
        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $tenantCtx = auth()->user()->tenant ?? null;
        $fromName = (string) (($tenantCtx?->fantasy_name) ?: ($tenantCtx?->name) ?: ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name'))));
        $active = SmtpConfig::where('is_active', true)->first();
        $host = (string) ($active->host ?? env('MAIL_HOST', '127.0.0.1'));
        $port = (int) ($active->port ?? (int) env('MAIL_PORT', 2525));
        $username = (string) ($active->username ?? env('MAIL_USERNAME'));
        $password = (string) ($active->password ?? env('MAIL_PASSWORD'));
        $encryption = strtolower((string) ($active->encryption ?? (env('MAIL_ENCRYPTION') ?: 'tls')));
        $fromAddress = (string) ($active->from_address ?? env('MAIL_FROM_ADDRESS'));
        $tenantCtx = auth()->user()->tenant ?? null;
        $fromName = (string) (($tenantCtx?->fantasy_name) ?: ($tenantCtx?->name) ?: ($active->from_name ?? (env('MAIL_FROM_NAME') ?: config('app.name'))));

        try {
            $mailer = new PHPMailer(true);
            \App\Http\Controllers\Admin\EmailTestController::configureMailer(
                $mailer,
                $host,
                $port,
                $username,
                $password,
                $encryption,
                $fromAddress,
                $fromName
            );
            // Forçar nome do remetente e Reply-To do tenant
            $tenantName = (string) (($tenantCtx?->fantasy_name) ?: ($tenantCtx?->name) ?: ($fromName));
            if (!empty($fromAddress)) {
                $mailer->setFrom($fromAddress, $tenantName);
            } elseif (!empty($username)) {
                $mailer->setFrom($username, $tenantName);
            }
            if (!empty($tenantCtx?->email)) {
                $mailer->addReplyTo($tenantCtx->email, $tenantName);
            }
            $mailer->addAddress($receivable->client->email, $receivable->client->name ?: 'Cliente');
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body . "\n" . $link);
            $mailer->send();
        } catch (PHPMailerException $e) {
            \Log::warning('Falha ao enviar boleto por e-mail', ['error' => $e->getMessage()]);
            return back()->withErrors(['email' => 'Falha ao enviar e-mail.']);
        }

        return back()->with('success', 'Boleto enviado por e-mail ao cliente.');
    }

    private function humanField(string $key): string
    {
        $map = [
            'client_id' => 'Cliente',
            'description' => 'Descrição',
            'amount' => 'Valor',
            'due_date' => 'Vencimento',
            'status' => 'Status',
            'payment_method' => 'Forma de pagamento',
            'document_number' => 'Documento',
            'tpag_override' => 'TPag (override)',
            'tpag_hint' => 'Sugestão de pagamento',
            'received_at' => 'Recebido em',
        ];
        return $map[$key] ?? ucfirst(str_replace('_',' ',$key));
    }
}


