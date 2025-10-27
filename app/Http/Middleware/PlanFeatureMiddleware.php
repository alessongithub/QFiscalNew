<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PlanFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $featureKey)
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $tenant = $user->tenant;
        if (!$tenant) {
            return redirect()->route('login');
        }

        // Se estiver em modo limitado (assinatura expirada), operar como plano gratuito
        $limitedMode = (bool) config('app.limited_mode', false);

        $features = [];
        $plan = $tenant->plan;
        if ($plan && is_array($plan->features)) {
            $features = $plan->features;
        } elseif ($plan && is_string($plan->features)) {
            $features = json_decode($plan->features, true) ?: [];
        }

        // Compatibilidade: se o plano existir mas não trouxer flags booleanas, definir defaults por slug
        if ($plan && (!array_key_exists('allow_issue_nfe', $features) || !array_key_exists('has_emissor', $features))) {
            $slug = strtolower((string) ($plan->slug ?? ''));
            $defaultsBySlug = [
                'free' => ['allow_issue_nfe' => false, 'allow_pos' => false, 'has_emissor' => false, 'has_erp' => true],
                'gratuito' => ['allow_issue_nfe' => false, 'allow_pos' => false, 'has_emissor' => false, 'has_erp' => true],
                'basic' => ['allow_issue_nfe' => true, 'allow_pos' => true, 'has_emissor' => false, 'has_erp' => true],
                'basico' => ['allow_issue_nfe' => true, 'allow_pos' => true, 'has_emissor' => false, 'has_erp' => true],
                'professional' => ['allow_issue_nfe' => true, 'allow_pos' => true, 'has_emissor' => true, 'has_erp' => true],
                'profissional' => ['allow_issue_nfe' => true, 'allow_pos' => true, 'has_emissor' => true, 'has_erp' => true],
                'enterprise' => ['allow_issue_nfe' => true, 'allow_pos' => true, 'has_emissor' => true, 'has_erp' => true],
                'emissor' => ['allow_issue_nfe' => false, 'allow_pos' => false, 'has_emissor' => true, 'has_erp' => true],
                'emissor-fiscal' => ['allow_issue_nfe' => false, 'allow_pos' => false, 'has_emissor' => true, 'has_erp' => true],
            ];
            if (isset($defaultsBySlug[$slug])) {
                $features = array_merge($defaultsBySlug[$slug], $features);
            }
        }

        if ($limitedMode) {
            // Defaults do plano gratuito quando limitado
            $features = array_merge($features, [
                'allow_issue_nfe' => false,
                'allow_pos' => false,
                'has_erp' => true, // mantém acesso básico do ERP para renovar
                'max_users' => 1,
                'max_clients' => 50,
                'max_products' => 50,
            ]);
        }

        $allowed = (bool) ($features[$featureKey] ?? false);
        if (!$allowed) {
            return redirect()->back()->with('error', 'Recurso não disponível no seu plano.')->withInput();
        }

        return $next($request);
    }
}


