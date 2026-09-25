<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comercial_puede_registrar_una_pieza_con_serie(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $articulo = ArticuloCatalogo::create([
            'tipo' => 'producto',
            'nombre' => 'Equipo',
            'codigo' => 'EQ-01',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 0,
        ]);
        $this->actingAs($usuario)->post('/inventario/series', [
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'SN-001',
        ])->assertRedirect();
        $this->assertDatabaseHas('piezas_inventario', [
            'articulo_catalogo_id' => $articulo->id,
            'numero_serie' => 'SN-001',
            'estado' => 'disponible',
        ]);
    }
}
