<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServicioAdministracionUsuarios
{
    public function actualizar(Usuario $actor, Usuario $objetivo, array $datos): void
    {
        $this->actualizarProtegido($actor, $objetivo, $datos);
    }

    public function cambiarEstado(Usuario $actor, Usuario $objetivo, bool $activo): void
    {
        $this->actualizarProtegido($actor, $objetivo, ['activo' => $activo]);
    }

    private function actualizarProtegido(Usuario $actor, Usuario $objetivo, array $datos): void
    {
        DB::transaction(function () use ($actor, $objetivo, $datos): void {
            // Todas las transiciones adquieren los bloqueos en el mismo orden.
            $administradores = Usuario::where('rol', Usuario::ROL_ADMINISTRADOR)
                ->where('activo', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $usuario = Usuario::whereKey($objetivo->getKey())->lockForUpdate()->firstOrFail();
            $eraAdministradorActivo = $usuario->activo && $usuario->esAdministrador();

            $usuario->fill($datos);

            if ($actor->is($usuario) && ! $usuario->activo) {
                throw ValidationException::withMessages([
                    'usuario' => 'No puedes desactivar tu propia cuenta.',
                ]);
            }

            if ($eraAdministradorActivo
                && (! $usuario->activo || ! $usuario->esAdministrador())
                && $administradores->count() <= 1) {
                throw ValidationException::withMessages([
                    'usuario' => 'Debe permanecer al menos un administrador activo.',
                ]);
            }

            if (array_key_exists('activo', $datos) && ! $usuario->activo) {
                $usuario->setRememberToken(Str::random(60));
            }

            $usuario->save();
        }, 5);
    }
}
