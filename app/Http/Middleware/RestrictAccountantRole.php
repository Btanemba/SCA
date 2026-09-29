<?php

namespace App\Http\Middleware;

use App\Models\Person;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAccountantRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (backpack_user()?->person?->sacRole?->code === Person::ROLE_ACCOUNTANT) {
            abort_unless($request->routeIs('person.*'), 403);
        }

        return $next($request);
    }
}
