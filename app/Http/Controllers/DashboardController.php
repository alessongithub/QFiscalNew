<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\Payable;
use App\Models\Client;
use App\Models\ServiceOrder;
use App\Models\Order;
use App\Models\TaxRate; // Added
use App\Models\NfeNote;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Contar clientes do tenant atual
        $totalClients = Client::where('tenant_id', $user->tenant_id)->count();
        
        $tenantId = $user->tenant_id;
        $today = now()->toDateString();
        $currentMonth = now()->startOfMonth();
        $nextMonth = now()->addMonth()->startOfMonth();

        // A Receber (hoje e vencidos)
        $receivablesToday = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', $today)
            ->sum('amount');
        $receivablesTodayCount = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', $today)
            ->count();
            
        $overdueReceivables = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', '<', $today)
            ->sum('amount');
            
        $overdueReceivablesCount = Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', '<', $today)
            ->count();

        // A Pagar (hoje e vencidos)
        $payablesToday = Payable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', $today)
            ->sum('amount');
        $payablesTodayCount = Payable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', $today)
            ->count();
            
        $overduePayables = Payable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', '<', $today)
            ->sum('amount');
            
        $overduePayablesCount = Payable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open','partial'])
            ->whereDate('due_date', '<', $today)
            ->count();

        // Receitas do Mês - Calculado a partir de múltiplas fontes
        $monthlyRevenue = 0;
        
        // 1. Recebíveis do mês (contas a receber)
        $monthlyRevenue += Receivable::where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'partial', 'paid'])
            ->whereBetween('due_date', [$currentMonth, $nextMonth])
            ->sum('amount');
        
        // 2. Pedidos do mês com NFe emitida (vendas faturadas)
        $monthlyRevenue += Order::where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'fulfilled'])
            ->whereNotNull('nfe_issued_at')
            ->whereBetween('nfe_issued_at', [$currentMonth, $nextMonth])
            ->sum('total_amount');
        
        // 3. Ordens de Serviço finalizadas do mês
        $monthlyRevenue += ServiceOrder::where('tenant_id', $tenantId)
            ->where('status', 'finished')
            ->whereBetween('finalized_at', [$currentMonth, $nextMonth])
            ->sum('total_amount');

        // Notas Fiscais emitidas no mês (NFe)
        $nfeCountMonth = NfeNote::where('tenant_id', $tenantId)
            ->where('status', 'emitted')
            ->whereBetween('emitted_at', [$currentMonth, $nextMonth])
            ->count();

        // Últimas NFes emitidas (ou criadas) para widget (máx 5)
        $latestNfe = NfeNote::with('client')
            ->where('tenant_id', $tenantId)
            ->orderByRaw('COALESCE(emitted_at, created_at) DESC')
            ->limit(5)
            ->get();

        // Pendências fiscais (NFe pendentes/erro)
        $fiscalPendencies = NfeNote::with('client')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'error'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();
        $fiscalPendenciesCount = NfeNote::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'error'])
            ->count();

        // Tributos do Mês - preferir totais do XML (ICMSTot: vICMS, vPIS, vCOFINS)
        $monthlyTaxes = 0;
        $emittedNotesForMonth = NfeNote::where('tenant_id', $tenantId)
            ->where('status', 'emitted')
            ->whereBetween('emitted_at', [$currentMonth, $nextMonth])
            ->get(['xml_path', 'response_received']);

        $icmsSum = 0.0; $pisSum = 0.0; $cofinsSum = 0.0;
        $xmlParsedCount = 0; $responseUsedCount = 0;
        foreach ($emittedNotesForMonth as $note) {
            $xmlPath = $note->xml_path;
            if (!empty($xmlPath) && @is_file($xmlPath)) {
                $taxes = $this->extractNfeTaxesFromXml($xmlPath);
                $icmsSum += $taxes['icms'] ?? 0.0;
                $pisSum += $taxes['pis'] ?? 0.0;
                $cofinsSum += $taxes['cofins'] ?? 0.0;
                $xmlParsedCount++;
                continue;
            }
            // Fallback leve: tentar response_received (se contiver totais)
            if (!empty($note->response_received)) {
                $resp = is_array($note->response_received) ? $note->response_received : (json_decode($note->response_received, true) ?: []);
                $icmsSum += (float)($resp['total']['icms'] ?? 0);
                $pisSum += (float)($resp['total']['pis'] ?? 0);
                $cofinsSum += (float)($resp['total']['cofins'] ?? 0);
                $responseUsedCount++;
            }
        }
        $monthlyTaxes = $icmsSum + $pisSum + $cofinsSum;
        $notesCount = $emittedNotesForMonth->count();
        $skipFallback = false;
        // Se não há NF-e no mês, zerar tributos e não aplicar fallback
        if ($notesCount === 0) {
            $monthlyTaxes = 0.0;
            $skipFallback = true;
            \Log::info('Dashboard.monthlyTaxes', [
                'tenant_id' => $tenantId,
                'month' => $currentMonth->toDateString(),
                'notes_count' => $notesCount,
                'source' => 'none',
                'monthly_taxes' => $monthlyTaxes,
            ]);
        }

        // Caso não encontremos totais em XML/resposta e haja notas, usar fallback configurado
        if (!$skipFallback && $monthlyTaxes <= 0) {
            // Buscar configurações de tributos do tenant
            $taxRates = TaxRate::where('tenant_id', $tenantId)->where('ativo', true)->get();

            if ($taxRates->isNotEmpty()) {
                // Pedidos com NFe emitida
                $ordersWithTaxes = Order::where('tenant_id', $tenantId)
                    ->whereIn('status', ['open', 'fulfilled'])
                    ->whereNotNull('nfe_issued_at')
                    ->whereBetween('nfe_issued_at', [$currentMonth, $nextMonth])
                    ->get();

                foreach ($ordersWithTaxes as $order) {
                    $taxRate = $taxRates->where('tipo_nota', 'produto')->first();
                    if ($taxRate) {
                        $monthlyTaxes += $this->calculateTaxes($order->total_amount, $taxRate);
                    }
                }

                // OS finalizadas
                $serviceOrdersWithTaxes = ServiceOrder::where('tenant_id', $tenantId)
                    ->where('status', 'finished')
                    ->whereBetween('finalized_at', [$currentMonth, $nextMonth])
                    ->get();

                foreach ($serviceOrdersWithTaxes as $serviceOrder) {
                    $taxRate = $taxRates->where('tipo_nota', 'servico')->first();
                    if ($taxRate) {
                        $monthlyTaxes += $this->calculateTaxes($serviceOrder->total_amount, $taxRate);
                    }
                }
            } else {
                // Fallback final: 15% do revenue
                $monthlyTaxes = $monthlyRevenue * 0.15;
                \Log::info('Dashboard.monthlyTaxes.fallback_percent', [
                    'tenant_id' => $tenantId,
                    'month' => $currentMonth->toDateString(),
                    'monthly_revenue' => (float) $monthlyRevenue,
                    'percent_used' => 0.15,
                    'monthly_taxes' => (float) $monthlyTaxes,
                ]);
            }
        }

        // Log consolidado quando houve XML/resposta
        if (!$skipFallback && ($xmlParsedCount > 0 || $responseUsedCount > 0)) {
            \Log::info('Dashboard.monthlyTaxes.from_documents', [
                'tenant_id' => $tenantId,
                'month' => $currentMonth->toDateString(),
                'notes_count' => $notesCount,
                'xml_parsed' => $xmlParsedCount,
                'response_used' => $responseUsedCount,
                'icms_sum' => (float) $icmsSum,
                'pis_sum' => (float) $pisSum,
                'cofins_sum' => (float) $cofinsSum,
                'monthly_taxes' => (float) $monthlyTaxes,
            ]);
        }

        // Dados para o dashboard
        $dashboardData = [
            'totalClients' => $totalClients,
            'nfeCountMonth' => $nfeCountMonth,
            'monthlyRevenue' => (float) $monthlyRevenue,
            'monthlyTaxes' => (float) $monthlyTaxes,
            // A receber / A pagar (para os cards de alertas)
            'todayReceivablesAmount' => (float) $receivablesToday,
            'todayReceivablesCount' => (int) $receivablesTodayCount,
            'overdueReceivablesAmount' => (float) $overdueReceivables,
            'overdueReceivablesCount' => (int) $overdueReceivablesCount,
            'todayPayablesAmount' => (float) $payablesToday,
            'todayPayablesCount' => (int) $payablesTodayCount,
            'overduePayablesAmount' => (float) $overduePayables,
            'overduePayablesCount' => (int) $overduePayablesCount,

            // Destaques de OS por aprovação/rejeição via e-mail (últimas 5 de hoje)
            'recentApprovedOs' => ServiceOrder::where('tenant_id', $tenantId)
                ->whereNotNull('approved_at')
                ->whereDate('approved_at', $today)
                ->orderByDesc('approved_at')
                ->limit(5)
                ->get(['id', 'number', 'title', 'approved_at', 'approved_by_email']),
            'recentRejectedOs' => ServiceOrder::where('tenant_id', $tenantId)
                ->whereNotNull('rejected_at')
                ->whereDate('rejected_at', $today)
                ->orderByDesc('rejected_at')
                ->limit(5)
                ->get(['id', 'number', 'title', 'rejected_at', 'rejected_by_email']),

            // NF-e widgets
            'latestNfe' => $latestNfe,
            'fiscalPendencies' => $fiscalPendencies,
            'fiscalPendenciesCount' => (int) $fiscalPendenciesCount,
        ];
        
        return view('dashboard', compact('dashboardData'));
    }
    
    /**
     * Calcula os tributos baseado no valor e nas alíquotas configuradas
     */
    private function calculateTaxes($amount, $taxRate)
    {
        $totalTaxes = 0;
        
        // ICMS
        if ($taxRate->icms_aliquota) {
            $totalTaxes += $amount * $taxRate->icms_aliquota;
        }
        
        // PIS
        if ($taxRate->pis_aliquota) {
            $totalTaxes += $amount * $taxRate->pis_aliquota;
        }
        
        // COFINS
        if ($taxRate->cofins_aliquota) {
            $totalTaxes += $amount * $taxRate->cofins_aliquota;
        }
        
        // ISS (para serviços)
        if ($taxRate->iss_aliquota) {
            $totalTaxes += $amount * $taxRate->iss_aliquota;
        }
        
        // CSLL
        if ($taxRate->csll_aliquota) {
            $totalTaxes += $amount * $taxRate->csll_aliquota;
        }
        
        // INSS
        if ($taxRate->inss_aliquota) {
            $totalTaxes += $amount * $taxRate->inss_aliquota;
        }
        
        // IRRF
        if ($taxRate->irrf_aliquota) {
            $totalTaxes += $amount * $taxRate->irrf_aliquota;
        }
        
        return $totalTaxes;
    }

    /**
     * Extrai vICMS, vPIS e vCOFINS de um XML de NFe (procNFe/NFe), independente de namespace.
     * Retorna ['icms'=>float, 'pis'=>float, 'cofins'=>float]
     */
    private function extractNfeTaxesFromXml(string $xmlPath): array
    {
        $result = ['icms' => 0.0, 'pis' => 0.0, 'cofins' => 0.0];
        try {
            $xml = @simplexml_load_file($xmlPath, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$xml) { return $result; }
            $namespaces = $xml->getDocNamespaces(true);
            // XPath por local-name para evitar problemas de namespace
            $totals = $xml->xpath('//*[local-name()="ICMSTot"]');
            if (!$totals || empty($totals[0])) { return $result; }
            $tot = $totals[0];
            $vICMS = $tot->xpath('*[local-name()="vICMS"][1]');
            $vPIS = $tot->xpath('*[local-name()="vPIS"][1]');
            $vCOFINS = $tot->xpath('*[local-name()="vCOFINS"][1]');
            $result['icms'] = isset($vICMS[0]) ? (float) $vICMS[0] : 0.0;
            $result['pis'] = isset($vPIS[0]) ? (float) $vPIS[0] : 0.0;
            $result['cofins'] = isset($vCOFINS[0]) ? (float) $vCOFINS[0] : 0.0;
        } catch (\Throwable $e) {
            // Ignorar erros de leitura/parse
        }
        return $result;
    }
}
