<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgregarEncabezadosSeguridad
{
    public function handle(Request $solicitud, Closure $siguiente): Response
    {
        $respuesta = $siguiente($solicitud);

        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $respuesta->headers->set(
            'Content-Security-Policy',
            "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'"
        );

        if ($solicitud->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $respuesta;
    }
}
