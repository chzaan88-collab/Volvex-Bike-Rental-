<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class FastApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Session::has('fastapi_token')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
