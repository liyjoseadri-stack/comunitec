<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorPerfil extends Controller
{
    public function mostrar(Request $solicitud): View
    {
        return view('perfil.mostrar', [
            'usuario' => $solicitud->user(),
        ]);
    }

    public function actualizar(Request $solicitud): RedirectResponse
    {
        $usuario = $solicitud->user();
        $usuario->update($solicitud->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => [
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'correo')->ignore($usuario),
            ],
        ]));

        return to_route('perfil.mostrar')->with('estado', 'Perfil actualizado.');
    }

    public function actualizarContrasena(Request $solicitud): RedirectResponse
    {
        $datos = $solicitud->validate([
            'contrasena_actual' => ['required', 'current_password'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'contrasena_actual.current_password' => 'La contraseña actual no es correcta.',
        ]);

        $solicitud->user()->update([
            'contrasena' => $datos['contrasena'],
        ]);

        return to_route('perfil.mostrar')->with('estado', 'Contraseña actualizada.');
    }
}
