<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AsegurarUsuarioActivo
{
    public function handle(Request $solicitud, Closure $siguiente): Response
    {
        if ($solicitud->user() && ! $solicitud->user()->refresh()->activo) {
            Auth::logout();

            $solicitud->session()->invalidate();
            $solicitud->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'correo' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        return $siguiente($solicitud);
    }
}
