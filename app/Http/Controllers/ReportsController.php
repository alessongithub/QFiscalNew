<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Receivable;
use App\Models\Payable;
use App\Models\ServiceOrder;
use App\Models\Order;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Category;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('reports.view'), 403);
        $tenantId = auth()->user()->tenant_id;

        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfMonth();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now()->endOfMonth();

        $receivables = Receivable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->get();
        $payables = Payable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->get();
        $serviceOrders = ServiceOrder::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();
        $orders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        $recSummary = [
            'open' => (float) $receivables->where('status','open')->sum('amount'),
            'paid' => (float) $receivables->where('status','paid')->sum('amount'),
            'overdue' => (float) $receivables->filter(fn($r)=>$r->status!=='paid' && Carbon::parse($r->due_date)->isPast())->sum('amount'),
        ];
        $paySummary = [
            'open' => (float) $payables->where('status','open')->sum('amount'),
            'paid' => (float) $payables->where('status','paid')->sum('amount'),
            'overdue' => (float) $payables->filter(fn($p)=>$p->status!=='paid' && Carbon::parse($p->due_date)->isPast())->sum('amount'),
        ];
        $osSummary = [
            'open' => (int) $serviceOrders->where('status','open')->count(),
            'in_progress' => (int) $serviceOrders->where('status','in_progress')->count(),
            'finished' => (int) $serviceOrders->where('status','finished')->count(),
            'total_value' => (float) $serviceOrders->sum('total_amount'),
        ];
        $ordersSummary = [
            'open' => (int) $orders->where('status','open')->count(),
            'fulfilled' => (int) $orders->where('status','fulfilled')->count(),
            'total_value' => (float) $orders->sum('total_amount'),
        ];

        $includeReceivables = $request->boolean('include_receivables');
        $includePayables = $request->boolean('include_payables');
        $includeClients = $request->boolean('include_clients');
        $includeProducts = $request->boolean('include_products');
        $includeSuppliers = $request->boolean('include_suppliers');
        $includeCategories = $request->boolean('include_categories');

        $receivablesDetailed = collect();
        $payablesDetailed = collect();
        $clients = collect();
        $products = collect();
        $suppliers = collect();
        $categories = collect();

        if ($includeReceivables) {
            $receivablesDetailed = Receivable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('due_date')
                ->get();
        }
        if ($includePayables) {
            $payablesDetailed = Payable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->orderBy('due_date')
                ->get();
        }
        if ($includeClients) {
            $clients = Client::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get();
        }
        if ($includeProducts) {
            $products = Product::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get();
        }
        if ($includeSuppliers) {
            $suppliers = Supplier::where('tenant_id', $tenantId)->orderBy('name')->get();
        }
        if ($includeCategories) {
            $categories = Category::where('tenant_id', $tenantId)->orderBy('parent_id')->orderBy('name')->get();
        }

        return view('reports.index', compact(
            'from','to','recSummary','paySummary','osSummary','ordersSummary',
            'includeReceivables','includePayables','includeClients','includeProducts','includeSuppliers','includeCategories',
            'receivablesDetailed','payablesDetailed','clients','products','suppliers','categories'
        ));
    }

    public function print(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('reports.view'), 403);
        $tenantId = auth()->user()->tenant_id;
        $from = $request->input('from') ? Carbon::parse($request->input('from')) : Carbon::now()->startOfMonth();
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : Carbon::now()->endOfMonth();

        $includeReceivables = $request->boolean('include_receivables');
        $includePayables = $request->boolean('include_payables');
        $includeClients = $request->boolean('include_clients');
        $includeProducts = $request->boolean('include_products');
        $includeSuppliers = $request->boolean('include_suppliers');
        $includeCategories = $request->boolean('include_categories');

        $receivablesDetailed = $includeReceivables
            ? Receivable::where('tenant_id', $tenantId)->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->orderBy('due_date')->get()
            : collect();
        $payablesDetailed = $includePayables
            ? Payable::where('tenant_id', $tenantId)->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->orderBy('due_date')->get()
            : collect();
        $clients = $includeClients
            ? Client::where('tenant_id', $tenantId)->orderBy('name')->get()
            : collect();
        $products = $includeProducts
            ? Product::where('tenant_id', $tenantId)->orderBy('name')->get()
            : collect();
        $suppliers = $includeSuppliers
            ? Supplier::where('tenant_id', $tenantId)->orderBy('name')->get()
            : collect();
        $categories = $includeCategories
            ? Category::where('tenant_id', $tenantId)->orderBy('parent_id')->orderBy('name')->get()
            : collect();

        return view('reports.print', compact(
            'from','to',
            'includeReceivables','includePayables','includeClients','includeProducts','includeSuppliers','includeCategories',
            'receivablesDetailed','payablesDetailed','clients','products','suppliers','categories'
        ));
    }
}


