<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalizacionPcCotizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guarda_una_pc_personalizada_como_grupo_privado_de_la_cotizacion(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente configurador',
            'rfc' => 'CCO010101AA2',
            'correo' => 'configurador@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Procesador Ryzen',
            'codigo' => 'CPU-01',
            'unidad' => 'pieza',
            'precio' => 3500,
            'existencias' => 4,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
        ]);

        $respuesta = $this->actingAs($usuario)->postJson(route('api.cotizaciones.guardar'), [
            'cliente_id' => $cliente->id,
            'porcentaje_descuento' => 0,
            'items' => [],
            'pc_armadas' => [[
                'nombre' => 'PC del cliente',
                'componentes' => [[
                    'producto_id' => $producto->id,
                    'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PROCESADOR,
                    'cantidad' => 1,
                ]],
            ]],
        ]);

        $respuesta->assertCreated();
        $cotizacion = Cotizacion::with('configuracionesPc.partidas')->findOrFail($respuesta->json('cotizacion_id'));
        $configuracion = $cotizacion->configuracionesPc->sole();

        $this->assertSame('PC del cliente', $configuracion->nombre);
        $this->assertNull($configuracion->pc_armada_id);
        $this->assertSame($producto->id, $configuracion->partidas->sole()->producto_id);
        $this->assertSame($configuracion->id, $configuracion->partidas->sole()->configuracion_pc_cotizacion_id);
    }

    public function test_rechaza_un_producto_con_apartado_incorrecto(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Cliente inválido',
            'rfc' => 'CIN010101AA2',
            'correo' => 'invalido@example.test',
            'telefono' => '9610000001',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Placa madre',
            'codigo' => 'MB-01',
            'unidad' => 'pieza',
            'precio' => 2200,
            'existencias' => 2,
            'tipo_componente_pc' => Producto::TIPO_COMPONENTE_PLACA_MADRE,
        ]);

        $this->actingAs($usuario)->postJson(route('api.cotizaciones.guardar'), [
            'cliente_id' => $cliente->id,
            'items' => [],
            'pc_armadas' => [[
                'nombre' => 'PC inválida',
                'componentes' => [[
                    'producto_id' => $producto->id,
                    'tipo_componente_pc' => Producto::TIPO_COMPONENTE_MEMORIA_RAM,
                    'cantidad' => 1,
                ]],
            ]],
        ])->assertUnprocessable();
    }
}
