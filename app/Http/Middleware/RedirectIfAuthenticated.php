<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (auth($guard)->check()) {
                if ($guard === 'partner') {
                    return redirect()->route('partner.dashboard');
                }
                // Se for admin, redirecionar para o dashboard admin
                if (auth($guard)->user()->is_admin) {
                    return redirect('/admin/dashboard');
                }
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}


