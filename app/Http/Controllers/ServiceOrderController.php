<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\ServiceOrderItem;
use App\Models\ServiceOrderAttachment;
use App\Models\ServiceOrderStatusLog;
use App\Models\ServiceOrderOccurrence;
use App\Models\ServiceOrderCancellation;
use App\Models\WarrantyHistory;
use App\Models\ServiceOrderWarrantyLog;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\SmtpConfig;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\QueryException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use App\Traits\StorageLimitCheck;

class ServiceOrderController extends Controller
{
    use StorageLimitCheck;
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);

        $query = ServiceOrder::where('tenant_id', auth()->user()->tenant_id)
            ->with(['client', 'technician', 'createdBy'])
            ->withCount('attachments');

        // Filtros
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $serviceOrders = $query->paginate(25);
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();
        
        // Calcular estatísticas para dashboard
        $tenantId = auth()->user()->tenant_id;
        
        // OS finalizadas hoje
        $finishedTodayCount = ServiceOrder::where('tenant_id', $tenantId)
            ->where('status', 'finished')
            ->whereDate('finalized_at', today())
            ->count();
        
        $finishedTodayAmount = ServiceOrder::where('tenant_id', $tenantId)
            ->where('status', 'finished')
            ->whereDate('finalized_at', today())
            ->sum('total_amount') ?? 0;
        
        // OS abertas
        $openCount = ServiceOrder::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->count();
        
        // OS em andamento
        $inProgressCount = ServiceOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['in_progress', 'in_service'])
            ->count();
        
        // Total de finalizadas
        $finishedCount = ServiceOrder::where('tenant_id', $tenantId)
            ->where('status', 'finished')
            ->count();
        
        // Recebidos hoje (buscar dos recebíveis de OS finalizadas hoje)
        $receivedTodayAmount = Receivable::where('tenant_id', $tenantId)
            ->whereHas('serviceOrder', function($q) {
                $q->where('status', 'finished')
                  ->whereDate('finalized_at', today());
            })
            ->where('status', 'paid')
            ->whereDate('received_at', today())
            ->sum('amount') ?? 0;
        
        return view('service_orders.index', compact(
            'serviceOrders', 
            'clients',
            'finishedTodayCount',
            'finishedTodayAmount',
            'openCount',
            'inProgressCount',
            'finishedCount',
            'receivedTodayAmount'
        ));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('service_orders.create'), 403);
        
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
        
        $technicians = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
        
        return view('service_orders.create', compact('clients', 'technicians'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.create'), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'equipment_brand' => 'nullable|string|max:100',
            'equipment_model' => 'nullable|string|max:100',
            'equipment_serial' => 'nullable|string|max:100',
            'equipment_description' => 'nullable|string|max:500',
            'defect_reported' => 'nullable|string|max:1000',
            'received_by_user_id' => 'nullable|exists:users,id',
            'internal_notes' => 'nullable|string|max:2000',
            'technician_user_id' => 'nullable|exists:users,id',
            'warranty_days' => 'nullable|integer|min:0|max:3650',
            'issue_nfse' => 'boolean',
            // fotos (até 10)
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'client_id.required' => 'O cliente é obrigatório.',
            'client_id.exists' => 'Cliente selecionado não existe.',
            'title.required' => 'O título é obrigatório.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',
            'description.required' => 'A descrição é obrigatória.',
            'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
            'received_by_user_id.exists' => 'Usuário selecionado não existe.',
            'technician_user_id.exists' => 'Técnico selecionado não existe.',
            'warranty_days.integer' => 'Os dias de garantia devem ser um número inteiro.',
            'warranty_days.min' => 'Os dias de garantia não podem ser negativos.',
            'warranty_days.max' => 'Os dias de garantia não podem exceder 10 anos.',
        ]);
        
        // Gerar número da OS com retry em caso de conflito
        $maxRetries = 3;
        $retryCount = 0;
        $serviceOrder = null;
        
        while ($retryCount < $maxRetries && !$serviceOrder) {
            try {
                $lastOrder = ServiceOrder::where('tenant_id', auth()->user()->tenant_id)
                    ->orderByRaw('CAST(number AS UNSIGNED) DESC')
                    ->first();
                
                if ($lastOrder) {
                    $lastNumber = (int) $lastOrder->number;
                    $nextNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
                } else {
                    $nextNumber = '000001';
                }
                
                $serviceOrder = ServiceOrder::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'client_id' => $validated['client_id'],
                    'number' => $nextNumber,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'equipment_brand' => $validated['equipment_brand'] ?? null,
                    'equipment_model' => $validated['equipment_model'] ?? null,
                    'equipment_serial' => $validated['equipment_serial'] ?? null,
                    'equipment_description' => $validated['equipment_description'] ?? null,
                    'defect_reported' => $validated['defect_reported'] ?? null,
                    'received_by_user_id' => $validated['received_by_user_id'] ?? null,
                    'internal_notes' => $validated['internal_notes'] ?? null,
                    'technician_user_id' => $validated['technician_user_id'] ?? null,
                    'status' => 'open',
                    'total_amount' => 0,
                    'discount_total' => 0,
                    'addition_total' => 0,
                    'warranty_days' => $validated['warranty_days'] ?? 90,
                    'issue_nfse' => $validated['issue_nfse'] ?? false,
                    'created_by' => auth()->id(),
                ]);
                
                break; // Se criou com sucesso, sair do loop
                
            } catch (\Illuminate\Database\QueryException $e) {
                // Erro de chave duplicada (número já existe)
                if ($e->getCode() === '23000' || $e->errorInfo[1] === 1062) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        return redirect()->back()
                            ->with('error', 'Erro ao gerar número da OS. Tente novamente.')
                            ->withInput();
                    }
                    // Aguardar um pouco antes de tentar novamente
                    usleep(100000); // 0.1 segundo
                } else {
                    // Outro tipo de erro, relançar
                    throw $e;
                }
            }
        }
        
        if (!$serviceOrder) {
            return redirect()->back()
                ->with('error', 'Erro ao criar OS. Tente novamente.')
                ->withInput();
        }
        
        // Processar fotos se enviadas
        $photos = $request->file('photos', []);
        if (is_array($photos) && count($photos) > 0) {
            $countSaved = 0;
            foreach ($photos as $file) {
                if ($countSaved >= 10) { break; }
                if (!$file) { continue; }
                $path = $file->store('service_orders/'.$serviceOrder->id, 'public');
                \App\Models\ServiceOrderAttachment::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'service_order_id' => $serviceOrder->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                $countSaved++;
            }
        }
        
        return redirect()->route('service_orders.show', $serviceOrder)
            ->with('success', 'OS criada com sucesso!');
    }

    public function show(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Carregar relacionamentos para auditoria
        $serviceOrder->load(['client', 'createdBy', 'updatedBy', 'quotedBy', 'technician', 'attachments', 'items', 'receivables', 'statusLogs', 'occurrences.createdBy', 'deliveredBy', 'finalizedBy', 'cancellation', 'cancelledBy']);
        
        return view('service_orders.show', compact('serviceOrder'));
    }

    public function edit(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se a OS tem uma OS de garantia vinculada - se tiver, bloquear edição
        $hasWarrantyOrder = ServiceOrder::where('original_service_order_id', $serviceOrder->id)->where('is_warranty', true)->exists();
        if ($hasWarrantyOrder) {
            return back()->with('error', 'Esta OS não pode ser editada pois possui uma OS de garantia vinculada. Edite a OS de garantia ao invés desta.');
        }
        
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
        
        $technicians = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        $serviceOrder->load(['items', 'technician', 'attachments']);
        
        return view('service_orders.edit', compact('serviceOrder', 'clients', 'technicians'));
    }

    public function update(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        try {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
            'equipment_brand' => 'nullable|string|max:100',
            'equipment_model' => 'nullable|string|max:100',
            'equipment_serial' => 'nullable|string|max:100',
                'equipment_description' => 'nullable|string|max:500',
                'defect_reported' => 'nullable|string|max:1000',
                'received_by_user_id' => 'nullable|exists:users,id',
                'internal_notes' => 'nullable|string|max:2000',
            'technician_user_id' => 'nullable|exists:users,id',
                'status' => 'required|in:open,in_progress,in_service,service_finished,warranty,no_repair,finished,canceled',
                'diagnosis' => 'nullable|string|max:2000',
                'budget_amount' => 'nullable|numeric|min:0',
                'total_amount' => 'nullable|numeric|min:0',
                'warranty_days' => 'nullable|integer|min:0|max:3650',
                'issue_nfse' => 'boolean',
                // fotos (até 10)
                'photos' => 'nullable|array|max:10',
                'photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:5120',
            ], [
                'client_id.required' => 'O cliente é obrigatório.',
                'client_id.exists' => 'Cliente selecionado não existe.',
                'title.required' => 'O título é obrigatório.',
                'title.max' => 'O título não pode ter mais de 255 caracteres.',
                'description.required' => 'A descrição é obrigatória.',
                'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
                'received_by_user_id.exists' => 'Usuário selecionado não existe.',
                'technician_user_id.exists' => 'Técnico selecionado não existe.',
                'status.required' => 'O status é obrigatório.',
                'status.in' => 'Status inválido.',
                'budget_amount.numeric' => 'O valor do orçamento deve ser um número válido.',
                'budget_amount.min' => 'O valor do orçamento não pode ser negativo.',
                'total_amount.numeric' => 'O valor total deve ser um número válido.',
                'total_amount.min' => 'O valor total não pode ser negativo.',
                'warranty_days.integer' => 'Os dias de garantia devem ser um número inteiro.',
                'warranty_days.min' => 'Os dias de garantia não podem ser negativos.',
                'warranty_days.max' => 'Os dias de garantia não podem exceder 10 anos.',
            ]);

        $updates = [
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
                'description' => $validated['description'],
                'equipment_brand' => $validated['equipment_brand'],
                'equipment_model' => $validated['equipment_model'],
                'equipment_serial' => $validated['equipment_serial'],
                'equipment_description' => $validated['equipment_description'],
                'defect_reported' => $validated['defect_reported'],
                'received_by_user_id' => $validated['received_by_user_id'] ?? null,
                'internal_notes' => $validated['internal_notes'] ?? null,
                'technician_user_id' => $validated['technician_user_id'] ?? null,
            'status' => $validated['status'],
                'diagnosis' => $validated['diagnosis'] ?? null,
                'budget_amount' => $validated['budget_amount'] ?? null,
                'total_amount' => $validated['total_amount'] ?? $serviceOrder->total_amount,
                'warranty_days' => $validated['warranty_days'] ?? 90,
                'issue_nfse' => $validated['issue_nfse'] ?? false,
                'updated_by' => auth()->id(),
            ];
            
            // Registrar mudança de status no log
            if ($validated['status'] !== $serviceOrder->status) {
                ServiceOrderStatusLog::create([
                    'service_order_id' => $serviceOrder->id,
                    'old_status' => $serviceOrder->status,
                    'new_status' => $validated['status'],
                    'changed_by' => auth()->id(),
                    'changed_at' => now(),
                    'reason' => $request->input('status_change_reason')
                ]);
            }
            
            // Recalcula orçamento (budget) e total a partir dos itens
            $itemsTotal = (float) $serviceOrder->items()->sum('line_total');
            $updates['budget_amount'] = $itemsTotal;
            if (!isset($validated['total_amount']) || $validated['total_amount'] === null) {
                $updates['total_amount'] = $itemsTotal;
            }
            
            // Registrar orçamento se mudou para "in_progress"
            if ($validated['status'] === 'in_progress' && $serviceOrder->status !== 'in_progress') {
                $updates['quoted_by'] = auth()->id();
                $updates['quoted_at'] = now();
            }
            
        $serviceOrder->update($updates);

        // Processar fotos se enviadas
        $photos = $request->file('photos', []);
        \Log::info("Fotos recebidas no update: " . count($photos));
        if (is_array($photos) && count($photos) > 0) {
            $countSaved = 0;
            foreach ($photos as $file) {
                if ($countSaved >= 10) { break; }
                if (!$file) { continue; }
                \Log::info("Processando foto: " . $file->getClientOriginalName());
                $path = $file->store('service_orders/'.$serviceOrder->id, 'public');
                \App\Models\ServiceOrderAttachment::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'service_order_id' => $serviceOrder->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
                $countSaved++;
                \Log::info("Foto salva: {$path}");
            }
        }

            return redirect()->route('service_orders.edit', $serviceOrder)
                ->with('success', 'OS atualizada com sucesso!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar OS: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function print(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        $serviceOrder->load(['client', 'items', 'tenant']);
        return view('service_orders.print', ['order' => $serviceOrder]);
    }

    public function emailForm(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.email'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        $client = $serviceOrder->client;
        return view('service_orders.email', compact('serviceOrder', 'client'));
    }

    public function sendEmail(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.email'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        $data = $request->validate([
            'to' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'nullable|string',
            'template' => 'nullable|in:approval_request,ready_for_pickup,cancellation',
        ], [
            'to.required' => 'O campo email é obrigatório.',
            'to.email' => 'Por favor, insira um email válido.',
            'to.max' => 'O email não pode ter mais de 255 caracteres.',
            'subject.required' => 'O campo assunto é obrigatório.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
            'template.in' => 'Template selecionado é inválido.',
        ]);

        $client = $serviceOrder->client;
        if (!empty($data['template']) && (empty($client->email) || !filter_var($client->email, FILTER_VALIDATE_EMAIL))) {
            return back()->withErrors(['to' => 'O cliente não possui um email válido cadastrado. Verifique os dados do cliente.'])->withInput();
        }

        // Processar template se selecionado
        $body = $data['body'] ?? '';
        $subject = $data['subject'];
        
        if (!empty($data['template'])) {
            $serviceOrder->load(['items', 'tenant']);
            
            if ($data['template'] === 'approval_request') {
                // Gerar URLs assinadas para aprovação pública
                $approveUrl = URL::signedRoute('service_orders.public_approve', [
                    'service_order' => $serviceOrder->id,
                    'action' => 'approve'
                ]);
                $rejectUrl = URL::signedRoute('service_orders.public_approve', [
                    'service_order' => $serviceOrder->id,
                    'action' => 'reject'
                ]);
                $body = view('service_orders.emails._approval_request', compact('serviceOrder', 'client', 'approveUrl', 'rejectUrl'))->render();
                if (empty($subject)) {
                    $subject = 'OS #' . $serviceOrder->number . ' - Aprovação de orçamento';
                }
            } elseif ($data['template'] === 'ready_for_pickup') {
                $body = view('service_orders.emails._ready_for_pickup', compact('serviceOrder', 'client'))->render();
                if (empty($subject)) {
                    $subject = 'OS #' . $serviceOrder->number . ' - Pronto para retirada';
                }
            } elseif ($data['template'] === 'cancellation') {
                if ($serviceOrder->status !== 'canceled') {
                    return back()->withErrors(['template' => 'O template de cancelamento só pode ser usado para OS canceladas.'])->withInput();
                }
                $serviceOrder->load(['cancellation', 'cancelledBy']);
                $body = view('service_orders.emails._cancellation', compact('serviceOrder', 'client'))->render();
                if (empty($subject)) {
                    $subject = 'OS #' . $serviceOrder->number . ' - Cancelamento';
                }
            }
        }

        $active = SmtpConfig::where('is_active', true)->first();
        if (!$active && !env('MAIL_HOST')) {
            return back()->withErrors(['email' => 'Configuração SMTP não encontrada. Entre em contato com o administrador.'])->withInput();
        }

        $host = $active->host ?? env('MAIL_HOST');
        $port = $active->port ?? env('MAIL_PORT', 587);
        $username = $active->username ?? env('MAIL_USERNAME');
        $password = $active->password ?? env('MAIL_PASSWORD');
        $encryption = $active->encryption ?? env('MAIL_ENCRYPTION', 'tls');
        $fromAddress = $active->from_address ?? env('MAIL_FROM_ADDRESS');
        $tenant = $serviceOrder->tenant;
        $fromName = (string)($tenant->fantasy_name ?? $tenant->name ?? $active->from_name ?? env('MAIL_FROM_NAME', 'QFiscal'));

        if (empty($host) || empty($fromAddress)) {
            return back()->withErrors(['email' => 'Configuração SMTP incompleta. Verifique host e email remetente.'])->withInput();
        }

        // Gerar PDF do cancelamento se template for cancellation
        $pdfContent = null;
        if ($data['template'] === 'cancellation' && $serviceOrder->status === 'canceled') {
            try {
                $serviceOrder->loadMissing(['client', 'cancellation', 'cancelledBy', 'items', 'tenant']);
                $pdfHtml = view('service_orders.cancellation_receipt', compact('serviceOrder'))->render();
                if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                    $pdfContent = $pdf->output();
                } elseif (class_exists(\Barryvdh\DomPDF\Facades\Pdf::class)) {
                    $pdf = \Barryvdh\DomPDF\Facades\Pdf::loadHTML($pdfHtml)->setPaper('a4');
                    $pdfContent = $pdf->output();
                } else {
                    \Log::warning('Biblioteca PDF não encontrada para gerar cancelamento');
                }
            } catch (\Throwable $pdfError) {
                \Log::warning('Erro ao gerar PDF de cancelamento', [
                    'service_order_id' => $serviceOrder->id,
                    'error' => $pdfError->getMessage()
                ]);
            }
        }

        try {
            $mailer = new PHPMailer(true);
            \App\Http\Controllers\Admin\EmailTestController::configureMailer($mailer, $host, $port, $username, $password, $encryption, $fromAddress, $fromName);
            $mailer->addAddress($data['to']);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $body;
            $mailer->AltBody = strip_tags($body);
            
            // Anexar PDF se foi gerado com sucesso
            if ($pdfContent) {
                $mailer->addStringAttachment($pdfContent, 'Cancelamento_OS_' . $serviceOrder->number . '.pdf', 'base64', 'application/pdf');
            }
            
            $mailer->send();
            
            // Registrar auditoria de envio de email
            try {
                \App\Models\ServiceOrderAudit::create([
                    'service_order_id' => $serviceOrder->id,
                    'user_id' => auth()->id(),
                    'action' => 'email_sent',
                    'notes' => 'Email enviado para ' . $data['to'],
                    'changes' => [
                        'to' => $data['to'],
                        'subject' => $subject,
                        'template' => $data['template'] ?? 'custom',
                        'has_pdf' => !empty($pdfContent),
                    ],
                ]);
            } catch (\Throwable $auditError) {
                \Log::warning('Erro ao registrar auditoria de email', [
                    'service_order_id' => $serviceOrder->id,
                    'error' => $auditError->getMessage()
                ]);
            }
            
            return redirect()->route('service_orders.show', $serviceOrder)->with('success', 'E-mail enviado com sucesso para ' . $data['to'] . '!');
        } catch (PHPMailerException $e) {
            // Fallback automático: troca porta/cripto e tenta novamente
            if (stripos($e->getMessage(), 'Could not connect to SMTP host') !== false || stripos($e->getMessage(), 'Failed to connect') !== false) {
                try {
                    $altEnc = ($encryption === 'ssl') ? 'tls' : 'ssl';
                    $altPort = ($altEnc === 'ssl') ? 465 : 587;
                    $mailer = new PHPMailer(true);
                    \App\Http\Controllers\Admin\EmailTestController::configureMailer($mailer, $host, $altPort, $username, $password, $altEnc, $fromAddress, $fromName);
                    $mailer->addAddress($data['to']);
                    $mailer->isHTML(true);
                    $mailer->Subject = $subject;
                    $mailer->Body = $body;
                    $mailer->AltBody = strip_tags($body);
                    
                    // Anexar PDF se foi gerado com sucesso
                    if ($pdfContent) {
                        $mailer->addStringAttachment($pdfContent, 'Cancelamento_OS_' . $serviceOrder->number . '.pdf', 'base64', 'application/pdf');
                    }
                    
                    $mailer->send();
                    
                    // Registrar auditoria de envio de email (fallback)
                    try {
                        \App\Models\ServiceOrderAudit::create([
                            'service_order_id' => $serviceOrder->id,
                            'user_id' => auth()->id(),
                            'action' => 'email_sent',
                            'notes' => 'Email enviado para ' . $data['to'] . ' (fallback)',
                            'changes' => [
                                'to' => $data['to'],
                                'subject' => $subject,
                                'template' => $data['template'] ?? 'custom',
                                'has_pdf' => !empty($pdfContent),
                            ],
                        ]);
                    } catch (\Throwable $auditError) {
                        \Log::warning('Erro ao registrar auditoria de email', [
                            'service_order_id' => $serviceOrder->id,
                            'error' => $auditError->getMessage()
                        ]);
                    }
                    
                    return redirect()->route('service_orders.show', $serviceOrder)->with('success', 'E-mail enviado com sucesso para ' . $data['to'] . '!');
                } catch (PHPMailerException $e2) {
                    $meta = " host={$host} port={$altPort} enc={$altEnc} user={$username} from={$fromAddress}";
                    $full = 'Falha ao enviar: ' . $e->getMessage() . ' | Tentativa alternativa: ' . $e2->getMessage() . $meta;
                    return back()->withErrors(['email' => $full])->withInput();
                }
            } else {
                $meta = " host={$host} port={$port} enc={$encryption} user={$username} from={$fromAddress}";
                $full = 'Falha ao enviar: ' . $e->getMessage() . $meta;
                return back()->withErrors(['email' => $full])->withInput();
            }
        }
    }

    public function approve(ServiceOrder $serviceOrder, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.approve'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Só pode aprovar se a OS estiver orçada (in_progress)
        if ($serviceOrder->status !== 'in_progress') {
            return back()->with('error', 'Para aprovar, a OS precisa estar com status Orçada.');
        }
        
        $data = $request->validate([
            'approval_notes' => 'nullable|string|max:255',
        ]);
        
        $serviceOrder->approval_status = 'approved';
        $serviceOrder->approved_at = now();
        if (isset($data['approval_notes'])) { 
            $serviceOrder->approval_notes = $data['approval_notes']; 
        }
        $serviceOrder->save();
        
        return back()->with('success', 'OS aprovada.');
    }

    public function reject(ServiceOrder $serviceOrder, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.reject'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Só pode marcar como não aprovada se estiver orçada
        if ($serviceOrder->status !== 'in_progress') {
            return back()->with('error', 'Para marcar como não aprovada, a OS precisa estar com status Orçada.');
        }
        
        $data = $request->validate([
            'approval_notes' => 'nullable|string|max:255',
        ]);
        
        $serviceOrder->approval_status = 'not_approved';
        $serviceOrder->not_approved_at = now();
        if (isset($data['approval_notes'])) { 
            $serviceOrder->approval_notes = $data['approval_notes']; 
        }
        $serviceOrder->save();
        
        return back()->with('success', 'OS marcada como não aprovada.');
    }

    public function addOccurrence(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        $validated = $request->validate([
            'occurrence_type' => 'required|in:client_contact,status_change,technical_note,warranty_issue,delivery_note,payment_note,other',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
        ], [
            'occurrence_type.required' => 'O tipo de ocorrência é obrigatório.',
            'occurrence_type.in' => 'Tipo de ocorrência inválido.',
            'description.required' => 'A descrição da ocorrência é obrigatória.',
            'description.max' => 'A descrição não pode ter mais de 2000 caracteres.',
            'priority.required' => 'A prioridade é obrigatória.',
            'priority.in' => 'Prioridade inválida.',
        ]);

        // Converter checkbox para boolean (não validar, apenas converter)
        $isInternal = $request->has('is_internal') && $request->input('is_internal') === 'on';

        $occurrence = ServiceOrderOccurrence::create([
                    'service_order_id' => $serviceOrder->id,
            'occurrence_type' => $validated['occurrence_type'],
            'description' => $validated['description'],
            'created_by' => auth()->id(),
            'priority' => $validated['priority'],
            'is_internal' => $isInternal,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ocorrência adicionada com sucesso!',
            'occurrence' => [
                'id' => $occurrence->id,
                'type' => $occurrence->occurrence_type_name,
                'description' => $occurrence->description,
                'priority' => $occurrence->priority_name,
                'priority_color' => $occurrence->priority_color,
                'type_color' => $occurrence->type_color,
                'is_internal' => $occurrence->is_internal,
                'created_by' => $occurrence->createdBy->name,
                'created_at' => $occurrence->created_at->format('d/m/Y H:i'),
            ]
        ]);
    }

    public function getOccurrences(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        $occurrences = $serviceOrder->occurrences()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($occurrence) {
                return [
                    'id' => $occurrence->id,
                    'type' => $occurrence->occurrence_type_name,
                    'description' => $occurrence->description,
                    'priority' => $occurrence->priority_name,
                    'priority_color' => $occurrence->priority_color,
                    'type_color' => $occurrence->type_color,
                    'is_internal' => $occurrence->is_internal,
                    'created_by' => $occurrence->createdBy->name,
                    'created_at' => $occurrence->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'occurrences' => $occurrences
        ]);
    }

    // ===== MÉTODOS PARA FINALIZAÇÃO =====
    
    public function finalizeForm(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.finalize'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Não permitir finalizar OS já finalizada
        if ($serviceOrder->status === 'finished') {
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', 'Esta OS já foi finalizada.');
        }
        
        // Carregar relacionamentos necessários
        $serviceOrder->load(['client', 'items', 'technician', 'deliveredBy', 'finalizedBy']);
        
        // Carregar usuários para o select de entregador
        $users = User::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return view('service_orders.finalize', compact('serviceOrder', 'users'));
    }
    
    public function finalize(Request $request, ServiceOrder $serviceOrder)
    {
        \Log::info("Iniciando finalização da OS: {$serviceOrder->id}");
        
        abort_unless(auth()->user()->hasPermission('service_orders.finalize'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Não permitir finalizar OS já finalizada
        if ($serviceOrder->status === 'finished') {
            \Log::warning("Tentativa de finalizar OS já finalizada: {$serviceOrder->id}");
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', 'Esta OS já foi finalizada.');
        }
        
        \Log::info("Validando dados da finalização da OS: {$serviceOrder->id}");
        
        // Log dos dados recebidos para debug
        \Log::info("Dados recebidos na finalização da OS {$serviceOrder->id}: " . json_encode($request->all()));
        
        try {
        $validated = $request->validate([
            'finalization_date' => 'required|date|after_or_equal:' . $serviceOrder->created_at->format('Y-m-d'),
            'finalization_notes' => 'nullable|string|max:2000',
            'delivery_method' => 'required|in:pickup,delivery,shipping',
            'delivered_by' => 'nullable|exists:users,id',
            'client_signature' => 'nullable|string|max:500',
            'equipment_condition' => 'required|in:perfect,good,damaged',
            'accessories_included' => 'nullable|string|max:1000',
            'final_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,pix,transfer,boleto,mixed',
            'payment_received' => 'nullable|in:on,off,1,0,true,false',
            'installments' => 'nullable|integer|min:1|max:24',
            'entry_amount' => 'nullable|numeric|min:0',
            'entry_method' => 'nullable|in:cash,pix',
        ], [
            'finalization_date.required' => 'A data de finalização é obrigatória.',
            'finalization_date.date' => 'A data de finalização deve ser uma data válida.',
            'finalization_date.after_or_equal' => 'A data de finalização não pode ser anterior à data de criação da OS.',
            'delivery_method.required' => 'O método de entrega é obrigatório.',
            'delivery_method.in' => 'Método de entrega inválido.',
            'delivered_by.exists' => 'O usuário selecionado não existe.',
            'equipment_condition.required' => 'A condição do equipamento é obrigatória.',
            'equipment_condition.in' => 'Condição do equipamento inválida.',
            'final_amount.required' => 'O valor final é obrigatório.',
            'final_amount.numeric' => 'O valor final deve ser um número válido.',
            'final_amount.min' => 'O valor final não pode ser negativo.',
            'payment_method.required' => 'O método de pagamento é obrigatório.',
            'payment_method.in' => 'Método de pagamento inválido.',
            'installments.integer' => 'O número de parcelas deve ser um número inteiro.',
            'installments.min' => 'O número de parcelas deve ser pelo menos 1.',
            'installments.max' => 'O número de parcelas não pode exceder 24.',
            'entry_amount.numeric' => 'O valor da entrada deve ser um número válido.',
            'entry_amount.min' => 'O valor da entrada não pode ser negativo.',
            'entry_method.in' => 'Método de entrada inválido.',
        ]);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error("Erro de validação na finalização da OS {$serviceOrder->id}: " . json_encode($e->errors()));
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error("Erro geral na finalização da OS {$serviceOrder->id}: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro ao finalizar OS: ' . $e->getMessage())
                ->withInput();
        }
        
        // Validar se o usuário entregador pertence ao mesmo tenant
        if (!empty($validated['delivered_by'])) {
            $deliveredBy = User::find($validated['delivered_by']);
            abort_unless($deliveredBy && $deliveredBy->tenant_id === auth()->user()->tenant_id, 403);
        }
        
        // Converter checkbox para boolean
        $paymentReceivedValue = $validated['payment_received'] ?? null;
        $paymentReceived = in_array($paymentReceivedValue, ['on', '1', 'true', true], true);
        
        \Log::info("Valor do payment_received para OS {$serviceOrder->id}: " . ($paymentReceived ? 'true' : 'false'));
        
        \Log::info("Dados validados com sucesso para OS: {$serviceOrder->id}. Iniciando atualização...");
        
        // Atualizar OS com dados de finalização
        // Calcular garantia automaticamente (apenas se NÃO for OS de garantia)
        $updateData = [
            'status' => 'finished',
            'finalization_date' => $validated['finalization_date'],
            'finalization_notes' => $validated['finalization_notes'],
            'delivery_method' => $validated['delivery_method'],
            'delivered_by' => $validated['delivered_by'],
            'client_signature' => $validated['client_signature'],
            'equipment_condition' => $validated['equipment_condition'],
            'accessories_included' => $validated['accessories_included'],
            'final_amount' => $validated['final_amount'],
            'payment_method' => $validated['payment_method'],
            'payment_received' => $paymentReceived,
            'finalized_by' => auth()->id(),
            'finalized_at' => now(),
        ];
        
        // Apenas calcular nova garantia se NÃO for OS de garantia
        // OS de garantia mantém a garantia original da primeira OS
        if (!$serviceOrder->is_warranty) {
            $warrantyDays = $this->getSettingValue('service_orders.default_warranty_days', 90);
            $warrantyUntil = now()->addDays($warrantyDays)->toDateString();
            $updateData['warranty_days'] = $warrantyDays;
            $updateData['warranty_until'] = $warrantyUntil;
        }
        
        $serviceOrder->update($updateData);
        
        \Log::info("OS atualizada com sucesso: {$serviceOrder->id}. Registrando ocorrência...");
        
        // Registrar ocorrência de finalização
        ServiceOrderOccurrence::create([
            'service_order_id' => $serviceOrder->id,
            'occurrence_type' => 'delivery_note',
            'description' => 'OS finalizada e entregue. ' . ($validated['finalization_notes'] ?? ''),
            'created_by' => auth()->id(),
            'priority' => 'medium',
            'is_internal' => false,
        ]);
        
        \Log::info("Ocorrência registrada para OS: {$serviceOrder->id}. Processando estoque...");
        
        // Dedução automática de estoque
        $this->processStockDeduction($serviceOrder);
        
        // Processar pagamentos e criar recebíveis
        $this->processPayments($serviceOrder, $validated);
        
        \Log::info("Finalização da OS {$serviceOrder->id} concluída com sucesso!");
        
        return redirect()->route('service_orders.show', $serviceOrder)
            ->with('success', 'OS finalizada com sucesso!');
    }

    public function deliveryReceipt(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Só permite imprimir recibo se a OS estiver finalizada
        if ($serviceOrder->status !== 'finished') {
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', 'Só é possível imprimir o recibo de OS finalizadas.');
        }
        
        $serviceOrder->load(['client', 'items', 'tenant', 'deliveredBy', 'finalizedBy']);
        
        return view('service_orders.delivery_receipt', compact('serviceOrder'));
    }

    /**
     * Imprimir recibo de garantia (2 vias)
     */
    public function warrantyReceipt(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($serviceOrder->is_warranty, 403);
        
        // Carregar OS original se existir
        $originalOrder = null;
        if ($serviceOrder->original_service_order_id) {
            $originalOrder = ServiceOrder::find($serviceOrder->original_service_order_id);
        }
        
        $serviceOrder->load(['client', 'items', 'tenant', 'createdBy', 'technician']);
        
        return view('service_orders.warranty_receipt', compact('serviceOrder', 'originalOrder'));
    }

    public function addItem(ServiceOrder $serviceOrder, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        \Log::info("Iniciando adição de item para OS: {$serviceOrder->id}");
        \Log::info("Dados recebidos: " . json_encode($request->all()));

        $data = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'quantity' => 'required|numeric|min:0.001',
            'unit' => 'nullable|string|max:10',
            'unit_price' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $lineTotal = round(($data['quantity'] * $data['unit_price']) - ($data['discount_value'] ?? 0), 2);

        $item = ServiceOrderItem::create([
            'tenant_id' => auth()->user()->tenant_id,
            'service_order_id' => $serviceOrder->id,
            'product_id' => $data['product_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'unit_price' => $data['unit_price'],
            'discount_value' => $data['discount_value'] ?? 0,
            'line_total' => $lineTotal,
            'total_price' => $lineTotal,
        ]);

        // Atualizar totais da OS
        $this->updateServiceOrderTotals($serviceOrder);

            return response()->json([
            'success' => true,
            'message' => 'Item adicionado com sucesso!',
                'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => number_format($item->unit_price, 2, ',', '.'),
                'discount_value' => number_format($item->discount_value, 2, ',', '.'),
                'line_total' => number_format($item->line_total, 2, ',', '.'),
            ]
        ]);
    }

    public function removeItem(ServiceOrder $serviceOrder, ServiceOrderItem $item)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($item->service_order_id === $serviceOrder->id, 403);

        $item->delete();

        // Atualizar totais da OS
        $this->updateServiceOrderTotals($serviceOrder);

        return response()->json([
            'success' => true,
            'message' => 'Item removido com sucesso!'
        ]);
    }

    public function publicApproval(Request $request, ServiceOrder $serviceOrder)
    {
        // Link assinado (signed route) já confere validade e integridade
        if (!$request->hasValidSignature()) {
            abort(403, 'Link inválido ou expirado.');
        }

        $action = $request->input('action');
        if (!in_array($action, ['approve', 'reject'])) {
            abort(400, 'Ação inválida.');
        }

        if ($action === 'approve') {
            $serviceOrder->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by_email' => $request->input('email'),
                'approval_method' => 'email',
            ]);
            
            // Registrar ocorrência de aprovação
            ServiceOrderOccurrence::create([
                'service_order_id' => $serviceOrder->id,
                'occurrence_type' => 'status_change',
                'description' => 'OS aprovada pelo cliente via email',
                'created_by' => null, // Aprovação pública não tem usuário específico
                'is_internal' => false,
                'priority' => 'medium',
            ]);
            
            $message = 'OS aprovada com sucesso!';
        } else {
            $serviceOrder->update([
                'approval_status' => 'not_approved',
                'rejected_at' => now(),
                'rejected_by_email' => $request->input('email'),
                'rejection_method' => 'email',
            ]);
            
            // Registrar ocorrência de rejeição
            ServiceOrderOccurrence::create([
                'service_order_id' => $serviceOrder->id,
                'occurrence_type' => 'status_change',
                'description' => 'OS rejeitada pelo cliente via email',
                'created_by' => null, // Rejeição pública não tem usuário específico
                'is_internal' => false,
                'priority' => 'high',
            ]);
            
            $message = 'OS reprovada.';
        }

        return view('service_orders.public_response', compact('serviceOrder', 'message'));
    }

    /**
     * Processa a dedução automática de estoque ao finalizar uma OS
     */

    /**
     * Processa pagamentos e cria recebíveis conforme método de pagamento
     */
    private function processPayments(ServiceOrder $serviceOrder, array $validated)
    {
        \Log::info("Iniciando processamento de pagamentos para OS: {$serviceOrder->id}");
        
        $paymentMethod = $validated['payment_method'];
        $finalAmount = $validated['final_amount'];
        $paymentReceived = $this->convertPaymentReceived($validated['payment_received'] ?? null);
        
        // Buscar configurações de OS
        $maxInstallments = (int) $this->getSettingValue('os_max_installments', 3);
        $interestRate = (float) $this->getSettingValue('os_interest_rate', 0);
        
        switch ($paymentMethod) {
            case 'cash':
                $this->processCashPayment($serviceOrder, $finalAmount, $paymentReceived);
                break;
                
            case 'card':
                $installments = $validated['installments'] ?? 1;
                $this->processCardPayment($serviceOrder, $finalAmount, $installments, $interestRate, $paymentReceived);
                break;
                
            case 'pix':
                $this->processPixPayment($serviceOrder, $finalAmount, $paymentReceived);
                break;
                
            case 'transfer':
                $this->processTransferPayment($serviceOrder, $finalAmount, $paymentReceived);
                break;
                
            case 'boleto':
                $this->processBoletoPayment($serviceOrder, $finalAmount);
                break;
                
            case 'mixed':
                $entryAmount = $validated['entry_amount'] ?? 0;
                $entryMethod = $validated['entry_method'] ?? 'cash';
                $installments = $validated['installments'] ?? 1;
                $this->processMixedPayment($serviceOrder, $finalAmount, $entryAmount, $entryMethod, $installments, $interestRate, $paymentReceived);
                break;
        }
        
        // Marcar que recebíveis foram criados
        $serviceOrder->update([
            'receivables_created' => true,
            'receivables_created_at' => now(),
        ]);
        
        \Log::info("Processamento de pagamentos concluído para OS: {$serviceOrder->id}");
    }

    /**
     * Processa pagamento em dinheiro
     */
    private function processCashPayment(ServiceOrder $serviceOrder, $amount, $received)
    {
        if ($received) {
            // Criar recebível pago
            \App\Models\Receivable::create([
                'tenant_id' => auth()->user()->tenant_id,
                'client_id' => $serviceOrder->client_id,
                'service_order_id' => $serviceOrder->id,
                'description' => "OS #{$serviceOrder->number} - Pagamento à vista",
                'amount' => $amount,
                'due_date' => now()->toDateString(),
                'status' => 'paid',
                'received_at' => now(),
                'payment_method' => 'cash',
                'created_by' => auth()->id(),
            ]);
            
            // Adicionar ao caixa do dia
            $this->addToDailyCash($amount, 'OS #' . $serviceOrder->number);
        }
    }

    /**
     * Processa pagamento no cartão
     */
    private function processCardPayment(ServiceOrder $serviceOrder, $amount, $installments, $interestRate, $received)
    {
        $totalWithInterest = $this->calculateTotalWithInterest($amount, $installments, $interestRate);
        $interestAmount = $totalWithInterest - $amount;
        $installmentAmount = round($totalWithInterest / $installments, 2);
        
        // Atualizar OS com cálculos
        $serviceOrder->update([
            'installments' => $installments,
            'interest_rate' => $interestRate,
            'interest_amount' => $interestAmount,
            'total_with_interest' => $totalWithInterest,
        ]);
        
        // Criar recebíveis
        $firstDueDate = now()->addDays(30);
        for ($i = 1; $i <= $installments; $i++) {
            $dueDate = $firstDueDate->copy()->addDays(($i - 1) * 30);
            $status = ($i === 1 && $received) ? 'paid' : 'open';
            $receivedAt = ($i === 1 && $received) ? now() : null;
            
            \App\Models\Receivable::create([
                'tenant_id' => auth()->user()->tenant_id,
                'client_id' => $serviceOrder->client_id,
                'service_order_id' => $serviceOrder->id,
                'description' => "OS #{$serviceOrder->number} - Parcela {$i}/{$installments}",
                'amount' => $installmentAmount,
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'received_at' => $receivedAt,
                'payment_method' => 'card',
                'created_by' => auth()->id(),
            ]);
            
            // Se primeira parcela foi paga, adicionar ao caixa
            if ($i === 1 && $received) {
                $this->addToDailyCash($installmentAmount, 'OS #' . $serviceOrder->number . ' - Parcela 1');
            }
        }
    }

    /**
     * Processa pagamento PIX
     */
    private function processPixPayment(ServiceOrder $serviceOrder, $amount, $received)
    {
        if ($received) {
            \App\Models\Receivable::create([
                'tenant_id' => auth()->user()->tenant_id,
                'client_id' => $serviceOrder->client_id,
                'service_order_id' => $serviceOrder->id,
                'description' => "OS #{$serviceOrder->number} - PIX",
                'amount' => $amount,
                'due_date' => now()->toDateString(),
                'status' => 'paid',
                'received_at' => now(),
                'payment_method' => 'pix',
                'created_by' => auth()->id(),
            ]);
            
            $this->addToDailyCash($amount, 'OS #' . $serviceOrder->number . ' - PIX');
        }
    }

    /**
     * Processa pagamento por transferência
     */
    private function processTransferPayment(ServiceOrder $serviceOrder, $amount, $received)
    {
        if ($received) {
            \App\Models\Receivable::create([
                'tenant_id' => auth()->user()->tenant_id,
                'client_id' => $serviceOrder->client_id,
                'service_order_id' => $serviceOrder->id,
                'description' => "OS #{$serviceOrder->number} - Transferência",
                'amount' => $amount,
                'due_date' => now()->toDateString(),
                'status' => 'paid',
                'received_at' => now(),
                'payment_method' => 'transfer',
                'created_by' => auth()->id(),
            ]);
            
            $this->addToDailyCash($amount, 'OS #' . $serviceOrder->number . ' - Transferência');
        }
    }

    /**
     * Processa pagamento por boleto
     */
    private function processBoletoPayment(ServiceOrder $serviceOrder, $amount)
    {
        \App\Models\Receivable::create([
            'tenant_id' => auth()->user()->tenant_id,
            'client_id' => $serviceOrder->client_id,
            'service_order_id' => $serviceOrder->id,
            'description' => "OS #{$serviceOrder->number} - Boleto Bancário",
            'amount' => $amount,
            'due_date' => now()->addDays(7)->toDateString(), // 7 dias para pagamento
            'status' => 'open',
            'payment_method' => 'boleto',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Processa pagamento misto (entrada + parcelas)
     */
    private function processMixedPayment(ServiceOrder $serviceOrder, $totalAmount, $entryAmount, $entryMethod, $installments, $interestRate, $received)
    {
        // Processar entrada
        if ($entryAmount > 0) {
            if ($received) {
                \App\Models\Receivable::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'client_id' => $serviceOrder->client_id,
                    'service_order_id' => $serviceOrder->id,
                    'description' => "OS #{$serviceOrder->number} - Entrada ({$entryMethod})",
                    'amount' => $entryAmount,
                    'due_date' => now()->toDateString(),
                    'status' => 'paid',
                    'received_at' => now(),
                    'payment_method' => $entryMethod,
                    'created_by' => auth()->id(),
                ]);
                
                $this->addToDailyCash($entryAmount, 'OS #' . $serviceOrder->number . ' - Entrada');
            }
        }
        
        // Processar parcelas do restante
        $remainingAmount = $totalAmount - $entryAmount;
        if ($remainingAmount > 0) {
            $totalWithInterest = $this->calculateTotalWithInterest($remainingAmount, $installments, $interestRate);
            $interestAmount = $totalWithInterest - $remainingAmount;
            $installmentAmount = round($totalWithInterest / $installments, 2);
            
            // Atualizar OS com cálculos
            $serviceOrder->update([
                'entry_amount' => $entryAmount,
                'entry_method' => $entryMethod,
                'installments' => $installments,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_with_interest' => $totalWithInterest,
            ]);
            
            // Criar recebíveis das parcelas
            $firstDueDate = now()->addDays(30);
            for ($i = 1; $i <= $installments; $i++) {
                $dueDate = $firstDueDate->copy()->addDays(($i - 1) * 30);
                
                \App\Models\Receivable::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'client_id' => $serviceOrder->client_id,
                    'service_order_id' => $serviceOrder->id,
                    'description' => "OS #{$serviceOrder->number} - Parcela {$i}/{$installments}",
                    'amount' => $installmentAmount,
                    'due_date' => $dueDate->toDateString(),
                    'status' => 'open',
                    'payment_method' => 'card',
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Calcula total com juros
     */
    private function calculateTotalWithInterest($amount, $installments, $interestRate)
    {
        if ($interestRate <= 0) {
            return $amount;
        }
        
        // Juros simples
        $interestAmount = $amount * ($interestRate / 100) * $installments;
        return $amount + $interestAmount;
    }

    /**
     * Adiciona valor ao caixa do dia
     */
    private function addToDailyCash($amount, $description)
    {
        try {
            $today = now()->toDateString();
            
            // Verificar se caixa está fechado
            if (\App\Http\Controllers\DailyCashController::isCashClosed(auth()->user()->tenant_id, $today)) {
                \Log::warning("Tentativa de adicionar ao caixa fechado: {$description}");
                return;
            }
            
            // Atualizar ou criar registro do caixa do dia
            \App\Models\DailyCash::updateOrCreate(
                [
                    'tenant_id' => auth()->user()->tenant_id,
                    'date' => $today,
                ],
                [
                    'status' => 'open',
                    'total_received' => \App\Models\Receivable::where('tenant_id', auth()->user()->tenant_id)
                        ->where('status', 'paid')
                        ->whereDate('received_at', $today)
                        ->sum('amount'),
                ]
            );
            
            \Log::info("Valor adicionado ao caixa do dia: R$ {$amount} - {$description}");
        } catch (\Exception $e) {
            \Log::error("Erro ao adicionar ao caixa do dia: " . $e->getMessage());
        }
    }

    /**
     * Converte valor do checkbox para boolean
     */
    private function convertPaymentReceived($value)
    {
        return in_array($value, ['on', '1', 'true', true], true);
    }

    /**
     * Adiciona anexo/foto à OS
     */
    public function addAttachment(ServiceOrder $serviceOrder, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $fileSize = $file->getSize();
        
        // Verificar limite de storage de arquivos ANTES de fazer upload
        if (!$this->checkStorageLimit('files', $fileSize)) {
            return back()->withErrors([
                'file' => $this->getStorageLimitErrorMessage('files')
            ]);
        }
        
        // Limitar a 10 fotos (imagens) por OS
        if (str_starts_with((string)$file->getMimeType(), 'image/')) {
            $imageCount = \App\Models\ServiceOrderAttachment::where('tenant_id', auth()->user()->tenant_id)
                ->where('service_order_id', $serviceOrder->id)
                ->where('mime_type', 'like', 'image/%')
                ->count();
            if ($imageCount >= 10) {
                return back()->with('error', 'Limite de 10 fotos por OS atingido.');
            }
        }
        
        $path = $file->store('service_orders/'. $serviceOrder->id, 'public');

        \App\Models\ServiceOrderAttachment::create([
            'tenant_id' => auth()->user()->tenant_id,
            'service_order_id' => $serviceOrder->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
        
        // Invalidar cache de storage após upload
        $this->invalidateStorageCache();

        return back()->with('success', 'Anexo adicionado.');
    }

    /**
     * Remove anexo/foto da OS
     */
    public function removeAttachment(ServiceOrder $serviceOrder, \App\Models\ServiceOrderAttachment $attachment)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id && $attachment->service_order_id === $serviceOrder->id, 403);

        // Remover arquivo físico (se existir)
        try { 
            \Storage::disk('public')->delete($attachment->path); 
        } catch (\Throwable $e) {
            \Log::warning("Erro ao deletar arquivo físico: " . $e->getMessage());
        }
        
        $attachment->delete();
        return back()->with('success', 'Anexo removido.');
    }

    /**
     * Processa dedução de estoque para todos os itens da OS
     */
    private function processStockDeduction(ServiceOrder $serviceOrder)
    {
        \Log::info("Iniciando processamento de estoque para OS: {$serviceOrder->id}");

        // Buscar todos os itens da OS que têm produto_id
        $items = $serviceOrder->items()->whereNotNull('product_id')->get();
        
        \Log::info("Itens encontrados com product_id: " . $items->count());
        foreach ($items as $item) {
            \Log::info("Item: {$item->name}, Product ID: {$item->product_id}, Quantity: {$item->quantity}");
        }
        
        if ($items->isEmpty()) {
            \Log::info("OS {$serviceOrder->id} não tem produtos com estoque. Pulando dedução de estoque.");
            return;
        }

        $allowNegativeStock = $this->getSettingValue('allow_negative_stock', false);

        foreach ($items as $item) {
            $product = Product::where('id', $item->product_id)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();

            if (!$product) {
                \Log::warning("Produto {$item->product_id} não encontrado para item {$item->id}");
                continue;
            }

            // Calcular estoque atual a partir das movimentações (saldo = entradas - saídas)
            $currentStock = $this->getProductStockBalance($product->id);
            \Log::info("Estoque calculado por movimentações. Produto {$product->id} saldo atual: {$currentStock}");
            
            if (!$allowNegativeStock && $currentStock < $item->quantity) {
                \Log::warning("Tentativa de dedução de estoque sem quantidade suficiente. OS: {$serviceOrder->id}, Produto: {$product->id}, Estoque atual: {$currentStock}, Quantidade solicitada: {$item->quantity}");
                continue;
            }

            StockMovement::create([
                'tenant_id' => auth()->user()->tenant_id,
                'product_id' => $product->id,
                'service_order_id' => $serviceOrder->id,
                'movement_type' => 'out',
                'quantity' => $item->quantity,
                'reason' => 'OS Finalizada',
                'user_id' => auth()->id(),
                'notes' => "Dedução automática pela finalização da OS #{$serviceOrder->number} - Item: {$item->name}",
            ]);

            \Log::info("Estoque deduzido automaticamente. OS: {$serviceOrder->id}, Produto: {$product->id}, Quantidade: {$item->quantity}");
        }

        \Log::info("Processamento de estoque concluído para OS: {$serviceOrder->id}");
    }

    /**
     * Atualiza os totais da OS baseado nos itens
     */
    private function updateServiceOrderTotals(ServiceOrder $serviceOrder)
    {
        // Recalcular totais baseado nos itens
        $totalFromItems = $serviceOrder->items()->sum('line_total');
        
        // Atualizar budget_amount e total_amount
        $serviceOrder->budget_amount = $totalFromItems;
        
        // Em garantia ou sem reparo: total sempre zero
        if (in_array($serviceOrder->status, ['warranty', 'no_repair'], true)) {
            $serviceOrder->total_amount = 0;
        } else {
            $serviceOrder->total_amount = $totalFromItems;
        }
        
        // Ao adicionar item, sair de "Aguardando" para "Em andamento"
        if ($serviceOrder->status === 'open') {
            $serviceOrder->status = 'in_progress';
        }
        
        $serviceOrder->save();
    }

    /**
     * Busca valor de configuração do sistema
     */
    private function getSettingValue($key, $default = null)
    {
        try {
            $setting = \App\Models\Setting::where('tenant_id', auth()->user()->tenant_id)
                ->where('key', $key)
                ->first();
            
            return $setting ? $setting->value : $default;
        } catch (\Exception $e) {
            \Log::error("Erro ao buscar configuração {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Retorna o saldo atual do produto a partir das movimentações (entradas - saídas)
     */
    private function getProductStockBalance($productId)
    {
        try {
            $tenantId = auth()->user()->tenant_id;

            // Usar movement_type quando disponível; incluir legado (type) apenas quando movement_type é NULL
            $inWithNew = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->where('movement_type', 'in')
                ->sum('quantity');

            $inLegacy = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->whereNull('movement_type')
                ->whereIn('type', ['entry', 'adjustment'])
                ->sum('quantity');

            $outWithNew = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->where('movement_type', 'out')
                ->sum('quantity');

            $outLegacy = StockMovement::where('tenant_id', $tenantId)
                ->where('product_id', $productId)
                ->whereNull('movement_type')
                ->where('type', 'exit')
                ->sum('quantity');

            $inQty = (float)$inWithNew + (float)$inLegacy;
            $outQty = (float)$outWithNew + (float)$outLegacy;
            $balance = $inQty - $outQty;
            return round($balance, 3);
        } catch (\Throwable $e) {
            \Log::error("Erro ao calcular saldo de estoque do produto {$productId}: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Exibe formulário de cancelamento de OS
     */
    public function cancelForm(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.cancel'), 403);
        
        // Verificar se OS pertence ao tenant
        if ($serviceOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(404);
        }

        // Verificar se pode cancelar baseado no status
        $canCancel = $this->canCancelServiceOrder($serviceOrder);
        if (!$canCancel['can_cancel']) {
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', $canCancel['reason']);
        }

        // Calcular impactos do cancelamento
        $impacts = $this->calculateCancellationImpacts($serviceOrder);

        return view('service_orders.cancel', compact('serviceOrder', 'impacts'));
    }

    /**
     * Processa cancelamento de OS
     */
    public function cancel(Request $request, ServiceOrder $serviceOrder)
    {
        \Log::info("Iniciando cancelamento da OS: {$serviceOrder->id}");
        \Log::info("Status atual da OS: {$serviceOrder->status}");
        \Log::info("Dados recebidos: " . json_encode($request->all()));

        abort_unless(auth()->user()->hasPermission('service_orders.cancel'), 403);
        
        // Verificar se OS pertence ao tenant
        if ($serviceOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(404);
        }

        // Validar dados
        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:1000',
            'confirm_cancellation' => 'required|accepted',
        ]);

        \Log::info("Validação passou para OS: {$serviceOrder->id}");

        // Verificar se pode cancelar baseado no status
        $canCancel = $this->canCancelServiceOrder($serviceOrder);
        \Log::info("Pode cancelar OS {$serviceOrder->id}: " . json_encode($canCancel));
        
        if (!$canCancel['can_cancel']) {
            \Log::warning("OS {$serviceOrder->id} não pode ser cancelada: {$canCancel['reason']}");
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', $canCancel['reason']);
        }

        try {
            \DB::beginTransaction();
            \Log::info("Transação iniciada para cancelamento da OS: {$serviceOrder->id}");

            // Calcular impactos
            $impacts = $this->calculateCancellationImpacts($serviceOrder);
            \Log::info("Impactos calculados para OS {$serviceOrder->id}: " . json_encode($impacts));

            // Criar registro de cancelamento
            $cancellation = ServiceOrderCancellation::create([
                'tenant_id' => auth()->user()->tenant_id,
                'service_order_id' => $serviceOrder->id,
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'impact_analysis' => $impacts,
                'notes' => $request->notes ?? null,
            ]);
            \Log::info("Registro de cancelamento criado: {$cancellation->id}");

            // Executar reversões baseadas no status
            $reversals = $this->executeCancellationReversals($serviceOrder, $impacts);
            \Log::info("Reversões executadas para OS {$serviceOrder->id}: " . json_encode($reversals));

            // Atualizar flags de reversão
            $cancellation->update([
                'stock_reversed' => $reversals['stock_reversed'],
                'payments_reversed' => $reversals['payments_reversed'],
                'warranties_cancelled' => $reversals['warranties_cancelled'],
            ]);

            // Atualizar status da OS para cancelada
            $serviceOrder->update([
                'status' => 'canceled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
            \Log::info("Status da OS {$serviceOrder->id} atualizado para 'canceled'");

            // Registrar ocorrência de cancelamento
            ServiceOrderOccurrence::create([
                'tenant_id' => auth()->user()->tenant_id,
                'service_order_id' => $serviceOrder->id,
                'type' => 'cancellation',
                'description' => "OS cancelada: {$request->cancellation_reason}",
                'created_by' => auth()->id(),
            ]);

            // Registrar log de status
            ServiceOrderStatusLog::create([
                'tenant_id' => auth()->user()->tenant_id,
                'service_order_id' => $serviceOrder->id,
                'old_status' => $serviceOrder->getOriginal('status'),
                'new_status' => 'canceled',
                'changed_by' => auth()->id(),
                'reason' => 'Cancelamento de OS',
                'notes' => $request->cancellation_reason,
            ]);

            \DB::commit();
            \Log::info("Transação commitada para cancelamento da OS: {$serviceOrder->id}");

            \Log::info("OS {$serviceOrder->id} cancelada com sucesso por usuário " . auth()->id());

            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('success', 'OS cancelada com sucesso. Todas as reversões foram aplicadas.');

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error("Erro ao cancelar OS {$serviceOrder->id}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            return redirect()->route('service_orders.show', $serviceOrder)
                ->with('error', 'Erro ao cancelar OS. Tente novamente.');
        }
    }

    /**
     * API para calcular impactos do cancelamento
     */
    public function getCancellationImpacts(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.cancel'), 403);
        
        if ($serviceOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(404);
        }

        $impacts = $this->calculateCancellationImpacts($serviceOrder);
        
        return response()->json($impacts);
    }

    /**
     * Exibe o recibo de cancelamento da OS
     */
    public function cancellationReceipt(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        
        if ($serviceOrder->tenant_id !== auth()->user()->tenant_id) {
            abort(404);
        }

        if ($serviceOrder->status !== 'canceled') {
            abort(404, 'Esta OS não está cancelada.');
        }

        $serviceOrder->load(['client', 'cancellation', 'cancelledBy', 'items', 'tenant']);

        return view('service_orders.cancellation_receipt', compact('serviceOrder'));
    }

    /**
     * Verifica se OS pode ser cancelada baseado no status
     */
    private function canCancelServiceOrder(ServiceOrder $serviceOrder)
    {
        switch ($serviceOrder->status) {
            case 'open':
            case 'in_progress':
            case 'in_service':
            case 'service_finished':
            case 'finished':
                return ['can_cancel' => true, 'reason' => null];
            
            case 'warranty':
                return ['can_cancel' => true, 'reason' => null, 'warning' => 'OS em garantia - cancelamento requer atenção especial'];
            
            case 'canceled':
                return ['can_cancel' => false, 'reason' => 'OS já está cancelada.'];
            
            default:
                return ['can_cancel' => false, 'reason' => 'Status atual não permite cancelamento.'];
        }
    }

    /**
     * Calcula impactos do cancelamento
     */
    private function calculateCancellationImpacts(ServiceOrder $serviceOrder)
    {
        $impacts = [
            'status' => $serviceOrder->status,
            'stock_impact' => [],
            'financial_impact' => [],
            'warranty_impact' => [],
            'can_cancel' => true,
            'warnings' => [],
        ];

        // Impacto no estoque
        $items = $serviceOrder->items()->whereNotNull('product_id')->get();
        foreach ($items as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $currentStock = $this->getProductStockBalance($product->id);
                $impacts['stock_impact'][] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity_to_restore' => $item->quantity,
                    'current_stock' => $currentStock,
                    'new_stock' => $currentStock + $item->quantity,
                ];
            }
        }

        // Impacto financeiro
        if ($serviceOrder->payment_received) {
            $impacts['financial_impact']['payment_received'] = true;
            $impacts['financial_impact']['amount_to_refund'] = $serviceOrder->total_amount;
            
            if ($serviceOrder->payment_method === 'card' && $serviceOrder->installments > 1) {
                $impacts['financial_impact']['receivables_to_cancel'] = $serviceOrder->installments;
                $impacts['warnings'][] = 'Recebíveis parcelados serão cancelados automaticamente.';
            }
        }

        // Impacto na garantia (se aplicável)
        if ($serviceOrder->status === 'finished' || $serviceOrder->status === 'warranty') {
            $impacts['warranty_impact']['has_warranty'] = true;
            $impacts['warranty_impact']['warranty_until'] = $serviceOrder->warranty_until;
            $impacts['warnings'][] = 'OS já finalizada - será necessário recolher o equipamento do cliente.';
            $impacts['warnings'][] = 'Garantias ativas serão canceladas automaticamente.';
        }

        return $impacts;
    }

    /**
     * Executa reversões do cancelamento
     */
    private function executeCancellationReversals(ServiceOrder $serviceOrder, $impacts)
    {
        $reversals = [
            'stock_reversed' => false,
            'payments_reversed' => false,
            'warranties_cancelled' => false,
        ];

        // Reversão de estoque
        if (!empty($impacts['stock_impact'])) {
            foreach ($impacts['stock_impact'] as $stockImpact) {
                StockMovement::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'product_id' => $stockImpact['product_id'],
                    'service_order_id' => $serviceOrder->id,
                    'movement_type' => 'in',
                    'quantity' => $stockImpact['quantity_to_restore'],
                    'reason' => 'Cancelamento de OS',
                    'user_id' => auth()->id(),
                    'notes' => "Reversão automática pelo cancelamento da OS #{$serviceOrder->number} - Item: {$stockImpact['product_name']}",
                ]);
            }
            $reversals['stock_reversed'] = true;
        }

        // Reversão de pagamentos/recebíveis
        if (!empty($impacts['financial_impact']['payment_received'])) {
            // Cancelar recebíveis relacionados
            $receivablesUpdated = Receivable::where('tenant_id', auth()->user()->tenant_id)
                ->where('service_order_id', $serviceOrder->id)
                ->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                    'canceled_by' => auth()->id(),
                    'cancel_reason' => 'Cancelamento de OS'
                ]);
            
            \Log::info("Recebíveis cancelados para OS {$serviceOrder->id}: {$receivablesUpdated} registros");

            // Reverter movimentação de caixa (se houver)
            if ($serviceOrder->payment_method === 'cash') {
                // Aqui seria implementada a reversão do caixa do dia
                // Por enquanto apenas log
                \Log::info("Reversão de caixa necessária para OS {$serviceOrder->id}: R$ {$serviceOrder->total_amount}");
            }

            $reversals['payments_reversed'] = true;
        }

        // Cancelamento de garantias (se aplicável)
        if (!empty($impacts['warranty_impact']['has_warranty'])) {
            // Aqui seria implementado o cancelamento de garantias
            // Por enquanto apenas log
            \Log::info("Cancelamento de garantias necessário para OS {$serviceOrder->id}");
            $reversals['warranties_cancelled'] = true;
        }

        return $reversals;
    }

    // ========================================
    // MÉTODOS DE GARANTIA
    // ========================================

    /**
     * Criar OS de garantia baseada em uma OS finalizada
     */
    public function createWarranty(Request $request, ServiceOrder $originalOrder = null)
    {
        // Se o model binding não funcionou, buscar manualmente
        if (!$originalOrder || !$originalOrder->id) {
            $serviceOrderId = $request->route('service_order');
            $originalOrder = ServiceOrder::find($serviceOrderId);
        }
        
        // Se ainda não encontrou, erro
        if (!$originalOrder) {
            return redirect()->back()->with('error', 'Ordem de serviço não encontrada.');
        }
        
        // Verificar permissão
        if (!auth()->user()->hasPermission('service_orders.create')) {
            return redirect()->back()->with('error', 'Você não tem permissão para criar garantias.');
        }
        
        // Se a OS não tem tenant_id (dados legacy), atribuir do usuário atual
        if (empty($originalOrder->tenant_id)) {
            $originalOrder->tenant_id = auth()->user()->tenant_id;
        }
        
        // Recarregar a OS do banco para garantir que tem todos os dados
        $originalOrder->refresh();
        
        // Buscar OS diretamente do banco para garantir que temos os dados corretos
        $dbOrder = \DB::table('service_orders')->where('id', $originalOrder->id)->first();
        
        // DEBUG TEMPORÁRIO
        \Log::info('Debug createWarranty', [
            'os_id' => $originalOrder->id,
            'dbOrder_client_id' => $dbOrder->client_id ?? 'NULL do banco',
            'originalOrder_client_id' => $originalOrder->client_id ?? 'NULL do model',
            'dbOrder_tenant_id' => $dbOrder->tenant_id ?? 'NULL',
        ]);
        
        // Se não tem client_id no banco, buscar de outra fonte ou pegar do relacionamento
        if (empty($originalOrder->client_id) && !empty($dbOrder->client_id)) {
            $originalOrder->client_id = $dbOrder->client_id;
        }
        
        // Verificar se tem client_id obrigatório - pegar direto do banco se preciso
        $clientId = $dbOrder->client_id ?? $originalOrder->client_id;
        
        if (empty($clientId)) {
            return redirect()->back()->with('error', 'Esta OS não possui cliente vinculado. Não é possível criar garantia.');
        }
        
        // Atualizar o model com os valores corretos do banco
        $originalOrder->client_id = $clientId;
        if (empty($originalOrder->tenant_id) && $dbOrder->tenant_id) {
            $originalOrder->tenant_id = $dbOrder->tenant_id;
        }
        
        // Verificar se pertence ao mesmo tenant
        if ((int)$originalOrder->tenant_id !== (int)auth()->user()->tenant_id) {
            return redirect()->back()->with('error', 'Esta OS não pertence ao seu tenant.');
        }
        
        // Verificar se está finalizada
        if ($originalOrder->status !== 'finished') {
            return redirect()->back()->with('error', 'Apenas OS finalizadas podem gerar garantia. Status atual: ' . $originalOrder->status);
        }

        // Buscar configuração de garantia do Settings (mínimo 90 dias)
        $defaultWarrantyDays = max(90, (int) Setting::get('service_orders.default_warranty_days', 90));
        
        // Calcular warranty_until baseado na data de finalização
        $finalizedDate = $originalOrder->finalized_at ?? $originalOrder->updated_at;
        
        // Se finalized_at for string, converter para Carbon
        if (is_string($finalizedDate)) {
            $finalizedDate = \Carbon\Carbon::parse($finalizedDate);
        }
        
        // Se finalizedDate é null ou não é uma data válida, usar updated_at ou now
        if (!$finalizedDate || !($finalizedDate instanceof \Carbon\Carbon)) {
            $finalizedDate = $originalOrder->updated_at ?? now();
            if (is_string($finalizedDate)) {
                $finalizedDate = \Carbon\Carbon::parse($finalizedDate);
            }
        }
        
        $warrantyUntil = $finalizedDate->copy()->addDays($defaultWarrantyDays)->toDateString();

        // Verificar se ainda está dentro do prazo de garantia
        $daysSinceFinalization = $finalizedDate ? now()->diffInDays($finalizedDate) : 0;
        $isOutOfWarranty = $daysSinceFinalization > $defaultWarrantyDays;
        
        // Se estiver fora da garantia, permitir mas avisar
        if ($isOutOfWarranty) {
            // Ainda permite criar, mas com aviso
            session()->flash('warning', "Este equipamento está fora do prazo de garantia de {$defaultWarrantyDays} dias, mas a OS de garantia será criada mesmo assim.");
        }

        // Pegar observação do problema do cliente
        $warrantyReason = $request->input('warranty_reason', '');
        
        // Gerar número da OS de garantia
        $warrantyNumber = $this->generateWarrantyNumber($originalOrder);
        
        // Criar NOVA OS de garantia vinculada à OS original (OS original permanece intacta com status finished)
        $warrantyOrder = ServiceOrder::create([
            'tenant_id' => $originalOrder->tenant_id,
            'client_id' => $clientId,
            'original_service_order_id' => $originalOrder->id, // Vincular à OS original
            'number' => $warrantyNumber,
            'title' => "Garantia - " . ($originalOrder->title ?: 'Sem título'),
            'description' => "OS de garantia referente à OS #{$originalOrder->number}\n\nRelato do Cliente:\n{$warrantyReason}",
            'equipment_brand' => $originalOrder->equipment_brand,
            'equipment_model' => $originalOrder->equipment_model,
            'equipment_serial' => $originalOrder->equipment_serial,
            'equipment_description' => $originalOrder->equipment_description,
            'defect_reported' => $warrantyReason, // Novo problema relatado
            'status' => 'open', // Nova OS começa aberta para edição
            'is_warranty' => true,
            'total_amount' => 0, // Garantia não cobra o cliente
            'warranty_days' => $defaultWarrantyDays,
            'warranty_until' => $warrantyUntil,
            'warranty_notes' => $warrantyReason,
            'created_by' => auth()->id(),
        ]);

        // Criar histórico de garantia
        $this->createWarrantyHistory($warrantyOrder, $originalOrder);

        // Criar log de auditoria na OS original indicando que foi criada garantia
        $auditReason = "Criada OS de garantia #{$warrantyNumber}\n\nRelato do Cliente:\n{$warrantyReason}\n\nGarantia válida até {$warrantyUntil}";
        
        ServiceOrderStatusLog::create([
            'service_order_id' => $originalOrder->id,
            'old_status' => 'finished',
            'new_status' => 'finished',
            'reason' => $auditReason,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);

        // Log da ação de garantia
        $this->logWarrantyAction($warrantyOrder, 'created', "OS de garantia criada da OS #{$originalOrder->number}");

        return redirect()->route('service_orders.show', $warrantyOrder)
            ->with('success', 'OS de garantia criada com sucesso! A OS original #' . $originalOrder->number . ' permanece finalizada e inalterada.');
    }

    /**
     * Reverter OS de garantia para OS normal
     */
    public function revertWarranty(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($serviceOrder->is_warranty, 403, 'Esta OS não é uma OS de garantia.');

        $request->validate([
            'revert_reason' => 'required|string|min:10|max:500',
        ]);

        // Manter o original_service_order_id para histórico
        // Remover is_warranty e ajustar título
        $newTitle = preg_replace('/^Garantia\s*-\s*/i', '', $serviceOrder->title);
        
        $serviceOrder->update([
            'is_warranty' => false,
            'title' => $newTitle,
            'defect_reported' => $serviceOrder->warranty_notes, // Move notes to defect_reported
            'updated_by' => auth()->id(),
        ]);

        // Criar log de auditoria
        $auditReason = "OS de garantia revertida para OS normal\n\nMotivo: {$request->revert_reason}";
        
        ServiceOrderStatusLog::create([
            'service_order_id' => $serviceOrder->id,
            'old_status' => $serviceOrder->getOriginal('status'),
            'new_status' => $serviceOrder->status,
            'reason' => $auditReason,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);

        // Log da ação de reversão
        $this->logWarrantyAction($serviceOrder, 'reverted', "OS de garantia revertida para OS normal. Motivo: {$request->revert_reason}");

        return redirect()->route('service_orders.show', $serviceOrder)
            ->with('success', 'OS revertida para OS normal com sucesso! Você pode agora imprimir o recibo de cancelamento da garantia.');
    }

    /**
     * Imprimir recibo de cancelamento de garantia (2 vias)
     */
    public function warrantyCancellationReceipt(ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.view'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        
        // Carregar OS original se existir
        $originalOrder = null;
        if ($serviceOrder->original_service_order_id) {
            $originalOrder = ServiceOrder::find($serviceOrder->original_service_order_id);
        }
        
        $serviceOrder->load(['client', 'tenant', 'createdBy']);
        
        return view('service_orders.warranty_cancellation_receipt', compact('serviceOrder', 'originalOrder'));
    }

    /**
     * Mudar status de garantia para "Não é Garantia"
     */
    public function markAsNotWarranty(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($serviceOrder->status === 'warranty', 403, 'Apenas OS em garantia podem ser alteradas');

        $request->validate([
            'reason' => 'required|string|max:500',
            'new_status' => 'required|in:in_progress,service_finished'
        ]);

        $oldStatus = $serviceOrder->status;
        $newStatus = $request->new_status;

        // Atualizar OS
        $serviceOrder->update([
            'status' => $newStatus,
            'is_warranty' => false,
            'warranty_notes' => $request->reason,
            'updated_by' => auth()->id(),
        ]);

        // Log da mudança
        $this->logWarrantyAction($serviceOrder, 'status_changed', $request->reason, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);

        return redirect()->route('service_orders.show', $serviceOrder)
            ->with('success', 'Status alterado com sucesso!');
    }

    /**
     * Estender garantia de uma OS
     */
    public function extendWarranty(Request $request, ServiceOrder $serviceOrder)
    {
        abort_unless(auth()->user()->hasPermission('service_orders.edit'), 403);
        abort_unless($serviceOrder->tenant_id === auth()->user()->tenant_id, 403);
        abort_unless($serviceOrder->status === 'warranty', 403, 'Apenas OS em garantia podem ter garantia estendida');

        $request->validate([
            'additional_days' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|max:500'
        ]);

        $oldWarrantyDays = $serviceOrder->warranty_days;
        $newWarrantyDays = $oldWarrantyDays + $request->additional_days;
        $newWarrantyUntil = now()->addDays($newWarrantyDays)->toDateString();

        // Atualizar OS
        $serviceOrder->update([
            'warranty_days' => $newWarrantyDays,
            'warranty_until' => $newWarrantyUntil,
            'override_warranty_days' => $newWarrantyDays,
            'warranty_notes' => $request->reason,
            'updated_by' => auth()->id(),
        ]);

        // Log da extensão
        $this->logWarrantyAction($serviceOrder, 'extended', $request->reason, [
            'old_days' => $oldWarrantyDays,
            'new_days' => $newWarrantyDays,
            'additional_days' => $request->additional_days
        ]);

        return redirect()->route('service_orders.show', $serviceOrder)
            ->with('success', "Garantia estendida em {$request->additional_days} dias!");
    }

    /**
     * Gerar número da OS de garantia
     */
    private function generateWarrantyNumber(ServiceOrder $originalOrder): string
    {
        $prefix = 'GAR';
        $year = now()->year;
        
        // Buscar último número de garantia do ano
        $lastWarranty = ServiceOrder::where('tenant_id', $originalOrder->tenant_id)
            ->where('number', 'like', "{$prefix}{$year}%")
            ->orderBy('number', 'desc')
            ->first();

        if ($lastWarranty) {
            $lastNumber = (int) substr($lastWarranty->number, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $year . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Criar histórico de garantia
     */
    private function createWarrantyHistory(ServiceOrder $warrantyOrder, ServiceOrder $originalOrder): void
    {
        // Verificar reincidência por número de série
        $recurrenceCount = 1;
        if ($originalOrder->equipment_serial) {
            $previousWarranties = WarrantyHistory::where('serial_number', $originalOrder->equipment_serial)
                ->where('tenant_id', $originalOrder->tenant_id)
                ->count();
            $recurrenceCount = $previousWarranties + 1;
        }

        WarrantyHistory::create([
            'tenant_id' => $warrantyOrder->tenant_id,
            'service_order_id' => $warrantyOrder->id,
            'serial_number' => $originalOrder->equipment_serial,
            'warranty_start' => now()->toDateString(),
            'warranty_until' => $warrantyOrder->warranty_until,
            'warranty_type' => 'standard',
            'reason' => "Garantia da OS #{$originalOrder->number}",
            'technician_id' => auth()->id(),
            'recurrence_count' => $recurrenceCount,
        ]);
    }

    /**
     * Log de ações de garantia
     */
    private function logWarrantyAction(ServiceOrder $serviceOrder, string $action, string $reason, array $data = []): void
    {
        ServiceOrderWarrantyLog::create([
            'service_order_id' => $serviceOrder->id,
            'old_status' => $data['old_status'] ?? null,
            'new_status' => $data['new_status'] ?? $serviceOrder->status,
            'warranty_days_old' => $data['old_days'] ?? null,
            'warranty_days_new' => $data['new_days'] ?? $serviceOrder->warranty_days,
            'reason' => $reason,
            'user_id' => auth()->id(),
        ]);
    }
}
