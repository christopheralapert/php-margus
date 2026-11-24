<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureChatEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('chat.enabled'), 404);

        return $next($request);
    }
}
