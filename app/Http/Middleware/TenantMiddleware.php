<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Se for admin, permitir acesso sem verificar tenant
        if (auth()->user()->is_admin) {
            return $next($request);
        }

        $tenant = auth()->user()->tenant;
        if (!$tenant) {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Sua conta não está associada a nenhuma empresa. Por favor, faça login novamente.');
        }

        // Bloquear se tenant estiver suspenso por falta de pagamento
        if ($tenant->status === 'suspended') {
            return redirect()->route('landing')->with('error', 'Conta suspensa por falta de pagamento. Entre em contato com o suporte.');
        }

        // Aviso e modo limitado por expiração de plano (não desloga; mantém acesso básico e renovação)
        $expiresAt = $tenant->plan_expires_at;
        if ($expiresAt) {
            $daysAfterExpire = now()->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false) * -1; // dias após expirar (negativo antes)
            $blockAfter = (int) (\App\Models\GatewayConfig::current()->block_login_after_days ?? 3);

            // 1) Limitar recursos após X dias de expiração
            if ($daysAfterExpire >= $blockAfter + 1) {
                $request->attributes->set('limited_mode', true);
                config(['app.limited_mode' => true]);
            }

            // 2) Downgrade automático para plano gratuito após 15 dias (sem suspensão futura)
            if ($daysAfterExpire >= 15) {
                try {
                    $freePlan = Plan::where('slug', 'free')->first();
                    if ($freePlan && ($tenant->plan_id !== $freePlan->id)) {
                        $tenant->plan_id = $freePlan->id;
                        // Plano gratuito não expira
                        $tenant->plan_expires_at = null;
                        $tenant->save();
                        // Continua em limited_mode (features do free já restringem)
                        $request->attributes->set('limited_mode', true);
                        config(['app.limited_mode' => true]);
                    }
                } catch (\Throwable $e) {
                    // não bloquear navegação por falha de downgrade
                }
            }
        }

        // Por enquanto, não vamos mudar a conexão do banco
        // Isso será implementado quando fizermos o multi-tenant completo
        return $next($request);
    }
}