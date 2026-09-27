<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSecurityRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (backpack_user()?->person?->sacRole?->code === \App\Models\Person::ROLE_SECURITY) {
            abort(403);
        }

        return $next($request);
    }
}
