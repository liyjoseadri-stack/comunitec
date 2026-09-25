<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductosInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_crea_producto_serializable_con_categoria_y_stock(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes']);

        $this->actingAs($usuario)->post('/inventario/productos', [
            'nombre' => 'Memoria RAM', 'codigo' => 'RAM-16',
            'categoria_id' => $categoria->id, 'descripcion' => 'DDR4 16 GB',
            'unidad' => 'pieza', 'precio' => 850, 'existencias' => 5,
            'requiere_numero_serie' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'codigo' => 'RAM-16', 'existencias' => 5, 'requiere_numero_serie' => true,
        ]);
    }

    public function test_servicio_se_guarda_en_su_tabla_sin_campos_de_inventario(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Servicios']);

        $this->actingAs($usuario)->post('/inventario/servicios', [
            'nombre' => 'Instalación', 'codigo' => 'SER-01',
            'categoria_id' => $categoria->id, 'unidad' => 'servicio', 'precio' => 1000,
        ])->assertRedirect();

        $this->assertDatabaseHas('servicios', ['codigo' => 'SER-01']);
        $this->assertFalse(Schema::hasColumn('servicios', 'existencias'));
        $this->assertFalse(Schema::hasColumn('servicios', 'requiere_numero_serie'));
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
            'nombre' => 'Laptop empresarial', 'codigo' => 'LAP-01',
            'categoria_id' => $categoria->id, 'unidad' => 'pieza', 'precio' => 15000,
            'existencias' => 4, 'requiere_numero_serie' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'nombre' => 'Laptop empresarial']);
    }

    public function test_detalle_incluye_la_categoria_actual_aunque_este_inactiva(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes heredados', 'activo' => false]);
        $producto = $this->producto($categoria, 'Memoria DDR3', 'RAM-DDR3', true);

        $this->actingAs($usuario)
            ->get(route('inventario.productos.detalle', $producto))
            ->assertOk()
            ->assertSee('Componentes heredados');
    }

    public function test_no_permite_asignar_categoria_inactiva(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Archivada', 'activo' => false]);

        $this->actingAs($usuario)->post('/inventario/productos', [
            'nombre' => 'Producto', 'codigo' => 'PRO-01',
            'categoria_id' => $categoria->id, 'unidad' => 'pieza', 'precio' => 1, 'existencias' => 1,
        ])->assertSessionHasErrors('categoria_id');
    }

    private function producto(Categoria $categoria, string $nombre, string $codigo, bool $activo): Producto
    {
        return Producto::create([
            'categoria_id' => $categoria->id,  'nombre' => $nombre,
            'codigo' => $codigo, 'unidad' => 'pieza', 'precio' => 1000,
            'existencias' => 3, 'activo' => $activo,
        ]);
    }
}
