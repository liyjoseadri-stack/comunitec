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
        foreach (['borrador', 'pendiente', 'aceptada', 'cancelada', 'rechazada', 'vencida'] as $estado) {
            $cotizacion->update(['estado' => $estado]);
            $this->get("/cotizaciones/{$cotizacion->id}")->assertOk()
                ->assertSee($cotizacion->folio)->assertDontSee('<form', false);
        }
        $this->get("/cotizaciones/{$cotizacion->id}/pdf")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_consulta_no_puede_modificar_por_solicitudes_directas(): void
    {
        $cotizacion = $this->preparar();
        $partida = $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Servicio de prueba',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        $this->post('/cotizaciones')->assertForbidden();
        $this->put("/cotizaciones/{$cotizacion->id}")->assertForbidden();
        foreach (['enviar', 'correo', 'aceptar', 'rechazar', 'cancelar', 'partidas'] as $accion) {
            $this->post("/cotizaciones/{$cotizacion->id}/{$accion}")->assertForbidden();
        }
        $this->put("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertForbidden();
        $this->delete("/cotizaciones/{$cotizacion->id}/partidas/{$partida->id}")->assertForbidden();
        $this->assertSame('borrador', $cotizacion->fresh()->estado);
        $this->assertDatabaseHas('partidas_cotizacion', ['id' => $partida->id]);
    }

    private function preparar(): Cotizacion
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_CONSULTA]);
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
            'folio' => 'COT-CONSULTA',
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => 'borrador',
            'total' => 0,
        ]);
    }
}
