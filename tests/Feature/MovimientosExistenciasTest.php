<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MovimientosExistenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_esquema_guarda_el_producto_responsable_valores_motivo_y_fecha(): void
    {
        $this->assertTrue(Schema::hasColumns('movimientos_existencias', [
            'id',
            'producto_id',
            'usuario_id',
            'existencias_anteriores',
            'existencias_nuevas',
            'motivo',
            'creado_en',
        ]));
    }

    public function test_cambiar_existencias_exige_motivo_y_no_modifica_el_producto_si_falta(): void
    {
        [$usuario, $producto] = $this->prepararProducto(5);

        $this->actingAs($usuario)
            ->from(route('inventario.productos.editar', $producto))
            ->put(route('inventario.productos.actualizar', $producto), $this->datos($producto, 8))
            ->assertRedirect(route('inventario.productos.editar', $producto))
            ->assertSessionHasErrors(['motivo_existencias']);

        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertDatabaseCount('movimientos_existencias', 0);
    }

    public function test_cambiar_existencias_registra_un_movimiento_con_responsable_y_motivo(): void
    {
        [$usuario, $producto] = $this->prepararProducto(5);

        $this->actingAs($usuario)
            ->put(route('inventario.productos.actualizar', $producto), [
                ...$this->datos($producto, 8),
                'motivo_existencias' => 'Recepción de tres módulos del proveedor.',
            ])
            ->assertRedirect(route('inventario.productos.detalle', $producto));

        $this->assertSame(8, $producto->fresh()->existencias);
        $this->assertDatabaseHas('movimientos_existencias', [
            'producto_id' => $producto->id,
            'usuario_id' => $usuario->id,
            'existencias_anteriores' => 5,
            'existencias_nuevas' => 8,
            'motivo' => 'Recepción de tres módulos del proveedor.',
        ]);
    }

    public function test_actualizar_otros_datos_sin_cambiar_existencias_no_exige_motivo_ni_registra_movimiento(): void
    {
        [$usuario, $producto] = $this->prepararProducto(5);

        $this->actingAs($usuario)
            ->put(route('inventario.productos.actualizar', $producto), [
                ...$this->datos($producto, 5),
                'nombre' => 'Memoria RAM actualizada',
                'motivo_existencias' => 'Este texto no debe generar un movimiento.',
            ])
            ->assertRedirect(route('inventario.productos.detalle', $producto));

        $this->assertSame('Memoria RAM actualizada', $producto->fresh()->nombre);
        $this->assertDatabaseCount('movimientos_existencias', 0);
    }

    public function test_el_detalle_muestra_el_historial_de_ajustes_mas_reciente_primero(): void
    {
        [$usuario, $producto] = $this->prepararProducto(5);

        $this->actingAs($usuario)->put(route('inventario.productos.actualizar', $producto), [
            ...$this->datos($producto, 7),
            'motivo_existencias' => 'Entrada por compra.',
        ]);

        $this->get(route('inventario.productos.detalle', $producto))
            ->assertOk()
            ->assertSee('Historial de ajustes de existencias')
            ->assertSee('Entrada por compra.')
            ->assertSee($usuario->nombre)
            ->assertSee('5')
            ->assertSee('7');
    }

    private function prepararProducto(int $existencias): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Memoria RAM',
            'codigo' => 'RAM-AUD-01',
            'unidad' => 'pieza',
            'precio' => 850,
            'existencias' => $existencias,
        ]);

        return [$usuario, $producto];
    }

    private function datos(Producto $producto, int $existencias): array
    {
        return [
            'categoria_id' => $producto->categoria_id,
            'nombre' => $producto->nombre,
            'codigo' => $producto->codigo,
            'unidad' => $producto->unidad,
            'precio' => $producto->precio,
            'existencias' => $existencias,
        ];
    }
}
