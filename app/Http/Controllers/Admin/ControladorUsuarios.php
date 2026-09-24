<?php

namespace App\Http\Controllers\Admin;

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
            'users' => Usuario::orderBy('name')->get(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        Usuario::create($this->datosValidados($request));

        return redirect()->route('administracion.usuarios.listado')->with('status', 'Usuario creado.');
    }

    public function actualizar(Request $request, Usuario $user): RedirectResponse
    {
        $data = $this->datosValidados($request, $user);

        if ($data['password'] === null) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('administracion.usuarios.listado')->with('status', 'Usuario actualizado.');
    }

    public function actualizarEstado(Request $request, Usuario $user): RedirectResponse
    {
        $user->update($request->validate([
            'active' => [
                'required',
                'boolean',
            ],
        ]));

        return redirect()->route('administracion.usuarios.listado')->with('status', 'Estado actualizado.');
    }

    /** @return array{name:string,email:string,role:string,password:string|null} */
    private function datosValidados(Request $request, ?Usuario $user = null): array
    {
        return $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user),
            ],

            'role' => [
                'required',
                Rule::in([
                    Usuario::ROL_ADMINISTRADOR,
                    Usuario::ROL_COMERCIAL,
                    Usuario::ROL_CONSULTA,
                ]),
            ],

            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                'min:8',
            ],

        ]);
    }
}
