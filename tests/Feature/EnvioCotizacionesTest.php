<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Tests\TestCase;

class EnvioCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_envio_exitoso_adjunta_pdf_y_activa_vigencia(): void
    {
        $this->travelTo(now()->startOfSecond());
        $cotizacion = $this->preparar();
        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())->assertRedirect();
        $mensaje = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('cliente@example.test', $mensaje->getTo()[0]->getAddress());
        $this->assertCount(1, $mensaje->getAttachments());
        $this->assertSame('application', $mensaje->getAttachments()[0]->getMediaType());
        $this->assertSame('pdf', $mensaje->getAttachments()[0]->getMediaSubtype());
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertTrue($cotizacion->fresh()->enviada_en->equalTo(now()));
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo(now()->addWeekdays(15)));
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'destinatario' => 'cliente@example.test',
            'resultado' => 'aceptada',
        ]);
        $this->get("/cotizaciones/{$cotizacion->id}")
            ->assertOk()
            ->assertSee('Historial de correo')
            ->assertSee('Aceptado por el servicio de correo')
            ->assertSee('name="token_envio"', false)
            ->assertSee('cliente@example.test');
    }

    public function test_fallo_del_correo_conserva_borrador_y_fechas(): void
    {
        $cotizacion = $this->preparar();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new UnexpectedResponseException('Rechazo simulado del servidor de correo'));
        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())->assertRedirect()->assertSessionHas('error');
        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->enviada_en);
        $this->assertNull($cotizacion->fresh()->vence_en);
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'destinatario' => 'cliente@example.test',
            'resultado' => 'fallido',
        ]);
    }

    public function test_reenviar_no_reinicia_la_vigencia(): void
    {
        $cotizacion = $this->preparar();
        $cotizacion->update([
            'estado' => 'pendiente',
            'enviada_en' => now()->subDays(3),
            'vence_en' => now()->addDays(12),
        ]);
        $vencimiento = $cotizacion->fresh()->vence_en;
        $this->post("/cotizaciones/{$cotizacion->id}/correo", $this->intento())->assertRedirect();
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo($vencimiento));
    }

    public function test_el_modo_de_registro_no_simula_un_envio_real(): void
    {
        config([
            'mail.default' => 'log',
        ]);
        $cotizacion = $this->preparar();
        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())->assertRedirect()->assertSessionHas('error');
        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->enviada_en);
    }

    public function test_un_fallo_al_reenviar_conserva_la_vigencia_original(): void
    {
        $cotizacion = $this->preparar();
        $cotizacion->update([
            'estado' => 'pendiente',
            'enviada_en' => now()->subDays(3),
            'vence_en' => now()->addDays(12),
        ]);
        $vencimiento = $cotizacion->fresh()->vence_en;
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Fallo simulado'));
        $this->post("/cotizaciones/{$cotizacion->id}/correo", $this->intento())->assertRedirect()->assertSessionHas('advertencia');
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo($vencimiento));
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
        ]);
    }

    public function test_doble_envio_del_mismo_formulario_contacta_smtp_una_sola_vez(): void
    {
        $cotizacion = $this->preparar();
        $datos = $this->intento();

        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $datos)->assertRedirect()->assertSessionHas('success');
        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $datos)->assertRedirect()->assertSessionHas('success');

        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertSame(1, $cotizacion->enviosCorreo()->where('tipo', EnvioCotizacion::TIPO_COTIZACION)->count());
    }

    public function test_el_envio_manual_exige_un_token_de_intento_valido(): void
    {
        $cotizacion = $this->preparar();

        $this->post("/cotizaciones/{$cotizacion->id}/enviar")
            ->assertRedirect()
            ->assertSessionHasErrors('token_envio');

        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertDatabaseCount('envios_correo_cotizacion', 0);
    }

    public function test_smtp_tiene_un_tiempo_limite_configurable(): void
    {
        $this->assertSame(30, config('mail.mailers.smtp.timeout'));
    }

    public function test_una_reserva_pendiente_impide_repetir_un_resultado_incierto(): void
    {
        $cotizacion = $this->preparar();
        $token = (string) Str::uuid();
        $cotizacion->enviosCorreo()->create([
            'usuario_id' => auth()->id(),
            'destinatario' => 'cliente@example.test',
            'tipo' => EnvioCotizacion::TIPO_COTIZACION,
            'clave_idempotencia' => "correo-manual:{$cotizacion->id}:{$token}",
            'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
            'mensaje' => 'Intento reservado.',
            'intentado_en' => now(),
        ]);

        $this->post("/cotizaciones/{$cotizacion->id}/enviar", ['token_envio' => $token])
            ->assertRedirect()
            ->assertSessionHas('advertencia');

        $this->assertCount(0, Mail::mailer()->getSymfonyTransport()->messages());
    }

    public function test_si_falla_la_confirmacion_despues_de_smtp_la_reserva_permanece_y_no_se_reenvia(): void
    {
        $cotizacion = $this->preparar();
        $datos = $this->intento();
        DB::statement("CREATE TRIGGER impedir_confirmacion_envio BEFORE UPDATE ON envios_correo_cotizacion WHEN NEW.resultado = 'aceptada' BEGIN SELECT RAISE(ABORT, 'fallo simulado'); END");

        try {
            $this->post("/cotizaciones/{$cotizacion->id}/enviar", $datos)
                ->assertRedirect()
                ->assertSessionHas('advertencia');
            $this->post("/cotizaciones/{$cotizacion->id}/enviar", $datos)
                ->assertRedirect()
                ->assertSessionHas('advertencia');
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS impedir_confirmacion_envio');
        }

        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
        ]);
        $this->assertSame(Cotizacion::ESTADO_BORRADOR, $cotizacion->fresh()->estado);
    }

    public function test_un_cambio_concurrente_de_estado_no_es_sobrescrito_al_confirmar_el_correo(): void
    {
        $cotizacion = $this->preparar();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andReturnUsing(function () use ($cotizacion): void {
            $cotizacion->update(['estado' => Cotizacion::ESTADO_CANCELADA]);
        });

        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())
            ->assertRedirect()
            ->assertSessionHas('advertencia');

        $this->assertSame(Cotizacion::ESTADO_CANCELADA, $cotizacion->fresh()->estado);
        $this->assertNull($cotizacion->fresh()->enviada_en);
        $this->assertNull($cotizacion->fresh()->vence_en);
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
        ]);
    }

    public function test_un_nuevo_token_permite_un_reintento_intencional_despues_de_un_fallo_confirmado(): void
    {
        $cotizacion = $this->preparar();
        $intento = 0;
        Mail::shouldReceive('to')->twice()->andReturnSelf();
        Mail::shouldReceive('send')->twice()->andReturnUsing(function () use (&$intento): void {
            $intento++;
            if ($intento === 1) {
                throw new UnexpectedResponseException('Fallo confirmado');
            }
        });

        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())->assertSessionHas('error');
        $this->post("/cotizaciones/{$cotizacion->id}/enviar", $this->intento())->assertSessionHas('success');

        $this->assertSame(Cotizacion::ESTADO_PENDIENTE, $cotizacion->fresh()->estado);
        $this->assertSame(2, $cotizacion->enviosCorreo()->count());
    }

    private function preparar(): Cotizacion
    {
        $usuario = Usuario::factory()->create([
            'rol' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($usuario);
        $cliente = Cliente::create([
            'tipo' => 'fisica',
            'nombre' => 'Cliente de prueba',
            'rfc' => 'CLI010101AA1',
            'correo' => 'cliente@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Domicilio de prueba',
            'codigo_postal' => '29000',
        ]);

        $cotizacion = Cotizacion::create([
            'folio' => 'COT-ENVIO',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'total' => 100,
        ]);
        $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Servicio de prueba',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        return $cotizacion;
    }

    /** @return array{token_envio: string} */
    private function intento(): array
    {
        return ['token_envio' => (string) Str::uuid()];
    }
}
