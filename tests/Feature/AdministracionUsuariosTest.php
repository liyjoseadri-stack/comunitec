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
        $admin = Usuario::factory()->create([
            'role' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->actingAs($admin)->post('/administracion/usuarios', [

            'name' => 'Ana Comercial',

            'email' => 'ana@example.com',

            'role' => Usuario::ROL_COMERCIAL,

            'password' => 'secret-password',

            'password_confirmation' => 'secret-password',

        ])->assertRedirect('/administracion/usuarios');

        $user = Usuario::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame(Usuario::ROL_COMERCIAL, $user->role);
        $this->assertTrue($user->active);

        $this->patch("/administracion/usuarios/{$user->id}/estado", [
            'active' => false,
        ])
            ->assertRedirect('/administracion/usuarios');

        $this->assertFalse($user->fresh()->active);
    }

    public function test_el_administrador_puede_actualizar_usuarios(): void
    {
        $admin = Usuario::factory()->create([
            'role' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($admin)->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Editar');

        $this->actingAs($admin)->put("/administracion/usuarios/{$user->id}", [

            'name' => 'Usuario actualizado',

            'email' => 'actualizado@example.com',

            'role' => Usuario::ROL_CONSULTA,

            'password' => '',

            'password_confirmation' => '',

        ])->assertRedirect('/administracion/usuarios');

        $this->assertDatabaseHas('users', [

            'id' => $user->id,

            'name' => 'Usuario actualizado',

            'email' => 'actualizado@example.com',

            'role' => Usuario::ROL_CONSULTA,

        ]);
    }
}
