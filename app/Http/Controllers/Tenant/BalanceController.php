<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTransfer;
use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantTransferRequested;

class BalanceController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;

        $balances = TenantBalance::forTenant($tenant->id)
            ->with('receivable')
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalAvailable = TenantBalance::forTenant($tenant->id)->available()->sum('net_amount');
        $totalPending = TenantBalance::forTenant($tenant->id)->pending()->sum('net_amount');
        $totalTransferred = TenantBalance::forTenant($tenant->id)
            ->where('status', 'transferred')
            ->sum('net_amount');

        return view('tenant.balance.index', compact(
            'balances', 'totalAvailable', 'totalPending', 'totalTransferred'
        ));
    }

    public function requestTransfer(Request $request)
    {
        $data = $request->validate([
            'balance_id' => 'required|exists:tenant_balances,id',
        ]);

        $tenant = auth()->user()->tenant;
        $balance = TenantBalance::findOrFail($data['balance_id']);

        if ($balance->tenant_id !== $tenant->id) {
            abort(403);
        }

        if ($balance->status !== 'available') {
            return back()->withErrors(['transfer' => 'Este saldo não está disponível para transferência.']);
        }

        $settings = TenantTransferSetting::where('tenant_id', $tenant->id)->first();
        if (!$settings || (!$settings->pix_key && !$settings->account)) {
            return back()->withErrors(['transfer' => 'Configure uma conta bancária ou chave PIX antes de solicitar transferência.']);
        }

        $balance->requestTransfer();
        dispatch(new ProcessTransfer($balance->id));

        // Notificar admin por e-mail
        try {
            $to = (string) (\App\Models\Setting::getGlobal('admin.request_email', 'solicitacao@qfiscal.com.br'));
            if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
                Mail::to($to)->send(new TenantTransferRequested($balance, $settings));
            }
        } catch (\Throwable $e) {
            \Log::error('Falha ao enviar e-mail de solicitação de transferência', [
                'balance_id' => $balance->id,
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Transferência solicitada. Processando...');
    }
}


