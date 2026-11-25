<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();
        $scripts = "'self' 'unsafe-eval'";
        // Standard Alpine evaluates Blade expressions. A CSP-build migration must
        // also replace the wizard, chat and admin expressions before removing eval.
        $styles = "'self' 'unsafe-inline'";
        // Inline styles remain for validated theme colours and Alpine x-show/style.
        $connections = "'self'";

        if (app()->isLocal() && Vite::isRunningHot()) {
            $hot = trim(file_get_contents(Vite::hotFile()));
            $parts = parse_url($hot);
            if ($parts && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
                && isset($parts['host']) && ! preg_match('/[\s;\x00-\x1f]/', $hot)) {
                $origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $scripts .= ' '.$origin;
                $styles .= ' '.$origin;
                $connections .= ' '.$origin.' '.($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            }
        }

        $response = $next($request);
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src {$scripts} 'nonce-".Vite::cspNonce()."'; style-src {$styles}; img-src 'self' data:; connect-src {$connections}; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
