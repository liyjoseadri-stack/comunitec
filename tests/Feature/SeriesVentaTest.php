<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeriesVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_formulario_solicita_la_cantidad_exacta_solo_para_productos_serializables(): void
    {
        [$usuario, $cotizacion, $partidaSerializable, $partidaSinSerie] = $this->prepararVenta();

        $detalleCotizacion = $this->actingAs($usuario)->get(route('cotizaciones.detalle', $cotizacion));
        $respuesta = $this->get(route('ventas.crear', $cotizacion));

        $respuesta->assertOk();
        $this->assertSame(
            2,
            substr_count($respuesta->getContent(), 'name="series['.$partidaSerializable->id.'][]"')
        );
        $respuesta->assertDontSee('name="series['.$partidaSinSerie->id.'][]"', false);
        $detalleCotizacion
            ->assertSee(route('ventas.crear', $cotizacion), false)
            ->assertDontSee('name="series[', false);
    }

    public function test_registra_las_series_escritas_en_la_venta_y_su_producto(): void
    {
        [$usuario, $cotizacion, $partidaSerializable] = $this->prepararVenta();

        $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'series' => [
                $partidaSerializable->id => [' AP-ABC-001 ', 'AP-ABC-002'],
            ],
        ])->assertRedirect();

        $venta = Venta::sole();
        $partidaVendida = $venta->partidas()
            ->where('partida_cotizacion_id', $partidaSerializable->id)
            ->sole();

        $this->assertDatabaseHas('piezas_inventario', [
            'numero_serie' => 'AP-ABC-001',
            'producto_id' => $partidaSerializable->producto_id,
            'partida_venta_id' => $partidaVendida->id,
            'estado' => 'entregada',
        ]);
        $this->assertDatabaseHas('piezas_inventario', [
            'numero_serie' => 'AP-ABC-002',
            'partida_venta_id' => $partidaVendida->id,
        ]);
    }

    public function test_rechaza_cantidades_incorrectas_series_vacias_y_duplicadas(): void
    {
        foreach ([
            ['AP-001'],
            ['AP-001', ''],
            ['AP-001', ' ap-001 '],
            ['AP-001', 'AP-002', 'AP-003'],
        ] as $seriesInvalidas) {
            [$usuario, $cotizacion, $partidaSerializable] = $this->prepararVenta();

            $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
                'metodo_pago' => Venta::METODO_EFECTIVO,
                'series' => [$partidaSerializable->id => $seriesInvalidas],
            ])->assertSessionHasErrors("series.{$partidaSerializable->id}");

            $this->assertSame(0, Venta::count());
        }
    }

    public function test_rechaza_una_serie_ya_vendida_y_claves_de_partidas_no_serializables(): void
    {
        [$usuario, $cotizacion, $partidaSerializable, $partidaSinSerie] = $this->prepararVenta();
        PiezaInventario::create([
            'producto_id' => $partidaSerializable->producto_id,
            'numero_serie' => 'SERIE-USADA',
            'estado' => 'entregada',
        ]);

        $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaSerializable->id => ['serie-usada', 'SERIE-NUEVA'],
                $partidaSinSerie->id => ['NO-DEBE-ACEPTARSE'],
            ],
        ])->assertSessionHasErrors("series.{$partidaSinSerie->id}");

        $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'series' => [
                $partidaSerializable->id => ['serie-usada', 'SERIE-NUEVA'],
            ],
        ])->assertSessionHasErrors("series.{$partidaSerializable->id}");

        $this->assertSame(0, Venta::count());
    }

    public function test_permite_vender_productos_no_serializables_sin_capturar_series(): void
    {
        [$usuario, $cotizacion, $partidaSerializable] = $this->prepararVenta();
        $partidaSerializable->articulo->update(['requiere_numero_serie' => false]);

        $this->actingAs($usuario)->post(route('ventas.guardar', $cotizacion), [
            'metodo_pago' => Venta::METODO_TARJETA,
        ])->assertRedirect();

        $this->assertSame(1, Venta::count());
        $this->assertSame(0, PiezaInventario::count());
    }

    private function prepararVenta(): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente de series',
            'rfc' => fake()->unique()->bothify('???######??#'),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $serializable = Producto::create([

            'nombre' => 'Access Point',
            'codigo' => fake()->unique()->bothify('AP-####'),
            'unidad' => 'pieza',
            'precio' => 1000,
            'existencias' => 3,
            'requiere_numero_serie' => true,
        ]);
        $sinSerie = Producto::create([

            'nombre' => 'Cable de red',
            'codigo' => fake()->unique()->bothify('CAB-####'),
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 5,
            'requiere_numero_serie' => false,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-SERIES-'.fake()->unique()->numerify('####'),
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'vence_en' => now()->addDays(5),
            'porcentaje_descuento' => 0,
            'total' => 2100,
        ]);
        $partidaSerializable = $cotizacion->partidas()->create([
            'producto_id' => $serializable->id,

            'descripcion' => 'Access Point',
            'cantidad' => 2,
            'precio_unitario' => 1000,
            'subtotal' => 2000,
        ]);
        $partidaSinSerie = $cotizacion->partidas()->create([
            'producto_id' => $sinSerie->id,

            'descripcion' => 'Cable de red',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        return [$usuario, $cotizacion, $partidaSerializable, $partidaSinSerie];
    }
}
