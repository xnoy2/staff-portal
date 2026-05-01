<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('dev_authenticated')) {
            return redirect('/dev/login');
        }

        return $next($request);
    }
}
