<?php

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PartnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $partner = null;

        // Dev helper: ?partner=slug
        if ($request->has('partner')) {
            $partner = Partner::where('slug', $request->query('partner'))->first();
        }

        // Subdomínio (prod) — usar cache para evitar I/O de banco em páginas públicas
        if (!$partner) {
            $parts = explode('.', $host);
            $hasSubdomain = count($parts) > 2;

            // Em páginas públicas (ex.: /login), se não houver subdomínio e o usuário não estiver autenticado, não consultar o banco
            if (!$hasSubdomain && !auth('web')->check()) {
                return $next($request);
            }

            if ($hasSubdomain) {
                $slug = $parts[0];
                $partner = Cache::remember('partner:slug:'.$slug, 300, function () use ($slug) {
                    return Partner::where('slug', $slug)->first();
                });
            } else {
                // domínio dedicado
                $partner = Cache::remember('partner:domain:'.$host, 300, function () use ($host) {
                    return Partner::where('domain', $host)->first();
                });
            }
        }

        if ($partner && $partner->active) {
            app()->instance('partner', $partner);
            view()->share('partner', $partner);
        } else {
            // Se não veio por subdomínio/query, mas o usuário web está logado e tem tenant com parceiro, compartilha também
            try {
                $user = auth('web')->user();
                $tenant = $user?->tenant;
                $tenantPartner = $tenant?->partner;
                if ($tenantPartner && $tenantPartner->active) {
                    app()->instance('partner', $tenantPartner);
                    view()->share('partner', $tenantPartner);
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        return $next($request);
    }
}


