<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Allow Cloudflare Turnstile & Midtrans to run properly
        // This overrides any restrictive CSP set by the hosting/Nginx
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
                "https://challenges.cloudflare.com " .
                "https://app.sandbox.midtrans.com " .
                "https://app.midtrans.com " .
                "https://cdnjs.cloudflare.com " .
                "https://fonts.bunny.net; " .
            "frame-src 'self' https://challenges.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' " .
                "https://fonts.bunny.net " .
                "https://cdnjs.cloudflare.com; " .
            "font-src 'self' " .
                "https://fonts.bunny.net " .
                "https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self' https://challenges.cloudflare.com; " .
            "worker-src blob:;"
        );

        return $response;
    }
}
