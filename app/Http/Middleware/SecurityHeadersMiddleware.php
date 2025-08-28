<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (!app()->isProduction()) {
            return $response;
        }

        // Content Security Policy (tuned for current app usage)
        $csp = [
            "default-src 'self'",
            "script-src 'self' https://cdn.tailwindcss.com",
            "style-src 'self' https://fonts.bunny.net 'unsafe-inline'",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ];

        $headers = [
            'Content-Security-Policy' => implode('; ', $csp),
            'Referrer-Policy' => 'same-origin',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Permissions-Policy' => "camera=(), microphone=(), geolocation=(), payment=(), usb=(), accelerometer=(), gyroscope=(), magnetometer=(), interest-cohort=()",
            'Cross-Origin-Resource-Policy' => 'same-site',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}


