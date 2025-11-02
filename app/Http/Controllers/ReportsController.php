<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Receivable;
use App\Models\Payable;
use App\Models\ServiceOrder;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Tenant;
use Carbon\Carbon;

class ReportsController extends Controller
{
    /**
     * Valida e parseia as datas do período
     */
    private function parsePeriod(Request $request): array
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ], [
            'from.date' => 'A data inicial deve ser uma data válida.',
            'to.date' => 'A data final deve ser uma data válida.',
        ]);

        $from = $request->input('from') 
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->startOfMonth();
        
        $to = $request->input('to') 
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // Validação: data início não pode ser maior que data fim
        if ($from->gt($to)) {
            throw ValidationException::withMessages([
                'from' => 'A data inicial não pode ser maior que a data final.',
            ]);
        }

        return [$from, $to];
    }

    /**
     * Calcula os resumos executivos
     */
    private function calculateSummaries(int $tenantId, Carbon $from, Carbon $to): array
    {
        $receivables = Receivable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->select('status', 'amount', 'due_date')
            ->get();
        
        $payables = Payable::where('tenant_id', $tenantId)
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->select('status', 'amount', 'due_date')
            ->get();
        
        $serviceOrders = ServiceOrder::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('status', 'total_amount')
            ->get();
        
        $orders = Order::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->select('status', 'total_amount')
            ->get();

        $recSummary = [
            'open' => (float) $receivables->where('status', 'open')->sum('amount'),
            'paid' => (float) $receivables->where('status', 'paid')->sum('amount'),
            'overdue' => (float) $receivables->filter(function($r) {
                return $r->status !== 'paid' && Carbon::parse($r->due_date)->isPast();
            })->sum('amount'),
        ];

        $paySummary = [
            'open' => (float) $payables->where('status', 'open')->sum('amount'),
            'paid' => (float) $payables->where('status', 'paid')->sum('amount'),
            'overdue' => (float) $payables->filter(function($p) {
                return $p->status !== 'paid' && Carbon::parse($p->due_date)->isPast();
            })->sum('amount'),
        ];

        $osSummary = [
            'open' => (int) $serviceOrders->where('status', 'open')->count(),
            'in_progress' => (int) $serviceOrders->where('status', 'in_progress')->count(),
            'finished' => (int) $serviceOrders->where('status', 'finished')->count(),
            'total_value' => (float) $serviceOrders->sum('total_amount'),
        ];

        $ordersSummary = [
            'open' => (int) $orders->where('status', 'open')->count(),
            'fulfilled' => (int) $orders->where('status', 'fulfilled')->count(),
            'total_value' => (float) $orders->sum('total_amount'),
        ];

        return compact('recSummary', 'paySummary', 'osSummary', 'ordersSummary');
    }

    /**
     * Busca dados detalhados baseado nos filtros selecionados
     */
    private function fetchDetailedData(Request $request, int $tenantId, Carbon $from, Carbon $to): array
    {
        $includeReceivables = $request->boolean('include_receivables');
        $includePayables = $request->boolean('include_payables');
        $includeClients = $request->boolean('include_clients');
        $includeProducts = $request->boolean('include_products');
        $includeSuppliers = $request->boolean('include_suppliers');
        $includeCategories = $request->boolean('include_categories');
        $includeOrders = $request->boolean('include_orders');
        $includeServiceOrders = $request->boolean('include_service_orders');
        $includeQuotes = $request->boolean('include_quotes');

        // Filtros avançados
        $filters = [
            'receivable_status' => $request->input('receivable_status'),
            'receivable_client_id' => $request->input('receivable_client_id'),
            'receivable_min_value' => $request->input('receivable_min_value'),
            'receivable_max_value' => $request->input('receivable_max_value'),
            'payable_status' => $request->input('payable_status'),
            'payable_supplier_id' => $request->input('payable_supplier_id'),
            'payable_min_value' => $request->input('payable_min_value'),
            'payable_max_value' => $request->input('payable_max_value'),
            'order_status' => $request->input('order_status'),
            'order_client_id' => $request->input('order_client_id'),
            'order_min_value' => $request->input('order_min_value'),
            'order_max_value' => $request->input('order_max_value'),
            'service_order_status' => $request->input('service_order_status'),
            'service_order_client_id' => $request->input('service_order_client_id'),
            'service_order_min_value' => $request->input('service_order_min_value'),
            'service_order_max_value' => $request->input('service_order_max_value'),
            'quote_status' => $request->input('quote_status'),
            'quote_client_id' => $request->input('quote_client_id'),
            'quote_min_value' => $request->input('quote_min_value'),
            'quote_max_value' => $request->input('quote_max_value'),
        ];

        $data = [
            'includeReceivables' => $includeReceivables,
            'includePayables' => $includePayables,
            'includeClients' => $includeClients,
            'includeProducts' => $includeProducts,
            'includeSuppliers' => $includeSuppliers,
            'includeCategories' => $includeCategories,
            'includeOrders' => $includeOrders,
            'includeServiceOrders' => $includeServiceOrders,
            'includeQuotes' => $includeQuotes,
            'filters' => $filters,
        ];

        if ($includeReceivables) {
            $query = Receivable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
            
            if ($filters['receivable_status']) {
                $query->where('status', $filters['receivable_status']);
            }
            if ($filters['receivable_client_id']) {
                $query->where('client_id', $filters['receivable_client_id']);
            }
            if ($filters['receivable_min_value']) {
                $query->where('amount', '>=', $filters['receivable_min_value']);
            }
            if ($filters['receivable_max_value']) {
                $query->where('amount', '<=', $filters['receivable_max_value']);
            }
            
            $data['receivablesDetailed'] = $query
                ->with('client:id,name,cpf_cnpj')
                ->select('id', 'client_id', 'description', 'amount', 'due_date', 'status')
                ->orderBy('due_date')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['receivablesDetailed'] = null;
        }

        if ($includePayables) {
            $query = Payable::where('tenant_id', $tenantId)
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()]);
            
            if ($filters['payable_status']) {
                $query->where('status', $filters['payable_status']);
            }
            if ($filters['payable_supplier_id']) {
                $query->where('supplier_id', $filters['payable_supplier_id']);
            }
            if ($filters['payable_min_value']) {
                $query->where('amount', '>=', $filters['payable_min_value']);
            }
            if ($filters['payable_max_value']) {
                $query->where('amount', '<=', $filters['payable_max_value']);
            }
            
            $data['payablesDetailed'] = $query
                ->with('supplier:id,name,cpf_cnpj')
                ->select('id', 'supplier_id', 'description', 'amount', 'due_date', 'status')
                ->orderBy('due_date')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['payablesDetailed'] = null;
        }

        if ($includeClients) {
            $data['clients'] = Client::where('tenant_id', $tenantId)
                ->select('id', 'name', 'cpf_cnpj', 'email', 'phone')
                ->orderBy('name')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['clients'] = null;
        }

        if ($includeProducts) {
            $data['products'] = Product::where('tenant_id', $tenantId)
                ->select('id', 'name', 'sku', 'unit', 'price')
                ->orderBy('name')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['products'] = null;
        }

        if ($includeSuppliers) {
            $data['suppliers'] = Supplier::where('tenant_id', $tenantId)
                ->select('id', 'name', 'cpf_cnpj', 'email', 'phone')
                ->orderBy('name')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['suppliers'] = null;
        }

        if ($includeCategories) {
            $data['categories'] = Category::where('tenant_id', $tenantId)
                ->with('parent:id,name')
                ->select('id', 'name', 'parent_id', 'active')
                ->orderBy('parent_id')
                ->orderBy('name')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['categories'] = null;
        }

        if ($includeOrders) {
            $query = Order::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            
            if ($filters['order_status']) {
                $query->where('status', $filters['order_status']);
            }
            if ($filters['order_client_id']) {
                $query->where('client_id', $filters['order_client_id']);
            }
            if ($filters['order_min_value']) {
                $query->where('total_amount', '>=', $filters['order_min_value']);
            }
            if ($filters['order_max_value']) {
                $query->where('total_amount', '<=', $filters['order_max_value']);
            }
            
            $data['orders'] = $query
                ->with('client:id,name,cpf_cnpj')
                ->select('id', 'number', 'title', 'status', 'total_amount', 'client_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['orders'] = null;
        }

        if ($includeServiceOrders) {
            $query = ServiceOrder::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            
            if ($filters['service_order_status']) {
                $query->where('status', $filters['service_order_status']);
            }
            if ($filters['service_order_client_id']) {
                $query->where('client_id', $filters['service_order_client_id']);
            }
            if ($filters['service_order_min_value']) {
                $query->where('total_amount', '>=', $filters['service_order_min_value']);
            }
            if ($filters['service_order_max_value']) {
                $query->where('total_amount', '<=', $filters['service_order_max_value']);
            }
            
            $data['serviceOrders'] = $query
                ->with('client:id,name,cpf_cnpj')
                ->select('id', 'number', 'title', 'status', 'total_amount', 'client_id', 'created_at', 'finalized_at')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['serviceOrders'] = null;
        }

        if ($includeQuotes) {
            $query = Quote::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
            
            if ($filters['quote_status']) {
                $query->where('status', $filters['quote_status']);
            }
            if ($filters['quote_client_id']) {
                $query->where('client_id', $filters['quote_client_id']);
            }
            if ($filters['quote_min_value']) {
                $query->where('total_amount', '>=', $filters['quote_min_value']);
            }
            if ($filters['quote_max_value']) {
                $query->where('total_amount', '<=', $filters['quote_max_value']);
            }
            
            $data['quotes'] = $query
                ->with('client:id,name,cpf_cnpj')
                ->select('id', 'number', 'title', 'status', 'total_amount', 'client_id', 'created_at', 'approved_at', 'validity_date')
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 25))
                ->withQueryString();
        } else {
            $data['quotes'] = null;
        }

        return $data;
    }

    /**
     * Obtém dados do tenant para usar nas views
     */
    private function getTenantData(int $tenantId): array
    {
        $tenant = Tenant::select('id', 'name', 'fantasy_name', 'cnpj', 'email', 'phone', 'address', 'number', 'complement', 'neighborhood', 'city', 'state', 'zip_code', 'logo_path')
            ->findOrFail($tenantId);

        $logoUrl = null;
        if ($tenant->logo_path) {
            if (\Storage::disk('public')->exists($tenant->logo_path)) {
                $logoUrl = asset('storage/' . ltrim($tenant->logo_path, '/'));
            } elseif (\Storage::disk('public')->exists('logos/' . $tenant->logo_path)) {
                $logoUrl = asset('storage/logos/' . ltrim($tenant->logo_path, '/'));
            }
        }

        // Fallback para logo padrão
        if (!$logoUrl) {
            $logoUrl = asset('logo/logo_transp.png');
        }

        return [
            'tenant' => $tenant,
            'logoUrl' => $logoUrl,
        ];
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('reports.view'), 403);
        
        $tenantId = auth()->user()->tenant_id;
        [$from, $to] = $this->parsePeriod($request);
        
        $summaries = $this->calculateSummaries($tenantId, $from, $to);
        $detailedData = $this->fetchDetailedData($request, $tenantId, $from, $to);
        $tenantData = $this->getTenantData($tenantId);
        
        // Buscar listas para filtros (clientes e fornecedores)
        $clientsList = Client::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        $suppliersList = Supplier::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('reports.index', array_merge(
            [
                'from' => $from,
                'to' => $to,
                'clientsList' => $clientsList,
                'suppliersList' => $suppliersList,
            ],
            $summaries,
            $detailedData,
            $tenantData
        ));
    }

    public function print(Request $request)
    {
        abort_unless(auth()->user()->hasPermission('reports.view'), 403);
        
        $tenantId = auth()->user()->tenant_id;
        [$from, $to] = $this->parsePeriod($request);
        
        $detailedData = $this->fetchDetailedData($request, $tenantId, $from, $to);
        $tenantData = $this->getTenantData($tenantId);

        return view('reports.print', array_merge(
            [
                'from' => $from,
                'to' => $to,
            ],
            $detailedData,
            $tenantData
        ));
    }
}


