<?php

namespace App\Jobs;

use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $balanceId
    ) {}

    public function handle(): void
    {
        $balance = TenantBalance::find($this->balanceId);
        if (!$balance) {
            return;
        }

        if (!in_array($balance->status, ['requested', 'available'], true)) {
            return;
        }

        $settings = TenantTransferSetting::where('tenant_id', $balance->tenant_id)->first();
        if (!$settings || (!$settings->pix_key && !$settings->account)) {
            return;
        }

        $balance->update(['status' => 'transferring']);

        // Aqui você realiza a transferência real (manual/API). Após concluir, marque como transferido.
        // Exemplo placeholder (marcação manual futura):
        // $balance->markAsTransferred('manual-' . now()->timestamp, $settings->preferred_method);
    }
}


