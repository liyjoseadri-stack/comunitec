<?php

namespace Tests\Feature;

use App\Http\Controllers\ControladorCotizaciones;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\ServicioConversionVenta;
use App\Services\ServicioInventarioCotizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IntegridadCotizacionTest extends TestCase
{
    use RefreshDatabase;

    public static function transicionesConDatosInvalidos(): iterable
    {
        foreach (['enviar', 'correo', 'aceptar', 'venta'] as $accion) {
            foreach ([
                'vacia' => [],
                'subtotal alterado' => ['subtotal' => 99],
                'total alterado' => ['total' => 99],
                'cantidad cero' => ['cantidad' => 0],
                'cantidad negativa' => ['cantidad' => -1],
                'producto fraccionario' => ['cantidad' => 1.5, 'subtotal' => 150, 'total' => 150],
                'precio negativo' => ['precio_unitario' => -100],
                'precio no numerico' => ['precio_unitario' => 'invalido'],
                'subtotal negativo' => ['subtotal' => -100],
                'total negativo' => ['total' => -100],
                'descuento de partida negativo' => ['porcentaje_descuento' => -10],
                'descuento de partida excesivo' => ['porcentaje_descuento' => 101],
                'descuento general invalido' => ['descuento_general' => 50, 'total' => 50],
                'cantidad de servicio con precision excesiva' => [
                    'tipo' => 'servicio',
                    'cantidad' => 1.333,
                    'subtotal' => 133.3,
                    'total' => 133.3,
                ],
                'precio con precision excesiva' => ['precio_unitario' => 12.345, 'subtotal' => 12.35, 'total' => 12.35],
                'descuento de partida con precision excesiva' => [
                    'porcentaje_descuento' => 7.255,
                    'subtotal' => 92.75,
                    'total' => 92.75,
                ],
                'descuento general con precision excesiva' => ['descuento_general' => 7.255, 'total' => 92.75],
                'subtotal con precision excesiva' => ['subtotal' => 100.001],
                'total con precision excesiva' => ['total' => 100.001],
            ] as $caso => $cambios) {
                yield "{$accion}: {$caso}" => [$accion, $caso, $cambios];
            }
        }
    }

    #[DataProvider('transicionesConDatosInvalidos')]
    public function test_una_cotizacion_inconsistente_no_avanza_ni_altera_las_reservas(
        string $accion,
        string $caso,
        array $cambios
    ): void {
        Mail::fake();
        [$usuario, $cotizacion, $producto, $partida] = $this->preparar($accion);
        if ($caso === 'vacia') {
            $partida->delete();
        } else {
            if (($cambios['tipo'] ?? null) === 'servicio') {
                $servicio = Servicio::create([
                    'nombre' => 'Servicio de prueba',
                    'codigo' => 'SER-INTEGRIDAD',
                    'unidad' => 'servicio',
                    'precio' => 100,
                ]);
                $cambios['producto_id'] = null;
                $cambios['servicio_id'] = $servicio->id;
            }
            if (array_key_exists('total', $cambios)) {
                DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['total' => $cambios['total']]);
                unset($cambios['total']);
            }
            if (array_key_exists('descuento_general', $cambios)) {
                DB::table('cotizaciones')->where('id', $cotizacion->id)->update([
                    'porcentaje_descuento' => $cambios['descuento_general'],
                ]);
                unset($cambios['descuento_general']);
            }
            if ($cambios !== []) {
                DB::table('partidas_cotizacion')->where('id', $partida->id)->update($cambios);
            }
        }
        $antes = $cotizacion->fresh()->getAttributes();
        $pieza = PiezaInventario::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'RESERVA-PREVIA',
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);
        $reservaAnterior = $pieza->fresh()->getAttributes();

        $this->actingAs($usuario)->postJson("/cotizaciones/{$cotizacion->id}/{$accion}", [
            'metodo_pago' => Venta::METODO_EFECTIVO,
            'token_envio' => '55555555-5555-4555-8555-555555555555',
        ])->assertUnprocessable()->assertJsonValidationErrors('cotizacion');

        $this->assertSame($antes, $cotizacion->fresh()->getAttributes());
        $this->assertSame($reservaAnterior, $pieza->fresh()->getAttributes());
        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('partidas_venta', 0);
        $this->assertDatabaseCount('envios_correo_cotizacion', 0);
        Mail::assertNothingSent();
    }

    public function test_aceptar_relee_las_partidas_y_el_total_aunque_reciba_una_copia_anterior(): void
    {
        [, $cotizacion, $producto] = $this->preparar('aceptar');
        $cotizacion->load('partidas');
        DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['total' => 1]);

        try {
            app(ServicioInventarioCotizacion::class)->reservar($cotizacion);
            $this->fail('La copia anterior no debe permitir reservar una cotización alterada.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('cotizacion', $excepcion->errors());
        }

        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertSame(5, $producto->fresh()->existencias);
    }

    public function test_el_controlador_de_aceptacion_valida_el_estado_persistido_dentro_de_la_transaccion(): void
    {
        [$usuario, $cotizacion, $producto] = $this->preparar('enviar');
        $this->actingAs($usuario);
        DB::table('cotizaciones')->where('id', $cotizacion->id)->update(['estado' => 'pendiente']);

        app(ControladorCotizaciones::class)->aceptar($cotizacion, app(ServicioInventarioCotizacion::class));

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame(4, $producto->fresh()->existencias);
    }

    public function test_enviar_relee_las_partidas_aunque_reciba_una_copia_anterior(): void
    {
        Mail::fake();
        [$usuario, $cotizacion, , $partida] = $this->preparar('enviar');
        $this->actingAs($usuario);
        $cotizacion->load('partidas');
        DB::table('partidas_cotizacion')->where('id', $partida->id)->update(['subtotal' => 99]);

        try {
            request()->merge(['token_envio' => '66666666-6666-4666-8666-666666666666']);
            app(ControladorCotizaciones::class)->enviar(request(), $cotizacion);
            $this->fail('La copia anterior no debe permitir enviar partidas alteradas.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('cotizacion', $excepcion->errors());
        }

        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->enviada_en);
        Mail::assertNothingSent();
        $this->assertDatabaseCount('envios_correo_cotizacion', 0);
    }

    public function test_convertir_relee_las_partidas_aunque_reciba_una_copia_anterior(): void
    {
        [$usuario, $cotizacion, $producto, $partida] = $this->preparar('venta');
        $cotizacion->load('partidas');
        DB::table('partidas_cotizacion')->where('id', $partida->id)->update(['cantidad' => 0]);

        try {
            app(ServicioConversionVenta::class)->convertir($cotizacion, $usuario, Venta::METODO_EFECTIVO, null, []);
            $this->fail('La copia anterior no debe permitir convertir partidas alteradas.');
        } catch (ValidationException $excepcion) {
            $this->assertArrayHasKey('cotizacion', $excepcion->errors());
        }

        $this->assertSame('aceptada', $cotizacion->fresh()->estado);
        $this->assertSame(5, $producto->fresh()->existencias);
        $this->assertDatabaseCount('ventas', 0);
    }

    private function preparar(string $accion): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Cliente de integridad',
            'rfc' => 'CLI010101AA1',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Equipo',
            'codigo' => 'INTEGRIDAD',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 5,
            'requiere_numero_serie' => false,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-INTEGRIDAD',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => match ($accion) {
                'enviar' => 'borrador',
                'venta' => 'aceptada',
                default => 'pendiente',
            },
            'vence_en' => now()->addDays(15),
            'aceptada_en' => $accion === 'venta' ? now() : null,
            'entrega_limite_en' => $accion === 'venta' ? now()->addDays(5) : null,
            'porcentaje_descuento' => 0,
            'total' => 100,
        ]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'producto_id' => $producto->id,
            'descripcion' => 'Equipo histórico',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'porcentaje_descuento' => 0,
            'subtotal' => 100,
        ]);

        return [$usuario, $cotizacion, $producto, $partida];
    }
}
