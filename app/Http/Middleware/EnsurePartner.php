<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePartner
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('partner')->user();
        if (!$user || empty($user->partner_id)) {
            abort(403);
        }
        return $next($request);
    }
}


