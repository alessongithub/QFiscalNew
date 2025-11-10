<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\GatewayConfig;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;

class CelcoinWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        $raw = $request->getContent();
        $headers = $request->headers->all();

        Log::info('Celcoin webhook received', [
            'headers' => $headers,
            'payload' => $payload,
        ]);

        // Validação conforme documentação Celcoin: Webhook pode usar Basic ou JWT
        $config = GatewayConfig::current();
        $authType = (string) ($config->celcoin_webhook_type ?? '');
        if ($authType === 'basic') {
            $expectedLogin = (string) ($config->celcoin_webhook_login ?? '');
            $expectedPwd = (string) ($config->celcoin_webhook_pwd ?? '');
            $authHeader = (string) ($request->header('Authorization') ?? '');
            if ($expectedLogin !== '' || $expectedPwd !== '') {
                if (!str_starts_with($authHeader, 'Basic ')) {
                    Log::warning('Celcoin webhook missing Basic Authorization header');
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
                $encoded = substr($authHeader, 6);
                $decoded = base64_decode($encoded, true) ?: '';
                if ($decoded !== ($expectedLogin . ':' . $expectedPwd)) {
                    Log::warning('Celcoin webhook Basic credentials mismatch');
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
            }
        } elseif ($authType === 'jwt') {
            // Caso use JWT, validar o token conforme configuração (não documentada aqui)
            // Como fallback mínimo, aceitar se houver Authorization: Bearer {token}
            $authHeader = (string) ($request->header('Authorization') ?? '');
            if (!str_starts_with($authHeader, 'Bearer ')) {
                Log::warning('Celcoin webhook missing Bearer token');
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            // Opcional: validar assinatura do JWT se chave pública/segredo forem disponibilizados
        } else {
            // Sem tipo definido: se houver segredo HMAC, aceitar validação opcional via X-Celcoin-Signature
            $secret = (string) ($config->celcoin_webhook_secret ?? '');
            $signature = (string) ($request->header('X-Celcoin-Signature') ?? '');
            if ($secret && $signature) {
                try {
                    $expected = base64_encode(hash_hmac('sha256', $raw, $secret, true));
                    if (!hash_equals($expected, $signature)) {
                        Log::warning('Celcoin webhook signature mismatch (fallback)');
                        return response()->json(['ok' => true], 202);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Celcoin webhook signature check error (fallback)', ['error' => $e->getMessage()]);
                }
            }
        }

        // Extrair informações relevantes
        $event = (string) ($payload['event'] ?? $payload['type'] ?? '');
        $data = $payload['subscription'] ?? $payload['data'] ?? $payload;

        $customer = $data['customer'] ?? ($payload['customer'] ?? []);
        $email = (string) ($customer['email'] ?? '');
        $document = (string) ($customer['document'] ?? '');

        $planInfo = $data['plan'] ?? ($payload['plan'] ?? []);
        $planCode = strtolower((string) ($planInfo['code'] ?? $planInfo['slug'] ?? $planInfo['id'] ?? ''));
        $description = strtolower((string) ($data['description'] ?? $payload['description'] ?? ''));

        // Mapear slug/descrição para slugs conhecidos
        $knownSlugs = ['basico','plano-enterprise','plano-platinum','plano-profissional'];
        $resolvedSlug = null;
        foreach ($knownSlugs as $slug) {
            if ($planCode === $slug || str_contains($description, $slug)) {
                $resolvedSlug = $slug;
                break;
            }
        }

        if (!$resolvedSlug && $planCode) {
            $resolvedSlug = $planCode;
        }

        // Encontrar plano por slug
        $plan = null;
        if ($resolvedSlug) {
            $plan = Plan::where('slug', $resolvedSlug)->first();
        }

        if (!$plan) {
            Log::warning('Celcoin webhook: plano não resolvido');
            return response()->json(['ok' => true], 202);
        }

        // Encontrar tenant por e-mail ou documento
        $tenant = null;
        if ($email) {
            $tenant = Tenant::where('email', $email)->first();
        }
        if (!$tenant && $document) {
            $tenant = Tenant::where('cnpj', preg_replace('/[^0-9]/', '', $document))->first();
        }

        if (!$tenant) {
            Log::info('Celcoin webhook: tenant não encontrado para email/documento', ['email' => $email, 'document' => $document]);
            return response()->json(['ok' => true], 202);
        }

        // Atualizar/ativar assinatura
        $subscription = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->where('plan_id', $plan->id)
            ->first();

        $now = Carbon::now();
        $next = (clone $now)->addMonth();

        if (!$subscription) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => $now,
                'current_period_end' => $next,
            ]);
        } else {
            $subscription->status = 'active';
            $subscription->current_period_start = $now;
            $subscription->current_period_end = $next;
            $subscription->save();
        }

        // Registrar pagamento recorrente, se houver
        $payment = $payload['payment'] ?? ($data['payment'] ?? []);
        $paidValue = (float) ($payment['value'] ?? $payload['value'] ?? $data['value'] ?? 0);
        $paidAt = $payment['paidAt'] ?? $payload['paidAt'] ?? $data['paidAt'] ?? null;
        $providerPaymentId = (string) ($payment['id'] ?? $payload['payment_id'] ?? $data['payment_id'] ?? null);
        $status = strtolower((string) ($payment['status'] ?? $payload['status'] ?? $data['status'] ?? 'received'));

        if ($paidValue > 0) {
            SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'provider' => 'celcoin',
                'provider_payment_id' => $providerPaymentId,
                'status' => $status,
                'amount' => $paidValue,
                'paid_at' => $paidAt ? Carbon::parse($paidAt) : Carbon::now(),
                'metadata' => $payload,
            ]);
        }

        // Atualizar tenant no plano
        $tenant->plan_id = $plan->id;
        $tenant->plan_expires_at = $next;
        $tenant->save();

        return response()->json(['ok' => true]);
    }
}


