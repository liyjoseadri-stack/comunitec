<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorUsuarios extends Controller
{
    public function listar(): View
    {
        return view('administracion.usuarios.listado', [
            'usuarios' => Usuario::orderBy('nombre')->get(),
        ]);
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        Usuario::create($this->datosValidados($solicitud));

        return redirect()->route('administracion.usuarios.listado')->with('estado', 'Usuario creado.');
    }

    public function actualizar(Request $solicitud, Usuario $usuario): RedirectResponse
    {
        $datos = $this->datosValidados($solicitud, $usuario);

        if ($datos['contrasena'] === null) {
            unset($datos['contrasena']);
        }

        $usuario->update($datos);

        return redirect()->route('administracion.usuarios.listado')->with('estado', 'Usuario actualizado.');
    }

    public function actualizarEstado(Request $solicitud, Usuario $usuario): RedirectResponse
    {
        $usuario->update($solicitud->validate([
            'activo' => [
                'required',
                'boolean',
            ],
        ]));

        return redirect()->route('administracion.usuarios.listado')->with('estado', 'Estado actualizado.');
    }

    /** @return array{name:string,email:string,role:string,password:string|null} */
    private function datosValidados(Request $solicitud, ?Usuario $usuario = null): array
    {
        return $solicitud->validate([

            'nombre' => [
                'required',
                'string',
                'max:255',
            ],

            'correo' => [
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios')->ignore($usuario),
            ],

            'rol' => [
                'required',
                Rule::in([
                    Usuario::ROL_ADMINISTRADOR,
                    Usuario::ROL_COMERCIAL,
                    Usuario::ROL_CONSULTA,
                ]),
            ],

            'contrasena' => [
                $usuario ? 'nullable' : 'required',
                'confirmed',
                'min:8',
            ],

        ]);
    }
}
