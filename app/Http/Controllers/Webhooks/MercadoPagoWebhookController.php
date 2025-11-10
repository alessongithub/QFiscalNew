<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\GatewayConfig;
use App\Models\Invoice;
use App\Models\Receivable;
use App\Models\Payment;
use App\Models\StorageAddon;
use App\Models\Tenant;
use App\Models\TenantStorageUsage;
use App\Models\TenantBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $config = GatewayConfig::current();

        $type = $request->get('type') ?? $request->input('type');
        $dataId = $request->input('data.id') ?? $request->input('id');

        if ($type !== 'payment' || !$dataId) {
            return response()->json(['status' => 'ignored']);
        }

        $paymentResponse = Http::withToken($config->active_access_token)
            ->get('https://api.mercadopago.com/v1/payments/' . $dataId);

        if (!$paymentResponse->successful()) {
            return response()->json(['status' => 'fetch_failed'], 400);
        }

        $paymentJson = $paymentResponse->json();
        $externalReference = $paymentJson['external_reference'] ?? null;

        if (!$externalReference) {
            return response()->json(['status' => 'no_reference']);
        }

        // Suporta referências de invoices, receivables (prefixo rec_) e addons (prefixo addon_)
        $isReceivable = is_string($externalReference) && str_starts_with($externalReference, 'rec_');
        $isAddon = is_string($externalReference) && str_starts_with($externalReference, 'addon_');
        $invoice = null; $receivable = null; $addon = null;
        
        if ($isReceivable) {
            $recId = (int) str_replace('rec_', '', $externalReference);
            $receivable = Receivable::find($recId);
            if (!$receivable) {
                return response()->json(['status' => 'receivable_not_found']);
            }
        } elseif ($isAddon) {
            $addonId = (int) str_replace('addon_', '', $externalReference);
            $addon = StorageAddon::find($addonId);
            if (!$addon) {
                return response()->json(['status' => 'addon_not_found']);
            }
        } else {
            $invoice = Invoice::find($externalReference);
            if (!$invoice) {
                return response()->json(['status' => 'invoice_not_found']);
            }
        }

        $status = $paymentJson['status'] ?? 'pending';
        $paymentMethod = $paymentJson['payment_method_id'] ?? null;
        $amount = (float) ($paymentJson['transaction_amount'] ?? 0);

        if ($invoice) {
            $invoice->external_payment_id = (string) $paymentJson['id'];
            $invoice->external_status = $status;
        }

        if ($status === 'approved') {
            if ($invoice) {
                // status numérico: 1 = paid
                $invoice->status = 1;
                $invoice->paid_at = now();

                // Atualiza Tenant: +30 dias por padrão
                $tenant = Tenant::find($invoice->tenant_id);
                if ($tenant) {
                    $today = now();
                    $baseDate = $tenant->plan_expires_at && $tenant->plan_expires_at->isFuture()
                        ? $tenant->plan_expires_at
                        : $today;
                    $tenant->plan_expires_at = $baseDate->copy()->addMonth();
                    $tenant->status = 'active';
                    $tenant->plan_id = $invoice->plan_id ?? $tenant->plan_id;
                    $tenant->save();
                }

                Payment::create([
                    'invoice_id' => $invoice->id,
                    'method' => $paymentMethod,
                    'status' => 'approved',
                    'amount' => $amount,
                    'paid_at' => now(),
                    'partner_id' => $invoice->partner_id,
                    'application_fee_amount' => $invoice->application_fee_amount ?? null,
                    'metadata' => $paymentJson,
                ]);
                $invoice->save();
            } elseif ($receivable) {
                $receivable->status = 'paid';
                $receivable->payment_method = ($paymentMethod === 'pix') ? 'pix' : 'boleto';
                $receivable->received_at = now();
                $receivable->save();

                // Criar saldo para repasse (aguardando liquidação)
                $this->createTenantBalance($receivable, $paymentJson);
            } elseif ($addon) {
                // Processar pagamento de storage addon
                if ($addon->status === 'pending') {
                    $addon->status = 'active';
                    $addon->save();

                    // Atualizar uso do tenant com espaço adicional
                    $usage = TenantStorageUsage::firstOrCreate(
                        ['tenant_id' => $addon->tenant_id],
                        [
                            'data_size_bytes' => 0,
                            'files_size_bytes' => 0,
                            'additional_data_mb' => 0,
                            'additional_files_mb' => 0,
                        ]
                    );

                    if ($addon->type === 'data') {
                        $usage->additional_data_mb += $addon->quantity_mb;
                    } else {
                        $usage->additional_files_mb += $addon->quantity_mb;
                    }
                    $usage->save();

                    \Log::info('Storage addon activated', [
                        'addon_id' => $addon->id,
                        'tenant_id' => $addon->tenant_id,
                        'type' => $addon->type,
                        'quantity_mb' => $addon->quantity_mb,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function createTenantBalance(Receivable $receivable, array $paymentData): void
{
    try {
        $grossAmount = (float) ($paymentData['transaction_amount'] ?? $receivable->amount);
        $paymentMethod = (string) ($paymentData['payment_method_id'] ?? $receivable->payment_method ?? '');
        $isPix = ($paymentMethod === 'pix' || $receivable->payment_method === 'pix');

        // Calcular taxa MP
        $mpFeeAmount = 0.0;
        
        if ($isPix) {
            // Taxa PIX: usar taxa percentual configurada
            $pixFeePercent = (float) \App\Models\Setting::getGlobal('pix.mp_fee_percent', '0.99');
            $mpFeeAmount = round($grossAmount * ($pixFeePercent / 100), 2);
        } else {
            // Taxa Boleto: usar fee_details quando disponível, senão usar taxa fixa configurada
            $fees = $paymentData['fee_details'] ?? [];
            $totalFee = 0.0;
            foreach ($fees as $fee) {
                $totalFee += (float) ($fee['amount'] ?? 0);
            }
            if ($totalFee > 0) {
                $mpFeeAmount = $totalFee;
            } else {
                $configuredFixedFee = (float) (\App\Models\Setting::getGlobal('boleto.mp_fee_fixed', '1.99'));
                $mpFeeAmount = round($configuredFixedFee, 2);
            }
        }

        // Taxa da plataforma de 1%
        $platformFeeAmount = round($grossAmount * 0.01, 2);
        $netAmount = max(0, $grossAmount - $mpFeeAmount - $platformFeeAmount);

        TenantBalance::create([
            'tenant_id' => $receivable->tenant_id,
            'receivable_id' => $receivable->id,
            'gross_amount' => $grossAmount,
            'mp_fee_amount' => $mpFeeAmount,
            'platform_fee_amount' => $platformFeeAmount,
            'net_amount' => $netAmount,
            'status' => 'pending',
            'payment_received_at' => now(),
            'mp_payment_id' => (string) ($paymentData['id'] ?? null),
        ]);
    } catch (\Throwable $e) {
        \Log::error('Falha ao criar TenantBalance', [
            'receivable_id' => $receivable->id,
            'error' => $e->getMessage(),
        ]);
    }
    }
}
