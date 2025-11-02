<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class PurgeOldAudits extends Command
{
    protected $signature = 'audits:purge-old';
    protected $description = 'Remove registros de auditoria antigos conforme retenção definida em settings';

    public function handle()
    {
        $map = [
            'tax' => \App\Models\TaxRateAudit::class,
            'settings' => \App\Models\SettingsAudit::class,
            'ncm' => \App\Models\NcmRuleAudit::class,
            'users' => \App\Models\UserAudit::class,
            'stock' => \App\Models\StockAudit::class,
            'products' => \App\Models\ProductAudit::class,
            'categories' => \App\Models\CategoryAudit::class,
            'clients' => \App\Models\ClientAudit::class,
            'suppliers' => \App\Models\SupplierAudit::class,
            'profile' => \App\Models\ProfileAudit::class,
        ];

        foreach ($map as $key => $model) {
            $days = (int) (Setting::get("logs.retention_days.{$key}", 180));
            if ($days <= 0) { continue; }
            $cut = Carbon::now()->subDays($days);
            $count = $model::where('created_at', '<', $cut)->delete();
            $this->info("Purged {$count} from {$key} older than {$days} days");
        }
    }
}


