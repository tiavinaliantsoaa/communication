<?php

namespace App\Http\Middleware;

use App\Support\Periode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberPeriode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Periode::remember($request);
        }

        return $next($request);
    }
}
