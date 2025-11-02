<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StorageCalculator;

class UpdateStorageUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:update-usage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza uso de storage de todos os tenants';

    /**
     * Execute the console command.
     */
    public function handle(StorageCalculator $calculator)
    {
        $this->info('Atualizando uso de storage...');
        
        $calculator->updateAllTenants();
        
        $this->info('Concluído! Uso de storage atualizado para todos os tenants.');
    }
}
