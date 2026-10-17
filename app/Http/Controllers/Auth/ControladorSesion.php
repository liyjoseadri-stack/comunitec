<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ControladorSesion extends Controller
{
    public function formulario(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('panel');
        }

        return view('autenticacion.ingresar');
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        $credenciales = $solicitud->validate([

            'correo' => [
                'required',
                'email',
            ],

            'contrasena' => [
                'required',
                'string',
            ],

        ]);

        $claveLimite = $this->claveLimite($solicitud, $credenciales['correo']);

        if (RateLimiter::tooManyAttempts($claveLimite, 5)) {
            $segundos = RateLimiter::availableIn($claveLimite);

            throw ValidationException::withMessages([
                'correo' => "Demasiados intentos de inicio de sesión. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        if (! Auth::attempt([
            'correo' => $credenciales['correo'],
            'activo' => true,
            'password' => $credenciales['contrasena'],
        ], $solicitud->boolean('recordar'))) {
            RateLimiter::hit($claveLimite, 60);

            throw ValidationException::withMessages([
                'correo' => 'Las credenciales no son correctas.',

            ]);
        }

        RateLimiter::clear($claveLimite);

        $solicitud->session()->regenerate();

        return redirect()->intended(route('panel', absolute: false));
    }

    public function eliminar(Request $solicitud): RedirectResponse
    {
        Auth::logout();

        $solicitud->session()->invalidate();
        $solicitud->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function claveLimite(Request $solicitud, string $correo): string
    {
        return Str::transliterate(Str::lower(trim($correo))).'|'.$solicitud->ip();
    }
}
