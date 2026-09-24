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
        $user = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $item = ArticuloCatalogo::create([
            'type' => 'product',
            'name' => 'Equipo',
            'code' => 'EQ-01',
            'unit' => 'pieza',
            'price' => 100,
            'stock' => 0,
        ]);
        $this->actingAs($user)->post('/inventario/series', [
            'catalog_item_id' => $item->id,
            'serial_number' => 'SN-001',
        ])->assertRedirect();
        $this->assertDatabaseHas('inventory_units', [
            'catalog_item_id' => $item->id,
            'serial_number' => 'SN-001',
            'status' => 'available',
        ]);
    }
}
