<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function index(Request $request)
    {
        $partnerId = auth('partner')->id() ? auth('partner')->user()->partner_id : null;

        $status = $request->get('status'); // open, paid, canceled, pending
        $due = $request->get('due'); // upcoming|overdue|all
        $dateFrom = $request->get('from');
        $dateTo = $request->get('to');

        $query = Invoice::with(['tenant'])
            ->where('partner_id', $partnerId);

        if ($status) {
            $query->where('status', $status);
        }

        if ($due === 'upcoming') {
            $query->whereDate('due_date', '>=', now()->toDateString());
        } elseif ($due === 'overdue') {
            $query->whereNotNull('due_date')->whereDate('due_date', '<', now()->toDateString());
        }

        if ($dateFrom) {
            $query->whereDate('due_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('due_date', '<=', $dateTo);
        }

        $invoices = $query->orderBy('due_date')->paginate(15)->withQueryString();

        return view('partner.invoices.index', compact('invoices','status','due','dateFrom','dateTo'));
    }
}



