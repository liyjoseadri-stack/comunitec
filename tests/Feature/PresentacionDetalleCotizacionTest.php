<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresentacionDetalleCotizacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_borrador_destaca_enviar_y_separa_la_cancelacion_destructiva(): void
    {
        $cotizacion = $this->prepararCotizacion(Cotizacion::ESTADO_BORRADOR);

        $respuesta = $this->get(route('cotizaciones.detalle', $cotizacion));

        $respuesta->assertOk()
            ->assertSee('Siguiente paso')
            ->assertSee('data-accion-principal', false)
            ->assertSee('Enviar cotización')
            ->assertSee('Acciones adicionales')
            ->assertSee('data-zona-destructiva', false)
            ->assertSee('Cancelar cotización')
            ->assertSee('data-confirmar', false);
    }

    public function test_la_pendiente_destaca_aceptar_y_mantiene_correo_rechazo_y_cancelacion(): void
    {
        $cotizacion = $this->prepararCotizacion(Cotizacion::ESTADO_PENDIENTE);

        $respuesta = $this->get(route('cotizaciones.detalle', $cotizacion));

        $respuesta->assertOk()
            ->assertSee('data-accion-principal', false)
            ->assertSee('Aceptar cotización')
            ->assertSee('Enviar por correo')
            ->assertSee('Rechazar cotización')
            ->assertSee('Cancelar cotización')
            ->assertSee('aria-labelledby="titulo-siguiente-paso"', false)
            ->assertSee('aria-labelledby="titulo-acciones-adicionales"', false);
    }

    public function test_la_aceptada_destaca_continuar_en_ventas_y_explica_el_siguiente_paso(): void
    {
        $cotizacion = $this->prepararCotizacion(Cotizacion::ESTADO_ACEPTADA);

        $respuesta = $this->get(route('cotizaciones.detalle', $cotizacion));

        $respuesta->assertOk()
            ->assertSee('data-accion-principal', false)
            ->assertSee('Continuar en Ventas')
            ->assertSee('El método de pago y los números de serie se registran en el módulo Ventas.')
            ->assertSee('Cancelar y liberar piezas')
            ->assertSee('data-confirmar', false);
    }

    public function test_un_estado_terminal_muestra_el_resultado_sin_acciones_de_transicion(): void
    {
        $cotizacion = $this->prepararCotizacion(Cotizacion::ESTADO_RECHAZADA);

        $respuesta = $this->get(route('cotizaciones.detalle', $cotizacion));

        $respuesta->assertOk()
            ->assertSee('Seguimiento concluido')
            ->assertSee('Esta cotización fue rechazada.')
            ->assertDontSee('data-accion-principal', false)
            ->assertDontSee('action="'.route('cotizaciones.aceptar', $cotizacion).'"', false);
    }

    public function test_el_resumen_presenta_fechas_con_etiquetas_y_elementos_time(): void
    {
        $cotizacion = $this->prepararCotizacion(Cotizacion::ESTADO_PENDIENTE);
        $cotizacion->update([
            'enviada_en' => '2026-10-01 08:00:00',
            'vence_en' => '2026-10-22 08:00:00',
        ]);

        $respuesta = $this->get(route('cotizaciones.detalle', $cotizacion));

        $respuesta->assertOk()
            ->assertSee('Fecha de emisión')
            ->assertSee('Vigente hasta')
            ->assertSee('datetime="2026-10-01T08:00:00', false)
            ->assertSee('datetime="2026-10-22T08:00:00', false);
    }

    private function prepararCotizacion(string $estado): Cotizacion
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
            'activo' => true,
        ]);
        $this->actingAs($usuario);

        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Tecnología Empresarial del Sur, S.A. de C.V.',
            'rfc' => 'TES010101AA1',
            'correo' => 'compras@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Tuxtla Gutiérrez, Chiapas',
            'codigo_postal' => '29000',
        ]);

        return Cotizacion::create([
            'folio' => 'COT-PRESENTACION-001',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'total' => 0,
        ]);
    }
}
