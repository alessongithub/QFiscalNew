<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receivable;
use App\Models\Order;
use App\Models\Payable;
use App\Models\CalendarEvent;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('calendar.view'), 403);
        $tenantId = auth()->user()->tenant_id;

        $month = (int) ($request->get('month', now()->month));
        $year = (int) ($request->get('year', now()->year));
        $status = $request->get('status'); // open|paid|partial|canceled|null
        $orderNumber = trim((string) $request->get('order'));
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $rq = Receivable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()]);
        if ($status) {
            $rq->where('status', $status);
        } else {
            // padrão: mostrar a receber
            $rq->whereIn('status', ['open', 'pending']);
        }
        if ($orderNumber !== '') {
            $order = Order::where('tenant_id', $tenantId)->where('number', $orderNumber)->first();
            if ($order) {
                $rq->where('order_id', $order->id);
            } else {
                $rq->where('description', 'like', 'Pedido '.$orderNumber.'%');
            }
        }
        $receivables = $rq->get()->groupBy(function ($r) { return \Carbon\Carbon::parse($r->due_date)->format('Y-m-d'); });
        $payables = Payable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(function ($p) { return \Carbon\Carbon::parse($p->due_date)->format('Y-m-d'); });
        $events = CalendarEvent::where('tenant_id', $tenantId)
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('start_date')
            ->get()
            ->groupBy('start_date');

        return view('calendar.index', compact('month','year','start','end','receivables','payables','events','status','orderNumber'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('calendar.create'), 403);
        $tenantId = auth()->user()->tenant_id;
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'notes' => 'nullable|string',
        ]);
        $validated['tenant_id'] = $tenantId;
        CalendarEvent::create($validated);
        return back()->with('success', 'Evento adicionado ao calendário.');
    }

    public function destroy(CalendarEvent $event)
    {
        abort_unless(auth()->user()->hasPermission('calendar.delete'), 403);
        abort_unless($event->tenant_id === auth()->user()->tenant_id, 403);
        $event->delete();
        return back()->with('success', 'Evento removido.');
    }
}


