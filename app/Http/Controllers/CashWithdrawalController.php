<?php

namespace App\Http\Controllers;

use App\Models\CashWithdrawal;
use Illuminate\Http\Request;

class CashWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.view') || auth()->user()->is_admin, 403);
        $tenantId = auth()->user()->tenant_id;
        
        // Filtros
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $sort = $request->get('sort', 'date');
        $direction = $request->get('direction', 'desc');
        $perPage = $request->get('per_page', 20);
        
        $query = CashWithdrawal::where('tenant_id', $tenantId);
        
        // Filtro por busca (motivo)
        if ($search) {
            $query->where('reason', 'like', "%{$search}%");
        }
        
        // Filtro por período
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }
        
        // Ordenação
        $allowedSorts = ['date', 'amount', 'reason', 'created_at'];
        $sort = in_array($sort, $allowedSorts) ? $sort : 'date';
        $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'desc';
        
        $query->orderBy($sort, $direction);
        
        $withdrawals = $query->paginate($perPage)->appends($request->query());
        
        return view('cash.withdrawals.index', compact('withdrawals', 'search', 'dateFrom', 'dateTo', 'sort', 'direction', 'perPage'));
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.create') || auth()->user()->is_admin, 403);
        $tenantId = auth()->user()->tenant_id;
        $date = $request->get('date', now()->toDateString());
        
        // Verificar se o caixa está aberto
        $dailyCash = \App\Models\DailyCash::where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->first();
            
        if ($dailyCash && $dailyCash->isClosed()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível registrar sangrias.');
        }
        
        return view('cash.withdrawals.create', compact('date'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.create') || auth()->user()->is_admin, 403);
        $tenantId = auth()->user()->tenant_id;
        $v = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ], [
            'date.before_or_equal' => '⚠️ Não é possível registrar sangrias em datas futuras.',
            'amount.min' => '⚠️ O valor deve ser maior que zero.',
            'reason.required' => '⚠️ O motivo da sangria é obrigatório.',
        ]);
        
        // Verificar se o caixa está aberto
        $dailyCash = \App\Models\DailyCash::where('tenant_id', $tenantId)
            ->whereDate('date', $v['date'])
            ->first();
            
        if ($dailyCash && $dailyCash->isClosed()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível registrar sangrias.');
        }
        
        $withdrawal = CashWithdrawal::create([
            'tenant_id' => $tenantId,
            'date' => $v['date'],
            'amount' => $v['amount'],
            'reason' => $v['reason'],
            'type' => 'normal',
            'created_by' => auth()->id(),
        ]);
        
        // Atualizar saldo do caixa
        if ($dailyCash) {
            $dailyCash->updateCurrentBalance();
        }
        
        return redirect()->route('cash_withdrawals.index')->with('success', 'Sangria registrada.');
    }

    public function edit(CashWithdrawal $cashWithdrawal)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.edit') || auth()->user()->is_admin, 403);
        abort_unless($cashWithdrawal->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se pode ser modificada
        if (!$cashWithdrawal->canBeModified()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível editar sangrias.');
        }
        
        return view('cash.withdrawals.edit', ['withdrawal' => $cashWithdrawal, 'date' => $cashWithdrawal->date->toDateString()]);
    }

    public function update(Request $request, CashWithdrawal $cashWithdrawal)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.edit') || auth()->user()->is_admin, 403);
        abort_unless($cashWithdrawal->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se pode ser modificada
        if (!$cashWithdrawal->canBeModified()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível editar sangrias.');
        }
        
        $v = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ], [
            'date.before_or_equal' => '⚠️ Não é possível registrar sangrias em datas futuras.',
            'amount.min' => '⚠️ O valor deve ser maior que zero.',
            'reason.required' => '⚠️ O motivo da sangria é obrigatório.',
        ]);
        
        $oldAmount = $cashWithdrawal->amount;
        $cashWithdrawal->update([
            ...$v,
            'updated_by' => auth()->id(),
        ]);
        
        // Atualizar saldo do caixa se o valor mudou
        if ($oldAmount != $v['amount']) {
            $dailyCash = \App\Models\DailyCash::where('tenant_id', $cashWithdrawal->tenant_id)
                ->whereDate('date', $v['date'])
                ->first();
            if ($dailyCash) {
                $dailyCash->updateCurrentBalance();
            }
        }
        
        return redirect()->route('cash_withdrawals.index')->with('success', 'Sangria atualizada.');
    }

    public function destroy(CashWithdrawal $cashWithdrawal)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.delete') || auth()->user()->is_admin, 403);
        abort_unless($cashWithdrawal->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se pode ser modificada
        if (!$cashWithdrawal->canBeModified()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível excluir sangrias.');
        }
        
        $date = $cashWithdrawal->date->toDateString();
        $cashWithdrawal->delete();
        
        // Atualizar saldo do caixa
        $dailyCash = \App\Models\DailyCash::where('tenant_id', $cashWithdrawal->tenant_id)
            ->whereDate('date', $date)
            ->first();
        if ($dailyCash) {
            $dailyCash->updateCurrentBalance();
        }
        
        return redirect()->route('cash_withdrawals.index')->with('success', 'Sangria excluída.');
    }
    
    /**
     * Criar estorno de sangria
     */
    public function reverse(Request $request, CashWithdrawal $cashWithdrawal)
    {
        abort_unless(auth()->user()->hasPermission('cash.withdraw.create') || auth()->user()->is_admin, 403);
        abort_unless($cashWithdrawal->tenant_id === auth()->user()->tenant_id, 403);
        
        // Verificar se o caixa está aberto
        $dailyCash = \App\Models\DailyCash::where('tenant_id', $cashWithdrawal->tenant_id)
            ->whereDate('date', $cashWithdrawal->date)
            ->first();
            
        if ($dailyCash && $dailyCash->isClosed()) {
            return redirect()->route('cash_withdrawals.index')
                ->with('error', '⚠️ O caixa desta data já foi fechado. Não é possível criar estornos.');
        }
        
        $reason = $request->validate(['reason' => 'nullable|string|max:255'])['reason'] ?? null;
        
        $reversal = $cashWithdrawal->createReversal($reason);
        
        // Atualizar saldo do caixa
        if ($dailyCash) {
            $dailyCash->updateCurrentBalance();
        }
        
        return redirect()->route('cash_withdrawals.index')->with('success', 'Estorno de sangria criado.');
    }
}

?>


