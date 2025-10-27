<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PartnerDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('partner')->user();
        $partnerId = $user->partner_id;
        $tenantsCount = Tenant::where('partner_id', $partnerId)->count();
        $invoicesCount = Invoice::where('partner_id', $partnerId)->count();
        $paymentsApproved = Payment::where('partner_id', $partnerId)->where('status','approved')->sum('amount');
        $applicationFees = Payment::where('partner_id', $partnerId)->where('status','approved')->sum('application_fee_amount');

        $q = trim((string) $request->get('q', ''));
        $tenants = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->when($q !== '', function($query) use ($q) {
                $query->where(function($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('fantasy_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('cnpj', 'like', "%".preg_replace('/[^0-9]/','',$q)."%")
                        ->orWhere('city', 'like', "%{$q}%")
                        ->orWhere('state', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Próximos vencimentos (com base no tenant.plan_expires_at)
        $upcomingExpirations = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->whereNotNull('plan_expires_at')
            ->orderBy('plan_expires_at')
            ->whereDate('plan_expires_at', '>=', now()->toDateString())
            ->limit(5)
            ->get();

        // Contas em atraso (plan_expires_at no passado)
        $overdueExpirations = Tenant::with(['plan'])
            ->where('partner_id', $partnerId)
            ->whereNotNull('plan_expires_at')
            ->whereDate('plan_expires_at', '<', now()->toDateString())
            ->orderBy('plan_expires_at')
            ->limit(5)
            ->get();

        // Últimos pagamentos aprovados
        $recentPayments = Payment::with(['invoice.tenant'])
            ->where('partner_id', $partnerId)
            ->where('status','approved')
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        return view('partner.dashboard', compact('tenantsCount','invoicesCount','paymentsApproved','applicationFees','tenants','q','upcomingExpirations','overdueExpirations','recentPayments'));
    }
}


