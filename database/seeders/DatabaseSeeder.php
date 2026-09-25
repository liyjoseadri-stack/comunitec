<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga datos iniciales en la base de datos.
     */
    public function run(): void
    {
        $correo = env('USUARIO_ADMIN_CORREO');
        $contrasena = env('USUARIO_ADMIN_CONTRASENA');

        if (! $correo || ! $contrasena) {
            $this->command?->warn('No se creó el administrador: configura USUARIO_ADMIN_CORREO y USUARIO_ADMIN_CONTRASENA.');

            return;
        }

        Usuario::query()->updateOrCreate(
            ['correo' => $correo],
            [
                'nombre' => env('USUARIO_ADMIN_NOMBRE', 'Administrador Comunitec'),
                'contrasena' => Hash::make($contrasena),
                'rol' => Usuario::ROL_ADMINISTRADOR,
                'activo' => true,
            ]
        );
    }
}
