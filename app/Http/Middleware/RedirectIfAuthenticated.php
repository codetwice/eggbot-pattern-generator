<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = $guards ?: [null];

        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                return redirect()->intended(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
