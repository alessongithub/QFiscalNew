<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Client;
use App\Models\Receivable;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receipts.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $q = Receipt::where('tenant_id', $tenantId)->with('client');

        if ($s = $request->get('search')) {
            $q->where(function ($qq) use ($s) {
                $qq->where('number', 'like', "%{$s}%")
                   ->orWhere('description', 'like', "%{$s}%")
                   ->orWhereHas('client', fn($qc) => $qc->where('name','like',"%{$s}%"));
            });
        }
        if ($st = $request->get('status')) { $q->where('status', $st); }
        if ($clientId = $request->get('client_id')) { $q->where('client_id', $clientId); }
        if ($from = $request->get('date_from')) { $q->whereDate('issue_date', '>=', $from); }
        if ($to = $request->get('date_to')) { $q->whereDate('issue_date', '<=', $to); }

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        if (!in_array($direction, ['asc','desc'], true)) { $direction = 'desc'; }
        $allowed = ['issue_date','number','amount','created_at'];
        if (!in_array($sort, $allowed, true)) { $sort = 'created_at'; }
        if ($sort === 'number' && in_array($direction, ['asc','desc'])) {
            $q->orderByRaw('CAST(number AS UNSIGNED) ' . strtoupper($direction));
        } else {
            $q->orderBy($sort, $direction);
        }

        $perPage = (int) $request->get('per_page', 12);
        $perPage = max(5, min(200, $perPage));
        $receipts = $q->paginate($perPage)->appends($request->query());

        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get(['id','name']);
        return view('receipts.index', compact('receipts','clients','sort','direction'));
    }

    private function generateNumber(int $tenantId): string
    {
        $last = Receipt::where('tenant_id',$tenantId)->orderByRaw('CAST(number AS UNSIGNED) DESC')->first();
        $n=0; if ($last && is_numeric($last->number)) { $n=(int)$last->number; }
        return str_pad((string)($n+1), 6, '0', STR_PAD_LEFT);
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('receipts.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $clients = Client::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('receipts.create', compact('clients'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('receipts.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'number' => 'nullable|string|max:30',
            'issue_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);
        $client = Client::findOrFail($v['client_id']);
        abort_unless($client->tenant_id === $tenantId, 403);

        $number = $v['number'] ?? $this->generateNumber($tenantId);
        $receipt = Receipt::create([
            'tenant_id'=>$tenantId,
            'client_id'=>$v['client_id'],
            'number'=>$number,
            'issue_date'=>$v['issue_date'],
            'description'=>$v['description'],
            'amount'=>$v['amount'],
            'notes'=>$v['notes'] ?? null,
            'status'=>'issued',
        ]);
        // Integração com Contas a Receber (entra no caixa do dia)
        $rec = Receivable::create([
            'tenant_id' => $tenantId,
            'client_id' => $v['client_id'],
            'service_order_id' => null,
            'description' => 'Recibo #'.$number.' - '.$v['description'],
            'amount' => $v['amount'],
            'due_date' => $v['issue_date'],
            'status' => 'paid',
            'received_at' => $v['issue_date'],
            'payment_method' => null,
            'document_number' => 'REC '.$number,
        ]);
        $receipt->receivable_id = $rec->id;
        $receipt->save();
        return redirect()->route('receipts.index')->with('success','Recibo emitido.');
    }

    public function edit(Receipt $receipt)
    {
        abort_unless(auth()->user()->hasPermission('receipts.edit'), 403);
        abort_unless($receipt->tenant_id === auth()->user()->tenant_id, 403);
        $clients = Client::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->get();
        return view('receipts.edit', compact('receipt','clients'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        abort_unless(auth()->user()->hasPermission('receipts.edit'), 403);
        abort_unless($receipt->tenant_id === auth()->user()->tenant_id, 403);
        $v = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
            'status' => 'required|in:issued,canceled',
        ]);
        $client = Client::findOrFail($v['client_id']);
        abort_unless($client->tenant_id === auth()->user()->tenant_id, 403);
        $receipt->update($v);
        // Sincroniza Contas a Receber vinculado ao recibo
        if ($receipt->receivable_id) {
            $r = Receivable::where('tenant_id', auth()->user()->tenant_id)->find($receipt->receivable_id);
            if ($r) {
                if ($v['status'] === 'canceled') {
                    $r->update([
                        'status' => 'canceled',
                        'received_at' => null,
                        'amount' => $v['amount'],
                        'due_date' => $v['issue_date'],
                        'description' => 'Recibo cancelado #'.$receipt->number.' - '.$v['description'],
                    ]);
                } else { // issued
                    $r->update([
                        'status' => 'paid',
                        'received_at' => $v['issue_date'],
                        'amount' => $v['amount'],
                        'due_date' => $v['issue_date'],
                        'description' => 'Recibo #'.$receipt->number.' - '.$v['description'],
                    ]);
                }
            }
        } else {
            // Se não existe, cria (migração de dados antigos)
            if ($v['status'] === 'issued') {
                $new = Receivable::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'client_id' => $v['client_id'],
                    'service_order_id' => null,
                    'description' => 'Recibo #'.$receipt->number.' - '.$v['description'],
                    'amount' => $v['amount'],
                    'due_date' => $v['issue_date'],
                    'status' => 'paid',
                    'received_at' => $v['issue_date'],
                    'payment_method' => null,
                    'document_number' => 'REC '.$receipt->number,
                ]);
                $receipt->update(['receivable_id' => $new->id]);
            }
        }
        return back()->with('success','Recibo atualizado.');
    }

    public function destroy(Request $request, Receipt $receipt)
    {
        \Log::info('ReceiptController::destroy iniciado', [
            'receipt_id' => $receipt->id,
            'receipt_number' => $receipt->number,
            'user_id' => auth()->user()->id,
            'user_name' => auth()->user()->name,
            'request_data' => $request->all()
        ]);

        abort_unless(auth()->user()->hasPermission('receipts.delete'), 403);
        \Log::info('Permissão receipts.delete verificada com sucesso');
        
        abort_unless($receipt->tenant_id === auth()->user()->tenant_id, 403);
        \Log::info('Tenant_id verificado com sucesso', [
            'receipt_tenant_id' => $receipt->tenant_id,
            'user_tenant_id' => auth()->user()->tenant_id
        ]);
        
        // Verificar se já está cancelado
        if ($receipt->status === 'canceled') {
            \Log::warning('Tentativa de cancelar recibo já cancelado', [
                'receipt_id' => $receipt->id,
                'current_status' => $receipt->status
            ]);
            return back()->with('error', 'Este recibo já foi cancelado.');
        }
        
        \Log::info('Iniciando validação do request');
        $v = $request->validate([
            'cancel_reason' => 'required|string|min:10|max:500',
        ]);
        \Log::info('Validação do request concluída', ['validated_data' => $v]);
        
        \Log::info('Iniciando atualização do recibo');
        // Cancelar o recibo ao invés de deletar
        $receipt->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'canceled_by' => auth()->user()->name,
            'cancel_reason' => $v['cancel_reason'],
        ]);
        \Log::info('Recibo atualizado com sucesso', [
            'receipt_id' => $receipt->id,
            'new_status' => 'canceled',
            'cancel_reason' => $v['cancel_reason']
        ]);
        
        // Cancela o título vinculado (mantém histórico do caixa)
        if ($receipt->receivable_id) {
            \Log::info('Cancelando título vinculado', ['receivable_id' => $receipt->receivable_id]);
            Receivable::where('tenant_id', auth()->user()->tenant_id)
                ->where('id', $receipt->receivable_id)
                ->update(['status' => 'canceled', 'received_at' => null]);
            \Log::info('Título vinculado cancelado com sucesso');
        } else {
            \Log::info('Nenhum título vinculado encontrado');
        }
        
        \Log::info('ReceiptController::destroy concluído com sucesso');
        return redirect()->route('receipts.index')->with('success', 'Recibo cancelado com sucesso. Esta ação não pode ser desfeita.');
    }

    public function show(Receipt $receipt)
    {
        abort_unless(auth()->user()->hasPermission('receipts.view'), 403);
        abort_unless($receipt->tenant_id === auth()->user()->tenant_id, 403);
        $receipt->load('client', 'tenant');
        return view('receipts.show', compact('receipt'));
    }

    public function print(Receipt $receipt)
    {
        abort_unless(auth()->user()->hasPermission('receipts.print'), 403);
        abort_unless($receipt->tenant_id === auth()->user()->tenant_id, 403);
        $receipt->load('client','tenant');
        return view('receipts.print', compact('receipt'));
    }
}


