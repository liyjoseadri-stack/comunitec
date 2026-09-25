<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_muestra_un_formulario_etiquetado_y_conserva_los_datos_anteriores(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $response = $this->actingAs($usuario)
            ->withSession([
                '_old_input' => [
                    'tipo' => 'servicio',
                    'nombre' => 'Instalación de red',
                    'codigo' => 'SER-001',
                    'precio' => '1250.00',
                ],
            ])
            ->get('/catalogo');

        $response->assertOk()
            ->assertSee('css/administracion.css', false)
            ->assertSee('<label for="tipo">Tipo de concepto</label>', false)
            ->assertSee('<label for="precio">Precio con IVA</label>', false)
            ->assertSee('<label for="existencias">Existencias iniciales</label>', false)
            ->assertSee('value="Instalación de red"', false)
            ->assertSee('value="SER-001"', false)
            ->assertSee('value="1250.00"', false)
            ->assertSee('<option value="servicio" selected>', false);
    }

    public function test_el_comercial_puede_crear_un_producto(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($usuario)->post('/catalogo', [
            'tipo' => 'producto',
            'nombre' => 'Laptop',
            'codigo' => 'LAP-001',
            'marca' => 'Dell',
            'model' => 'Latitude',
            'unidad' => 'pieza',
            'precio' => '12000.00',
            'existencias' => 3,
        ])->assertRedirect('/catalogo');
        $this->assertDatabaseHas('articulos_catalogo', [
            'codigo' => 'LAP-001',
            'tipo' => 'producto',
        ]);
    }

    public function test_un_servicio_puede_crearse_sin_existencias(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($usuario)->post('/catalogo', [
            'tipo' => 'servicio',
            'nombre' => 'Instalación de red',
            'codigo' => 'SER-001',
            'unidad' => 'servicio',
            'precio' => '1250.00',
            'existencias' => '',
        ])->assertRedirect('/catalogo')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('articulos_catalogo', [
            'codigo' => 'SER-001',
            'tipo' => 'servicio',
            'existencias' => 0,
        ]);
    }

    public function test_un_producto_sigue_requiriendo_existencias_enteras(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($usuario)->post('/catalogo', [
            'tipo' => 'producto',
            'nombre' => 'Laptop',
            'codigo' => 'LAP-SIN-STOCK',
            'unidad' => 'pieza',
            'precio' => '12000.00',
            'existencias' => '',
        ])->assertSessionHasErrors('existencias');

        $this->assertDatabaseMissing('articulos_catalogo', [
            'codigo' => 'LAP-SIN-STOCK',
        ]);
    }

    public function test_el_comercial_puede_desactivar_y_reactivar_un_articulo(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'servicio',
            'nombre' => 'Instalación de red',
            'codigo' => 'SER-ESTADO',
            'unidad' => 'servicio',
            'precio' => 1250,
            'existencias' => 0,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->patch("/catalogo/{$articulo->id}/estado")
            ->assertRedirect('/catalogo');

        $this->assertDatabaseHas('articulos_catalogo', [
            'id' => $articulo->id,
            'activo' => false,
        ]);

        $this->patch("/catalogo/{$articulo->id}/estado")
            ->assertRedirect('/catalogo');

        $this->assertDatabaseHas('articulos_catalogo', [
            'id' => $articulo->id,
            'activo' => true,
        ]);
    }
}
