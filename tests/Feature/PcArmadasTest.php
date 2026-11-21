<?php

namespace Tests\Feature;

use App\Models\PcArmada;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PcArmadasTest extends TestCase
{
    use RefreshDatabase;

    public function test_comercial_puede_crear_consultar_y_editar_una_pc_armada(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $producto = Producto::create([
            'nombre' => 'Placa B650',
            'codigo' => 'MB-PC-01',
            'unidad' => 'pieza',
            'precio' => 3500,
            'existencias' => 2,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
        ]);

        $respuesta = $this->actingAs($usuario)->post(route('inventario.pc-armadas.guardar'), [
            'nombre' => 'PC Oficina',
            'descripcion' => 'Configuración de oficina',
            'componentes' => [[
                'producto_id' => $producto->id,
                'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
                'cantidad' => 1,
            ]],
        ]);

        $plantilla = PcArmada::sole();
        $respuesta->assertRedirect(route('inventario.pc-armadas.detalle', $plantilla));
        $this->get(route('inventario.pc-armadas.listado'))->assertOk()->assertSee('PC Oficina');
        $this->get(route('inventario.pc-armadas.detalle', $plantilla))
            ->assertOk()
            ->assertSee('Placa B650');
        $this->get(route('inventario.pc-armadas.editar', $plantilla))->assertOk();

        $this->put(route('inventario.pc-armadas.actualizar', $plantilla), [
            'nombre' => 'PC Oficina Actualizada',
            'descripcion' => 'Configuración actualizada',
            'componentes' => [[
                'producto_id' => $producto->id,
                'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
                'cantidad' => 1,
            ]],
        ])->assertRedirect(route('inventario.pc-armadas.detalle', $plantilla));

        $this->assertDatabaseHas('pc_armadas', ['id' => $plantilla->id, 'nombre' => 'PC Oficina Actualizada']);
    }

    public function test_consulta_puede_ver_pero_no_modificar_plantillas(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]);
        $plantilla = PcArmada::create(['nombre' => 'PC Consulta']);

        $this->actingAs($usuario)
            ->get(route('inventario.pc-armadas.listado'))
            ->assertOk()
            ->assertSee('PC Consulta')
            ->assertDontSee(route('inventario.pc-armadas.editar', $plantilla), false);
    }
}
