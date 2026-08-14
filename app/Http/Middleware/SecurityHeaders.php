<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);
        $nonce = Vite::cspNonce();
        $localAssetSources = app()->isLocal()
            ? ' http://localhost:5173 http://127.0.0.1:5173'
            : '';
        $localConnectionSources = app()->isLocal()
            ? ' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173'
            : '';
        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$localAssetSources}",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$localAssetSources}",
            "font-src 'self' data: https://fonts.bunny.net",
            "img-src 'self' data: blob:",
            "connect-src 'self'{$localConnectionSources}",
            "media-src 'self'",
            "manifest-src 'self'",
        ]);

        if (app()->isProduction()) {
            $policy .= '; upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', $policy);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        );
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('Cache-Control', 'no-store, private');

        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
