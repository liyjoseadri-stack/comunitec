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
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertTrue($cotizacion->fresh()->sent_at->equalTo(now()));
        $this->assertTrue($cotizacion->fresh()->expires_at->equalTo(now()->addDays(15)));
    }

    public function test_fallo_del_correo_conserva_borrador_y_fechas(): void
    {
        $cotizacion = $this->preparar();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Fallo simulado del servidor de correo'));
        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect()->assertSessionHas('error');
        $this->assertSame('draft', $cotizacion->fresh()->status);
        $this->assertNull($cotizacion->fresh()->sent_at);
        $this->assertNull($cotizacion->fresh()->expires_at);
    }

    public function test_reenviar_no_reinicia_la_vigencia(): void
    {
        $cotizacion = $this->preparar();
        $cotizacion->update([
            'status' => 'pending',
            'sent_at' => now()->subDays(3),
            'expires_at' => now()->addDays(12),
        ]);
        $vencimiento = $cotizacion->fresh()->expires_at;
        $this->post("/cotizaciones/{$cotizacion->id}/correo")->assertRedirect();
        $this->assertTrue($cotizacion->fresh()->expires_at->equalTo($vencimiento));
    }

    public function test_el_modo_de_registro_no_simula_un_envio_real(): void
    {
        config([
            'mail.default' => 'log',
        ]);
        $cotizacion = $this->preparar();
        $this->post("/cotizaciones/{$cotizacion->id}/enviar")->assertRedirect()->assertSessionHas('error');
        $this->assertSame('draft', $cotizacion->fresh()->status);
        $this->assertNull($cotizacion->fresh()->sent_at);
    }

    public function test_un_fallo_al_reenviar_conserva_la_vigencia_original(): void
    {
        $cotizacion = $this->preparar();
        $cotizacion->update([
            'status' => 'pending',
            'sent_at' => now()->subDays(3),
            'expires_at' => now()->addDays(12),
        ]);
        $vencimiento = $cotizacion->fresh()->expires_at;
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Fallo simulado'));
        $this->post("/cotizaciones/{$cotizacion->id}/correo")->assertRedirect()->assertSessionHas('error');
        $this->assertSame('pending', $cotizacion->fresh()->status);
        $this->assertTrue($cotizacion->fresh()->expires_at->equalTo($vencimiento));
    }

    private function preparar(): Cotizacion
    {
        $usuario = Usuario::factory()->create([
            'role' => Usuario::ROL_COMERCIAL,
        ]);
        $this->actingAs($usuario);
        $cliente = Cliente::create([
            'type' => 'fisica',
            'name' => 'Cliente de prueba',
            'rfc' => 'CLI010101AA1',
            'email' => 'cliente@example.test',
            'phone' => '9610000000',
            'address' => 'Domicilio de prueba',
            'postal_code' => '29000',
        ]);

        return Cotizacion::create([
            'folio' => 'COT-ENVIO',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'draft',
            'total' => 0,
        ]);
    }
}
