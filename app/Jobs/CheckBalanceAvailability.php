<?php

namespace App\Jobs;

use App\Models\GatewayConfig;
use App\Models\TenantBalance;
use App\Models\TenantTransferSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class CheckBalanceAvailability implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $pendingBalances = TenantBalance::where('status', 'pending')
            ->where('payment_received_at', '<=', now()->subDay())
            ->get();

        $config = GatewayConfig::current();
        $accessToken = $config->active_access_token;

        foreach ($pendingBalances as $balance) {
            if (!$balance->mp_payment_id) {
                continue;
            }

            $response = Http::withToken($accessToken)
                ->get('https://api.mercadopago.com/v1/payments/' . $balance->mp_payment_id);

            if (!$response->successful()) {
                continue;
            }

            $payment = $response->json();

            $statusDetail = $payment['status_detail'] ?? null;
            $dateAvailable = $payment['date_available'] ?? null;

            $isAvailable = false;
            if ($statusDetail === 'accredited') {
                $isAvailable = true;
            } elseif ($dateAvailable) {
                try {
                    $isAvailable = now()->greaterThanOrEqualTo($dateAvailable);
                } catch (\Throwable $e) {
                    $isAvailable = false;
                }
            }

            if ($isAvailable) {
                $balance->markAsAvailable();

                // Auto-transfer opcional
                $settings = TenantTransferSetting::where('tenant_id', $balance->tenant_id)->first();
                if ($settings && $settings->auto_transfer_enabled) {
                    $min = (float) ($settings->auto_transfer_min_amount ?? 0);
                    if ($balance->net_amount >= $min) {
                        dispatch(new ProcessTransfer($balance->id));
                    }
                }
            }
        }
    }
}


