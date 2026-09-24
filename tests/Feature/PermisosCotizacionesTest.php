<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosCotizacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_puede_leer_cotizaciones_y_descargar_pdf_sin_formularios(): void
    {
        $cotizacion = $this->preparar();

        $this->get('/cotizaciones')->assertOk()->assertDontSee('<form', false);
        foreach (['draft', 'pending', 'accepted', 'cancelled', 'rejected', 'expired'] as $estado) {
            $cotizacion->update(['status' => $estado]);
            $this->get("/cotizaciones/{$cotizacion->id}")->assertOk()
                ->assertSee($cotizacion->folio)->assertDontSee('<form', false);
        }
        $this->get("/cotizaciones/{$cotizacion->id}/pdf")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_consulta_no_puede_modificar_por_solicitudes_directas(): void
    {
        $cotizacion = $this->preparar();
        $partida = $cotizacion->lines()->create([
            'type' => 'other',
            'description' => 'Servicio de prueba',
            'quantity' => 1,
            'unit_price' => 100,
            'subtotal' => 100,
        ]);

        $this->post('/cotizaciones')->assertForbidden();
        $this->put("/cotizaciones/{$cotizacion->id}")->assertForbidden();
        foreach (['enviar', 'correo', 'aceptar', 'rechazar', 'cancelar', 'partidas'] as $accion) {
            $this->post("/cotizaciones/{$cotizacion->id}/{$accion}")->assertForbidden();
        }
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertForbidden();
        $this->delete("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertForbidden();
        $this->assertSame('draft', $cotizacion->fresh()->status);
        $this->assertDatabaseHas('quote_lines', ['id' => $partida->id]);
    }

    private function preparar(): Cotizacion
    {
        $usuario = Usuario::factory()->create(['role' => Usuario::ROL_CONSULTA]);
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
            'folio' => 'COT-CONSULTA',
            'customer_id' => $cliente->id,
            'user_id' => $usuario->id,
            'status' => 'draft',
            'total' => 0,
        ]);
    }
}
