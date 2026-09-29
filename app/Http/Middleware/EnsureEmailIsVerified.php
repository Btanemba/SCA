<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prologue\Alerts\Facades\Alert;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = backpack_user();

        if ($user && ! $user->email_verified_at) {
            backpack_auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Alert::error('Please complete your registration from the invitation email before signing in.')->flash();

            return redirect()->guest(backpack_url('login'));
        }

        return $next($request);
    }
}
