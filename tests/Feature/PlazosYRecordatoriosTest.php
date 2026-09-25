<?php

namespace Tests\Feature;

use App\Mail\CorreoRecordatorioCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlazosYRecordatoriosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_vigencia_de_quince_dias_omite_fines_de_semana(): void
    {
        Carbon::setTestNow('2026-09-25 10:00:00');
        Mail::fake();
        [$cotizacion, $usuario] = $this->crearCotizacion('borrador');

        $this->actingAs($usuario)
            ->post(route('cotizaciones.enviar', $cotizacion))
            ->assertRedirect();

        $this->assertSame('2026-10-16 10:00:00', $cotizacion->fresh()->vence_en->format('Y-m-d H:i:s'));
    }

    public function test_envia_un_solo_recordatorio_un_dia_habil_antes_y_solo_si_sigue_pendiente(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        Mail::fake();
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');
        [$aceptada] = $this->crearCotizacion('aceptada', '2026-10-16 10:00:00');

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();
        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        Mail::assertSent(CorreoRecordatorioCotizacion::class, 1);
        Mail::assertSent(CorreoRecordatorioCotizacion::class, fn ($correo) => $correo->cotizacion->is($pendiente));
        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $pendiente->id,
            'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
            'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
        ]);
        $this->assertDatabaseMissing('envios_correo_cotizacion', [
            'cotizacion_id' => $aceptada->id,
            'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
        ]);
    }

    public function test_al_aceptar_programa_la_entrega_a_cinco_dias_habiles(): void
    {
        Carbon::setTestNow('2026-09-25 10:00:00');
        [$cotizacion, $usuario] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');

        $this->actingAs($usuario)
            ->post(route('cotizaciones.aceptar', $cotizacion))
            ->assertRedirect();

        $cotizacion->refresh();
        $this->assertSame('aceptada', $cotizacion->estado);
        $this->assertSame('2026-10-02 10:00:00', $cotizacion->entrega_limite_en->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-16 10:00:00', $cotizacion->vence_en->format('Y-m-d H:i:s'));
    }

    public function test_el_administrador_ve_la_alerta_un_dia_habil_antes_de_la_entrega(): void
    {
        Carbon::setTestNow('2026-10-16 09:00:00');
        [$cotizacion, $administrador] = $this->crearCotizacion('aceptada');
        $administrador->update(['rol' => Usuario::ROL_ADMINISTRADOR]);
        $cotizacion->update(['entrega_limite_en' => '2026-10-19 10:00:00']);

        $this->actingAs($administrador)->get(route('panel'))
            ->assertOk()
            ->assertSee('Recordatorios de entrega')
            ->assertSee($cotizacion->folio)
            ->assertSee('19/10/2026');

        $comercial = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $this->actingAs($comercial)->get(route('panel'))
            ->assertOk()
            ->assertDontSee($cotizacion->folio);
    }

    public function test_las_metricas_mensuales_estan_en_reportes_y_no_en_el_panel(): void
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_ADMINISTRADOR]);

        $this->actingAs($usuario)->get(route('panel'))
            ->assertOk()
            ->assertDontSee('Indicadores mensuales');

        $this->get(route('reportes.resumen'))
            ->assertOk()
            ->assertSee('Indicadores mensuales')
            ->assertDontSee('Alertas de inventario');

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Alertas de inventario');
    }

    private function crearCotizacion(string $estado, ?string $venceEn = null): array
    {
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente recordatorio',
            'rfc' => 'REC'.str_pad((string) Cliente::count(), 9, '0'),
            'correo' => 'recordatorio'.Cliente::count().'@example.test',
            'telefono' => '9610000000',
            'direccion' => 'Dirección de prueba',
            'codigo_postal' => '29000',
        ]);
        $cotizacion = Cotizacion::create([
            'folio' => 'COT-REC-'.Cotizacion::count(),
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'estado' => $estado,
            'vence_en' => $venceEn,
            'total' => 100,
        ]);

        return [$cotizacion, $usuario];
    }
}
