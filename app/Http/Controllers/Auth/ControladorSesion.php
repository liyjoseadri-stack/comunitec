<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if (! Auth::attempt([
            'correo' => $credenciales['correo'],
            'activo' => true,
            'password' => $credenciales['contrasena'],
        ], $solicitud->boolean('recordar'))) {
            throw ValidationException::withMessages([
                'correo' => 'Las credenciales no son correctas.',

            ]);
        }

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
}
