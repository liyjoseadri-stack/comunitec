<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_inventario_unificado_no_permite_registrar_series_antes_de_la_venta(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);

        $this->actingAs($usuario)->post('/inventario/series', [
            'articulo_catalogo_id' => 1,
            'numero_serie' => 'SN-ANTICIPADA',
        ])->assertNotFound();
    }
}
