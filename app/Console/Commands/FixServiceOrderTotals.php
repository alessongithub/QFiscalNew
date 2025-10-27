<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServiceOrder;

class FixServiceOrderTotals extends Command
{
    protected $signature = 'service-orders:fix-totals';
    protected $description = 'Corrige os valores total_amount e budget_amount das OS baseado nos itens';

    public function handle()
    {
        $this->info('Corrigindo valores das OS...');
        
        $orders = ServiceOrder::where(function($query) {
            $query->whereNull('total_amount')
                  ->orWhere('total_amount', 0)
                  ->orWhereNull('budget_amount')
                  ->orWhere('budget_amount', 0);
        })->get();
        
        $updated = 0;
        
        foreach ($orders as $order) {
            $itemsTotal = $order->items()->sum('line_total');
            
            if ($itemsTotal > 0) {
                $order->update([
                    'total_amount' => $itemsTotal,
                    'budget_amount' => $itemsTotal
                ]);
                
                $this->line("OS #{$order->number} atualizada: R$ " . number_format($itemsTotal, 2, ',', '.'));
                $updated++;
            }
        }
        
        $this->info("Total de OS atualizadas: {$updated}");
        return 0;
    }
}