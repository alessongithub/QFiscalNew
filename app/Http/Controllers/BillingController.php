<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function invoicesIndex(Request $request)
    {
        $tenant = auth()->user()->tenant;
        abort_unless($tenant, 403);

        $payments = Payment::with('invoice')
            ->whereHas('invoice', function($q) use ($tenant){ $q->where('tenant_id', $tenant->id); })
            ->where('status','approved')
            ->orderByDesc('paid_at')
            ->paginate(15);

        $subscriptionPayments = SubscriptionPayment::where('tenant_id', $tenant->id)
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'subs_page');

        return view('billing.invoices.index', compact('payments','subscriptionPayments'));
    }
}




