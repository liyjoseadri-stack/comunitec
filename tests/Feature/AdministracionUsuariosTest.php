<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministracionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_puede_crear_y_desactivar_usuarios(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->actingAs($administrador)->post('/administracion/usuarios', [

            'nombre' => 'Ana Comercial',

            'correo' => 'ana@example.com',

            'rol' => Usuario::ROL_COMERCIAL,

            'contrasena' => 'secret-password',

            'contrasena_confirmation' => 'secret-password',

        ])->assertRedirect('/administracion/usuarios');

        $usuario = Usuario::where('correo', 'ana@example.com')->firstOrFail();
        $this->assertSame(Usuario::ROL_COMERCIAL, $usuario->rol);
        $this->assertTrue($usuario->activo);

        $this->patch("/administracion/usuarios/{$usuario->id}/estado", [
            'activo' => false,
        ])
            ->assertRedirect('/administracion/usuarios');

        $this->assertFalse($usuario->fresh()->activo);
    }

    public function test_el_administrador_puede_actualizar_usuarios(): void
    {
        $administrador = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($administrador)->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Editar');

        $this->actingAs($administrador)->put("/administracion/usuarios/{$usuario->id}", [

            'nombre' => 'Usuario actualizado',

            'correo' => 'actualizado@example.com',

            'rol' => Usuario::ROL_CONSULTA,

            'contrasena' => '',

            'contrasena_confirmation' => '',

        ])->assertRedirect('/administracion/usuarios');

        $this->assertDatabaseHas('usuarios', [

            'id' => $usuario->id,

            'nombre' => 'Usuario actualizado',

            'correo' => 'actualizado@example.com',

            'rol' => Usuario::ROL_CONSULTA,

        ]);
    }
}
