<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        $partnerId = auth('partner')->id() ? auth('partner')->user()->partner_id : null;

        $status = $request->get('status');
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        $payments = Payment::with(['invoice.tenant'])
            ->where('partner_id', $partnerId)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($dateFrom, fn($q) => $q->whereDate('paid_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('paid_at', '<=', $dateTo))
            ->orderByDesc('paid_at')
            ->paginate(15)
            ->withQueryString();

        return view('partner.payments.index', compact('payments','status','dateFrom','dateTo'));
    }
}



