<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\GatewayConfig;
use App\Models\Invoice;
use App\Models\Receivable;
use App\Models\Payment;
use App\Models\Tenant;
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

        // Suporta referências de invoices e receivables (prefixo rec_)
        $isReceivable = is_string($externalReference) && str_starts_with($externalReference, 'rec_');
        $invoice = null; $receivable = null;
        if ($isReceivable) {
            $recId = (int) str_replace('rec_', '', $externalReference);
            $receivable = Receivable::find($recId);
            if (!$receivable) {
                return response()->json(['status' => 'receivable_not_found']);
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
                $receivable->payment_method = 'boleto';
                $receivable->received_at = now();
                $receivable->save();
            }
        }

        return response()->json(['status' => 'ok']);
    }
}


