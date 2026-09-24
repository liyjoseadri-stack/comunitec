<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutorizacionRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_ADMINISTRADOR,
        ]))
            ->get('/administracion/usuarios')
            ->assertOk()
            ->assertSee('Administración de usuarios');
    }

    public function test_el_comercial_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }

    public function test_el_usuario_de_consulta_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create([
            'role' => Usuario::ROL_CONSULTA,
        ]))
            ->get('/administracion/usuarios')
            ->assertForbidden();
    }
}
