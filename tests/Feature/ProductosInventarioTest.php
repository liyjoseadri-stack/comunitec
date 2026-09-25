<?php

namespace Tests\Feature;

use App\Models\ArticuloCatalogo;
use App\Models\Categoria;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductosInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_crea_producto_serializable_con_categoria_y_stock(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes']);

        $this->actingAs($usuario)->post('/inventario/productos', [
            'tipo' => 'producto', 'nombre' => 'Memoria RAM', 'codigo' => 'RAM-16',
            'categoria_id' => $categoria->id, 'descripcion' => 'DDR4 16 GB',
            'unidad' => 'pieza', 'precio' => 850, 'existencias' => 5,
            'requiere_numero_serie' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('articulos_catalogo', [
            'codigo' => 'RAM-16', 'existencias' => 5, 'requiere_numero_serie' => true,
        ]);
    }

    public function test_servicio_fuerza_stock_cero_y_no_requiere_series(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Servicios']);

        $this->actingAs($usuario)->post('/inventario/productos', [
            'tipo' => 'servicio', 'nombre' => 'Instalación', 'codigo' => 'SER-01',
            'categoria_id' => $categoria->id, 'unidad' => 'servicio', 'precio' => 1000,
            'existencias' => 9, 'requiere_numero_serie' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('articulos_catalogo', [
            'codigo' => 'SER-01', 'existencias' => 0, 'requiere_numero_serie' => false,
        ]);
    }

    public function test_listado_busca_por_sku_y_filtra_categoria_estado(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Redes']);
        $this->producto($categoria, 'Switch visible', 'SW-VISIBLE', true);
        $this->producto($categoria, 'Router oculto', 'ROU-OCULTO', false);

        $this->actingAs($usuario)->get("/inventario/productos?buscar=SW-VISIBLE&categoria={$categoria->id}&estado=activos")
            ->assertOk()->assertSee('Switch visible')->assertDontSee('Router oculto');
    }

    public function test_editar_producto_no_elimina_su_identidad_historica(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Equipos']);
        $producto = $this->producto($categoria, 'Laptop', 'LAP-01', true);

        $this->actingAs($usuario)->put("/inventario/productos/{$producto->id}", [
            'tipo' => 'producto', 'nombre' => 'Laptop empresarial', 'codigo' => 'LAP-01',
            'categoria_id' => $categoria->id, 'unidad' => 'pieza', 'precio' => 15000,
            'existencias' => 4, 'requiere_numero_serie' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('articulos_catalogo', ['id' => $producto->id, 'nombre' => 'Laptop empresarial']);
    }

    public function test_no_permite_asignar_categoria_inactiva(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Archivada', 'activo' => false]);

        $this->actingAs($usuario)->post('/inventario/productos', [
            'tipo' => 'producto', 'nombre' => 'Producto', 'codigo' => 'PRO-01',
            'categoria_id' => $categoria->id, 'unidad' => 'pieza', 'precio' => 1, 'existencias' => 1,
        ])->assertSessionHasErrors('categoria_id');
    }

    private function producto(Categoria $categoria, string $nombre, string $codigo, bool $activo): ArticuloCatalogo
    {
        return ArticuloCatalogo::create([
            'categoria_id' => $categoria->id, 'tipo' => 'producto', 'nombre' => $nombre,
            'codigo' => $codigo, 'unidad' => 'pieza', 'precio' => 1000,
            'existencias' => 3, 'activo' => $activo,
        ]);
    }
}
