<?php

namespace App\Http\Controllers;

use App\Models\DailyCash;
use App\Models\Receivable;
use App\Models\Payable;
use App\Models\Receipt;
use Illuminate\Http\Request;

class DailyCashController extends Controller
{
    public function show(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('cash.view') || auth()->user()->is_admin, 403);
        $tenantId = auth()->user()->tenant_id;
        $date = $request->get('date', now()->toDateString());

        $received = Receivable::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereDate('received_at', $date)
            ->sum('amount');

        $paid = Payable::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereDate('paid_at', $date)
            ->sum('amount');

        // Sangrias (apenas normais)
        $withdrawalsTotal = \App\Models\CashWithdrawal::where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->where('type', 'normal')
            ->sum('amount');

        $net = (float) $received - (float) $paid - (float) $withdrawalsTotal;
        $closed = DailyCash::where('tenant_id', $tenantId)->whereDate('date', $date)->first();

        // Movimentações detalhadas
        $receiptsPaid = Receivable::with('client')
            ->where('tenant_id', $tenantId)
            ->where('status','paid')
            ->whereDate('received_at', $date)
            ->get(['id','client_id','description','amount','received_at']);

        $payablesPaid = Payable::where('tenant_id', $tenantId)
            ->where('status','paid')
            ->whereDate('paid_at', $date)
            ->get(['id','supplier_name','description','amount','paid_at']);

        $withdrawals = \App\Models\CashWithdrawal::where('tenant_id', $tenantId)
            ->whereDate('date', $date)->orderByDesc('id')->get();

        return view('cash.show', compact('date', 'received', 'paid', 'net', 'closed', 'receiptsPaid', 'payablesPaid', 'withdrawals', 'withdrawalsTotal'));
    }

    public function close(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('cash.close') || auth()->user()->is_admin, 403);
        $tenantId = auth()->user()->tenant_id;
        $date = $request->validate(['date' => 'required|date'])['date'];

        $received = Receivable::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereDate('received_at', $date)
            ->sum('amount');

        $paid = Payable::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereDate('paid_at', $date)
            ->sum('amount');

        $net = (float) $received - (float) $paid;

        $record = DailyCash::updateOrCreate(
            ['tenant_id' => $tenantId, 'date' => $date],
            [
                'status' => 'closed',
                'total_received' => $received,
                'total_paid' => $paid,
                'net_total' => $net,
                'current_balance' => $net - \App\Models\CashWithdrawal::where('tenant_id', $tenantId)
                    ->whereDate('date', $date)
                    ->where('type', 'normal')
                    ->sum('amount'),
                'closed_by' => auth()->id(),
                'closed_at' => now(),
            ]
        );

        return redirect()->route('cash.show', ['date' => $date])->with('success', 'Caixa do dia fechado.');
    }
    
    /**
     * Verifica se o caixa está fechado para uma data específica
     */
    public static function isCashClosed($tenantId, $date): bool
    {
        $dailyCash = DailyCash::where('tenant_id', $tenantId)
            ->whereDate('date', $date)
            ->first();
            
        return $dailyCash ? $dailyCash->isClosed() : false;
    }
}


