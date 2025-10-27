<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payables.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $query = Payable::where('tenant_id', $tenantId);

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

        // Somatórios com filtros
        $base = Payable::where('tenant_id', $tenantId);
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

        $payables = $query->paginate($perPage)->appends($request->query());
        return view('payables.index', compact('payables', 'totalOpen', 'totalPaid', 'totalOverdue', 'status', 'dateFrom', 'dateTo', 'overdue'));
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('payables.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->orderBy('name')->get(['id','name']);
        return view('payables.create', compact('suppliers'));
    }

    public function show(Payable $payable)
    {
        abort_unless(auth()->user()->hasPermission('payables.view'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Carregar relacionamentos para auditoria
        $payable->load(['supplier', 'createdBy', 'updatedBy', 'paidBy', 'reversedBy', 'canceledBy', 'deletedBy']);
        
        return view('payables.show', compact('payable'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payables.create'), 403);
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'payment_method' => 'nullable|in:cash,card,pix',
            'document_number' => 'nullable|string|max:100',
        ]);

        // Escopo do fornecedor no tenant e normalização de fornecedor
        $supplier = null;
        if (!empty($validated['supplier_id'])) {
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            abort_unless($supplier->tenant_id === $tenantId, 403);
        }
        if (!$supplier && empty($validated['supplier_name'])) {
            return back()->withErrors(['supplier_name' => 'Informe um fornecedor (selecionado) ou um nome avulso.'])->withInput();
        }

        Payable::create([
            'tenant_id' => $tenantId,
            'supplier_id' => $supplier?->id,
            'supplier_name' => $validated['supplier_name'] ?? ($supplier?->name ?? null),
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'],
            'payment_method' => $validated['payment_method'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('payables.index')->with('success', 'Conta a pagar lançada.');
    }

    public function edit(Payable $payable)
    {
        abort_unless(auth()->user()->hasPermission('payables.edit'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear edição de payables pagos
        if ($payable->status === 'paid') {
            return back()->with('error', 'Payables já pagos não podem ser editados. Use a função de estorno se necessário.');
        }
        
        return view('payables.edit', compact('payable'));
    }

    public function update(Request $request, Payable $payable)
    {
        abort_unless(auth()->user()->hasPermission('payables.edit'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear edição de payables pagos
        if ($payable->status === 'paid') {
            return back()->with('error', 'Payables já pagos não podem ser editados. Use a função de estorno se necessário.');
        }

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'due_date' => 'required|date',
            'status' => 'required|in:open,partial,paid,canceled',
            'payment_method' => 'nullable|string|max:50',
            'document_number' => 'nullable|string|max:100',
        ]);

        if ($validated['status'] === 'paid' && !$payable->paid_at) {
            $payable->paid_at = now();
            $payable->paid_by = auth()->id();
        }

        $payable->update(array_merge($validated, ['updated_by' => auth()->id()]));
        return redirect()->route('payables.index')->with('success', 'Conta a pagar atualizada.');
    }

    public function cancel(Payable $payable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payables.edit'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear cancelamento de payables pagos
        if ($payable->status === 'paid') {
            return back()->with('error', 'Payables já pagos não podem ser cancelados. Use a função de estorno se necessário.');
        }
        
        // Bloquear cancelamento de payables já cancelados
        if ($payable->status === 'canceled') {
            return back()->with('error', 'Esta conta já está cancelada.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|min:10|max:500',
        ]);
        
        $payable->update([
            'status' => 'canceled',
            'updated_by' => auth()->id(),
            'cancel_reason' => $validated['cancel_reason'],
            'canceled_at' => now(),
            'canceled_by' => auth()->id(),
        ]);
        
        return redirect()->route('payables.index')->with('success', 'Conta a pagar cancelada com sucesso.');
    }

    public function pay(Payable $payable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payables.pay'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);

        $data = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'paid_at' => 'nullable|date',
        ]);

        $payable->status = 'paid';
        $payable->payment_method = $data['payment_method'] ?? $payable->payment_method;
        $payable->paid_at = isset($data['paid_at']) ? $data['paid_at'] : now();
        $payable->paid_by = auth()->id();
        $payable->updated_by = auth()->id();
        $payable->save();

        return back()->with('success', 'Conta baixada como paga.');
    }

    public function reverse(Payable $payable, Request $request)
    {
        abort_unless(auth()->user()->hasPermission('payables.create'), 403);
        abort_unless($payable->tenant_id === auth()->user()->tenant_id, 403);
        
        // Bloquear estorno de estornos automáticos
        if (str_contains($payable->supplier_name, 'Estorno Financeiro') || 
            str_contains($payable->description, '⚡ Estorno Automático')) {
            return back()->with('error', 'Estornos automáticos de pedidos não podem ser estornados.');
        }
        
        // Bloquear estorno de payables não pagos
        if ($payable->status !== 'paid') {
            return back()->with('error', 'Apenas pagamentos já realizados podem ser estornados.');
        }

        $validated = $request->validate([
            'reverse_reason' => 'required|string|min:10|max:500',
        ]);

        // Registrar estorno no payable original
        $payable->status = 'reversed';
        $payable->reversed_by = auth()->id();
        $payable->reversed_at = now();
        $payable->reverse_reason = $validated['reverse_reason'];
        $payable->updated_by = auth()->id();
        $payable->save();

        // Criar estorno como novo payable
        Payable::create([
            'tenant_id' => $payable->tenant_id,
            'supplier_id' => null,
            'supplier_name' => 'Estorno Financeiro',
            'description' => '🔄 Estorno Manual - ' . $payable->supplier_name . ' (ID: ' . $payable->id . '): ' . $validated['reverse_reason'],
            'amount' => -(float)$payable->amount,
            'due_date' => now()->toDateString(),
            'payment_method' => $payable->payment_method,
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('payables.index')->with('success', 'Estorno criado com sucesso. O pagamento original foi preservado para auditoria.');
    }
}


