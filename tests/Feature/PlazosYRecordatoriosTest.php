<?php

namespace Tests\Feature;

use App\Mail\CorreoRecordatorioCotizacion;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EnvioCotizacion;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
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
            ->post(route('cotizaciones.enviar', $cotizacion), [
                'token_envio' => '44444444-4444-4444-8444-444444444444',
            ])
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

    public function test_reserva_el_recordatorio_antes_de_contactar_al_servicio_de_correo(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');

        Mail::shouldReceive('to')
            ->once()
            ->with($pendiente->cliente->correo)
            ->andReturnSelf();
        Mail::shouldReceive('send')
            ->once()
            ->withArgs(function (CorreoRecordatorioCotizacion $correo) use ($pendiente): bool {
                $this->assertDatabaseHas('envios_correo_cotizacion', [
                    'cotizacion_id' => $pendiente->id,
                    'clave_idempotencia' => "recordatorio-vencimiento:{$pendiente->id}:2026-10-16",
                    'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
                ]);

                return true;
            });

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $pendiente->id,
            'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
        ]);
    }

    public function test_una_reserva_existente_impide_duplicar_el_recordatorio(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        Mail::fake();
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');
        $pendiente->enviosCorreo()->create([
            'usuario_id' => $pendiente->usuario_id,
            'destinatario' => $pendiente->cliente->correo,
            'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
            'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
            'mensaje' => 'Intento reservado antes del envío.',
            'clave_idempotencia' => "recordatorio-vencimiento:{$pendiente->id}:2026-10-16",
            'intentado_en' => now(),
        ]);

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(1, $pendiente->enviosCorreo()->where('tipo', EnvioCotizacion::TIPO_RECORDATORIO)->count());
    }

    public function test_un_recordatorio_anterior_a_la_migracion_tambien_impide_un_duplicado(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        Mail::fake();
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');
        $pendiente->enviosCorreo()->create([
            'usuario_id' => $pendiente->usuario_id,
            'destinatario' => $pendiente->cliente->correo,
            'tipo' => EnvioCotizacion::TIPO_RECORDATORIO,
            'resultado' => EnvioCotizacion::RESULTADO_ACEPTADO,
            'mensaje' => 'Registro anterior a la clave idempotente.',
            'intentado_en' => now(),
        ]);

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(1, $pendiente->enviosCorreo()->where('tipo', EnvioCotizacion::TIPO_RECORDATORIO)->count());
    }

    public function test_el_viernes_busca_en_sql_las_cotizaciones_que_vencen_el_lunes(): void
    {
        Carbon::setTestNow('2026-10-16 08:00:00');
        Mail::fake();
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-19 10:00:00');

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        Mail::assertSent(CorreoRecordatorioCotizacion::class, fn ($correo) => $correo->cotizacion->is($pendiente));
    }

    public function test_un_fallo_no_se_reintenta_automaticamente_para_evitar_un_envio_duplicado_incierto(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('Resultado SMTP incierto'));

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();
        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $pendiente->id,
            'clave_idempotencia' => "recordatorio-vencimiento:{$pendiente->id}:2026-10-16",
            'resultado' => EnvioCotizacion::RESULTADO_PENDIENTE,
        ]);
        $this->assertSame(1, $pendiente->enviosCorreo()->where('tipo', EnvioCotizacion::TIPO_RECORDATORIO)->count());
    }

    public function test_un_rechazo_confirmado_del_servidor_smtp_se_registra_como_fallido(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        [$pendiente] = $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');

        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new UnexpectedResponseException('Mensaje rechazado'));

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        $this->assertDatabaseHas('envios_correo_cotizacion', [
            'cotizacion_id' => $pendiente->id,
            'clave_idempotencia' => "recordatorio-vencimiento:{$pendiente->id}:2026-10-16",
            'resultado' => EnvioCotizacion::RESULTADO_FALLIDO,
        ]);
    }

    public function test_filtra_la_fecha_objetivo_en_sql_y_procesa_por_identificador_en_bloques(): void
    {
        Carbon::setTestNow('2026-10-15 08:00:00');
        Mail::fake();

        foreach (range(1, 101) as $numero) {
            $this->crearCotizacion('pendiente', '2026-10-16 10:00:00');
        }
        $this->crearCotizacion('pendiente', '2026-10-19 10:00:00');

        $consultas = [];
        DB::listen(function ($consulta) use (&$consultas): void {
            if (str_contains($consulta->sql, 'cotizaciones')) {
                $consultas[] = [$consulta->sql, $consulta->bindings];
            }
        });

        $this->artisan('cotizaciones:recordar-vencimiento')->assertSuccessful();

        Mail::assertSent(CorreoRecordatorioCotizacion::class, 101);
        $consultasObjetivo = collect($consultas)->filter(function (array $consulta): bool {
            $valores = collect($consulta[1])->map(fn ($valor) => (string) $valor);

            return str_contains($consulta[0], 'vence_en')
                && $valores->contains(fn (string $valor): bool => str_contains($valor, '2026-10-16 00:00:00'))
                && $valores->contains(fn (string $valor): bool => str_contains($valor, '2026-10-17 00:00:00'));
        });

        $this->assertGreaterThanOrEqual(2, $consultasObjetivo->count());
    }

    public function test_el_esquema_incluye_la_clave_unica_para_reservar_recordatorios(): void
    {
        $this->assertTrue(Schema::hasColumns('envios_correo_cotizacion', [
            'clave_idempotencia',
        ]));
        $this->assertSame('pendiente', EnvioCotizacion::RESULTADO_PENDIENTE);
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
            ->assertSee('Entregas del siguiente día hábil')
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
            ->assertDontSee('Productos con stock bajo');

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Productos con stock bajo');
    }

    private function crearCotizacion(string $estado, ?string $venceEn = null): array
    {
        $consecutivo = Cliente::count() + 1;
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_COMERCIAL]);
        $cliente = Cliente::create([
            'tipo' => 'moral',
            'nombre' => 'Cliente recordatorio',
            'rfc' => 'REC'.str_pad((string) $consecutivo, 9, '0', STR_PAD_LEFT),
            'correo' => "recordatorio{$consecutivo}@example.test",
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
        $cotizacion->partidas()->create([
            'tipo' => 'otro',
            'descripcion' => 'Servicio de prueba',
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        return [$cotizacion, $usuario];
    }
}
