<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_tiene_el_rol_correcto(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->assertTrue($user->esAdministrador());
        $this->assertFalse($user->esComercial());
        $this->assertFalse($user->esConsulta());
    }
}
