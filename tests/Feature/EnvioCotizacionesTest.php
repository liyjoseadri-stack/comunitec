<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EnvioCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_envio_exitoso_adjunta_pdf_y_activa_vigencia(): void
    {
        $this->travelTo(now()->startOfSecond());
        $cotizacion = $this->preparar();
        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect();
        $mensaje = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('cliente@example.test', $mensaje->getTo()[0]->getAddress());
        $this->assertCount(1, $mensaje->getAttachments());
        $this->assertSame('application', $mensaje->getAttachments()[0]->getMediaType());
        $this->assertSame('pdf', $mensaje->getAttachments()[0]->getMediaSubtype());
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertTrue($cotizacion->fresh()->enviada_en->equalTo(now()));
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo(now()->addDays(15)));
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $cotizacion->id,
            'destinatario' => 'cliente@example.test',
            'resultado' => 'aceptada',
        ]);
        $this->get("/cotizaciones/{$cotizacion->id}")
            ->assertOk()
            ->assertSee('Historial de correo')
            ->assertSee('Aceptado por el servicio de correo')
            ->assertSee('cliente@example.test');
    }

    public function test_fallo_del_correo_conserva_borrador_y_fechas(): void
    {
        $cotizacion = $this->preparar();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Fallo simulado del servidor de correo'));
        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect()->assertSessionHas('error');
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
        $this->post("/cotizaciones/{$cotizacion->id}/correo")->assertRedirect();
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo($vencimiento));
    }

    public function test_el_modo_de_registro_no_simula_un_envio_real(): void
    {
        config([
            'mail.default' => 'log',
        ]);
        $cotizacion = $this->preparar();
        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect()->assertSessionHas('error');
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
        $this->post("/cotizaciones/{$cotizacion->id}/correo")->assertRedirect()->assertSessionHas('error');
        $this->assertSame('pendiente', $cotizacion->fresh()->estado);
        $this->assertTrue($cotizacion->fresh()->vence_en->equalTo($vencimiento));
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

        return Cotizacion::create([
            'folio' => 'COT-ENVIO',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'total' => 0,
        ]);
    }
}
