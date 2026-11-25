<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->user()->forceFill(['last_active_at' => now()])->save();

        return $next($request);
    }
}
