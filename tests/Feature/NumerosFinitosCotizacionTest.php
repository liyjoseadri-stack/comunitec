<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\PiezaInventario;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NumerosFinitosCotizacionTest extends TestCase
{
    use RefreshDatabase;

    public static function camposNumericosNoFinitos(): iterable
    {
        foreach ([
            'crear' => ['porcentaje_descuento', 'items.0.cantidad', 'items.0.precio_unitario', 'items.0.porcentaje_descuento'],
            'editar_encabezado' => ['porcentaje_descuento'],
            'agregar_partida' => ['cantidad', 'precio_unitario', 'porcentaje_descuento'],
            'editar_partida' => ['cantidad', 'precio_unitario', 'porcentaje_descuento'],
        ] as $accion => $campos) {
            foreach ($campos as $campo) {
                foreach (['1e309', '-1e309', '"1e309"', '"-1e309"'] as $valorJson) {
                    yield "{$accion}: {$campo} = {$valorJson}" => [$accion, $campo, $valorJson];
                    if (in_array($campo, ['cantidad', 'items.0.cantidad'], true)) {
                        yield "{$accion}, producto: {$campo} = {$valorJson}" => [$accion, $campo, $valorJson, 'producto'];
                    }
                }
            }
        }
    }

    #[DataProvider('camposNumericosNoFinitos')]
    public function test_rechaza_numeros_no_finitos_con_422_sin_modificar_datos(
        string $accion,
        string $campo,
        string $valorJson,
        string $tipo = 'servicio'
    ): void {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Cliente de validación',
            'rfc' => 'CLI010101AA1',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $producto = Producto::create([
            'nombre' => 'Producto reservado',
            'codigo' => 'PR-VALIDACION',
            'unidad' => 'pieza',
            'precio' => 100,
            'existencias' => 0,
        ]);
        $servicio = Servicio::create([
            'nombre' => 'Servicio de prueba',
            'codigo' => 'SER-VALIDACION',
            'unidad' => 'servicio',
            'precio' => 100,
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-NUMEROS-FINITOS',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'aceptada',
            'aceptada_en' => now(),
            'entrega_limite_en' => now()->addDays(5),
            'total' => 100,
        ]);
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'producto',
            'producto_id' => $producto->id,
            'descripcion' => 'Producto reservado',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);
        $pieza = PiezaInventario::create([
            'producto_id' => $producto->id,
            'numero_serie' => 'RESERVA-VALIDACION',
            'estado' => 'reservada',
            'cotizacion_id' => $cotizacion->id,
        ]);
        $antes = $cotizacion->fresh()->getAttributes();
        $partidaAntes = $partida->fresh()->getAttributes();
        $reservaAntes = $pieza->fresh()->getAttributes();

        $concepto = [
            'tipo' => $tipo,
            'servicio_id' => $tipo === 'servicio' ? $servicio->id : null,
            'producto_id' => $tipo === 'producto' ? $producto->id : null,
            'descripcion' => 'Servicio de prueba',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'porcentaje_descuento' => 0,
        ];
        $entrada = match ($accion) {
            'crear' => ['cliente_id' => $cliente->id, 'porcentaje_descuento' => 0, 'items' => [$concepto]],
            'editar_encabezado' => ['cliente_id' => $cliente->id, 'porcentaje_descuento' => 0],
            default => $concepto,
        };
        data_set($entrada, $campo, 'VALOR_NO_FINITO');
        // El exponente sin comillas se decodifica como float INF; postJson no puede codificarlo.
        $contenido = str_replace('"VALOR_NO_FINITO"', $valorJson, json_encode($entrada, JSON_THROW_ON_ERROR));
        $ruta = match ($accion) {
            'crear' => '/api/cotizaciones',
            'editar_encabezado' => "/cotizaciones/{$cotizacion->id}",
            'agregar_partida' => "/cotizaciones/{$cotizacion->id}/partidas",
            default => "/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}",
        };

        $this->actingAs($usuario);
        $respuesta = $this->call(
            in_array($accion, ['crear', 'agregar_partida'], true) ? 'POST' : 'PUT',
            $ruta,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $contenido
        )->assertUnprocessable()->assertJsonValidationErrors($campo);

        $this->assertStringContainsString('número finito', $respuesta->json('errors')[$campo][0]);
        $this->assertSame($antes, $cotizacion->fresh()->getAttributes());
        $this->assertSame($partidaAntes, $partida->fresh()->getAttributes());
        $this->assertSame($reservaAntes, $pieza->fresh()->getAttributes());
        $this->assertSame(0, $producto->fresh()->existencias);
        $this->assertDatabaseCount('cotizaciones', 1);
        $this->assertDatabaseCount('partidas_cotizacion', 1);
    }
}
