<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PanelReportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_panel_cuenta_estados_y_ventas_dentro_del_mes_seleccionado(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]);
        $cliente = $this->crearCliente();
        $estados = [
            'borrador',
            'pendiente',
            'aceptada',
            'rechazada',
            'cancelada',
            'vencida',
        ];

        $this->crearCotizacion($cliente, $usuario, 'borrador', '2026-07-31 23:59:59');
        $cotizacionesDelMes = collect($estados)->map(
            fn (string $estado, int $indice) => $this->crearCotizacion(
                $cliente,
                $usuario,
                $estado,
                '2026-08-'.str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT).' 10:00:00'
            )
        );
        $this->crearCotizacion($cliente, $usuario, 'pendiente', '2026-09-01 00:00:00');

        $cotizacionExterna = $this->crearCotizacion(
            $cliente,
            $usuario,
            'aceptada',
            '2026-07-15 10:00:00'
        );
        $this->crearVenta(
            $cotizacionesDelMes->firstWhere('estado', 'aceptada'),
            $usuario,
            1000,
            '2026-08-01 00:00:00'
        );
        $this->crearVenta(
            $cotizacionExterna,
            $usuario,
            2500,
            '2026-08-31 23:59:59'
        );
        $cotizacionSeptiembre = $this->crearCotizacion(
            $cliente,
            $usuario,
            'aceptada',
            '2026-09-01 00:00:01'
        );
        $this->crearVenta(
            $cotizacionSeptiembre,
            $usuario,
            9999,
            '2026-09-01 00:00:00'
        );

        $respuesta = $this->actingAs($usuario)->get('/panel?mes=2026-08');

        $respuesta->assertOk()
            ->assertViewHas('totalCotizaciones', 6)
            ->assertViewHas('resumenCotizaciones', function (array $resumen): bool {
                foreach (['borrador', 'pendiente', 'aceptada', 'rechazada', 'cancelada', 'vencida'] as $estado) {
                    if (($resumen[$estado] ?? null) !== 1) {
                        return false;
                    }
                }

                return true;
            })
            ->assertViewHas('porcentajeAceptacion', 16.7)
            ->assertViewHas('cantidadVentas', 2)
            ->assertViewHas('totalVentas', 3500.0)
            ->assertSee(route('reportes.cotizaciones', [
                'desde' => '2026-08-01',
                'hasta' => '2026-08-31',
            ]))
            ->assertSee(route('reportes.ventas', [
                'desde' => '2026-08-01',
                'hasta' => '2026-08-31',
            ]));
    }

    public function test_el_reporte_de_cotizaciones_aplica_filtros_combinados(): void
    {
        $responsable = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $otroResponsable = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = $this->crearCliente('A');
        $otroCliente = $this->crearCliente('B');
        $objetivo = $this->crearCotizacion(
            $cliente,
            $responsable,
            'pendiente',
            '2026-08-15 12:00:00'
        );
        $fueraPorCliente = $this->crearCotizacion(
            $otroCliente,
            $responsable,
            'pendiente',
            '2026-08-16 12:00:00'
        );
        $fueraPorResponsable = $this->crearCotizacion(
            $cliente,
            $otroResponsable,
            'pendiente',
            '2026-08-17 12:00:00'
        );
        $fueraPorEstado = $this->crearCotizacion(
            $cliente,
            $responsable,
            'rechazada',
            '2026-08-18 12:00:00'
        );
        $fueraPorFecha = $this->crearCotizacion(
            $cliente,
            $responsable,
            'pendiente',
            '2026-09-01 00:00:00'
        );

        $respuesta = $this->actingAs($responsable)->get('/reportes/cotizaciones?'.http_build_query([
            'desde' => '2026-08-01',
            'hasta' => '2026-08-31',
            'cliente' => $cliente->id,
            'responsable' => $responsable->id,
            'estado' => 'pendiente',
        ]));

        $respuesta->assertOk()
            ->assertSee($objetivo->folio)
            ->assertDontSee($fueraPorCliente->folio)
            ->assertDontSee($fueraPorResponsable->folio)
            ->assertDontSee($fueraPorEstado->folio)
            ->assertDontSee($fueraPorFecha->folio)
            ->assertViewHas('cantidadResultados', 1)
            ->assertViewHas('totalImporte', 1000.0);
    }

    public function test_el_reporte_de_ventas_filtra_y_totaliza_antes_de_paginar(): void
    {
        $responsable = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $otroResponsable = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $cliente = $this->crearCliente('C');
        $otroCliente = $this->crearCliente('D');

        foreach (range(1, 21) as $dia) {
            $fecha = '2026-08-'.str_pad((string) $dia, 2, '0', STR_PAD_LEFT).' 12:00:00';
            $cotizacion = $this->crearCotizacion(
                $cliente,
                $responsable,
                'aceptada',
                $fecha
            );
            $this->crearVenta($cotizacion, $responsable, 100, $fecha);
        }
        $ventaOtroCliente = $this->crearVenta(
            $this->crearCotizacion($otroCliente, $responsable, 'aceptada', '2026-08-22 12:00:00'),
            $responsable,
            500,
            '2026-08-22 12:00:00'
        );
        $ventaOtroResponsable = $this->crearVenta(
            $this->crearCotizacion($cliente, $otroResponsable, 'aceptada', '2026-08-23 12:00:00'),
            $otroResponsable,
            600,
            '2026-08-23 12:00:00'
        );
        $ventaOtroMetodo = $this->crearVenta(
            $this->crearCotizacion($cliente, $responsable, 'aceptada', '2026-08-24 12:00:00'),
            $responsable,
            700,
            '2026-08-24 12:00:00',
            Venta::METODO_EFECTIVO
        );

        $respuesta = $this->actingAs($responsable)->get('/reportes/ventas?'.http_build_query([
            'desde' => '2026-08-01',
            'hasta' => '2026-08-31',
            'cliente' => $cliente->id,
            'responsable' => $responsable->id,
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
        ]));

        $respuesta->assertOk()
            ->assertSee('Cliente histórico')
            ->assertDontSee($ventaOtroCliente->folio)
            ->assertDontSee($ventaOtroResponsable->folio)
            ->assertDontSee($ventaOtroMetodo->folio)
            ->assertViewHas('cantidadResultados', 21)
            ->assertViewHas('totalImporte', 2100.0)
            ->assertViewHas('ventas', fn ($ventas): bool => $ventas->count() === 20
                && $ventas->total() === 21)
            ->assertSee('cliente='.$cliente->id, false)
            ->assertSee('responsable='.$responsable->id, false)
            ->assertSee('metodo_pago='.Venta::METODO_TRANSFERENCIA, false);

        $this->actingAs($responsable)->get('/reportes/ventas?'.http_build_query([
            'desde' => '2026-08-01',
            'hasta' => '2026-08-31',
            'cliente' => $cliente->id,
            'responsable' => $responsable->id,
            'metodo_pago' => Venta::METODO_TRANSFERENCIA,
            'page' => 2,
        ]))->assertOk()
            ->assertViewHas('cantidadResultados', 21)
            ->assertViewHas('totalImporte', 2100.0)
            ->assertViewHas('ventas', fn ($ventas): bool => $ventas->count() === 1
                && $ventas->currentPage() === 2);
    }

    public function test_el_panel_usa_el_mes_actual_y_muestra_ceros_sin_operaciones(): void
    {
        Carbon::setTestNow('2026-09-24 12:00:00');
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_CONSULTA,
        ]);

        $respuesta = $this->actingAs($usuario)->get('/panel');

        $respuesta->assertOk()
            ->assertViewHas('periodo', fn ($periodo): bool => $periodo->mes() === '2026-09')
            ->assertViewHas('totalCotizaciones', 0)
            ->assertViewHas('porcentajeAceptacion', 0.0)
            ->assertViewHas('cantidadVentas', 0)
            ->assertViewHas('totalVentas', 0.0)
            ->assertSee('No hay cotizaciones en este mes.')
            ->assertSee('No hay ventas en este mes.');
    }

    public function test_el_panel_rechaza_un_mes_invalido_con_mensaje_en_espanol(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->from('/panel')
            ->followingRedirects()
            ->get('/panel?mes=2026-13')
            ->assertOk()
            ->assertSee('Selecciona un mes válido.');
    }

    public function test_los_reportes_validan_intervalos_identificadores_y_metodos(): void
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_CONSULTA,
        ]);
        $this->actingAs($usuario);

        $this->from('/reportes/cotizaciones')
            ->get('/reportes/cotizaciones?desde=2026-09-30&hasta=2026-09-01')
            ->assertRedirect('/reportes/cotizaciones')
            ->assertSessionHasErrors('hasta');
        $this->from('/reportes/ventas')
            ->get('/reportes/ventas?cliente=999999')
            ->assertRedirect('/reportes/ventas')
            ->assertSessionHasErrors('cliente');
        $this->from('/reportes/ventas')
            ->get('/reportes/ventas?metodo_pago=bitcoin')
            ->assertRedirect('/reportes/ventas')
            ->assertSessionHasErrors('metodo_pago');
    }

    public function test_consulta_puede_leer_reportes_sin_formularios_de_escritura(): void
    {
        Carbon::setTestNow('2026-09-24 12:00:00');
        $consulta = Usuario::factory()->create([
            'rol' => Usuario::ROL_CONSULTA,
        ]);

        $this->actingAs($consulta)->get('/reportes/cotizaciones')
            ->assertOk()
            ->assertSee('Reporte de cotizaciones')
            ->assertDontSee('method="POST"', false)
            ->assertDontSee('method="PUT"', false)
            ->assertDontSee('method="DELETE"', false);
        $this->get('/reportes/ventas')
            ->assertOk()
            ->assertSee('Reporte de ventas')
            ->assertDontSee('method="POST"', false)
            ->assertDontSee('method="PUT"', false)
            ->assertDontSee('method="DELETE"', false);
    }

    public function test_fechas_vacias_en_reportes_recuperan_el_mes_actual(): void
    {
        Carbon::setTestNow('2026-09-24 12:00:00');
        $consulta = Usuario::factory()->create([
            'rol' => Usuario::ROL_CONSULTA,
        ]);

        $this->actingAs($consulta)->get('/reportes/cotizaciones?desde=&hasta=')
            ->assertOk()
            ->assertViewHas('filtros', fn (array $filtros): bool => $filtros['desde'] === '2026-09-01'
                && $filtros['hasta'] === '2026-09-30');
        $this->get('/reportes/ventas?desde=&hasta=')
            ->assertOk()
            ->assertViewHas('filtros', fn (array $filtros): bool => $filtros['desde'] === '2026-09-01'
                && $filtros['hasta'] === '2026-09-30');
    }

    public function test_el_reporte_recupera_nombres_de_una_venta_heredada_sin_copias(): void
    {
        $responsable = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
            'nombre' => 'Responsable heredado',
        ]);
        $cliente = $this->crearCliente('E');
        $cotizacion = $this->crearCotizacion(
            $cliente,
            $responsable,
            'aceptada',
            '2026-09-10 12:00:00'
        );
        $venta = $this->crearVenta(
            $cotizacion,
            $responsable,
            800,
            '2026-09-10 12:00:00'
        );
        $venta->update([
            'nombre_cliente' => '',
            'nombre_responsable' => '',
        ]);

        $this->actingAs($responsable)->get('/reportes/ventas?desde=2026-09-01&hasta=2026-09-30')
            ->assertOk()
            ->assertSee('<td>Cliente de reportes E</td>', false)
            ->assertSee('<td>Responsable heredado</td>', false);
    }

    private function crearCliente(string $sufijo = ''): Cliente
    {
        return Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente de reportes '.$sufijo,
            'rfc' => 'CRE010101A'.($sufijo ?: '1'),
            'correo' => 'reportes'.strtolower($sufijo).'@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de reportes',
            'codigo_postal' => '29000',
        ]);
    }

    private function crearCotizacion(
        Cliente $cliente,
        Usuario $usuario,
        string $estado,
        string $fecha
    ): Cotizacion {
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-REPORTE-'.Str::ulid(),
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'porcentaje_descuento' => 0,
            'total' => 1000,
        ]);
        $cotizacion->forceFill([
            'creado_en' => $fecha,
            'actualizado_en' => $fecha,
        ])->save();

        return $cotizacion;
    }

    private function crearVenta(
        Cotizacion $cotizacion,
        Usuario $usuario,
        float $total,
        string $fecha,
        string $metodoPago = Venta::METODO_TRANSFERENCIA
    ): Venta {
        return Venta::create([
            'folio' => 'VEN-REPORTE-'.str_replace([' ', ':', '-'], '', $fecha),
            'cotizacion_id' => $cotizacion->id,
            'cliente_id' => $cotizacion->cliente_id,
            'tipo_cliente' => 'moral',
            'nombre_cliente' => 'Cliente histórico',
            'rfc_cliente' => 'CRE010101AA1',
            'correo_cliente' => 'historico@example.test',
            'telefono_cliente' => '9610000000',
            'direccion_cliente' => 'Domicilio histórico',
            'codigo_postal_cliente' => '29000',
            'usuario_id' => $usuario->id,
            'nombre_responsable' => $usuario->nombre,
            'correo_responsable' => $usuario->correo,
            'vendida_en' => $fecha,
            'metodo_pago' => $metodoPago,
            'subtotal' => $total,
            'porcentaje_descuento' => 0,
            'total' => $total,
        ]);
    }
}
