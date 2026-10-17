<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PreparaDominio;
use Tests\TestCase;

class AuditoriaIntegridadDominioTest extends TestCase
{
    use DatabaseMigrations;
    use PreparaDominio;

    public function test_el_auditor_acepta_base_limpia_y_huerfanos_con_snapshot_sin_escribir(): void
    {
        $datos = $this->prepararDominio();
        DB::table('partidas_cotizacion')->update(['producto_id' => null]);
        DB::table('partidas_venta')->insert([...$datos['partidas_venta'], 'producto_id' => null]);
        $antes = $this->filasDominio();
        DB::enableQueryLog();

        $this->artisan('dominio:auditar')->expectsOutputToContain('Sin incompatibilidades')->assertExitCode(0);

        $consultas = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertNotEmpty($consultas);
        foreach ($consultas as $consulta) {
            $this->assertMatchesRegularExpression('/^select\b/i', $consulta['query']);
        }
        $this->assertEquals($antes, $this->filasDominio());
    }

    public function test_el_auditor_informa_tablas_conteos_e_ids_sin_corregir_ni_borrar(): void
    {
        $datos = $this->prepararDominio();
        $this->migracionDominio()->down();
        DB::table('cotizaciones')->update(['estado' => 'DESCONOCIDO']);
        DB::table('partidas_cotizacion')->update(['servicio_id' => $datos['servicio_id']]);
        DB::table('partidas_venta')->insert([...$datos['partidas_venta'], 'tipo' => 'otro']);
        DB::table('piezas_inventario')->insert([
            ...$datos['piezas_inventario'], 'estado' => 'reservada',
        ]);
        $antes = $this->filasDominio();

        $comando = $this->artisan('dominio:auditar');
        foreach (array_keys($antes) as $tabla) {
            $comando->expectsOutputToContain($tabla.': 1 registro(s) incompatible(s); IDs: 1');
        }
        $comando->assertExitCode(1);
        $this->assertEquals($antes, $this->filasDominio());
    }

    public function test_el_auditor_cuenta_todos_los_invalidos_y_limita_los_ids_mostrados(): void
    {
        $datos = $this->prepararDominio();
        $this->migracionDominio()->down();
        $cotizacion = $datos['cotizaciones'];
        unset($cotizacion['id']);
        for ($indice = 1; $indice <= 25; $indice++) {
            DB::table('cotizaciones')->insert([
                ...$cotizacion, 'folio' => 'INVALIDA-'.$indice, 'estado' => 'invalido',
            ]);
        }

        $this->artisan('dominio:auditar')
            ->expectsOutputToContain('cotizaciones: 25 registro(s) incompatible(s); IDs: '.implode(', ', range(2, 21)))
            ->expectsOutputToContain('primeros 20 identificadores')
            ->assertExitCode(1);
        $this->assertDatabaseCount('cotizaciones', 26);
    }

    #[DataProvider('nombresHistoricosVacios')]
    public function test_el_auditor_detecta_huerfanos_con_nombre_compuesto_solo_por_espacios(string $nombre): void
    {
        $datos = $this->prepararDominio();
        $this->migracionDominio()->down();
        DB::table('partidas_cotizacion')->delete();
        foreach (['producto', 'servicio'] as $tipo) {
            $snapshot = [
                'tipo' => $tipo, 'producto_id' => null, 'servicio_id' => null,
                'nombre_concepto' => $nombre,
            ];
            $cotizada = DB::table('partidas_cotizacion')->insertGetId([
                ...$datos['partidas_cotizacion'], ...$snapshot,
            ]);
            DB::table('partidas_venta')->insert([
                ...$datos['partidas_venta'], ...$snapshot, 'partida_cotizacion_id' => $cotizada,
            ]);
        }
        $antes = $this->filasDominio();

        $this->artisan('dominio:auditar')
            ->expectsOutputToContain('partidas_cotizacion: 2 registro(s) incompatible(s)')
            ->expectsOutputToContain('partidas_venta: 2 registro(s) incompatible(s)')
            ->assertExitCode(1);
        $this->assertEquals($antes, $this->filasDominio());
    }
}
