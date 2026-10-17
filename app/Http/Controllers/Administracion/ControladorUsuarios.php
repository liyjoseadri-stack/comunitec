<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\ServicioAdministracionUsuarios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControladorUsuarios extends Controller
{
    public function listar(Request $solicitud): View
    {
        $busqueda = trim($solicitud->string('buscar')->toString());
        $rol = $solicitud->string('rol')->toString();
        $estado = $solicitud->string('estado')->toString();

        return view('administracion.usuarios.listado', [
            'usuarios' => Usuario::query()
                ->when($busqueda, fn ($consulta) => $consulta->where(function ($consulta) use ($busqueda) {
                    $consulta
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('correo', 'like', "%{$busqueda}%");
                }))
                ->when(in_array($rol, $this->roles(), true), fn ($consulta) => $consulta->where('rol', $rol))
                ->when($estado === 'activo', fn ($consulta) => $consulta->where('activo', true))
                ->when($estado === 'inactivo', fn ($consulta) => $consulta->where('activo', false))
                ->orderBy('nombre')
                ->orderBy('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function crear(): View
    {
        return view('administracion.usuarios.crear');
    }

    public function editar(Request $solicitud, Usuario $usuario): View
    {
        abort_if($usuario->is($solicitud->user()), 403);

        return view('administracion.usuarios.editar', compact('usuario'));
    }

    public function guardar(Request $solicitud): RedirectResponse
    {
        Usuario::create($this->datosValidados($solicitud));

        return redirect()->route('administracion.usuarios.listado')->with('estado', 'Usuario creado.');
    }

    public function actualizar(Request $solicitud, Usuario $usuario, ServicioAdministracionUsuarios $servicio): RedirectResponse
    {
        abort_if($usuario->is($solicitud->user()), 403);

        $datos = $this->datosValidados($solicitud, $usuario);

        if ($datos['contrasena'] === null) {
            unset($datos['contrasena']);
        }

        $servicio->actualizar($solicitud->user(), $usuario, $datos);

        return redirect()->route('administracion.usuarios.listado')->with('estado', 'Usuario actualizado.');
    }

    public function actualizarEstado(Request $solicitud, Usuario $usuario, ServicioAdministracionUsuarios $servicio): RedirectResponse
    {
        $datos = $solicitud->validate([
            'activo' => [
                'required',
                'boolean',
            ],
        ]);

        $servicio->cambiarEstado($solicitud->user(), $usuario, (bool) $datos['activo']);

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
                Rule::in($this->roles()),
            ],

            'contrasena' => [
                $usuario ? 'nullable' : 'required',
                'confirmed',
                'min:8',
            ],

        ]);
    }

    /** @return list<string> */
    private function roles(): array
    {
        return [
            Usuario::ROL_ADMINISTRADOR,
            Usuario::ROL_COMERCIAL,
            Usuario::ROL_CONSULTA,
        ];
    }
}
