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
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);

        $this->assertTrue($usuario->esAdministrador());
        $this->assertFalse($usuario->esComercial());
        $this->assertFalse($usuario->esConsulta());
    }
}
