<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_anterior_del_catalogo_dirige_al_inventario_unificado(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($usuario)->get('/catalogo')->assertRedirect('/inventario');
    }

    public function test_las_escrituras_del_catalogo_anterior_ya_no_existen(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($usuario)->post('/catalogo')->assertStatus(405);
        $this->patch('/catalogo/1/estado')->assertNotFound();
    }
}
