<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductosComponentesPcTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_guarda_el_tipo_de_componente_pc(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes']);

        $this->actingAs($usuario)->post(route('inventario.productos.guardar'), [
            'nombre' => 'Placa madre B650',
            'codigo' => 'MB-B650',
            'categoria_id' => $categoria->id,
            'unidad' => 'pieza',
            'precio' => 3500,
            'existencias' => 2,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
        ])->assertRedirect();

        $this->assertDatabaseHas('productos', [
            'codigo' => 'MB-B650',
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
        ]);
    }

    public function test_no_permite_un_tipo_de_componente_inexistente(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $categoria = Categoria::create(['nombre' => 'Componentes']);

        $this->actingAs($usuario)->post(route('inventario.productos.guardar'), [
            'nombre' => 'Producto inválido',
            'codigo' => 'PC-INVALIDO',
            'categoria_id' => $categoria->id,
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 1,
            'tipo_componente_pc' => 'monitor',
        ])->assertSessionHasErrors('tipo_componente_pc');
    }

    public function test_listado_filtra_productos_por_tipo_de_componente(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        Producto::create([
            'nombre' => 'Placa visible',
            'codigo' => 'MB-VISIBLE',
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 1,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
        ]);
        Producto::create([
            'nombre' => 'Memoria oculta',
            'codigo' => 'RAM-OCULTA',
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 1,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_MEMORIA_RAM,
        ]);

        $this->actingAs($usuario)
            ->get(route('inventario.productos.listado', ['tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE]))
            ->assertOk()
            ->assertSee('Placa visible')
            ->assertDontSee('Memoria oculta');
    }
}
