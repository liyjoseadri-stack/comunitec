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

    public function guardar(Request $request): RedirectResponse
    {
        $credentials = $request->validate([

            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],

        ]);

        if (! Auth::attempt([
            ...$credentials,
            'active' => true,
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas.',

            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('panel', absolute: false));
    }

    public function eliminar(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
