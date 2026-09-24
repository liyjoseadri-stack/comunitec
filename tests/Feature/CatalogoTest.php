<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogoTest extends TestCase
{
    use RefreshDatabase;

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
}
