<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateIntendedUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $intended = $request->session()->get('url.intended');
        if ($intended !== null && ! $this->isSafe($intended, $request)) {
            $request->session()->forget('url.intended');
        }

        return $next($request);
    }

    private function isSafe(mixed $url, Request $request): bool
    {
        if (! is_string($url) || preg_match('/[\\\\\x00-\x20\x7f]/', rawurldecode($url))) {
            return false;
        }
        if (str_starts_with($url, '/') && ! str_starts_with(rawurldecode($url), '//')) {
            return true;
        }
        $parts = parse_url($url);

        return $parts && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && strcasecmp($parts['host'] ?? '', $request->getHost()) === 0
            && ! isset($parts['user']) && ! isset($parts['pass']);
    }
}
