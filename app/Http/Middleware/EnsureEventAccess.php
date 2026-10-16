<?php

namespace App\Http\Middleware;

use App\Models\Person;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEventAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            in_array(backpack_user()?->person?->sacRole?->code, [
                Person::ROLE_STAFF,
                Person::ROLE_ADMIN,
                Person::ROLE_FOUNDER,
            ], true),
            403
        );

        return $next($request);
    }
}