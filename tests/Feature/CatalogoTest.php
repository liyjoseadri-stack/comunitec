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
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                '_old_input' => [
                    'type' => 'service',
                    'name' => 'Instalación de red',
                    'code' => 'SER-001',
                    'price' => '1250.00',
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
            ->assertSee('<option value="service" selected>', false);
    }

    public function test_el_comercial_puede_crear_un_producto(): void
    {
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($user)->post('/catalogo', [
            'type' => 'product',
            'name' => 'Laptop',
            'code' => 'LAP-001',
            'brand' => 'Dell',
            'model' => 'Latitude',
            'unit' => 'pieza',
            'price' => '12000.00',
            'stock' => 3,
        ])->assertRedirect('/catalogo');
        $this->assertDatabaseHas('catalog_items', [
            'code' => 'LAP-001',
            'type' => 'product',
        ]);
    }

    public function test_un_servicio_puede_crearse_sin_existencias(): void
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($usuario)->post('/catalogo', [
            'type' => 'service',
            'name' => 'Instalación de red',
            'code' => 'SER-001',
            'unit' => 'servicio',
            'price' => '1250.00',
            'stock' => '',
        ])->assertRedirect('/catalogo')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('catalog_items', [
            'code' => 'SER-001',
            'type' => 'service',
            'stock' => 0,
        ]);
    }

    public function test_un_producto_sigue_requiriendo_existencias_enteras(): void
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);

        $this->actingAs($usuario)->post('/catalogo', [
            'type' => 'product',
            'name' => 'Laptop',
            'code' => 'LAP-SIN-STOCK',
            'unit' => 'pieza',
            'price' => '12000.00',
            'stock' => '',
        ])->assertSessionHasErrors('stock');

        $this->assertDatabaseMissing('catalog_items', [
            'code' => 'LAP-SIN-STOCK',
        ]);
    }

    public function test_el_comercial_puede_desactivar_y_reactivar_un_articulo(): void
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $articulo = ArticuloCatalogo::create([
            'type' => 'service',
            'name' => 'Instalación de red',
            'code' => 'SER-ESTADO',
            'unit' => 'servicio',
            'price' => 1250,
            'stock' => 0,
            'active' => true,
        ]);

        $this->actingAs($usuario)
            ->patch("/catalogo/{$articulo->id}/estado")
            ->assertRedirect('/catalogo');

        $this->assertDatabaseHas('catalog_items', [
            'id' => $articulo->id,
            'active' => false,
        ]);

        $this->patch("/catalogo/{$articulo->id}/estado")
            ->assertRedirect('/catalogo');

        $this->assertDatabaseHas('catalog_items', [
            'id' => $articulo->id,
            'active' => true,
        ]);
    }
}
