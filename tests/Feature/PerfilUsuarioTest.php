<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PerfilUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_actualiza_solo_nombre_y_correo(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
            'activo' => true,
        ]);

        $this->actingAs($usuario)->put(route('perfil.actualizar'), [
            'nombre' => 'Nombre actualizado',
            'correo' => 'actualizado@example.test',
            'rol' => Usuario::ROL_ADMINISTRADOR,
            'activo' => false,
        ])->assertRedirect(route('perfil.mostrar'));

        $usuario->refresh();
        $this->assertSame('Nombre actualizado', $usuario->nombre);
        $this->assertSame('actualizado@example.test', $usuario->correo);
        $this->assertSame(Usuario::ROL_COMERCIAL, $usuario->rol);
        $this->assertTrue($usuario->activo);
    }

    public function test_perfil_permite_conservar_el_correo_propio_pero_no_duplicarlo(): void
    {
        $usuario = Usuario::factory()->create(['correo' => 'propio@example.test']);
        Usuario::factory()->create(['correo' => 'ocupado@example.test']);

        $this->actingAs($usuario)->put(route('perfil.actualizar'), [
            'nombre' => $usuario->nombre,
            'correo' => 'propio@example.test',
        ])->assertSessionHasNoErrors();

        $this->put(route('perfil.actualizar'), [
            'nombre' => $usuario->nombre,
            'correo' => 'ocupado@example.test',
        ])->assertSessionHasErrors('correo');
    }

    public function test_contrasena_actual_incorrecta_no_cambia_el_hash(): void
    {
        $usuario = Usuario::factory()->create([
            'contrasena' => Hash::make('Anterior2026!'),
        ]);
        $hash = $usuario->contrasena;

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'contrasena_actual' => 'Incorrecta',
            'contrasena' => 'Nueva2026!',
            'contrasena_confirmation' => 'Nueva2026!',
        ])->assertSessionHasErrors('contrasena_actual');

        $this->assertSame($hash, $usuario->fresh()->contrasena);
    }

    public function test_usuario_cambia_su_contrasena_con_la_actual_y_confirmacion(): void
    {
        $usuario = Usuario::factory()->create([
            'contrasena' => Hash::make('Anterior2026!'),
        ]);

        $this->actingAs($usuario)->put(route('perfil.contrasena'), [
            'contrasena_actual' => 'Anterior2026!',
            'contrasena' => 'Nueva2026!',
            'contrasena_confirmation' => 'Nueva2026!',
        ])->assertRedirect(route('perfil.mostrar'));

        $this->assertTrue(Hash::check('Nueva2026!', $usuario->fresh()->contrasena));
    }

    public function test_los_tres_roles_pueden_ver_su_perfil(): void
    {
        foreach ([
            Usuario::ROL_ADMINISTRADOR,
            Usuario::ROL_COMERCIAL,
            Usuario::ROL_CONSULTA,
        ] as $rol) {
            $usuario = Usuario::factory()->create(['rol' => $rol]);

            $this->actingAs($usuario)->get(route('perfil.mostrar'))
                ->assertOk()
                ->assertSee($usuario->nombre)
                ->assertSee($usuario->correo)
                ->assertSee('Cambiar contraseña');
        }
    }
}
