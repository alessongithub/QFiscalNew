<?php

namespace App\Services;

use App\Models\TaxCredit;
use App\Models\Product;
use App\Models\OrderItem;

class TaxCreditService
{
    /**
     * Busca créditos fiscais disponíveis para um produto
     */
    public function getAvailableCredits(int $productId, int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return TaxCredit::where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->active()
            ->orderBy('document_date', 'asc') // FIFO - First In, First Out
            ->get();
    }

    /**
     * Calcula ICMS considerando créditos fiscais disponíveis
     */
    public function calculateIcmsWithCredits(
        Product $product,
        float $baseCalculo,
        float $aliquota,
        float $quantity,
        int $tenantId
    ): array {
        // Se não há alíquota ou base, retorna zero
        if ($aliquota <= 0 || $baseCalculo <= 0) {
            return [
                'icms_due' => 0.0,
                'icms_credit_used' => 0.0,
                'credits_used' => [],
                'suggestion' => null
            ];
        }

        // ICMS que seria devido normalmente
        $icmsDue = round($baseCalculo * $aliquota, 2);

        // Busca créditos disponíveis
        $credits = $this->getAvailableCredits($product->id, $tenantId);
        
        if ($credits->isEmpty()) {
            return [
                'icms_due' => $icmsDue,
                'icms_credit_used' => 0.0,
                'credits_used' => [],
                'suggestion' => $this->getSuggestionMessage($product, $icmsDue)
            ];
        }

        // Calcula créditos disponíveis
        $totalCreditAvailable = 0.0;
        $creditsUsed = [];
        $remainingQuantity = $quantity;

        foreach ($credits as $credit) {
            if ($remainingQuantity <= 0) break;

            $usableQuantity = min($remainingQuantity, $credit->quantity - $credit->quantity_used);
            if ($usableQuantity <= 0) continue;

            $creditValue = $credit->getAvailableIcmsValue($usableQuantity);
            $totalCreditAvailable += $creditValue;
            
            $creditsUsed[] = [
                'credit_id' => $credit->id,
                'document' => $credit->document_number,
                'quantity' => $usableQuantity,
                'value' => $creditValue
            ];

            $remainingQuantity -= $usableQuantity;
        }

        // ICMS final após desconto dos créditos
        $finalIcms = max(0, $icmsDue - $totalCreditAvailable);

        return [
            'icms_due' => $finalIcms,
            'icms_credit_used' => min($totalCreditAvailable, $icmsDue),
            'credits_used' => $creditsUsed,
            'suggestion' => $this->getSuggestionMessage($product, $finalIcms, $totalCreditAvailable)
        ];
    }

    /**
     * Registra crédito fiscal de uma nota de entrada
     */
    public function registerCreditFromInbound(array $data): TaxCredit
    {
        return TaxCredit::create([
            'tenant_id' => $data['tenant_id'],
            'product_id' => $data['product_id'],
            'document_type' => $data['document_type'] ?? 'nfe',
            'document_number' => $data['document_number'],
            'document_series' => $data['document_series'],
            'document_date' => $data['document_date'],
            'supplier_cnpj' => $data['supplier_cnpj'],
            'supplier_name' => $data['supplier_name'],
            'base_calculo_icms' => $data['base_calculo_icms'],
            'valor_icms' => $data['valor_icms'],
            'aliquota_icms' => $data['aliquota_icms'],
            'cst_icms' => $data['cst_icms'],
            'cfop' => $data['cfop'],
            'ncm' => $data['ncm'],
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'total_value' => $data['total_value'],
            'status' => 'active',
        ]);
    }

    /**
     * Utiliza créditos fiscais em uma venda
     */
    public function useCredits(array $creditsUsed): void
    {
        foreach ($creditsUsed as $creditData) {
            $credit = TaxCredit::find($creditData['credit_id']);
            if ($credit) {
                $credit->markAsUsed($creditData['quantity'], $creditData['value']);
            }
        }
    }

    /**
     * Gera mensagem de sugestão para o usuário
     */
    private function getSuggestionMessage(Product $product, float $icmsDue, float $creditUsed = 0): ?string
    {
        if ($creditUsed > 0) {
            return "ICMS reduzido em R$ " . number_format($creditUsed, 2, ',', '.') . 
                   " devido a créditos fiscais de entradas anteriores.";
        }

        // Verifica se há créditos disponíveis mas não utilizados
        $availableCredits = $this->getAvailableCredits($product->id, $product->tenant_id);
        if ($availableCredits->isNotEmpty()) {
            $totalAvailable = $availableCredits->sum('valor_icms') - $availableCredits->sum('valor_icms_used');
            if ($totalAvailable > 0) {
                return "Há R$ " . number_format($totalAvailable, 2, ',', '.') . 
                       " em créditos fiscais disponíveis para este produto.";
            }
        }

        return "Nenhum crédito fiscal encontrado para este produto. ICMS será calculado normalmente.";
    }

    /**
     * Verifica se um produto tem CST que indica ICMS já recolhido
     */
    public function hasIcmsAlreadyCollected(string $cstIcms): bool
    {
        $cstsWithIcmsCollected = ['00', '10', '30', '60', '70'];
        return in_array($cstIcms, $cstsWithIcmsCollected);
    }

    /**
     * Obtém sugestão de ICMS baseada em entradas anteriores
     */
    public function getIcmsSuggestion(Product $product, float $baseCalculo, int $tenantId): array
    {
        $credits = $this->getAvailableCredits($product->id, $tenantId);
        
        if ($credits->isEmpty()) {
            return [
                'suggestion' => null,
                'message' => 'Nenhuma entrada encontrada para este produto.'
            ];
        }

        // Pega o crédito mais recente como referência
        $latestCredit = $credits->first();
        $suggestedAliquota = $latestCredit->aliquota_icms;
        $suggestedIcms = round($baseCalculo * $suggestedAliquota, 2);

        return [
            'suggestion' => $suggestedIcms,
            'aliquota' => $suggestedAliquota,
            'message' => "Sugestão baseada na entrada {$latestCredit->document_number} de " . 
                        $latestCredit->document_date->format('d/m/Y') . 
                        " (alíquota: " . number_format($suggestedAliquota * 100, 2) . "%)"
        ];
    }
}
