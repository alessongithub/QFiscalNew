<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        if (!$request->expectsJson()) {
            // Se rota de parceiro, redireciona para login do parceiro
            if ($request->is('partner') || $request->is('partner/*') || $request->is('parceiros/*')) {
                return route('partner.login');
            }
            return route('login');
        }
        return null;
    }
}


