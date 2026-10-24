<?php

namespace App\Http\Middleware;

use App\Models\Person;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFounder
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(backpack_user()?->person?->sacRole?->code === Person::ROLE_FOUNDER, 403);

        return $next($request);
    }
}
