<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité globaux : CSP stricte (avec nonce), nosniff, anti-clickjacking,
 * Referrer-Policy, Permissions-Policy, HSTS (HTTPS uniquement) et X-Robots-Tag pour les pages internes.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Le nonce doit être défini AVANT le rendu des vues (Blade : {{ Vite::cspNonce() }}).
        $nonce = Vite::useCspNonce();

        $response = $next($request);
        $headers = $response->headers;

        // Pas de CSP quand le serveur de développement Vite (hot reload) tourne.
        if (! Vite::isRunningHot()) {
            $csp = implode('; ', [
                "default-src 'none'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "style-src-attr 'unsafe-inline'",   // attributs style="..." existants dans les vues
                "img-src 'self' data: blob:",       // blob: = aperçus des images envoyées
                "font-src 'self'",
                "connect-src 'self'",
                "form-action 'self'",
                "base-uri 'none'",
                "object-src 'none'",
                "manifest-src 'self'",
                "frame-ancestors 'none'",
                'upgrade-insecure-requests',
            ]);

            // CSP_REPORT_ONLY=true : la politique n'est que signalée (console du navigateur), pas appliquée.
            $headers->set(
                config('security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $csp
            );
        }

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Pages internes et de connexion : ne pas indexer (sans révéler de chemins dans robots.txt).
        if ($request->user() || $request->routeIs('login', 'password.*')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
